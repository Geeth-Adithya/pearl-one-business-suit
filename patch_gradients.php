<?php
$file = 'includes/header.php';
$content = file_get_contents($file);

// Replace body classes
$search_body = '$body_class = "bg-blue-200 text-gray-900 dark:bg-gray-900 dark:text-gray-100 min-h-screen flex flex-col";';
$replace_body = '$body_class = "bg-gradient-to-br from-blue-100 via-white to-blue-50 text-gray-900 dark:bg-gradient-to-br dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 dark:text-gray-100 min-h-screen flex flex-col";';
$content = str_replace($search_body, $replace_body, $content);

// Replace nav classes
$search_nav = '<nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">';
$replace_nav = '<nav class="bg-gradient-to-r from-white to-blue-50 dark:bg-gradient-to-r dark:from-gray-900 dark:to-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">';
$content = str_replace($search_nav, $replace_nav, $content);

file_put_contents($file, $content);
echo "Added gradient backgrounds to header.php\n";
?>

