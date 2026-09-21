<?php
$file = 'admin/edit_admin.php';
$content = file_get_contents($file);

// 1. Fetch current settings for the form
$fetch_settings = <<<PHP
// Fetch admin data
\$stmt = \$pdo->prepare("SELECT * FROM Users WHERE id = ? AND role = 'admin'");
\$stmt->execute([\$admin_id]);
\$admin = \$stmt->fetch();

if (!\$admin) {
    \$_SESSION['error_message'] = 'Admin not found.';
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
\$shop_name = \$settings['shop_name_' . \$admin_id] ?? '';
\$shop_contact = \$settings['shop_contact_' . \$admin_id] ?? '';
\$shop_address = \$settings['shop_address_' . \$admin_id] ?? '';
PHP;

$content = preg_replace('/\/\/ Fetch admin data.*?(?=if \(\$_SERVER\[\'REQUEST_METHOD\'\] === \'POST\'\))/s', $fetch_settings . "\n\n", $content);

// 2. Add backend update logic
$backend_update = <<<PHP
            if (empty(\$error)) {
                \$sql .= " WHERE id = ?";
                \$params[] = \$admin_id;

                \$stmt = \$pdo->prepare(\$sql);
                if (\$stmt->execute(\$params)) {
                    // Update shop settings
                    \$new_shop_name = trim(\$_POST['shop_name'] ?? '');
                    \$new_shop_contact = trim(\$_POST['shop_contact'] ?? '');
                    \$new_shop_address = trim(\$_POST['shop_address'] ?? '');
                    
                    \$settingsStmt = \$pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    \$settingsStmt->execute(['shop_name_' . \$admin_id, \$new_shop_name, \$new_shop_name]);
                    \$settingsStmt->execute(['shop_contact_' . \$admin_id, \$new_shop_contact, \$new_shop_contact]);
                    \$settingsStmt->execute(['shop_address_' . \$admin_id, \$new_shop_address, \$new_shop_address]);
                    
                    \$_SESSION['success_message'] = 'Admin updated successfully.';
PHP;

$content = preg_replace('/if \(empty\(\$error\)\) \{\s*\$sql \.= " WHERE id = \?";\s*\$params\[\] = \$admin_id;\s*\$stmt = \$pdo->prepare\(\$sql\);\s*if \(\$stmt->execute\(\$params\)\) \{/s', $backend_update, $content);

// 3. Add HTML inputs
$html_inputs = <<<HTML
            <div>
                <label for="shop_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Name</label>
                <input type="text" name="shop_name" id="shop_name" value="<?= htmlspecialchars(\$shop_name) ?>" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="shop_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Contact</label>
                <input type="text" name="shop_contact" id="shop_contact" value="<?= htmlspecialchars(\$shop_contact) ?>" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="shop_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Address (Optional)</label>
                <input type="text" name="shop_address" id="shop_address" value="<?= htmlspecialchars(\$shop_address) ?>"
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            
            <div class="col-span-1 md:col-span-2">
HTML;

$content = str_replace('<div class="col-span-1 md:col-span-2">', $html_inputs, $content);

file_put_contents($file, $content);
echo "Updated edit_admin.php\n";
?>

