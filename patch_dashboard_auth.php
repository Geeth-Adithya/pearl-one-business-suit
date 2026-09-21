<?php
$file = 'user/dashboard.php';
$content = file_get_contents($file);

$content = str_replace(
    "requireUser();\nif (empty(\$_SESSION['module_pos']) && \$_SESSION['role'] !== 'superadmin' && \$_SESSION['role'] !== 'admin') {",
    "requireLogin();\nif (empty(\$_SESSION['module_pos']) && \$_SESSION['role'] !== 'superadmin' && \$_SESSION['role'] !== 'admin') {",
    $content
);

file_put_contents($file, $content);
echo "Fixed user/dashboard.php auth\n";
?>

