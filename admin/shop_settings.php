<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$admin_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = trim($_POST['shop_name'] ?? '');
    $shop_contact = trim($_POST['shop_contact'] ?? '');
    $shop_address = trim($_POST['shop_address'] ?? '');
    $shop_currency = trim($_POST['shop_currency'] ?? 'Rs');

    if (empty($shop_name) || empty($shop_contact)) {
        $error = 'Shop Name and Contact are required.';
    } else {
        $settingsStmt = $pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        $settingsStmt->execute(['shop_name_' . $admin_id, $shop_name, $shop_name]);
        $settingsStmt->execute(['shop_contact_' . $admin_id, $shop_contact, $shop_contact]);
        $settingsStmt->execute(['shop_address_' . $admin_id, $shop_address, $shop_address]);
        $settingsStmt->execute(['shop_currency_' . $admin_id, $shop_currency, $shop_currency]);
        
        $success = 'Shop settings updated successfully.';
    }
}

// Fetch current settings
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
if ($stmtSettings) {
    while ($row = $stmtSettings->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
$shop_name = $settings['shop_name_' . $admin_id] ?? ($settings['shop_name'] ?? 'My Shop');
$shop_address = $settings['shop_address_' . $admin_id] ?? ($settings['shop_address'] ?? '');
$shop_contact = $settings['shop_contact_' . $admin_id] ?? ($settings['shop_contact'] ?? '');
$shop_currency = $settings['shop_currency_' . $admin_id] ?? ($settings['shop_currency'] ?? 'Rs');

$page_title = "Shop Settings";
require_once '../includes/header.php';
?>

<div class="max-w-3xl mx-auto py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Shop Settings</h1>
    </div>
</div>

    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
            <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-md">
            <p class="text-green-700"><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
        <form action="" method="POST" class="p-6">
            <div class="space-y-6">
                <div>
                    <label for="shop_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Name</label>
                    <input type="text" name="shop_name" id="shop_name" required value="<?= htmlspecialchars($shop_name) ?>"
                        class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label for="shop_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Contact Number</label>
                    <input type="text" name="shop_contact" id="shop_contact" required value="<?= htmlspecialchars($shop_contact) ?>"
                        class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                <div>
                    <label for="shop_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Currency</label>
                    <select name="shop_currency" id="shop_currency" class="mt-1 block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="Rs" <?= $shop_currency === 'Rs' ? 'selected' : '' ?>>Sri Lankan Rupee (Rs)</option>
                        <option value="$" <?= $shop_currency === '$' ? 'selected' : '' ?>>US Dollar ($)</option>
                        <option value="€" <?= $shop_currency === '€' ? 'selected' : '' ?>>Euro (€)</option>
                        <option value="£" <?= $shop_currency === '£' ? 'selected' : '' ?>>British Pound (£)</option>
                        <option value="₹" <?= $shop_currency === '₹' ? 'selected' : '' ?>>Indian Rupee (₹)</option>
                        <option value="৳" <?= $shop_currency === '৳' ? 'selected' : '' ?>>Bangladeshi Taka (৳)</option>
                        <option value="د.إ" <?= $shop_currency === 'د.إ' ? 'selected' : '' ?>>UAE Dirham (د.إ)</option>
                        <option value="A$" <?= $shop_currency === 'A$' ? 'selected' : '' ?>>Australian Dollar (A$)</option>
                        <option value="C$" <?= $shop_currency === 'C$' ? 'selected' : '' ?>>Canadian Dollar (C$)</option>
                        <option value="ر.س" <?= $shop_currency === 'ر.س' ? 'selected' : '' ?>>Saudi Riyal (ر.س)</option>
                    </select>
                </div>

                <div>
                    <label for="shop_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Address (Optional)</label>
                    <textarea name="shop_address" id="shop_address" rows="3"
                        class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"><?= htmlspecialchars($shop_address) ?></textarea>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-brand-blue hover:bg-brand-blueDark text-white px-6 py-2 rounded-md shadow-sm transition">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

