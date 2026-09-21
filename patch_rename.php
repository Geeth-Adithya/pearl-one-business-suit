<?php
function replaceNameInDir($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            if ($file !== '.git' && $file !== 'assets') {
                replaceNameInDir($path);
            }
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            
            // Search and Replace
            $new_content = str_replace('Store App', 'Pearl Store', $content);
            $new_content = str_replace('Store_App', 'Pearl_Store', $new_content);
            
            if ($content !== $new_content) {
                file_put_contents($path, $new_content);
                echo "Updated: $path\n";
            }
        }
    }
}

replaceNameInDir(__DIR__);
echo "Finished renaming to Pearl Store.\n";
?>

