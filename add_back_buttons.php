<?php
$dir = "admin/";
$files = glob($dir . "*.php");
$count = 0;
$modified = [];

foreach ($files as $file) {
    if (in_array(basename($file), ['products.php', 'index.php', 'login.php'])) continue;
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Pattern 1: h1 + subtitle
    $pattern1 = '/<div class="mb-8[^>]*>\s*<h1 class="text-3xl font-bold text-gray-900 dark:text-white">([^<]+)<\/h1>\s*<p class="text-gray-500[^>]*>([^<]+)<\/p>\s*<\/div>/is';
    
    $content = preg_replace_callback($pattern1, function($matches) {
        $title = $matches[1];
        $subtitle = trim($matches[2]);
        
        $html = '<div class="mb-8 flex justify-between items-center">' . "\n";
        $html .= '    <div class="flex items-center gap-4">' . "\n";
        $html .= '        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">' . "\n";
        $html .= '            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>' . "\n";
        $html .= '        </a>' . "\n";
        $html .= '        <div>' . "\n";
        $html .= '            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">' . $title . '</h1>' . "\n";
        $html .= '            <p class="text-gray-500 dark:text-gray-400 mt-1">' . $subtitle . '</p>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '</div>';
        return $html;
    }, $content);
    
    // Pattern 2: h1 + back link (like google_sync.php, product_add.php, product_edit.php)
    $pattern2 = '/<div class="mb-8[^>]*>\s*<h1 class="text-3xl font-bold text-gray-900 dark:text-white">([^<]+)<\/h1>\s*<a href="[^"]*"[^>]*>\s*<ion-icon name="arrow-back[^"]*"><\/ion-icon>[^<]*<\/a>\s*<\/div>/is';
    
    $content = preg_replace_callback($pattern2, function($matches) {
        $title = $matches[1];
        
        $html = '<div class="mb-8 flex justify-between items-center">' . "\n";
        $html .= '    <div class="flex items-center gap-4">' . "\n";
        $html .= '        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">' . "\n";
        $html .= '            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>' . "\n";
        $html .= '        </a>' . "\n";
        $html .= '        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">' . $title . '</h1>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '</div>';
        return $html;
    }, $content);

    // Pattern 3: just h1 (no subtitle, no back link)
    $pattern3 = '/<div class="mb-8[^>]*>\s*<h1 class="text-3xl font-bold text-gray-900 dark:text-white">([^<]+)<\/h1>\s*<\/div>/is';
    $content = preg_replace_callback($pattern3, function($matches) {
        $title = $matches[1];
        
        $html = '<div class="mb-8 flex justify-between items-center">' . "\n";
        $html .= '    <div class="flex items-center gap-4">' . "\n";
        $html .= '        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">' . "\n";
        $html .= '            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>' . "\n";
        $html .= '        </a>' . "\n";
        $html .= '        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">' . $title . '</h1>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '</div>';
        return $html;
    }, $content);

    if ($content !== $original) {
        file_put_contents($file, $content);
        $modified[] = basename($file);
        $count++;
    }
}
echo "Modified $count files: \n" . implode("\n", $modified);
?>
