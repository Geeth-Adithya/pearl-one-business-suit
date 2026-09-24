<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

// Fix VALUES count
$content = str_replace(
    'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    $content
);

// Fix array elements
$search = <<<PHP
                    \$unit,
                    \$description,
                    \$supplier_name,
                    \$image_url,
                    \$video_url,
                    \$_SESSION['user_id']
PHP;

$replace = <<<PHP
                    \$unit,
                    \$description,
                    \$supplier_name,
                    \$_POST['stock_quantity'] ?? 0,
                    \$image_url,
                    \$video_url,
                    \$_SESSION['user_id']
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Fixed product_add.php insert logic\n";
?>

