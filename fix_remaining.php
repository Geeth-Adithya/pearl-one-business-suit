<?php
// Fix shop_settings.php
$content = file_get_contents("admin/shop_settings.php");
$pattern = '/<div class="flex justify-between items-center mb-6">\s*<h1 class="text-2xl font-bold text-gray-900 dark:text-white">([^<]+)<\/h1>\s*<a href="[^"]*"[^>]*>[^<]+<\/a>\s*<\/div>/is';
$content = preg_replace_callback($pattern, function($matches) {
    $title = $matches[1];
    return '<div class="mb-6 flex justify-between items-center">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">' . $title . '</h1>
    </div>
</div>';
}, $content);
file_put_contents("admin/shop_settings.php", $content);

// Fix edit_user.php
$content = file_get_contents("admin/edit_user.php");
$pattern2 = '/<div class="mb-8">\s*<h1 class="text-3xl font-bold text-gray-900 dark:text-white">([^<]+)<\/h1>\s*<p class="text-gray-500 dark:text-gray-400 mt-2">([^<]+)<\/p>\s*<\/div>/is';
$content = preg_replace_callback($pattern2, function($matches) {
    $title = $matches[1];
    $subtitle = $matches[2];
    return '<div class="mb-8 flex justify-between items-center">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">' . $title . '</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">' . $subtitle . '</p>
        </div>
    </div>
</div>';
}, $content);
file_put_contents("admin/edit_user.php", $content);

// Fix supplier_view.php
$content = file_get_contents("admin/supplier_view.php");
$pattern3 = '/<div class="mb-8 flex justify-between items-center">\s*<div>\s*<h1 class="text-3xl font-bold text-gray-900 dark:text-white">([^<]+)<\/h1>\s*<\/div>\s*<a href="[^"]*"[^>]*>\s*<ion-icon name="arrow-back[^"]*"><\/ion-icon>[^<]*<\/a>\s*<\/div>/is';
$content = preg_replace_callback($pattern3, function($matches) {
    $title = $matches[1];
    return '<div class="mb-8 flex justify-between items-center">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">' . $title . '</h1>
    </div>
</div>';
}, $content);
file_put_contents("admin/supplier_view.php", $content);

echo "Manual fixes applied.";
?>
