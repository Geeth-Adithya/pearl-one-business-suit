<?php
$files = ['user/invoice_history.php', 'user/sales_manage.php'];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = str_replace('FROM OrderItems', 'FROM Order_Items', $content);
    file_put_contents($file, $content);
    echo "Fixed table name in $file\n";
}
?>

