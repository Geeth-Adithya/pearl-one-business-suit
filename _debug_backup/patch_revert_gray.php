<?php
$header_path = 'includes/header.php';
$content = file_get_contents($header_path);

// We want to remove the custom `gray` object entirely and let Tailwind use its default grays.
// Alternatively, we can use `slate` colors which have a nice subtle blue tint perfect for blue themes.

$search = <<<JS
                        gray: {
                            900: '#0B1736', // Very dark blue for main background
                            800: '#112350', // Slightly lighter for panels/sidebar
                            700: '#1E3A8A', // Brand dark blue for borders/accents
                        }
JS;

$replace = <<<JS
                        // Removed custom gray to restore good contrast.
JS;

$content = str_replace($search, $replace, $content);
file_put_contents($header_path, $content);
echo "Reverted custom gray colors in header.php\n";
?>

