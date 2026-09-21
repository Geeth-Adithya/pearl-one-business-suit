<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

// Replace missing overflow-x-auto around the first table
$content = preg_replace(
    '/<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">\s*<table/',
    '<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">' . "\n        " . '<div class="overflow-x-auto">' . "\n            " . '<table',
    $content
);
$content = preg_replace(
    '/<\/table>\s*<\/div>\s*<\/div>\s*<!-- Quick Actions -->/',
    '</table>' . "\n        " . '</div>' . "\n    " . '</div>' . "\n" . '</div>' . "\n" . '<!-- Quick Actions -->',
    $content
);

file_put_contents($file, $content);
echo "Fixed missing overflow-x-auto in admin/index.php";
?>
