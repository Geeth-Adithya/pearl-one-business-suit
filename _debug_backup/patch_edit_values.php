<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

// Fix array elements for update
$search = <<<PHP
                  \$product['supplier_name'],
                  \$image_url,
                  \$video_url,
                  \$product_id
PHP;

$replace = <<<PHP
                  \$product['supplier_name'],
                  \$image_url,
                  \$video_url,
                  \$_POST['stock_quantity'] ?? 0,
                  \$product_id
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Fixed product_edit.php update logic\n";
?>

