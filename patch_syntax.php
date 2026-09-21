<?php
$file = 'admin/manage_suppliers.php';
$content = file_get_contents($file);

$content = str_replace('if (!\$supplier) {', 'if (!$supplier) {', $content);

file_put_contents($file, $content);
echo "Fixed syntax error on line 75.\n";
?>

