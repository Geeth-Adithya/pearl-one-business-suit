<?php
$files = [
    'user/checkout.php',
    'user/dashboard.php',
    'user/invoice_history.php',
    'user/print_bill.php',
    'user/sales_manage.php',
    'admin/orders.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // 1. Replace 'Rs. ' (inside javascript string literals)
        $content = str_replace("'Rs. '", "'<?= htmlspecialchars(\$shop_currency) ?> '", $content);
        
        // 2. Replace Rs.  (normal text)
        $content = str_replace("Rs. ", "<?= htmlspecialchars(\$shop_currency) ?> ", $content);
        
        // 3. Replace (Rs.)
        $content = str_replace("(Rs.)", "(<?= htmlspecialchars(\$shop_currency) ?>)", $content);

        file_put_contents($file, $content);
    }
}
echo "Currency replaced in all views.\n";
?>

