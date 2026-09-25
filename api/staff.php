<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only Shop Admins can manage staff.']);
    exit;
}

$admin_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'list') {
            $stmt = $pdo->prepare("
                SELECT id, username, full_name, email, is_active, created_at, last_seen, role,
                       (CASE WHEN last_seen >= DATE_SUB(NOW(), INTERVAL 3 MINUTE) THEN 1 ELSE 0 END) as is_online 
                FROM Users 
                WHERE role IN ('user', 'manager') AND assigned_admin_id = ? 
                ORDER BY id DESC
            ");
            $stmt->execute([$admin_id]);
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'staff' => $staff]);
            exit;
        }

        if ($action === 'check_username') {
            $username = trim($_GET['username'] ?? '');
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Username required']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = ?");
            $stmt->execute([$username]);
            $exists = $stmt->fetch() ? true : false;
            echo json_encode(['success' => true, 'available' => !$exists]);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'create') {
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $full_name = trim($input['full_name'] ?? '');
            $role = (isset($input['role']) && $input['role'] === 'manager') ? 'manager' : 'user';

            if (empty($username) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Username, email, and password are required.']);
                exit;
            }
            if (strlen($password) < 4) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters.']);
                exit;
            }

            // Check limits
            $limitStmt = $pdo->prepare("SELECT max_users FROM users WHERE id = ?");
            $limitStmt->execute([$admin_id]);
            $max_users = (int)$limitStmt->fetchColumn();
            if ($max_users <= 0) $max_users = 5;
            
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE assigned_admin_id = ? AND role IN ('user', 'manager')");
            $countStmt->execute([$admin_id]);
            $current_users = (int)$countStmt->fetchColumn();
            
            if ($current_users >= $max_users) {
                echo json_encode(['success' => false, 'message' => "User limit reached! You can only add up to $max_users users/admins. Please contact the Super Admin to upgrade your limit."]);
                exit;
            }

            // Check if username or email exists
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username or email already in use.']);
                exit;
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO Users (username, full_name, email, password_hash, role, assigned_admin_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$username, $full_name, $email, $hash, $role, $admin_id]);
            
            echo json_encode(['success' => true, 'message' => 'Staff member added successfully.']);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? 0);
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $full_name = trim($input['full_name'] ?? '');
            $password = $input['password'] ?? '';
            $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;
            $role = (isset($input['role']) && $input['role'] === 'manager') ? 'manager' : 'user';

            if (!$id || empty($username) || empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
                exit;
            }

            // Check if username/email belongs to someone else
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE (email = ? OR username = ?) AND id != ?");
            $stmt->execute([$email, $username, $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username or email already in use.']);
                exit;
            }

            // Ensure this staff belongs to this admin
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE id = ? AND assigned_admin_id = ? AND role IN ('user', 'manager')");
            $stmt->execute([$id, $admin_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Staff member not found.']);
                exit;
            }

            if (!empty($password)) {
                if (strlen($password) < 4) {
                    echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters.']);
                    exit;
                }
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE Users SET username = ?, full_name = ?, email = ?, password_hash = ?, is_active = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $full_name, $email, $hash, $is_active, $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE Users SET username = ?, full_name = ?, email = ?, is_active = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $full_name, $email, $is_active, $role, $id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Staff member updated successfully.']);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE id = ? AND assigned_admin_id = ? AND role IN ('user', 'manager')");
            $stmt->execute([$id, $admin_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Staff member not found.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM Users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Staff member deleted.']);
            exit;
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
