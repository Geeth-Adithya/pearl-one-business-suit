<?php
$dir = "user/";
$files = glob($dir . "*.php");
$count = 0;
$modified = [];

foreach ($files as $file) {
    if (in_array(basename($file), ['dashboard.php', 'invoice_history.php'])) continue;
    
    $content = file_get_contents($file);
    $original = $content;
    
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
echo "Modified $count user files: \n" . implode("\n", $modified);
?>
