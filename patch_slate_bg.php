<?php
$header_path = 'includes/header.php';
$content = file_get_contents($header_path);

$search = <<<JS
                        // Removed custom gray to restore good contrast.
JS;

$replace = <<<JS
                        gray: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
JS;

$content = str_replace($search, $replace, $content);
file_put_contents($header_path, $content);
echo "Updated Tailwind config to use slate colors\n";
?>

