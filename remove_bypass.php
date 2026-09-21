<?php
$file = 'forgot_password.php';
$content = file_get_contents($file);

$content = preg_replace(
    '/\/\/ --- LOCALHOST DEV WORKAROUND \(FORCE ENABLED\) ---.*?\/\/ --------------------------------/s',
    '',
    $content
);

file_put_contents($file, $content);
echo "Bypass removed.";
?>
