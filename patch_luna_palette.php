<?php
$header_path = 'includes/header.php';
$content = file_get_contents($header_path);

// We will replace the Tailwind config colors completely.
$pattern = '/colors:\s*{[^}]+(brand:\s*{[^}]+},?\s*)?(gray:\s*{[^}]+})?\s*}/s';

$replace = <<<JS
colors: {
                        brand: {
                            blue: '#26658C',
                            blueDark: '#023859',
                            light: '#54ACBF',
                            lighter: '#A7EBF2',
                        },
                        gray: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#26658C', // LUNA mid-dark for inputs/borders
                            800: '#023859', // LUNA dark for panels
                            900: '#011C40', // LUNA darkest for main background
                        }
                    }
JS;

$content = preg_replace($pattern, $replace, $content);
file_put_contents($header_path, $content);
echo "Updated Tailwind config to use LUNA color palette\n";
?>

