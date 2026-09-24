<?php
$header_path = 'includes/header.php';
$content = file_get_contents($header_path);

$pattern = '/colors:\s*{\s*brand:\s*{[^}]+}\s*}/s';
$replace = <<<JS
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

$content = preg_replace($pattern, $replace, $content);
file_put_contents($header_path, $content);
echo "Regex patched header.php colors\n";
?>

