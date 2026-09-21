<?php
$file = 'user/dashboard.php';
$content = file_get_contents($file);

// Add dark mode text color for gray-500
$content = preg_replace('/text-gray-500(?! dark:)/', 'text-gray-500 dark:text-gray-300', $content);

file_put_contents($file, $content);
echo "Fixed text-gray-500 in dashboard.php\n";
?>

