<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$pattern = '/(\$stmt->execute\(\[\s*\$product\[\'item_code\'\],\s*\$product\[\'name\'\],)\s*(\$product\[\'attribute\'\],)/is';
$replacement = '$1' . "\n                    \$_POST['search_keywords'] ?? '',\n                    " . '$2';

$content = preg_replace($pattern, $replacement, $content);

file_put_contents($file, $content);
echo "Injected search_keywords into execute array via regex.\n";
?>

