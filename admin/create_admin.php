<?php
// c:\xampp\htdocs\Store_Purchase&Sell\admin\create_admin.php

require_once '../includes/db.php';
require_once '../includes/auth.php';

// Authorization check: Only superadmins can create new admins.
requireRole('superadmin');

function getRedirectUrl()
{
    if (!empty($_POST['redirect']) && str_starts_with($_POST['redirect'], '/')) {
        return $_POST['redirect'];
    }

    return BASE_URL . '/admin/manage_admins.php';
}

function respond($success, $message)
{
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    $_SESSION[$success ? 'success_message' : 'error_message'] = $message;
    header('Location: ' . getRedirectUrl());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

try {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username)) {
        respond(false, 'Username is required.');
    }
    if (empty($email)) {
        respond(false, 'Email is required.');
    }
    if (empty($password)) {
        respond(false, 'Password is required.');
    }

    if ($password !== $confirm_password) {
        respond(false, 'Passwords do not match.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Invalid email format.');
    }

    if (strlen($password) < 4) {
        respond(false, 'Password must be at least 4 characters long.');
    }

    $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        respond(false, 'Email or Username is already in use by another account.');
    }

    $subscription_plan = $_POST['subscription_plan'] ?? null;
    if (!in_array($subscription_plan, ['7 Days', 'Monthly', 'Yearly'])) {
        respond(false, 'Please select a valid subscription plan.');
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    if ($password_hash === false) {
        respond(false, 'Unable to process the password at this time.');
    }

    $start_date = date('Y-m-d H:i:s');
        if ($subscription_plan === '7 Days') {
        $end_date = date('Y-m-d H:i:s', strtotime('+7 days'));
    } elseif ($subscription_plan === 'Monthly') {
        $end_date = date('Y-m-d H:i:s', strtotime('+1 month'));
    } else {
        $end_date = date('Y-m-d H:i:s', strtotime('+1 year'));
    }

    $module_pos = isset($_POST['module_pos']) ? 1 : 0;
    $module_stock = isset($_POST['module_stock']) ? 1 : 0;
    $module_supply = isset($_POST['module_supply']) ? 1 : 0;

        $full_name = trim($_POST['full_name'] ?? '');
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash, role, is_active, subscription_plan, subscription_start_date, subscription_end_date, module_pos, module_stock, module_supply, full_name) VALUES (?, ?, ?, 'admin', 1, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$username, $email, $password_hash, $subscription_plan, $start_date, $end_date, $module_pos, $module_stock, $module_supply, $full_name])) {
        $new_admin_id = $pdo->lastInsertId();
        
        // Insert shop settings
        $shop_name = trim($_POST['shop_name'] ?? 'My Shop');
        $shop_contact = trim($_POST['shop_contact'] ?? '');
        $shop_address = trim($_POST['shop_address'] ?? '');
        
        $settingsStmt = $pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        $settingsStmt->execute(['shop_name_' . $new_admin_id, $shop_name, $shop_name]);
        $settingsStmt->execute(['shop_contact_' . $new_admin_id, $shop_contact, $shop_contact]);
        $settingsStmt->execute(['shop_address_' . $new_admin_id, $shop_address, $shop_address]);

        respond(true, 'New shop created successfully!');
    }

    respond(false, 'Failed to create the admin account.');
} catch (PDOException $e) {
    error_log("Admin creation PDO error: " . $e->getMessage());
    respond(false, 'A database error occurred during admin creation.');
}
?>
