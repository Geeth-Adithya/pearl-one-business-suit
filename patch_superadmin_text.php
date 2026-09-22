<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

// 1. Rename Total Admins to Total Shops
$content = str_replace('Total Admins', 'Total Shops', $content);

// 2. Fix descriptions
$content = str_replace('Quickly create a new shop shop.', 'Quickly create a new shop.', $content);
$content = str_replace('Quickly create a new user shop.', 'Quickly create a new user.', $content);

file_put_contents($file, $content);
echo "Superadmin text updated!";
?>
