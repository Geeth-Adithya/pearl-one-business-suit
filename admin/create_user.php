<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();




function respond($success, $message) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    } else {
        if ($success) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = $message;
        }
        header('Location: manage_users.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

try {
    $username = preg_replace('/[^a-zA-Z0-9]/', '', trim($_POST['username'] ?? ''));
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $assigned_admin_id = null; // Automatically assigning later

    if (empty($full_name)) { $full_name = $username; } // Fallback to username if no full name is provided
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        respond(false, 'Please fill all required fields.');
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

    // Role specific restrictions
    if ($_SESSION['role'] === 'admin') {
        $assigned_admin_id = $_SESSION['user_id'];
        $countStmt = $pdo->prepare("SELECT count(*) FROM Users WHERE assigned_admin_id = ? AND role = 'user'");
        $countStmt->execute([$assigned_admin_id]);
        if ($countStmt->fetchColumn() >= 2) {
            respond(false, 'You have reached the maximum limit of 2 users for your shop.');
        }
    } else if ($_SESSION['role'] === 'superadmin') {
        // Just fail if superadmin tries to create a user directly without assigned admin
        // Or assign to superadmin? Superadmins shouldn't create 'user' directly.
        // Actually earlier code just allowed superadmins to use the dropdown, but we removed the dropdown!
        // To simplify, if superadmin creates a user, it assigns to themselves for testing.
        $assigned_admin_id = $_SESSION['user_id'];
    }

    // Check email
    $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        respond(false, 'Email is already in use by another account.');
    }

    // Check username
    $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        respond(false, 'Username is already in use. Try checking/regenerating it.');
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    if ($password_hash === false) {
        respond(false, 'Unable to process the password at this time.');
    }

    $stmt = $pdo->prepare("INSERT INTO Users (full_name, username, email, password_hash, role, assigned_admin_id, is_active, force_password_change) VALUES (?, ?, ?, ?, 'user', ?, 1, 1)");
    if ($stmt->execute([$full_name, $username, $email, $password_hash, $assigned_admin_id])) {
        respond(true, 'New user created successfully!');
    }

    respond(false, 'Failed to create the user account.');
} catch (PDOException $e) {
    error_log("User creation PDO error: " . $e->getMessage());
    respond(false, 'A database error occurred during user creation.');
}
?>

