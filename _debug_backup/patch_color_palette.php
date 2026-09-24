<?php
function replaceColorsInDir($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            if ($file !== '.git' && $file !== 'assets') {
                replaceColorsInDir($path);
            }
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            
            // Replace hardcoded purples with the new dark blue (#1E3A8A)
            $content = str_replace('#581c87', '#1E3A8A', $content);
            $content = str_replace('#4c1d95', '#1e3a8a', $content); // slightly darker/same
            
            // Replace brand-purple with brand-blue
            $content = str_replace('brand-purpleDark', 'brand-blueDark', $content);
            $content = str_replace('brand-purple', 'brand-blue', $content);
            
            file_put_contents($path, $content);
        }
    }
}

replaceColorsInDir(__DIR__);

// Also update header.php tailwind config directly
$header_path = __DIR__ . '/includes/header.php';
if (file_exists($header_path)) {
    $header_content = file_get_contents($header_path);
    
    $search_config = <<<JS
                    colors: {
                        brand: {
                            blue: '#3B82F6',
                            blueDark: '#1D4ED8',
                            purple: '#8B5CF6',
                            purpleDark: '#6D28D9',
                        }
                    }
JS;

    $replace_config = <<<JS
                    colors: {
                        brand: {
                            blue: '#3B82F6',
                            blueDark: '#1E3A8A',
                            light: '#60A5FA',
                            lighter: '#93C5FD',
                            lightest: '#BFDBFE'
                        }
                    }
JS;

    $header_content = str_replace($search_config, $replace_config, $header_content);
    file_put_contents($header_path, $header_content);
}

echo "Color palette updated project-wide.\n";
?>

