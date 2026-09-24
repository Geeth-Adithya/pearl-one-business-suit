<?php
$file = 'admin/shop_settings.php';
$content = file_get_contents($file);

// 1. Backend processing
$search1 = "\$shop_address = trim(\$_POST['shop_address'] ?? '');";
$replace1 = <<<PHP
\$shop_address = trim(\$_POST['shop_address'] ?? '');
    \$shop_currency = trim(\$_POST['shop_currency'] ?? 'Rs');
PHP;
$content = str_replace($search1, $replace1, $content);

$search2 = "\$settingsStmt->execute(['shop_address_' . \$admin_id, \$shop_address, \$shop_address]);";
$replace2 = <<<PHP
\$settingsStmt->execute(['shop_address_' . \$admin_id, \$shop_address, \$shop_address]);
        \$settingsStmt->execute(['shop_currency_' . \$admin_id, \$shop_currency, \$shop_currency]);
PHP;
$content = str_replace($search2, $replace2, $content);

// 2. Fetching current settings
$search3 = "\$shop_contact = \$settings['shop_contact_' . \$admin_id] ?? (\$settings['shop_contact'] ?? '');";
$replace3 = <<<PHP
\$shop_contact = \$settings['shop_contact_' . \$admin_id] ?? (\$settings['shop_contact'] ?? '');
\$shop_currency = \$settings['shop_currency_' . \$admin_id] ?? (\$settings['shop_currency'] ?? 'Rs');
PHP;
$content = str_replace($search3, $replace3, $content);

// 3. UI Form
$search4 = <<<HTML
                <div>
                    <label for="shop_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Address (Optional)</label>
HTML;

$replace4 = <<<HTML
                <div>
                    <label for="shop_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Currency</label>
                    <select name="shop_currency" id="shop_currency" class="mt-1 block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="Rs" <?= \$shop_currency === 'Rs' ? 'selected' : '' ?>>Sri Lankan Rupee (Rs)</option>
                        <option value="$" <?= \$shop_currency === '$' ? 'selected' : '' ?>>US Dollar ($)</option>
                        <option value="€" <?= \$shop_currency === '€' ? 'selected' : '' ?>>Euro (€)</option>
                        <option value="£" <?= \$shop_currency === '£' ? 'selected' : '' ?>>British Pound (£)</option>
                        <option value="₹" <?= \$shop_currency === '₹' ? 'selected' : '' ?>>Indian Rupee (₹)</option>
                        <option value="৳" <?= \$shop_currency === '৳' ? 'selected' : '' ?>>Bangladeshi Taka (৳)</option>
                        <option value="د.إ" <?= \$shop_currency === 'د.إ' ? 'selected' : '' ?>>UAE Dirham (د.إ)</option>
                        <option value="A$" <?= \$shop_currency === 'A$' ? 'selected' : '' ?>>Australian Dollar (A$)</option>
                        <option value="C$" <?= \$shop_currency === 'C$' ? 'selected' : '' ?>>Canadian Dollar (C$)</option>
                        <option value="ر.س" <?= \$shop_currency === 'ر.س' ? 'selected' : '' ?>>Saudi Riyal (ر.س)</option>
                    </select>
                </div>

                <div>
                    <label for="shop_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Address (Optional)</label>
HTML;
$content = str_replace($search4, $replace4, $content);

file_put_contents($file, $content);
echo "Added shop_currency to shop_settings.php\n";
?>

