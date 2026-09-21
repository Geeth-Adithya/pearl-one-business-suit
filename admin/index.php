<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Fetch quick stats
if ($_SESSION['role'] === 'admin') {
    $prodStmt = $pdo->prepare("SELECT COUNT(*) FROM Products WHERE created_by_admin_id = ?");
    $prodStmt->execute([$_SESSION['user_id']]);
    $totalProducts = $prodStmt->fetchColumn();
    // Fetch assigned users for the current admin
    $assigned_users_stmt = $pdo->prepare("SELECT id, username, email, last_seen, is_active, (last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) AS is_online FROM Users WHERE assigned_admin_id = ? AND role = 'user' ORDER BY username");
    $assigned_users_stmt->execute([$_SESSION['user_id']]);
        $assigned_users_stmt->execute([$_SESSION['user_id']]);
    $assigned_users = $assigned_users_stmt->fetchAll();

    // Fetch Low Stock Items
    $low_stock_items = [];
    if (!empty($_SESSION['module_stock'])) {
        $lowStockStmt = $pdo->prepare("
            SELECT id, name, attribute, stock_quantity, low_stock_threshold, supplier_name 
            FROM Products 
            WHERE created_by_admin_id = ? 
            AND stock_quantity <= COALESCE(low_stock_threshold, 10)
            ORDER BY stock_quantity ASC
        ");
        $lowStockStmt->execute([$_SESSION['user_id']]);
        $low_stock_items = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch supplier phones if module_supply is active
        if (!empty($_SESSION['module_supply'])) {
            $supStmt = $pdo->prepare("SELECT name, phone FROM Suppliers WHERE admin_id = ?");
            $supStmt->execute([$_SESSION['user_id']]);
            $suppliers = $supStmt->fetchAll(PDO::FETCH_ASSOC);
            $supplierMap = [];
            foreach ($suppliers as $s) {
                $supplierMap[trim(strtolower($s['name']))] = $s['phone'];
            }
        }
    }
} elseif ($_SESSION['role'] === 'superadmin') {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'user'")->fetchColumn();
    $totalAdmins = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'admin'")->fetchColumn();
}
require_once '../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Admin Dashboard</h1>
</div>
<div id="quick-action-message" class="mb-4"></div>

<?php if ($_SESSION['role'] === 'admin'): ?>
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow">
            <div class="flex items-center text-brand-blue dark:text-brand-lighter mb-4">
                <ion-icon name="cube" class="text-3xl"></ion-icon>
            </div>
            <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Total Products</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1"><?= $totalProducts ?></p>
        </div>
        
        <?php if ($_SESSION['module_pos']): ?>
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="bg-gradient-to-r from-[#1E3A8A] to-[#1e3a8a] p-6 rounded-xl card-shadow hover:opacity-90 transition group flex flex-col justify-between">
            <div class="flex items-center text-white mb-4">
                <ion-icon name="calculator" class="text-3xl"></ion-icon>
            </div>
            <div>
                <h3 class="text-white text-sm font-medium uppercase tracking-wider">Open POS System</h3>
                <p class="text-xl font-bold text-white mt-1 group-hover:underline">Start New Sale &rarr;</p>
            </div>
        </a>
        <?php endif; ?>
    </div>
<?php elseif ($_SESSION['role'] === 'superadmin'): ?>
    <!-- Superadmin Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow">
            <div class="flex items-center text-green-600 mb-4">
                <ion-icon name="people" class="text-3xl"></ion-icon>
            </div>
            <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Total Users</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1"><?= $totalUsers ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow">
            <div class="flex items-center text-yellow-500 mb-4">
                <ion-icon name="shield-checkmark" class="text-3xl"></ion-icon>
            </div>
            <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Total Admins</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1"><?= $totalAdmins ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Low Stock Alerts -->
<?php if ($_SESSION['role'] === 'admin' && !empty($_SESSION['module_stock'])): ?>
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <ion-icon name="warning" class="text-red-500"></ion-icon> Low Stock Alerts
    </h2>
    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Threshold</th>
                    <?php if (!empty($_SESSION['module_supply'])): ?>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($low_stock_items)): ?>
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">All products have sufficient stock.</td></tr>
                <?php endif; ?>
                <?php foreach ($low_stock_items as $item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                            <?= htmlspecialchars($item['name'] . ($item['attribute'] ? ' - ' . $item['attribute'] : '')) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600 dark:text-red-400">
                            <?= $item['stock_quantity'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            Below <?= $item['low_stock_threshold'] ?? 10 ?>
                        </td>
                        <?php if (!empty($_SESSION['module_supply'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <?php 
                            $supplier_phone = '';
                            $supplier_name = trim(explode(',', $item['supplier_name'] ?? '')[0]);
                            if ($supplier_name && isset($supplierMap[strtolower($supplier_name)])) {
                                $phone = preg_replace('/[^0-9]/', '', $supplierMap[strtolower($supplier_name)]);
                                if (strpos($phone, '0') === 0 && strlen($phone) === 10) {
                                    $supplier_phone = '94' . substr($phone, 1);
                                } elseif (strlen($phone) > 10) {
                                    $supplier_phone = $phone; // Assuming it already has country code
                                }
                            }
                            if ($supplier_phone):
                                $shop_name = $_SESSION['username'] ?? 'Shop';
                                $msg = "Hello $supplier_name,\n\nWe need a restock of:\n- " . $item['name'] . ($item['attribute'] ? ' - ' . $item['attribute'] : '') . "\n\nPlease arrange delivery.\n\nThank you,\n$shop_name";
                                $wa_link = "https://wa.me/{$supplier_phone}?text=" . urlencode($msg);
                            ?>
                                <a href="<?= $wa_link ?>" target="_blank" class="inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-xs font-bold transition">
                                    <ion-icon name="logo-whatsapp"></ion-icon> Request Restock
                                </a>
                            <?php else: ?>
                                <span class="text-xs text-gray-400 italic">No phone/supplier</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <!-- Manage Products -->
            <?php if ($_SESSION['module_stock']): ?>
            <a href="<?= BASE_URL ?>/admin/products.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-brand-blue dark:text-brand-lighter mb-3">
                    <ion-icon name="pricetags-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manage Products</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add, edit, and delete products.</p>
            </a>
            <?php endif; ?>

            <!-- Shop Settings -->
            <a href="<?= BASE_URL ?>/admin/shop_settings.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-pink-500 mb-3">
                    <ion-icon name="settings-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Shop Settings</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update shop name, contact, and address.</p>
            </a>

            <!-- Manage Categories -->
            <?php if ($_SESSION['module_stock']): ?>
            <a href="<?= BASE_URL ?>/admin/manage_categories.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-teal-500 mb-3">
                    <ion-icon name="bookmarks-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manage Categories</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add, edit, and delete categories.</p>
            </a>
            <?php endif; ?>

            <!-- Manage Suppliers -->
            <?php if ($_SESSION['module_supply']): ?>
            <a href="<?= BASE_URL ?>/admin/manage_suppliers.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-orange-500 mb-3">
                    <ion-icon name="business-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manage Suppliers</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add, edit, and delete suppliers.</p>
            </a>
            <?php endif; ?>

            <!-- Manage Sales & Orders -->
            <?php if ($_SESSION['module_pos']): ?>
            <a href="<?= BASE_URL ?>/admin/orders.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-indigo-500 mb-3">
                    <ion-icon name="cart-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manage Sales</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View shop sales and track orders.</p>
            </a>
            <?php endif; ?>

            <!-- Manage Shop Users -->
            <a href="<?= BASE_URL ?>/admin/manage_users.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-purple-500 mb-3">
                    <ion-icon name="people-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manage Shop Users</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create up to 2 users for your shop.</p>
            </a>

            <!-- Google Sheets Sync -->
            <?php if ($_SESSION['module_stock']): ?>
            <a href="<?= BASE_URL ?>/admin/google_sync.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-green-500 mb-3">
                    <ion-icon name="sync-circle-outline" class="text-4xl group-hover:animate-spin"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Google Sheets</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sync product data with Sheets.</p>
            </a>
            <?php endif; ?>

            <!-- Add User quick action removed -->
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'superadmin'): ?>

            <!-- Add Admin -->
            <button id="addAdminBtn"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-blue-500 mb-3">
                    <ion-icon name="person-add-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Add Shop</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Quickly create a new shop shop.</p>
            </button>

            <!-- Add User (superadmin only) -->
            <button id="addUserBtnSA"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-indigo-500 mb-3">
                    <ion-icon name="person-add-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Add User</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Quickly create a new user shop.</p>
            </button>

            <!-- Add User quick action removed for superadmin -->

            <!-- Manage Shops -->
            <a href="<?= BASE_URL ?>/admin/manage_admins.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-yellow-500 mb-3">
                    <ion-icon name="shield-checkmark-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manage Shops</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create and manage admin shops.</p>
            </a>

            <!-- Manage Users -->
            <a href="<?= BASE_URL ?>/admin/manage_users.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-green-600 mb-3">
                    <ion-icon name="people-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Manage Users</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create, edit, and assign users.</p>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow w-full max-w-md max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-shrink-0">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New Shop</h3>
            <button id="closeAddAdminModal"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <form id="quickCreateAdminForm" action="<?= BASE_URL ?>/admin/create_admin.php" method="POST"
            class="p-6 space-y-4 ajax-submit-form overflow-y-auto">

            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                        <div>
                <label for="shop_name_quick" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Name</label>
                <input type="text" name="shop_name" id="shop_name_quick" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="full_name_quick" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner's Name</label>
                <input type="text" name="full_name" id="full_name_quick" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="shop_contact_quick" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                <input type="text" name="shop_contact" id="shop_contact_quick" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" name="username" id="username" required onkeyup="checkUsername(this.value, 'quick_username_feedback', 'username')"
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <p id="quick_username_feedback" class="text-xs mt-1"></p>
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" id="email" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="password"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <input type="password" name="password" id="password" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm
                    Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="subscription_plan"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subscription Plan</label>
                <select name="subscription_plan" id="subscription_plan" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="" disabled selected>Select a plan</option>
                    <option value="7 Days">7 Days</option>
                      <option value="Monthly">Monthly</option>
                    <option value="Yearly">Yearly</option>
                </select>
            </div>
            <!-- Module Features -->
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Enable Features</label>
                <div class="grid grid-cols-1 gap-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative flex-shrink-0">
                            <input type="checkbox" name="module_pos" value="1" checked class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-300 dark:bg-gray-600 peer-checked:bg-brand-blue rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">POS (Point of Sale)</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Billing and checkout system</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative flex-shrink-0">
                            <input type="checkbox" name="module_stock" value="1" checked class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-300 dark:bg-gray-600 peer-checked:bg-brand-blue rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Stock Balance</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Track product stock quantities</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative flex-shrink-0">
                            <input type="checkbox" name="module_supply" value="1" checked class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-300 dark:bg-gray-600 peer-checked:bg-brand-blue rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Supply Management</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Manage suppliers and supply orders</p>
                        </div>
                    </label>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                    class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition">
                    Create Shop
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add User Modal (superadmin only) -->
<div id="addUserModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow w-full max-w-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New User</h3>
            <button id="closeAddUserModal"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <form id="quickCreateUserForm" action="<?= BASE_URL ?>/admin/create_user.php" method="POST"
            class="p-6 space-y-4 ajax-submit-form">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="user_username"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                    <input type="text" name="username" id="user_username" required
                        class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label for="user_email"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email" id="user_email" required
                        class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <?php if ($_SESSION['role'] === 'superadmin'): ?>
                    <div>
                        <label for="assigned_admin_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign to Admin</label>
                        <select name="assigned_admin_id" id="assigned_admin_id"
                            class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="0">-- Unassigned --</option>
                            <?php
                            $admins = $pdo->query("SELECT id, username FROM Users WHERE role = 'admin'")->fetchAll();
                            foreach ($admins as $admin): ?>
                                <option value="<?= $admin['id'] ?>"><?= htmlspecialchars($admin['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: // Regular admin creating a user, so auto-assign to them ?>
                    <input type="hidden" name="assigned_admin_id" value="<?= $_SESSION['user_id'] ?>">
                <?php endif; ?>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                    class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition">Create
                    User</button>
            </div>
        </form>
    </div>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
    <!-- Assigned Users List -->
    <div class="mt-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Your Assigned Users</h2>
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Username</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Email</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($low_stock_items)): ?>
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">All products have sufficient stock.</td></tr>
                <?php endif; ?>
                        <?php if (empty($assigned_users)): ?>
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">You have
                                    no users assigned to you.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assigned_users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center">
                                            <?php
                                            if ($user['is_active']) {
                                                $is_online = false;
                                                $status_title = 'Offline';
                                                if ($user['last_seen']) {
                                                    $last_seen_time = strtotime($user['last_seen']);
                                                    if ($user['is_online']) {
                                                        $is_online = true;
                                                        $status_title = 'Online';
                                                    } else {
                                                        $status_title = 'Last seen: ' . date('M d, Y g:i A', $last_seen_time);
                                                    }
                                                }
                                                $status_color_class = $is_online ? 'bg-green-500' : 'bg-gray-400';
                                            } else {
                                                $status_title = 'Disabled';
                                                $status_color_class = 'bg-red-500';
                                            }
                                            ?>
                                            <span class="h-2 w-2 rounded-full mr-2 <?= $status_color_class ?>"
                                                title="<?= htmlspecialchars($status_title) ?>"></span>
                                            <?= htmlspecialchars($user['username']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

