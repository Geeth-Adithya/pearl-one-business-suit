<?php
function replaceTextColorsInDir($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            if ($file !== '.git' && $file !== 'assets') {
                replaceTextColorsInDir($path);
            }
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            
            $new_content = str_replace('dark:text-brand-blue', 'dark:text-brand-lighter', $content);
            $new_content = str_replace('dark:text-[#1E3A8A]', 'dark:text-brand-lighter', $new_content);
            
            if ($content !== $new_content) {
                file_put_contents($path, $new_content);
                echo "Updated text colors in: $path\n";
            }
        }
    }
}

replaceTextColorsInDir(__DIR__);
echo "Finished updating text colors.\n";
?>

