<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

// 1. Remove the hover text color completely
$content = str_replace('group-hover:text-blue-500 transition', 'transition', $content);

// 2. Add the "pop up" hover effect to the cards
$searchHover = 'hover:bg-gray-50 dark:hover:bg-gray-700 transition group';
$replaceHover = 'hover:bg-gray-50 dark:hover:bg-gray-700 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 group';
$content = str_replace($searchHover, $replaceHover, $content);

file_put_contents($file, $content);
echo "Hover effect fixed to pop up box!";
?>
