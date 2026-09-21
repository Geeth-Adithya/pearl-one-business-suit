<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

// Add to DB Update
$content = str_replace(
    'description = ?, supplier_name = ?, image_url = ?, video_url = ?',
    'description = ?, supplier_name = ?, image_url = ?, video_url = ?, stock_quantity = ?',
    $content
);
$content = str_replace(
    '$product[\'image_url\'],
                $product[\'video_url\'],
                $product[\'id\']',
    "\$product['image_url'],\n                \$product['video_url'],\n                \$_POST['stock_quantity'] ?? 0,\n                \$product['id']",
    $content
);

file_put_contents($file, $content);
echo "Updated product_edit.php DB logic\n";
?>

