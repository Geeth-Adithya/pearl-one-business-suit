<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

$oldBtn = 'class="text-xs text-brand-blue hover:underline flex items-center gap-1"';
$newBtn = 'class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-500/40 px-2 py-1 rounded flex items-center gap-1 transition font-medium border border-blue-200 dark:border-blue-500/30"';

$content = str_replace($oldBtn, $newBtn, $content);

file_put_contents($file, $content);
echo "Button styling updated for clarity!";
?>
