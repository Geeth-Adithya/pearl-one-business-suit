<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$search = "if (\$new_variant['item_code'] === '' || \$new_variant['attribute'] === '' || \$new_variant['purchasing_price'] === '' || \$new_variant['margin_percent'] === '' || \$new_variant['selling_price'] === '') {";
$replace = "if (\$new_variant['item_code'] === '' || \$new_variant['attribute'] === '' || \$new_variant['purchasing_price'] === '' || \$new_variant['selling_price'] === '') {";
$content = str_replace($search, $replace, $content);

$search2 = "\$error = 'Please complete the new variant Item Code, Attribute, Purchasing Price, Margin %, and Selling Price.';";
$replace2 = "\$error = 'Please complete the new variant Item Code, Attribute, Purchasing Price, and Selling Price.';";
$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Removed margin_percent requirement from new variant validation.\n";
?>

