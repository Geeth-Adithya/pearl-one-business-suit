<?php
$file = 'admin/manage_users.php';
$content = file_get_contents($file);

$search = <<<PHP
    if (\$current_status !== false) {
        \$new_status = \$current_status ? 0 : 1;
        \$update_stmt = \$pdo->prepare("UPDATE Users SET is_active = ? WHERE id = ?");
        if (\$update_stmt->execute([\$new_status, \$user_id_to_toggle])) {
            \$_SESSION['success_message'] = 'User account status updated successfully.';
        } else {
            \$_SESSION['error_message'] = 'Failed to update user account status.';
        }
    } else {
        \$_SESSION['error_message'] = 'User not found.';
    }
    header('Location: manage_users.php');
    exit;
PHP;

$replace = <<<PHP
    if (\$current_status !== false) {
        \$new_status = \$current_status ? 0 : 1;
        \$update_stmt = \$pdo->prepare("UPDATE Users SET is_active = ? WHERE id = ?");
        \$success = \$update_stmt->execute([\$new_status, \$user_id_to_toggle]);
        
        // Handle AJAX response
        if (!empty(\$_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(\$_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => \$success,
                'message' => \$success ? 'Status updated.' : 'Failed to update.',
                'new_status' => \$new_status
            ]);
            exit;
        }

        // Fallback for non-AJAX
        if (\$success) {
            \$_SESSION['success_message'] = 'User account status updated successfully.';
        } else {
            \$_SESSION['error_message'] = 'Failed to update user account status.';
        }
    } else {
        if (!empty(\$_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(\$_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }
        \$_SESSION['error_message'] = 'User not found.';
    }
    header('Location: manage_users.php');
    exit;
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Patched manage_users.php to return JSON for AJAX toggle.\n";
?>

