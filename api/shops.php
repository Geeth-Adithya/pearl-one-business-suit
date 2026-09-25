<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    // ---- LIST ----
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.full_name, u.email, u.is_active,
                       u.subscription_plan, u.subscription_start_date, u.subscription_end_date,
                       u.module_pos, u.module_inventory, u.module_suppliers, u.module_customers,
                       u.module_expenses, u.module_reports, u.module_staff, u.module_branches, u.max_users,
                       (SELECT setting_value FROM settings WHERE setting_key = CONCAT('shop_name_', u.id)) as shop_name,
                       (SELECT setting_value FROM settings WHERE setting_key = CONCAT('shop_contact_', u.id)) as shop_contact
                FROM users u
                WHERE u.role = 'admin' AND u.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($shop) {
                echo json_encode(['success' => true, 'shop' => $shop]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Shop not found']);
            }
            exit;
        }

        $stmt = $pdo->query("
            SELECT u.id, u.username, u.full_name, u.email, u.is_active,
                   u.subscription_plan, u.subscription_start_date, u.subscription_end_date,
                   u.module_pos, u.module_stock, u.module_supply, u.created_at,
                   (SELECT setting_value FROM settings WHERE setting_key = CONCAT('shop_name_', u.id)) as shop_name,
                   (SELECT setting_value FROM settings WHERE setting_key = CONCAT('shop_contact_', u.id)) as shop_contact,
                   (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
                   (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status='Completed') as total_sales
            FROM users u
            WHERE u.role = 'admin'
            ORDER BY u.created_at DESC
        ");
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'shops' => $shops]);
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
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $confirm = $input['confirm_password'] ?? '';
        $full_name = trim($input['full_name'] ?? '');
        $shop_name = trim($input['shop_name'] ?? $username . "'s Shop");
        $shop_contact = trim($input['shop_contact'] ?? '');
        $subscription_plan = $input['subscription_plan'] ?? 'Monthly';
        
        $mod_pos = isset($input['module_pos']) ? (int)$input['module_pos'] : 1;
        $mod_inv = isset($input['module_inventory']) ? (int)$input['module_inventory'] : 1;
        $mod_sup = isset($input['module_suppliers']) ? (int)$input['module_suppliers'] : 1;
        $mod_cus = isset($input['module_customers']) ? (int)$input['module_customers'] : 1;
        $mod_exp = isset($input['module_expenses']) ? (int)$input['module_expenses'] : 1;
        $mod_rep = isset($input['module_reports']) ? (int)$input['module_reports'] : 1;
        $mod_stf = isset($input['module_staff']) ? (int)$input['module_staff'] : 1;
        $mod_brn = isset($input['module_branches']) ? (int)$input['module_branches'] : 1;
        $max_users = isset($input['max_users']) ? (int)$input['max_users'] : 5;

        if (empty($username)) { echo json_encode(['success' => false, 'message' => 'Username is required.']); exit; }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success' => false, 'message' => 'Valid email is required.']); exit; }
        if (empty($password) || strlen($password) < 8) { echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']); exit; }
        if ($password !== $confirm) { echo json_encode(['success' => false, 'message' => 'Passwords do not match.']); exit; }
        if (!in_array($subscription_plan, ['7 Days', 'Monthly', 'Yearly'])) { echo json_encode(['success' => false, 'message' => 'Invalid subscription plan.']); exit; }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Email or Username already in use.']); exit; }

        $start_date = date('Y-m-d H:i:s');
        if ($subscription_plan === '7 Days') $end_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        elseif ($subscription_plan === 'Monthly') $end_date = date('Y-m-d H:i:s', strtotime('+1 month'));
        else $end_date = date('Y-m-d H:i:s', strtotime('+1 year'));

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password_hash, role, is_active, subscription_plan, subscription_start_date, subscription_end_date, module_pos, module_inventory, module_suppliers, module_customers, module_expenses, module_reports, module_staff, module_branches, max_users) VALUES (?, ?, ?, ?, 'admin', 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $full_name, $email, $password_hash, $subscription_plan, $start_date, $end_date, $mod_pos, $mod_inv, $mod_sup, $mod_cus, $mod_exp, $mod_rep, $mod_stf, $mod_brn, $max_users]);
        $new_id = $pdo->lastInsertId();

        $settingsStmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $settingsStmt->execute(["shop_name_$new_id", $shop_name, $shop_name]);
        $settingsStmt->execute(["shop_contact_$new_id", $shop_contact, $shop_contact]);

        echo json_encode(['success' => true, 'message' => 'Shop created successfully!']);
        exit;
    }

    // ---- TOGGLE ACTIVE ----
    if ($action === 'toggle') {
        $id = (int)($input['id'] ?? 0);
        if ($id === $_SESSION['user_id']) { echo json_encode(['success' => false, 'message' => 'Cannot change your own status.']); exit; }
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        if ($current === false) { echo json_encode(['success' => false, 'message' => 'Shop not found.']); exit; }
        $new_status = $current ? 0 : 1;
        $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_status, $id]);
        $pdo->prepare("UPDATE users SET is_active = ? WHERE assigned_admin_id = ? AND role = 'user'")->execute([$new_status, $id]);
        echo json_encode(['success' => true, 'is_active' => $new_status]);
        exit;
    }

    // ---- DELETE ----
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id === $_SESSION['user_id']) { echo json_encode(['success' => false, 'message' => 'Cannot delete your own account.']); exit; }
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Shop deleted.']);
        exit;
    }

    // ---- UPDATE SHOP ----
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        $full_name = trim($input['full_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $shop_name = trim($input['shop_name'] ?? '');
        $shop_contact = trim($input['shop_contact'] ?? '');

        $mod_pos = isset($input['module_pos']) ? (int)$input['module_pos'] : 1;
        $mod_inv = isset($input['module_inventory']) ? (int)$input['module_inventory'] : 1;
        $mod_sup = isset($input['module_suppliers']) ? (int)$input['module_suppliers'] : 1;
        $mod_cus = isset($input['module_customers']) ? (int)$input['module_customers'] : 1;
        $mod_exp = isset($input['module_expenses']) ? (int)$input['module_expenses'] : 1;
        $mod_rep = isset($input['module_reports']) ? (int)$input['module_reports'] : 1;
        $mod_stf = isset($input['module_staff']) ? (int)$input['module_staff'] : 1;
        $mod_brn = isset($input['module_branches']) ? (int)$input['module_branches'] : 1;
        $max_users = isset($input['max_users']) ? (int)$input['max_users'] : 5;

        $sub_plan = $input['subscription_plan'] ?? 'Monthly';
        $sub_end_date = $input['subscription_end_date'] ?? date('Y-m-d H:i:s', strtotime('+1 month'));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success' => false, 'message' => 'Valid email is required.']); exit; }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) { echo json_encode(['success' => false, 'message' => 'Email already in use.']); exit; }

        $pdo->prepare("UPDATE users SET full_name = ?, email = ?, 
            subscription_plan = ?, subscription_end_date = ?,
            module_pos = ?, module_inventory = ?, module_suppliers = ?, module_customers = ?,
            module_expenses = ?, module_reports = ?, module_staff = ?, module_branches = ?, max_users = ?
            WHERE id = ? AND role = 'admin'")
            ->execute([
                $full_name, $email, 
                $sub_plan, $sub_end_date,
                $mod_pos, $mod_inv, $mod_sup, $mod_cus, $mod_exp, $mod_rep, $mod_stf, $mod_brn, $max_users,
                $id
            ]);

        $settingsStmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $settingsStmt->execute(["shop_name_$id", $shop_name, $shop_name]);
        $settingsStmt->execute(["shop_contact_$id", $shop_contact, $shop_contact]);

        echo json_encode(['success' => true, 'message' => 'Shop details updated!']);
        exit;
    }

    // ---- RESET PASSWORD ----
    if ($action === 'reset_password') {
        $id = (int)($input['id'] ?? 0);
        $password = $input['password'] ?? '';
        
        if (empty($password) || strlen($password) < 8) { echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']); exit; }
        
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'admin'")->execute([$password_hash, $id]);

        echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);
        exit;
    }

    // ---- RENEW ----
    if ($action === 'renew') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT subscription_plan, subscription_end_date FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$id]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$info) { echo json_encode(['success' => false, 'message' => 'Shop not found.']); exit; }
        $plan = $info['subscription_plan'] ?: 'Monthly';
        $current_end = strtotime($info['subscription_end_date']);
        $base = ($current_end && $current_end > time()) ? $current_end : time();
        if ($plan === '7 Days') $new_end = date('Y-m-d H:i:s', strtotime('+7 days', $base));
        elseif ($plan === 'Monthly') $new_end = date('Y-m-d H:i:s', strtotime('+1 month', $base));
        else $new_end = date('Y-m-d H:i:s', strtotime('+1 year', $base));
        $pdo->prepare("UPDATE users SET subscription_end_date = ?, is_active = 1 WHERE id = ?")->execute([$new_end, $id]);
        $pdo->prepare("UPDATE users SET is_active = 1 WHERE assigned_admin_id = ? AND role = 'user'")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Subscription renewed until ' . date('M d, Y', strtotime($new_end))]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

