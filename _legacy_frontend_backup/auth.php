<?php
// includes/auth.php
// db.php should be included before this file to start the session and define BASE_URL.

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in.']);
        } else {
            header("Location: " . BASE_URL . "/login.php");
        }
        exit;
    }

    // Also, check if the currently logged-in user's account is active.
    // This prevents a user from continuing to use the site after their account has been disabled by an admin.
    global $pdo;
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT role, is_active, subscription_end_date FROM Users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $is_active = $user['is_active'];
                // Auto-disable admin if subscription expired
                if ($user['role'] === 'admin' && $user['subscription_end_date']) {
                    if (strtotime($user['subscription_end_date']) < time()) {
                        $is_active = 0;
                        if ($user['is_active'] == 1) { // update db if not already disabled
                            $pdo->prepare("UPDATE Users SET is_active = 0 WHERE id = ?")->execute([$_SESSION['user_id']]);
                            // Also disable all users assigned to this admin
                            $pdo->prepare("UPDATE Users SET is_active = 0 WHERE assigned_admin_id = ? AND role = 'user'")->execute([$_SESSION['user_id']]);
                        }
                    }
                }

                if ($is_active == 0) {
                    // Account is disabled, so destroy the session and log them out.
                    session_unset();
                    session_destroy();
                    session_start(); // Start a new session for the message

                    if (isAjaxRequest()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Your account has been disabled.']);
                    } else {
                        $_SESSION['error_message'] = "Your account has been disabled.";
                        header("Location: " . BASE_URL . "/login.php");
                    }
                    exit;
                }
            }

            $update_seen_stmt = $pdo->prepare("UPDATE Users SET last_seen = NOW() WHERE id = ?");
            $update_seen_stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log('Database check failed in requireLogin: ' . $e->getMessage());
            // For AJAX requests, we might want to return an error, but for regular requests,
            // silently failing and letting the page load might be acceptable if it's just a status check.
            // For now, let's assume it's okay to fail silently for non-critical status checks.
        }
    }
}

function isAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function handleUnauthorizedAjax()
{
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You are not authorized to perform this action.']);
    exit;
}

function requireRole($role)
{
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        // Not authorized
        if (isAjaxRequest()) {
            handleUnauthorizedAjax();
        } else {
            if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin')) {
                header("Location: " . BASE_URL . "/admin/index.php");
            } else {
                header("Location: " . BASE_URL . "/user/dashboard.php");
            }
        }
        exit;
    }
}

function requireAdmin()
{
    requireLogin();
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') {
        if (isAjaxRequest()) {
            handleUnauthorizedAjax();
        } else {
            header("Location: " . BASE_URL . "/user/dashboard.php");
        }
        exit;
    }
}

function requireUser()
{
    requireRole('user');
}
function requireModule($module_name)
{
    requireLogin();
    // Superadmins don't have modules
    if ($_SESSION['role'] === 'superadmin') {
        $_SESSION['error_message'] = "Superadmins do not use operational modules.";
        header("Location: " . BASE_URL . "/admin/index.php");
        exit;
    }
    // Check if the module flag is set and truthy in session
    if (empty($_SESSION[$module_name])) {
        if (isAjaxRequest()) {
            handleUnauthorizedAjax();
        } else {
            $_SESSION['error_message'] = "Your shop does not have access to this module.";
            if ($_SESSION['role'] === 'admin') {
                header("Location: " . BASE_URL . "/admin/index.php");
            } else {
                header("Location: " . BASE_URL . "/login.php"); // or wherever appropriate
            }
        }
        exit;
    }
}
?>
