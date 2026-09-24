<?php
require_once 'config.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    $shopName = 'My Shop';
    $modules = null;
    
    $admin_id = null;
    if ($_SESSION['role'] === 'admin') {
        $admin_id = $_SESSION['user_id'];
    } else if ($_SESSION['role'] === 'user') {
        $stmt = $pdo->prepare("SELECT assigned_admin_id FROM Users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $admin_id = $stmt->fetchColumn();
    }
    
    if ($admin_id) {
        $shopStmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
        $shopStmt->execute(['shop_name_' . $admin_id]);
        $shopName = $shopStmt->fetchColumn() ?: 'My Shop';
        
        $modStmt = $pdo->prepare("SELECT module_pos, module_inventory, module_suppliers, module_customers, module_expenses, module_reports, module_staff, module_branches FROM Users WHERE id = ?");
        $modStmt->execute([$admin_id]);
        $modules = $modStmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $modules = [
            'module_pos' => 1, 'module_inventory' => 1, 'module_suppliers' => 1,
            'module_customers' => 1, 'module_expenses' => 1, 'module_reports' => 1,
            'module_staff' => 1, 'module_branches' => 1
        ];
    }

    if ($modules) {
        foreach ($modules as $k => $v) {
            $modules[$k] = (bool)$v;
        }
    }

    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'shopName' => $shopName,
            'modules' => $modules
        ]
    ]);
} else {
    echo json_encode(['authenticated' => false]);
}
?>
