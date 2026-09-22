<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

// Replace hover classes
$content = str_replace('group-hover:text-brand-blue', 'group-hover:text-blue-500', $content);

file_put_contents($file, $content);
echo "Hover effect fixed!";
?>
