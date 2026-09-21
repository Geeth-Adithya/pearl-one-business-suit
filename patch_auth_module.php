<?php
$file = 'includes/auth.php';
$content = file_get_contents($file);

$func = <<<PHP
function requireModule(\$module_name)
{
    requireLogin();
    // Superadmins don't have modules
    if (\$_SESSION['role'] === 'superadmin') {
        \$_SESSION['error_message'] = "Superadmins do not use operational modules.";
        header("Location: " . BASE_URL . "/admin/index.php");
        exit;
    }
    // Check if the module flag is set and truthy in session
    if (empty(\$_SESSION[\$module_name])) {
        if (isAjaxRequest()) {
            handleUnauthorizedAjax();
        } else {
            \$_SESSION['error_message'] = "Your shop does not have access to this module.";
            if (\$_SESSION['role'] === 'admin') {
                header("Location: " . BASE_URL . "/admin/index.php");
            } else {
                header("Location: " . BASE_URL . "/login.php"); // or wherever appropriate
            }
        }
        exit;
    }
}
?>
PHP;

$content = str_replace('?>', trim($func), $content);
file_put_contents($file, $content);
echo "Added requireModule to auth.php\n";
?>

