<?php
$header_path = 'includes/header.php';
$content = file_get_contents($header_path);

$search = <<<JS
                        brand: {
                            blue: '#3B82F6',
                            blueDark: '#1E3A8A',
                            light: '#60A5FA',
                            lighter: '#93C5FD',
                            lightest: '#BFDBFE'
                        }
JS;

$replace = <<<JS
                        brand: {
                            blue: '#3B82F6',
                            blueDark: '#1E3A8A',
                            light: '#60A5FA',
                            lighter: '#93C5FD',
                            lightest: '#BFDBFE'
                        },
                        gray: {
                            900: '#0B1736', // Very dark blue for main background
                            800: '#112350', // Slightly lighter for panels/sidebar
                            700: '#1E3A8A', // Brand dark blue for borders/accents
                        }
JS;

$content = str_replace($search, $replace, $content);
file_put_contents($header_path, $content);
echo "Updated Tailwind config to use blue-tinted grays for dark mode\n";
?>

