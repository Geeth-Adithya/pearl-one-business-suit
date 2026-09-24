<?php
$content = file_get_contents("admin/supplier_view.php");
$pattern = '/<div class="mb-8 flex items-center justify-between">\s*<div class="flex items-center gap-4">\s*<a href="[^"]*" class="[^"]*">\s*<ion-icon name="arrow-back-outline"[^>]*><\/ion-icon>\s*<\/a>\s*<h1 class="text-3xl font-bold text-gray-900 dark:text-white">([^<]+)<\/h1>\s*<\/div>/is';
$content = preg_replace_callback($pattern, function($matches) {
    $title = $matches[1];
    return '<div class="mb-8 flex justify-between items-center">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">' . $title . '</h1>
    </div>';
}, $content);
file_put_contents("admin/supplier_view.php", $content);
echo "supplier_view.php updated.";
?>
