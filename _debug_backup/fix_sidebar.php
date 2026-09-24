<?php
$file = 'user/includes/sidebar.php';
$content = file_get_contents($file);

// Replace :class logic for responsive sidebar
$content = preg_replace(
    "/:class=\"sidebarExpanded \? 'w-56' : 'w-16'\"/is",
    ":class=\"sidebarExpanded ? 'w-56 absolute sm:relative shadow-2xl sm:shadow-none' : 'w-14 sm:w-16'\"",
    $content
);

file_put_contents($file, $content);
echo "Updated sidebar.php layout for mobile.";
?>
