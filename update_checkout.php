<?php
$content = file_get_contents('user/checkout.php');

// Replace the commented validation
$pattern1 = '/\/\/\s*Note: If you want strict stock validation, uncomment this:\s*\/\/\s*if \(\\[\'stock_quantity\'\] < \\) \{\s*\/\/\s*throw new Exception\("Not enough stock for " \. \\[\'name\'\] \. "\. Available: " \. \\[\'stock_quantity\'\]\);\s*\/\/\s*\}/is';
$replacement1 = 'if (!empty([\'module_stock\']) && [\'stock_quantity\'] < ) {
                throw new Exception("Not enough stock for " . [\'name\'] . ". Available: " . [\'stock_quantity\']);
            }';

$content = preg_replace($pattern1, $replacement1, $content);

// Replace the stock deduction loop
$pattern2 = '/\ = \->prepare\("UPDATE Products SET stock_quantity = stock_quantity - \? WHERE id = \?"\);\s*foreach \(\ as \\) \{\s*\->execute\(\[\, \\[\'product_id\'\], \\[\'quantity\'\], \\[\'price\'\]\]\);\s*\/\/\s*Deduct inventory\s*\->execute\(\[\\[\'quantity\'\], \\[\'product_id\'\]\]\);\s*\}/is';
$replacement2 = '\ = \->prepare("INSERT INTO Order_Items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
        \ = \->prepare("UPDATE Products SET stock_quantity = stock_quantity - ? WHERE id = ?");

        foreach (\ as \) {
            \->execute([\, \[\'product_id\'], \[\'quantity\'], \[\'price\']]);
            // Deduct inventory only if module is enabled
            if (!empty(\[\'module_stock\'])) {
                \->execute([\[\'quantity\'], \[\'product_id\']]);
            }
        }';

$content = preg_replace($pattern2, $replacement2, $content);
file_put_contents('user/checkout.php', $content);
echo "checkout.php updated!";
?>
