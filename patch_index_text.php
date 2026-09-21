<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

$content = str_replace('<h3 class="text-lg font-bold text-gray-900 dark:text-white">Add Admin</h3>', '<h3 class="text-lg font-bold text-gray-900 dark:text-white">Add Shop</h3>', $content);
$content = str_replace('<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Quickly create a new admin account.</p>', '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Quickly create a new shop account.</p>', $content);
$content = str_replace('<h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New Admin</h3>', '<h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New Shop</h3>', $content);
$content = str_replace('Create Admin', 'Create Shop', $content);
$content = str_replace('Manage Admins', 'Manage Shops', $content);
$content = str_replace('accounts.', 'shops.', $content);
$content = str_replace('account.', 'shop.', $content);

file_put_contents($file, $content);
echo "Updated index.php text\n";
?>

