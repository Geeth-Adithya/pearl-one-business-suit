<?php
$file = 'login.php';
$content = file_get_contents($file);

$content = str_replace(
    'SELECT id, username, password_hash, role, is_active',
    'SELECT id, username, full_name, password_hash, role, is_active',
    $content
);

$content = str_replace(
    "\$_SESSION['username'] = \$user['username'];",
    "\$_SESSION['username'] = \$user['username'];\n                \$_SESSION['full_name'] = \$user['full_name'];",
    $content
);

file_put_contents($file, $content);
echo "Updated login.php\n";
?>

