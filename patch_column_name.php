<?php
$file_sales = 'user/sales_manage.php';
$content_sales = file_get_contents($file_sales);
$content_sales = str_replace('oi.price *', 'oi.price_at_purchase *', $content_sales);
file_put_contents($file_sales, $content_sales);

$file_invoice = 'user/invoice_history.php';
$content_invoice = file_get_contents($file_invoice);
$content_invoice = str_replace('oi.price', 'oi.price_at_purchase as price', $content_invoice);
file_put_contents($file_invoice, $content_invoice);

echo "Fixed column names in both files.\n";
?>

