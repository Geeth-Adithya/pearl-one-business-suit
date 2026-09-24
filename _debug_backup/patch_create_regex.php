<?php
$file = 'admin/create_admin.php';
$content = file_get_contents($file);

$pattern = '/\$stmt = \$pdo->prepare\("INSERT INTO Users \(username, email, password_hash, role, is_active, subscription_plan, subscription_start_date, subscription_end_date, module_pos, module_stock, module_supply\) VALUES \(\?, \?, \?, \'admin\', 1, \?, \?, \?, \?, \?, \?\)"\);\s*if \(\$stmt->execute\(\[\$username, \$email, \$password_hash, \$subscription_plan, \$start_date, \$end_date, \$module_pos, \$module_stock, \$module_supply\]\)\) \{\s*respond\(true, \'New admin created successfully!\'\);\s*\}/s';

$replace = <<<PHP
    \$full_name = trim(\$_POST['full_name'] ?? '');
    \$stmt = \$pdo->prepare("INSERT INTO Users (username, email, password_hash, role, is_active, subscription_plan, subscription_start_date, subscription_end_date, module_pos, module_stock, module_supply, full_name) VALUES (?, ?, ?, 'admin', 1, ?, ?, ?, ?, ?, ?, ?)");
    if (\$stmt->execute([\$username, \$email, \$password_hash, \$subscription_plan, \$start_date, \$end_date, \$module_pos, \$module_stock, \$module_supply, \$full_name])) {
        \$new_admin_id = \$pdo->lastInsertId();
        
        // Insert shop settings
        \$shop_name = trim(\$_POST['shop_name'] ?? 'My Shop');
        \$shop_contact = trim(\$_POST['shop_contact'] ?? '');
        \$shop_address = trim(\$_POST['shop_address'] ?? '');
        
        \$settingsStmt = \$pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        \$settingsStmt->execute(['shop_name_' . \$new_admin_id, \$shop_name, \$shop_name]);
        \$settingsStmt->execute(['shop_contact_' . \$new_admin_id, \$shop_contact, \$shop_contact]);
        \$settingsStmt->execute(['shop_address_' . \$new_admin_id, \$shop_address, \$shop_address]);

        respond(true, 'New shop created successfully!');
    }
PHP;

$content = preg_replace($pattern, $replace, $content);
file_put_contents($file, $content);
echo "Updated create_admin.php with regex\n";
?>

