<?php
$file = 'includes/mail_config.php';
$content = file_get_contents($file);

$content = str_replace(
    "define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');",
    "define('SMTP_PASSWORD', 'clpd ltsw iwyf wiao');",
    $content
);

$content = str_replace(
    "define('SMTP_USERNAME', 'your-email@gmail.com');",
    "define('SMTP_USERNAME', 'geethadithya004@gmail.com');",
    $content
);

file_put_contents($file, $content);
echo "mail_config.php updated successfully.";
?>
