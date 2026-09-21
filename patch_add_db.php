<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

// Add to DB Insert
$content = str_replace(
    'image_url, video_url, created_by_admin_id',
    'stock_quantity, image_url, video_url, created_by_admin_id',
    $content
);
$content = str_replace(
    '$unit, $description, $supplier_name, $image_url, $video_url,',
    "\$_POST['stock_quantity'] ?? 0, \$unit, \$description, \$supplier_name, \$image_url, \$video_url,",
    $content
);

// For variants loop
$content = str_replace(
    '$unit, $description, $supplier_name, $image_url, $video_url,',
    "\$_POST['stock_quantity'] ?? 0, \$unit, \$description, \$supplier_name, \$image_url, \$video_url,",
    $content
);

file_put_contents($file, $content);
echo "Updated product_add.php DB logic\n";
?>

