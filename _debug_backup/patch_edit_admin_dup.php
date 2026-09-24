<?php
$file = 'admin/edit_admin.php';
$content = file_get_contents($file);

$dup_search = <<<PHP
                    // Update shop settings
                    \$new_shop_name = trim(\$_POST['shop_name'] ?? '');
                    \$new_shop_contact = trim(\$_POST['shop_contact'] ?? '');
                    \$new_shop_address = trim(\$_POST['shop_address'] ?? '');
                    
                    \$settingsStmt = \$pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    \$settingsStmt->execute(['shop_name_' . \$admin_id, \$new_shop_name, \$new_shop_name]);
                    \$settingsStmt->execute(['shop_contact_' . \$admin_id, \$new_shop_contact, \$new_shop_contact]);
                    \$settingsStmt->execute(['shop_address_' . \$admin_id, \$new_shop_address, \$new_shop_address]);
                    // Update shop settings
                    \$new_shop_name = trim(\$_POST['shop_name'] ?? '');
                    \$new_shop_contact = trim(\$_POST['shop_contact'] ?? '');
                    \$new_shop_address = trim(\$_POST['shop_address'] ?? '');
                    
                    \$settingsStmt = \$pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    \$settingsStmt->execute(['shop_name_' . \$admin_id, \$new_shop_name, \$new_shop_name]);
                    \$settingsStmt->execute(['shop_contact_' . \$admin_id, \$new_shop_contact, \$new_shop_contact]);
                    \$settingsStmt->execute(['shop_address_' . \$admin_id, \$new_shop_address, \$new_shop_address]);
                    
                    \$_SESSION['success_message'] = 'Admin updated successfully.';
                    \$_SESSION['success_message'] = 'Admin updated successfully.';
PHP;

$dup_replace = <<<PHP
                    // Update shop settings
                    \$new_shop_name = trim(\$_POST['shop_name'] ?? '');
                    \$new_shop_contact = trim(\$_POST['shop_contact'] ?? '');
                    \$new_shop_address = trim(\$_POST['shop_address'] ?? '');
                    
                    \$settingsStmt = \$pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    \$settingsStmt->execute(['shop_name_' . \$admin_id, \$new_shop_name, \$new_shop_name]);
                    \$settingsStmt->execute(['shop_contact_' . \$admin_id, \$new_shop_contact, \$new_shop_contact]);
                    \$settingsStmt->execute(['shop_address_' . \$admin_id, \$new_shop_address, \$new_shop_address]);
                    
                    \$_SESSION['success_message'] = 'Shop updated successfully.';
PHP;

$content = str_replace($dup_search, $dup_replace, $content);

$content = str_replace('<h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Admin</h1>', '<h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Shop</h1>', $content);
$content = str_replace('<p class="text-gray-500 dark:text-gray-400 mt-2">Modify details for', '<p class="text-gray-500 dark:text-gray-400 mt-2">Modify details for shop', $content);

file_put_contents($file, $content);
echo "Fixed duplicates in edit_admin.php\n";
?>

