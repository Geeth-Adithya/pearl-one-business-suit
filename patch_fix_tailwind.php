<?php
$header_path = 'includes/header.php';
$content = file_get_contents($header_path);

// Replace everything between <script> tailwind.config = ... </script>
$pattern = '/<script>\s*tailwind\.config\s*=\s*{.*?}\s*<\/script>/is';

$replace = <<<JS
<script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
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
                            700: '#26658C',
                            800: '#023859',
                            900: '#011C40',
                        }
                    }
                }
            }
        }
    </script>
JS;

$content = preg_replace($pattern, $replace, $content);
file_put_contents($header_path, $content);
echo "Fixed Tailwind config syntax\n";
?>

