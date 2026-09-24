<?php
$file = 'includes/header.php';
$content = file_get_contents($file);

$pattern = '/<a href="<\?= BASE_URL \?>\/index\.php" class="flex-shrink-0 flex items-center">\s*<img class="h-8 w-auto" src="<\?= BASE_URL \?>\/assets\/images\/logo\.png" alt="StoreApp Logo">\s*<\/a>/s';

$replace = <<<HTML
<a href="<?= BASE_URL ?>/index.php" class="flex-shrink-0 flex items-center gap-3">
                        <img class="h-8 w-auto" src="<?= BASE_URL ?>/assets/images/logo.png" alt="Pearl Store Logo">
                        <span class="text-xl font-bold text-gray-900 dark:text-white tracking-wide">Pearl Store</span>
                    </a>
HTML;

$content = preg_replace($pattern, $replace, $content);
file_put_contents($file, $content);
echo "Regex patched logo.\n";
?>

