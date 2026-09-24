<?php
$file = 'admin/edit_admin.php';
$content = file_get_contents($file);

// 1. Fetch settings in the beginning
$fetch_code = <<<PHP
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
\$admin['shop_name'] = \$settings['shop_name_' . \$admin_id] ?? '';
\$admin['shop_contact'] = \$settings['shop_contact_' . \$admin_id] ?? '';
\$admin['shop_address'] = \$settings['shop_address_' . \$admin_id] ?? '';
PHP;

$content = preg_replace('/\/\/ Fetch admin data.*?if \(!\$admin\) \{.*?\}/s', $fetch_code, $content);


// 2. Update backend save logic
// Find the part that sets the params and sql
$save_pattern = '/\$params = \[\$username, \$email, \$subscription_plan, \$module_pos, \$module_stock, \$module_supply\];\s*\$sql = "UPDATE Users SET username = \?, email = \?, subscription_plan = \?, module_pos = \?, module_stock = \?, module_supply = \?";/';
$save_replace = <<<PHP
            \$full_name = trim(\$_POST['full_name'] ?? '');
            \$params = [\$username, \$email, \$subscription_plan, \$module_pos, \$module_stock, \$module_supply, \$full_name];
            \$sql = "UPDATE Users SET username = ?, email = ?, subscription_plan = ?, module_pos = ?, module_stock = ?, module_supply = ?, full_name = ?";
PHP;
$content = preg_replace($save_pattern, $save_replace, $content);

// 3. Update shop settings save
$save_settings_pattern = '/if \(\$stmt->execute\(\$params\)\) \{/';
$save_settings_replace = <<<PHP
if (\$stmt->execute(\$params)) {
                    // Update shop settings
                    \$new_shop_name = trim(\$_POST['shop_name'] ?? '');
                    \$new_shop_contact = trim(\$_POST['shop_contact'] ?? '');
                    \$new_shop_address = trim(\$_POST['shop_address'] ?? '');
                    
                    \$settingsStmt = \$pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    \$settingsStmt->execute(['shop_name_' . \$admin_id, \$new_shop_name, \$new_shop_name]);
                    \$settingsStmt->execute(['shop_contact_' . \$admin_id, \$new_shop_contact, \$new_shop_contact]);
                    \$settingsStmt->execute(['shop_address_' . \$admin_id, \$new_shop_address, \$new_shop_address]);
PHP;
$content = preg_replace($save_settings_pattern, $save_settings_replace, $content);


// 4. Repopulate error logic
$repopulate_pattern = '/\$admin\[\'username\'\] = \$username;\s*\$admin\[\'email\'\] = \$email;/';
$repopulate_replace = <<<PHP
\$admin['username'] = \$username;
    \$admin['email'] = \$email;
    \$admin['full_name'] = \$_POST['full_name'] ?? '';
    \$admin['shop_name'] = \$_POST['shop_name'] ?? '';
    \$admin['shop_contact'] = \$_POST['shop_contact'] ?? '';
    \$admin['shop_address'] = \$_POST['shop_address'] ?? '';
PHP;
$content = preg_replace($repopulate_pattern, $repopulate_replace, $content);


// 5. Add HTML fields
$html_pattern = '/<div>\s*<label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username<\/label>/';
$html_replace = <<<HTML
        <div>
            <label for="shop_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Name</label>
            <input type="text" name="shop_name" id="shop_name" value="<?= htmlspecialchars(\$admin['shop_name']) ?>" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner's Name</label>
            <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars(\$admin['full_name'] ?? '') ?>" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="shop_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
            <input type="text" name="shop_contact" id="shop_contact" value="<?= htmlspecialchars(\$admin['shop_contact']) ?>" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="shop_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Address (Optional)</label>
            <input type="text" name="shop_address" id="shop_address" value="<?= htmlspecialchars(\$admin['shop_address']) ?>"
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
HTML;
$content = preg_replace($html_pattern, $html_replace, $content);

// 6. Title and texts
$content = str_replace('Edit Admin', 'Edit Shop', $content);

file_put_contents($file, $content);
echo "Updated admin/edit_admin.php\n";
?>

