<?php
$content = file_get_contents("admin/product_edit.php");

$insert = <<<PHP
    if (empty(\$error)) {
        try {
            \$pdo->beginTransaction();

            \$sql = "UPDATE Products SET 
                        item_code = ?, name = ?, search_keywords = ?, attribute = ?, type = ?, purchasing_price = ?,
                        margin_percent = ?, selling_price = ?, unit = ?, 
                        description = ?, supplier_name = ?, image_url = ?, video_url = ?, stock_quantity = ?, low_stock_threshold = ?
                    WHERE id = ?";

            \$stmt = \$pdo->prepare(\$sql);
            \$stmt->execute([
                \$product['item_code'],
                \$product['name'],
                \$_POST['search_keywords'] ?? '',
                \$product['attribute'],
                \$product['type'],
                \$product['purchasing_price'],
                \$product['margin_percent'],
                \$product['selling_price'],
                \$product['unit'],
                \$product['description'],
                \$product['supplier_name'],
                \$image_url,
                \$video_url,
                \$_POST['stock_quantity'] ?? 0,
                \$_POST['low_stock_threshold'] ?? 10,
                \$product_id
            ]);

            \$del_stmt = \$pdo->prepare("DELETE FROM Product_Categories WHERE product_id = ?");
            \$del_stmt->execute([\$product_id]);

            if (!empty(\$category_ids)) {
                \$cat_stmt = \$pdo->prepare("INSERT INTO Product_Categories (product_id, category_id) VALUES (?, ?)");
                foreach (\$category_ids as \$cat_id) {
                    \$cat_stmt->execute([\$product_id, \$cat_id]);
                }
            }

            if (\$add_variant) {
                \$newStmt = \$pdo->prepare("INSERT INTO Products (item_code, name, search_keywords, attribute, type, purchasing_price, margin_percent, selling_price, unit, description, supplier_name, stock_quantity, low_stock_threshold, image_url, video_url, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                \$newStmt->execute([
                    \$new_variant['item_code'], \$product['name'], \$_POST['search_keywords'] ?? '', \$new_variant['attribute'], \$product['type'],
                    \$new_variant['purchasing_price'], \$new_variant['margin_percent'], \$new_variant['selling_price'],
                    \$product['unit'], \$product['description'], \$product['supplier_name'], \$_POST['stock_quantity'] ?? 0, \$_POST['low_stock_threshold'] ?? 10, \$image_url, \$video_url,
                    \$_SESSION['user_id']
                ]);
                \$new_product_id = \$pdo->lastInsertId();
                if (!empty(\$category_ids)) {
                    foreach (\$category_ids as \$cat_id) {
                        \$cat_stmt->execute([\$new_product_id, \$cat_id]);
                    }
                }
            }
PHP;

$pattern = '/if \(\$new_variant\[\'item_code\'\] === \'\' \|\|(.*?)\}\s*\}\s*\}\s*\$pdo->commit\(\);/is';
$replacement = "if (\$new_variant['item_code'] === '' || \$new_variant['attribute'] === '' || \$new_variant['purchasing_price'] === '' || \$new_variant['selling_price'] === '') {
                \$error = 'Please complete the new variant Item Code, Attribute, Purchasing Price, and Selling Price.';
            }
        }
    }
" . $insert . "

            \$pdo->commit();";

$content = preg_replace($pattern, $replacement, $content);
file_put_contents("admin/product_edit.php", $content);
echo "Fixed product_edit.php";
?>
