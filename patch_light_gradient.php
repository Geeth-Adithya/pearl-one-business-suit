<?php
$file = 'includes/header.php';
$content = file_get_contents($file);

// Replace body classes
$search_body = '$body_class = "bg-gradient-to-br from-blue-100 via-white to-blue-50 text-gray-900 dark:bg-gradient-to-br dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 dark:text-gray-100 min-h-screen flex flex-col";';
$replace_body = '$body_class = "bg-gradient-to-br from-cyan-100 via-blue-50 to-indigo-100 text-gray-900 dark:bg-gradient-to-br dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 dark:text-gray-100 min-h-screen flex flex-col";';
$content = str_replace($search_body, $replace_body, $content);

// Replace nav classes
$search_nav = '<nav class="bg-gradient-to-r from-white to-blue-50 dark:bg-gradient-to-r dark:from-gray-900 dark:to-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">';
$replace_nav = '<nav class="bg-gradient-to-r from-cyan-50 to-blue-100 dark:bg-gradient-to-r dark:from-gray-900 dark:to-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">';
$content = str_replace($search_nav, $replace_nav, $content);

file_put_contents($file, $content);
echo "Updated light mode gradient in header.php\n";
?>

