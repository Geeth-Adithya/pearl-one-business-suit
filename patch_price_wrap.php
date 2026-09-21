<?php
$file = 'user/dashboard.php';
$content = file_get_contents($file);

$search = '<span class="font-bold text-gray-900 dark:text-white text-lg" x-text="\'Rs. \' + parseFloat(product.selling_price).toFixed(2)"></span>';
$replace = '<span class="font-bold text-gray-900 dark:text-white text-base whitespace-nowrap tracking-tight" x-text="\'Rs. \' + parseFloat(product.selling_price).toFixed(2)"></span>';

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Price text updated in user/dashboard.php\n";
?>

