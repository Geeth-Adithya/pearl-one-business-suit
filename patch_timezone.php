<?php
$file = 'includes/db.php';
$content = file_get_contents($file);

$search = "if (session_status() === PHP_SESSION_NONE) {";
$replace = "date_default_timezone_set('Asia/Colombo');\n\nif (session_status() === PHP_SESSION_NONE) {";

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Set default timezone in db.php\n";
?>

