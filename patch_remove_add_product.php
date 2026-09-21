<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

$pattern = '/\s*<!-- Add Product -->\s*<\?php if \(\$_SESSION\[\'module_stock\'\]\): \?>\s*<a href="<\?= BASE_URL \?>\/admin\/product_add\.php".*?<\/a>\s*<\?php endif; \?>/is';

$content = preg_replace($pattern, '', $content);

file_put_contents($file, $content);
echo "Removed Add Product card from admin dashboard.\n";
?>

