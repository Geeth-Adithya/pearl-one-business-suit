<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
// Authorization check for superadmin
requireRole('superadmin');

$admin_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($admin_id === 0) {
    header('Location: manage_admins.php');
    exit;
}

// Fetch the admin's data
$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ? AND role = 'admin'");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if (!$admin) {
    $_SESSION['error_message'] = "Admin not found.";
    header('Location: manage_admins.php');
    exit;
}

// Fetch shop settings
$stmtSettings = $pdo->prepare("SELECT setting_key, setting_value FROM Settings WHERE setting_key IN (?, ?, ?)");
$stmtSettings->execute(['shop_name_' . $admin_id, 'shop_contact_' . $admin_id, 'shop_address_' . $admin_id]);
$settings = [];
while ($row = $stmtSettings->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$admin['shop_name'] = $settings['shop_name_' . $admin_id] ?? '';
$admin['shop_contact'] = $settings['shop_contact_' . $admin_id] ?? '';
$admin['shop_address'] = $settings['shop_address_' . $admin_id] ?? '';

// These variables are used for form validation errors that don't cause a redirect.
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    if (isset($_POST['unassign_user']) && isset($_POST['user_id'])) {
        $user_id = (int) $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE Users SET assigned_admin_id = NULL WHERE id = ? AND assigned_admin_id = ? AND role = 'user'");
        if ($stmt->execute([$user_id, $admin_id])) {
            $_SESSION['success_message'] = 'User removed from this admin successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to remove the user from this admin.';
        }
        header("Location: edit_admin.php?id={$admin_id}");
        exit;
    }

    if (isset($_POST['toggle_user_active']) && isset($_POST['user_id'])) {
        $user_id = (int) $_POST['user_id'];
        $stmt = $pdo->prepare("SELECT is_active FROM Users WHERE id = ? AND assigned_admin_id = ? AND role = 'user'");
        $stmt->execute([$user_id, $admin_id]);
        $userStatus = $stmt->fetch();
        if ($userStatus) {
            $newStatus = $userStatus['is_active'] ? 0 : 1;
            $updateStmt = $pdo->prepare("UPDATE Users SET is_active = ? WHERE id = ?");
            if ($updateStmt->execute([$newStatus, $user_id])) {
                $_SESSION['success_message'] = $newStatus ? 'User access enabled successfully.' : 'User access disabled successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to update user access status.';
            }
        } else {
            $_SESSION['error_message'] = 'User not found or not assigned to this admin.';
        }
        header("Location: edit_admin.php?id={$admin_id}");
        exit;
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $subscription_plan = $_POST['subscription_plan'] ?? null;

    if (empty($username) || empty($email)) {
        $error = 'Username and Email are required.';
    } elseif (!in_array($subscription_plan, ['7 Days', 'Monthly', 'Yearly'])) {
        $error = 'Please select a valid subscription plan.';
    } else {
        // Check if email or username is taken by ANOTHER user
        $stmt = $pdo->prepare("SELECT id FROM Users WHERE (email = ? OR username = ?) AND id != ?");
        $stmt->execute([$email, $username, $admin_id]);
        if ($stmt->fetch()) {
            $error = 'Email or Username is already in use by another account.';
        } else {
            $module_pos = isset($_POST['module_pos']) ? 1 : 0;
            $module_stock = isset($_POST['module_stock']) ? 1 : 0;
            $module_supply = isset($_POST['module_supply']) ? 1 : 0;
            
                        $full_name = trim($_POST['full_name'] ?? '');
            $params = [$username, $email, $subscription_plan, $module_pos, $module_stock, $module_supply, $full_name];
            $sql = "UPDATE Users SET username = ?, email = ?, subscription_plan = ?, module_pos = ?, module_stock = ?, module_supply = ?, full_name = ?";

            if (!empty($password)) {
                if (strlen($password) < 4) {
                    $error = 'Password must be at least 4 characters long.';
                } else {
                    $sql .= ", password_hash = ?";
                    $params[] = password_hash($password, PASSWORD_BCRYPT);
                }
            }

                        if (empty($error)) {
                $sql .= " WHERE id = ?";
                $params[] = $admin_id;

                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    // Update shop settings
                    $new_shop_name = trim($_POST['shop_name'] ?? '');
                    $new_shop_contact = trim($_POST['shop_contact'] ?? '');
                    $new_shop_address = trim($_POST['shop_address'] ?? '');
                    
                    $settingsStmt = $pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $settingsStmt->execute(['shop_name_' . $admin_id, $new_shop_name, $new_shop_name]);
                    $settingsStmt->execute(['shop_contact_' . $admin_id, $new_shop_contact, $new_shop_contact]);
                    $settingsStmt->execute(['shop_address_' . $admin_id, $new_shop_address, $new_shop_address]);
                    // Update shop settings
                    $new_shop_name = trim($_POST['shop_name'] ?? '');
                    $new_shop_contact = trim($_POST['shop_contact'] ?? '');
                    $new_shop_address = trim($_POST['shop_address'] ?? '');
                    
                    $settingsStmt = $pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $settingsStmt->execute(['shop_name_' . $admin_id, $new_shop_name, $new_shop_name]);
                    $settingsStmt->execute(['shop_contact_' . $admin_id, $new_shop_contact, $new_shop_contact]);
                    $settingsStmt->execute(['shop_address_' . $admin_id, $new_shop_address, $new_shop_address]);
                    
                    $_SESSION['success_message'] = 'Admin updated successfully.';
                    $_SESSION['success_message'] = 'Admin updated successfully.';
                    header('Location: manage_admins.php');
                    exit;
                } else {
                    $error = 'An error occurred while updating the admin.';
                }
            }
        }
    }
    // If there was an error, repopulate the admin object with the submitted data
    $admin['username'] = $username;
    $admin['email'] = $email;
    $admin['full_name'] = $_POST['full_name'] ?? '';
    $admin['shop_name'] = $_POST['shop_name'] ?? '';
    $admin['shop_contact'] = $_POST['shop_contact'] ?? '';
    $admin['shop_address'] = $_POST['shop_address'] ?? '';
}

// Fetch assigned users for this admin
$assigned_users_stmt = $pdo->prepare("SELECT id, username, email, last_seen, is_active FROM Users WHERE assigned_admin_id = ? AND role = 'user' ORDER BY username");
$assigned_users_stmt->execute([$admin_id]);
$assigned_users = $assigned_users_stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-8 flex items-center gap-4">
    <a href="manage_admins.php" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Back to Shops">
        <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
    </a>
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Shop</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Modify details for shop
            <?= htmlspecialchars($admin['username']) ?>.
        </p>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow">
    <form action="edit_admin.php?id=<?= $admin_id ?>" method="POST" class="p-6 space-y-6 confirm-submit-form"
        data-confirm-title="Confirm Save" data-confirm-message="Are you sure you want to save changes for this admin?">
        <!-- CSRF Protection -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div>
            <label for="shop_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Name</label>
            <input type="text" name="shop_name" id="shop_name" value="<?= htmlspecialchars($admin['shop_name']) ?>" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner's Name</label>
            <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="shop_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
            <input type="text" name="shop_contact" id="shop_contact" value="<?= htmlspecialchars($admin['shop_contact']) ?>" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="shop_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Address (Optional)</label>
            <input type="text" name="shop_address" id="shop_address" value="<?= htmlspecialchars($admin['shop_address']) ?>"
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($admin['username']) ?>"
                required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($admin['email']) ?>" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New
                Password</label>
            <input type="password" name="password" id="password"
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Leave blank to keep the current password.</p>
        </div>
        <div>
            <label for="subscription_plan"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subscription Plan</label>
            <select name="subscription_plan" id="subscription_plan" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="7 Days" <?= $admin['subscription_plan'] === '7 Days' ? 'selected' : '' ?>>7 Days</option>
                <option value="Monthly" <?= $admin['subscription_plan'] === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="Yearly" <?= $admin['subscription_plan'] === 'Yearly' ? 'selected' : '' ?>>Yearly</option>
            </select>
        </div>

        <!-- Module Feature Toggles -->
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Enable Features</label>
            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative flex-shrink-0">
                        <input type="checkbox" name="module_pos" value="1" <?= $admin['module_pos'] ? 'checked' : '' ?> class="sr-only peer">
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
                        <input type="checkbox" name="module_stock" value="1" <?= $admin['module_stock'] ? 'checked' : '' ?> class="sr-only peer">
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
                        <input type="checkbox" name="module_supply" value="1" <?= $admin['module_supply'] ? 'checked' : '' ?> class="sr-only peer">
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

        <div class="flex justify-between items-center pt-4">
            <a href="manage_admins.php"
                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Cancel</a>
            <button type="submit" title="Save Changes"
                class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2">
                <ion-icon name="save-outline"></ion-icon> <span class="hidden sm:inline">Save Changes</span>
            </button>
        </div>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden mt-8">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Assigned Users</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Edit, remove, or disable access for users assigned to
            this admin.</p>
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
                        Status</th>
                    <th
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($assigned_users)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No users are
                            assigned to this admin.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assigned_users as $user): ?>
                        <tr class="<?= $user['is_active'] ? '' : 'opacity-70' ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($user['username']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($user['email']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $user['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Disabled' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= BASE_URL ?>/admin/edit_user.php?id=<?= $user['id'] ?>"
                                        class="text-brand-blue hover:text-brand-blueDark">Edit</a>
                                    <form action="edit_admin.php?id=<?= $admin_id ?>" method="POST"
                                        class="inline-flex confirm-submit-form"
                                        data-confirm-message="Are you sure you want to remove this user from the admin?">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="unassign_user"
                                            class="p-2 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600"
                                            title="Remove user from admin">
                                            <ion-icon name="trash-bin-outline" class="text-base"></ion-icon></button>
                                    </form>
                                    <form action="edit_admin.php?id=<?= $admin_id ?>" method="POST"
                                        class="inline-flex confirm-submit-form" data-confirm-title="Confirm Status Change"
                                        data-confirm-message="Are you sure you want to <?= $user['is_active'] ? 'disable' : 'enable' ?> this user access?">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="toggle_user_active" value="1">
                                        <button type="submit"
                                            class="p-2 rounded-md <?= $user['is_active'] ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white' ?>"
                                            title="<?= $user['is_active'] ? 'Disable user access' : 'Enable user access' ?>">
                                            <ion-icon name="<?= $user['is_active'] ? 'eye-off-outline' : 'eye-outline' ?>"
                                                class="text-base"></ion-icon>
                                        </button>
                                    </form>
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