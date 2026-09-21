<?php
$file = 'admin/create_user.php';
$content = file_get_contents($file);

// Replace the strict empty check to remove `full_name` requirement
$search = "if (empty(\$username) || empty(\$full_name) || empty(\$email) || empty(\$password) || empty(\$confirm_password)) {";
$replace = <<<PHP
if (empty(\$full_name)) { \$full_name = \$username; } // Fallback to username if no full name is provided
    if (empty(\$username) || empty(\$email) || empty(\$password) || empty(\$confirm_password)) {
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Fixed full_name validation requirement.\n";
?>
