<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['role'];
$my_id = $_SESSION['user_id'];

try {
    // ---- LIST ----
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($role === 'superadmin') {
            $users = $pdo->query("
                SELECT u.id, u.username, u.full_name, u.email, u.is_active, u.created_at,
                       a.username as admin_username
                FROM users u
                LEFT JOIN users a ON u.assigned_admin_id = a.id
                WHERE u.role = 'user'
                ORDER BY u.created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.full_name, u.email, u.is_active, u.created_at,
                       a.username as admin_username
                FROM users u
                LEFT JOIN users a ON u.assigned_admin_id = a.id
                WHERE u.role = 'user' AND u.assigned_admin_id = ?
                ORDER BY u.created_at DESC
            ");
            $stmt->execute([$my_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // Also get admins for dropdown
        $admins = $pdo->query("SELECT id, username FROM users WHERE role='admin' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $users, 'admins' => $admins]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? 'create';

    // ---- CHECK USERNAME ----
    if ($action === 'check_username') {
        $username = trim($input['username'] ?? '');
        $exclude_id = (int)($input['exclude_id'] ?? 0);
        if (empty($username)) { echo json_encode(['available' => false]); exit; }
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $exclude_id]);
        echo json_encode(['available' => !$stmt->fetch()]);
        exit;
    }

    // ---- CREATE ----
    if ($action === 'create') {
        $username = trim($input['username'] ?? '');
        $full_name = trim($input['full_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $confirm = $input['confirm_password'] ?? '';
        $assigned_admin_id = $role === 'admin' ? $my_id : (int)($input['assigned_admin_id'] ?? 0);

        if (empty($username)) { echo json_encode(['success' => false, 'message' => 'Username is required.']); exit; }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success' => false, 'message' => 'Valid email is required.']); exit; }
        if (empty($password) || strlen($password) < 8) { echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']); exit; }
        if ($password !== $confirm) { echo json_encode(['success' => false, 'message' => 'Passwords do not match.']); exit; }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Email or Username already in use.']); exit; }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password_hash, role, assigned_admin_id, is_active) VALUES (?, ?, ?, ?, 'user', ?, 1)");
        $stmt->execute([$username, $full_name, $email, $password_hash, $assigned_admin_id ?: null]);

        echo json_encode(['success' => true, 'message' => 'User created successfully!']);
        exit;
    }

    // ---- TOGGLE ACTIVE ----
    if ($action === 'toggle') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ? AND role = 'user'");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        if ($current === false) { echo json_encode(['success' => false, 'message' => 'User not found.']); exit; }
        $new_status = $current ? 0 : 1;
        $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_status, $id]);
        echo json_encode(['success' => true, 'is_active' => $new_status]);
        exit;
    }

    // ---- DELETE ----
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'user'")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'User deleted.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

