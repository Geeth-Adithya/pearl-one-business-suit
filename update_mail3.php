<?php
$file = 'includes/mail_config.php';
$content = file_get_contents($file);

$content = str_replace(
    "define('SMTP_PASSWORD', 'clpd ltsw iwyf wiao');",
    "define('SMTP_PASSWORD', 'clpdltswiwyfwiao');",
    $content
);

file_put_contents($file, $content);
echo "SMTP_PASSWORD updated (removed spaces).";
?>
