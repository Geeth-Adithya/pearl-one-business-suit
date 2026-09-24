<?php
$file = 'admin/edit_admin.php';
$content = file_get_contents($file);

// 1. Fetch settings in the beginning
$fetch_code = <<<PHP
// Fetch the admin's data
\$stmt = \$pdo->prepare("SELECT * FROM Users WHERE id = ? AND role = 'admin'");
\$stmt->execute([\$admin_id]);
\$admin = \$stmt->fetch();

if (!\$admin) {
    \$_SESSION['error_message'] = "Admin not found.";
    header('Location: manage_admins.php');
    exit;
}

// Fetch shop settings
\$stmtSettings = \$pdo->prepare("SELECT setting_key, setting_value FROM Settings WHERE setting_key IN (?, ?, ?)");
\$stmtSettings->execute(['shop_name_' . \$admin_id, 'shop_contact_' . \$admin_id, 'shop_address_' . \$admin_id]);
\$settings = [];
while (\$row = \$stmtSettings->fetch()) {
    \$settings[\$row['setting_key']] = \$row['setting_value'];
}
\$admin['shop_name'] = \$settings['shop_name_' . \$admin_id] ?? '';
\$admin['shop_contact'] = \$settings['shop_contact_' . \$admin_id] ?? '';
\$admin['shop_address'] = \$settings['shop_address_' . \$admin_id] ?? '';
PHP;

$content = preg_replace('/\/\/ Fetch the admin\'s data.*?if \(!\$admin\) \{.*?\}/s', $fetch_code, $content);

file_put_contents($file, $content);
echo "Patched fetch\n";
?>

