<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

$content = str_replace(
    '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">',
    '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">',
    $content
);

file_put_contents($file, $content);
echo "Fixed Quick Actions grid in admin/index.php";
?>
