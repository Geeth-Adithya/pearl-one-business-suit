<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

// 1. Fix the main UPDATE array
$pattern1 = '/\$stmt->execute\(\[\s*\$product\[\'item_code\'\],\s*\$product\[\'name\'\],\s*\$product\[\'attribute\'\],\s*\$product\[\'type\'\],\s*\$product\[\'purchasing_price\'\],\s*\$product\[\'margin_percent\'\],\s*\$product\[\'selling_price\'\],\s*\$product\[\'unit\'\],\s*\$product\[\'description\'\],\s*\$product\[\'supplier_name\'\],\s*\$image_url,\s*\$video_url,\s*\$product_id\s*\]\);/s';

$replace1 = <<<PHP
\$stmt->execute([
                  \$product['item_code'],
                  \$product['name'],
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
                  \$product_id
              ]);
PHP;

$content = preg_replace($pattern1, $replace1, $content);

// 2. Fix the "Add Variant" INSERT query in product_edit.php (it also missed stock_quantity)
$pattern2 = '/INSERT INTO Products \(item_code, name, attribute, type, purchasing_price, margin_percent, selling_price, unit, description, supplier_name, image_url, video_url, created_by_admin_id\) VALUES \(\?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?\)/s';

$replace2 = 'INSERT INTO Products (item_code, name, attribute, type, purchasing_price, margin_percent, selling_price, unit, description, supplier_name, stock_quantity, image_url, video_url, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

$content = preg_replace($pattern2, $replace2, $content);

// 3. Fix the "Add Variant" array
$pattern3 = '/\$newStmt->execute\(\[\s*\$new_variant\[\'item_code\'\], \$product\[\'name\'\], \$new_variant\[\'attribute\'\], \$product\[\'type\'\],\s*\$new_variant\[\'purchasing_price\'\], \$new_variant\[\'margin_percent\'\], \$new_variant\[\'selling_price\'\],\s*\$product\[\'unit\'\], \$product\[\'description\'\], \$product\[\'supplier_name\'\], \$image_url, \$video_url,\s*\$_SESSION\[\'user_id\'\]\s*\]\);/s';

$replace3 = <<<PHP
\$newStmt->execute([
                      \$new_variant['item_code'], \$product['name'], \$new_variant['attribute'], \$product['type'],
                      \$new_variant['purchasing_price'], \$new_variant['margin_percent'], \$new_variant['selling_price'],
                      \$product['unit'], \$product['description'], \$product['supplier_name'], \$_POST['stock_quantity'] ?? 0, \$image_url, \$video_url,
                      \$_SESSION['user_id']
                  ]);
PHP;

$content = preg_replace($pattern3, $replace3, $content);

file_put_contents($file, $content);
echo "Fixed product_edit.php logic using regex\n";
?>

