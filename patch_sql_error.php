<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$search = "                    \$product['name'],\n                    \$product['attribute'],";
$replace = "                    \$product['name'],\n                    \$_POST['search_keywords'] ?? '',\n                    \$product['attribute'],";

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Patched SQLSTATE error in product_edit.php\n";
?>

