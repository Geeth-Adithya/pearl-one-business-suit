<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

$content = str_replace('placeholder=""', 'placeholder=""', $content);

file_put_contents($file, $content);
echo "Placeholder removed successfully!";
?>
