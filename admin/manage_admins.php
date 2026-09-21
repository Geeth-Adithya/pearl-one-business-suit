<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
// Authorization check for superadmin
requireRole('superadmin');

// These variables are used for form validation errors that don't cause a redirect.
$error = '';
$success = '';

// Handle Delete Admin
if (isset($_GET['delete'])) {
    $admin_id_to_delete = (int) $_GET['delete'];

    // Prevent superadmin from deleting their own account
    if ($admin_id_to_delete === $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot delete your own account.";
        header('Location: manage_admins.php');
        exit;
    }

    // The ON DELETE SET NULL on the foreign key in the Users table will handle
    // un-assigning any users currently assigned to this admin.
    $stmt = $pdo->prepare("DELETE FROM Users WHERE id = ? AND role = 'admin'");
    if ($stmt->execute([$admin_id_to_delete])) {
        $_SESSION['success_message'] = 'Admin account deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to delete admin account.';
    }
    header('Location: manage_admins.php');
    exit;
}

// Handle Toggle Active State
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $admin_id_to_toggle = (int) $_POST['admin_id'];

    // Prevent superadmin from disabling their own account
    if ($admin_id_to_toggle === $_SESSION['user_id']) {
        $_SESSION['error_message'] = "You cannot change the status of your own account.";
        header('Location: manage_admins.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT is_active FROM Users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$admin_id_to_toggle]);
    $current_status = $stmt->fetchColumn();

    if ($current_status !== false) {
        $new_status = $current_status ? 0 : 1;
        $update_stmt = $pdo->prepare("UPDATE Users SET is_active = ? WHERE id = ?");
        if ($update_stmt->execute([$new_status, $admin_id_to_toggle])) {
            // Also update the status of users assigned to this admin
            $pdo->prepare("UPDATE Users SET is_active = ? WHERE assigned_admin_id = ? AND role = 'user'")->execute([$new_status, $admin_id_to_toggle]);
            $_SESSION['success_message'] = 'Admin account status updated successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to update admin account status.';
        }
    } else {
        $_SESSION['error_message'] = 'Admin not found.';
    }
    header('Location: manage_admins.php');
    exit;
}

// Handle Renew Subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew_subscription'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $admin_id_to_renew = (int) $_POST['admin_id'];

    $stmt = $pdo->prepare("SELECT subscription_plan, subscription_end_date FROM Users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$admin_id_to_renew]);
    $admin_info = $stmt->fetch();

    if ($admin_info) {
        $plan = $admin_info['subscription_plan'] ?: 'Monthly'; // Default to Monthly if null
        $current_end = strtotime($admin_info['subscription_end_date']);
        $base_date = ($current_end && $current_end > time()) ? $current_end : time(); // Extend from current end date or now if expired

        $new_end_date = ($plan === 'Monthly') ? date('Y-m-d H:i:s', strtotime('+1 month', $base_date)) : date('Y-m-d H:i:s', strtotime('+1 year', $base_date));

        $update_stmt = $pdo->prepare("UPDATE Users SET subscription_plan = ?, subscription_end_date = ?, is_active = 1 WHERE id = ?");
        if ($update_stmt->execute([$plan, $new_end_date, $admin_id_to_renew])) {
            // Re-enable assigned users
            $pdo->prepare("UPDATE Users SET is_active = 1 WHERE assigned_admin_id = ? AND role = 'user'")->execute([$admin_id_to_renew]);
            $_SESSION['success_message'] = "Subscription renewed successfully until " . date('M d, Y', strtotime($new_end_date)) . ".";
        } else {
            $_SESSION['error_message'] = 'Failed to renew subscription.';
        }
    } else {
        $_SESSION['error_message'] = 'Admin not found.';
    }
    header('Location: manage_admins.php');
    exit;
}

// Fetch all admins to display
$admins = $pdo->query("SELECT id, username, email, created_at, last_seen, is_active, subscription_plan, subscription_start_date, subscription_end_date, (last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) AS is_online FROM Users WHERE role = 'admin' ORDER BY created_at DESC")->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-8 flex items-center gap-4">
    <a href="index.php" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Back to Dashboard">
        <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
    </a>
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Shops</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-2">Create and manage administrator accounts.</p>
    </div>
</div>

<!-- Create New Shop Form -->
<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Error!</strong>
        <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>
<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow mb-8">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New Shop</h3>
    </div>
    <form id="createAdminPageForm" action="<?= BASE_URL ?>/admin/create_admin.php" method="POST"
        class="p-6 space-y-4 ajax-submit-form"
        data-confirm-message="Are you sure you want to create this new admin account?">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="shop_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Name</label>
                <input type="text" name="shop_name" id="shop_name" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner's Name</label>
                <input type="text" name="full_name" id="full_name" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="shop_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                <input type="text" name="shop_contact" id="shop_contact" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" name="username" id="username" required onkeyup="checkUsername(this.value, 'username_feedback', 'username')"
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <p id="username_feedback" class="text-xs mt-1"></p>
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" id="email" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <input type="password" name="password" id="password" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="subscription_plan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subscription Plan</label>
                <select name="subscription_plan" id="subscription_plan" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="" disabled selected>Select a plan</option>
                    <option value="7 Days">7 Days</option>
                    <option value="Monthly">Monthly</option>
                    <option value="Yearly">Yearly</option>
                </select>
            </div>
            <!-- Module Features -->
            <div class="col-span-1 md:col-span-3 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Enable Features</label>
                <div class="flex flex-wrap gap-6">
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
        </div>
        <div class="flex justify-end mt-4">
            <button type="submit"
                class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2"
                title="Create Shop">
                <ion-icon name="add-outline"></ion-icon> <span class="hidden sm:inline">Create Shop</span>
            </button>
        </div>
    </form>
</div>

<!-- Admins List -->
<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Existing Shops</h3>
    </div>
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
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Created At</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Subscription</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status</th>
                    <th
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No admin
                            accounts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                <div class="flex items-center">
                                    <?php
                                    if ($admin['is_active']) {
                                        $is_online = false;
                                        $status_title = 'Offline';
                                        if ($admin['last_seen']) {
                                            $last_seen_time = strtotime($admin['last_seen']);
                                            if ($admin['is_online']) {
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
                                    <?= htmlspecialchars($admin['username']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($admin['email']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= date('M d, Y', strtotime($admin['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php if ($admin['subscription_plan']): ?>
                                    <div><?= htmlspecialchars($admin['subscription_plan']) ?></div>
                                    <?php
                                    $is_expired = strtotime($admin['subscription_end_date']) < time();
                                    $date_class = $is_expired ? 'text-red-500 font-bold' : 'text-gray-500';
                                    ?>
                                    <div class="text-xs <?= $date_class ?>">
                                        <?= date('M d, Y', strtotime($admin['subscription_end_date'])) ?>
                                        <?= $is_expired ? '(Expired)' : '' ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $admin['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' ?>">
                                    <?= $admin['is_active'] ? 'Active' : 'Disabled' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= BASE_URL ?>/admin/edit_admin.php?id=<?= $admin['id'] ?>"
                                        class="text-brand-blue hover:text-brand-blueDark">Edit</a>
                                    <?php if ($admin['id'] !== $_SESSION['user_id']): ?>
                                        <form action="manage_admins.php" method="POST" class="inline-flex ajax-toggle-form"
                                            data-confirm-title="Confirm Renewal"
                                            data-confirm-message="Are you sure you want to renew this admin's subscription?">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                            <input type="hidden" name="renew_subscription" value="1">
                                            <button type="submit"
                                                class="p-2 rounded-md bg-blue-500 hover:bg-blue-600 text-white mr-1"
                                                title="Renew Subscription">
                                                <ion-icon name="refresh-outline" class="text-xl"></ion-icon>
                                            </button>
                                        </form>
                                        <a href="manage_admins.php?delete=<?= $admin['id'] ?>"
                                            class="text-red-600 hover:text-red-900 confirm-delete-link"
                                            data-confirm-message="Are you sure you want to delete this admin? All users assigned to this admin will become unassigned.">Delete</a>
                                        <form action="manage_admins.php" method="POST" class="inline-flex ajax-toggle-form"
                                            data-confirm-title="Confirm Status Change"
                                            data-confirm-message="Are you sure you want to <?= $admin['is_active'] ? 'disable' : 'enable' ?> this admin account?">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                            <input type="hidden" name="toggle_active" value="1">
                                            <button type="submit"
                                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-blue focus:ring-offset-2 <?= $admin['is_active'] ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' ?>"
                                                title="<?= $admin['is_active'] ? 'Disable admin account' : 'Enable admin account' ?>">
                                                <span class="sr-only">Toggle Active Status</span>
                                                <span aria-hidden="true"
                                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out <?= $admin['is_active'] ? 'translate-x-5' : 'translate-x-0' ?>"></span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

