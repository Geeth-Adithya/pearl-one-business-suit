<?php
$file = 'user/checkout.php';
$content = file_get_contents($file);

$search = <<<PHP
            // 3. Create Order Items and Deduct Stock
            \$stmtItem = \$pdo->prepare("INSERT INTO Order_Items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");

            foreach (\$orderItemsData as \$item) {
                \$stmtItem->execute([\$order_id, \$item['product_id'], \$item['quantity'], \$item['price']]);
            }
PHP;

$replace = <<<PHP
            // 3. Create Order Items and Deduct Stock
            \$stmtItem = \$pdo->prepare("INSERT INTO Order_Items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            \$stmtStock = \$pdo->prepare("UPDATE Products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach (\$orderItemsData as \$item) {
                \$stmtItem->execute([\$order_id, \$item['product_id'], \$item['quantity'], \$item['price']]);
                // Update Inventory
                \$stmtStock->execute([\$item['quantity'], \$item['product_id']]);
            }
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Updated checkout.php\n";
?>

