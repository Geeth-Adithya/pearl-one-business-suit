<?php
$file = 'includes/mail_config.php';
$content = file_get_contents($file);

$content = str_replace(
    "define('SMTP_USERNAME', 'geethadithya004@gmail.com');",
    "define('SMTP_USERNAME', 'pearlwaves2004@gmail.com');",
    $content
);

file_put_contents($file, $content);
echo "SMTP_USERNAME updated to pearlwaves2004@gmail.com";
?>
