<?php
$file = 'admin/manage_admins.php';
$content = file_get_contents($file);

$insert_html = <<<HTML
            <div>
                <label for="shop_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Name</label>
                <input type="text" name="shop_name" id="shop_name" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="shop_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Contact</label>
                <input type="text" name="shop_contact" id="shop_contact" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="shop_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Address (Optional)</label>
                <input type="text" name="shop_address" id="shop_address"
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
HTML;

$search = "<div>\n                <label for=\"password\"";
$replace = $insert_html . "\n            " . $search;
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Updated manage_admins.php HTML\n";

$file = 'admin/create_admin.php';
$content = file_get_contents($file);
$search = <<<PHP
    \$stmt = \$pdo->prepare("INSERT INTO Users (username, email, password_hash, role, is_active, subscription_plan, subscription_start_date, subscription_end_date, module_pos, module_stock, module_supply) VALUES (?, ?, ?, 'admin', 1, ?, ?, ?, ?, ?, ?)");
    if (\$stmt->execute([\$username, \$email, \$password_hash, \$subscription_plan, \$start_date, \$end_date, \$module_pos, \$module_stock, \$module_supply])) {
        respond(true, 'New admin created successfully!');
    }
PHP;

$replace = <<<PHP
    \$stmt = \$pdo->prepare("INSERT INTO Users (username, email, password_hash, role, is_active, subscription_plan, subscription_start_date, subscription_end_date, module_pos, module_stock, module_supply) VALUES (?, ?, ?, 'admin', 1, ?, ?, ?, ?, ?, ?)");
    if (\$stmt->execute([\$username, \$email, \$password_hash, \$subscription_plan, \$start_date, \$end_date, \$module_pos, \$module_stock, \$module_supply])) {
        \$new_admin_id = \$pdo->lastInsertId();
        
        // Insert shop settings
        \$shop_name = trim(\$_POST['shop_name'] ?? 'My Shop');
        \$shop_contact = trim(\$_POST['shop_contact'] ?? '');
        \$shop_address = trim(\$_POST['shop_address'] ?? '');
        
        \$settingsStmt = \$pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        \$settingsStmt->execute(['shop_name_' . \$new_admin_id, \$shop_name, \$shop_name]);
        \$settingsStmt->execute(['shop_contact_' . \$new_admin_id, \$shop_contact, \$shop_contact]);
        \$settingsStmt->execute(['shop_address_' . \$new_admin_id, \$shop_address, \$shop_address]);

        respond(true, 'New admin created successfully!');
    }
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Updated create_admin.php backend\n";

?>

