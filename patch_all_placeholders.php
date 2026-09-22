<?php
$dir = new RecursiveDirectoryIterator('.');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach($files as $file) {
    $path = $file[0];
    if (strpos($path, 'vendor') !== false) continue; // Skip vendor folder
    
    $content = file_get_contents($path);
    $original = $content;
    
    // Replace placeholder=""
    $content = preg_replace('/placeholder="e\.g\.[^"]*"/i', 'placeholder=""', $content);
    // Replace placeholder=""
    $content = preg_replace('/placeholder="your@email\.com"/i', 'placeholder=""', $content);
    // Replace placeholder="https:\/\/example\.com[^"]*"/i
    $content = preg_replace('/placeholder="https:\/\/example\.com[^"]*"/i', 'placeholder=""', $content);
    
    if ($content !== $original) {
        file_put_contents($path, $content);
        $count++;
    }
}
echo "Removed example placeholders in $count files.";
?>
