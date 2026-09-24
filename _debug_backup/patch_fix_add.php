<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

$search = <<<PHP
                \$stmt->execute([
                    \$variant['item_code'],
                    \$name,
                    \$variant['attribute'],
                    \$type,
                    \$cat_stmt = \$pdo->prepare("INSERT INTO Product_Categories (product_id, category_id) VALUES (?, ?)");
PHP;

$replace = <<<PHP
                \$stmt->execute([
                    \$variant['item_code'],
                    \$name,
                    \$variant['attribute'],
                    \$type,
                    \$variant['purchasing_price'],
                    \$variant['margin_percent'],
                    \$variant['selling_price'],
                    \$unit,
                    \$description,
                    \$supplier_name,
                    \$_POST['stock_quantity'] ?? 0,
                    \$image_url,
                    \$video_url,
                    \$_SESSION['user_id']
                ]);
                \$product_id = \$pdo->lastInsertId();

                if (!empty(\$category_ids)) {
                    \$cat_stmt = \$pdo->prepare("INSERT INTO Product_Categories (product_id, category_id) VALUES (?, ?)");
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Fixed product_add.php corrupted logic\n";
?>

