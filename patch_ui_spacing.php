<?php
$file = 'user/includes/sidebar.php';
$content = file_get_contents($file);

// Make sidebar smaller: change lg:w-64 to lg:w-56 (or w-48)
$content = str_replace('lg:w-64', 'lg:w-52', $content);

file_put_contents($file, $content);
echo "Sidebar resized.\n";

$file_dash = 'user/dashboard.php';
$content_dash = file_get_contents($file_dash);

// Reduce padding and gap
$content_dash = str_replace('p-4 lg:p-6', 'p-2 lg:p-4', $content_dash);
$content_dash = str_replace('gap-6', 'gap-4', $content_dash);

file_put_contents($file_dash, $content_dash);
echo "Dashboard spacing resized.\n";
?>

