<?php
$file = 'user/dashboard.php';
$content = file_get_contents($file);
$content = str_replace("requireUser();", "requireLogin();", $content);
file_put_contents($file, $content);
echo "Fixed user/dashboard.php requireUser\n";
?>

