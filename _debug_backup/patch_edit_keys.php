<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$search1 = "\$product['margin_percent'] = \$_POST['margin_percent'];";
$replace1 = "\$product['margin_percent'] = \$_POST['margin_percent'] ?? 0;";
$content = str_replace($search1, $replace1, $content);

$search2 = "\$product['purchasing_price'] = \$_POST['purchasing_price'];";
$replace2 = "\$product['purchasing_price'] = \$_POST['purchasing_price'] ?? 0;";
$content = str_replace($search2, $replace2, $content);

$search3 = "\$product['selling_price'] = \$_POST['selling_price'];";
$replace3 = "\$product['selling_price'] = \$_POST['selling_price'] ?? 0;";
$content = str_replace($search3, $replace3, $content);

$search4 = "\$product['unit'] = trim(\$_POST['unit']);";
$replace4 = "\$product['unit'] = trim(\$_POST['unit'] ?? '');";
$content = str_replace($search4, $replace4, $content);

file_put_contents($file, $content);
echo "Patched undefined array keys in product_edit.php\n";
?>

