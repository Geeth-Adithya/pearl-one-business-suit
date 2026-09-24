<?php
$dir = 'admin/';
$files = glob($dir . '*.php');
$count = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Make headers responsive
    $content = preg_replace(
        '/<div class="mb-8 flex justify-between items-center">/',
        '<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">',
        $content
    );
    
    $content = preg_replace(
        '/<div class="mb-6 flex justify-between items-center">/',
        '<div class="mb-6 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">',
        $content
    );

    if ($content !== $original) {
        file_put_contents($file, $content);
        $count++;
    }
}
echo "Fixed responsive headers in $count admin files.";
?>
