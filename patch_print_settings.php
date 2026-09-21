<?php
$file = 'user/print_bill.php';
$content = file_get_contents($file);

$search = <<<PHP
\$stmtSettings = \$pdo->query("SELECT setting_key, setting_value FROM settings");
\$settings = [];
if (\$stmtSettings) {
    while (\$row = \$stmtSettings->fetch()) {
        \$settings[\$row['setting_key']] = \$row['setting_value'];
    }
}
\$shop_name = \$settings['shop_name'] ?? 'My Store';
\$shop_address = \$settings['shop_address'] ?? '123 Main Street, City';
\$shop_phone = \$settings['shop_contact'] ?? '+1 234 567 8900';
PHP;

$replace = <<<PHP
\$admin_id_to_use = \$_SESSION['assigned_admin_id'] ?? \$_SESSION['user_id'];
\$stmtSettings = \$pdo->query("SELECT setting_key, setting_value FROM settings");
\$settings = [];
if (\$stmtSettings) {
    while (\$row = \$stmtSettings->fetch()) {
        \$settings[\$row['setting_key']] = \$row['setting_value'];
    }
}
\$shop_name = \$settings['shop_name_' . \$admin_id_to_use] ?? (\$settings['shop_name'] ?? 'My Store');
\$shop_address = \$settings['shop_address_' . \$admin_id_to_use] ?? (\$settings['shop_address'] ?? '123 Main Street, City');
\$shop_phone = \$settings['shop_contact_' . \$admin_id_to_use] ?? (\$settings['shop_contact'] ?? '+1 234 567 8900');
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Updated print_bill.php to use admin specific settings\n";
?>

