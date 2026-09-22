<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$error = '';
$success = '';
// Handle Delete User
if (isset($_GET['delete'])) {
    $user_id_to_delete = (int) $_GET['delete'];

    // Deleting a user will also delete their orders due to ON DELETE CASCADE in the database schema.
    $stmt = $pdo->prepare("DELETE FROM Users WHERE id = ? AND role = 'user'");
    if ($stmt->execute([$user_id_to_delete])) {
        $_SESSION['success_message'] = 'User account deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to delete user account.';
    }
    header('Location: manage_users.php');
    exit;
}

// Handle Toggle Active State
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $user_id_to_toggle = (int) $_POST['user_id'];

    // First, get the current status
    $stmt = $pdo->prepare("SELECT is_active FROM Users WHERE id = ? AND role = 'user'");
    $stmt->execute([$user_id_to_toggle]);
    $current_status = $stmt->fetchColumn();

    if ($current_status !== false) {
        $new_status = $current_status ? 0 : 1;
        $update_stmt = $pdo->prepare("UPDATE Users SET is_active = ? WHERE id = ?");
        if ($update_stmt->execute([$new_status, $user_id_to_toggle])) {
            $_SESSION['success_message'] = 'User account status updated successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to update user account status.';
        }
    } else {
        $_SESSION['error_message'] = 'User not found.';
    }
    header('Location: manage_users.php');
    exit;
}


// Fetch users based on role
if ($_SESSION['role'] === 'admin') {
    $users_query = "
        SELECT u.id, u.username, u.email, u.created_at, u.assigned_admin_id, u.last_seen, u.is_active, (u.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) AS is_online, a.username as admin_username
        FROM Users u
        LEFT JOIN Users a ON u.assigned_admin_id = a.id
        WHERE u.role = 'user' AND u.assigned_admin_id = ?
        ORDER BY u.created_at DESC
    ";
    $stmt = $pdo->prepare($users_query);
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll();
} else {
    $users_query = "
        SELECT u.id, u.username, u.email, u.created_at, u.assigned_admin_id, u.last_seen, u.is_active, (u.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) AS is_online, a.username as admin_username
        FROM Users u
        LEFT JOIN Users a ON u.assigned_admin_id = a.id
        WHERE u.role = 'user'
        ORDER BY u.created_at DESC
    ";
    $users = $pdo->query($users_query)->fetchAll();
}

// Fetch all admins for the dropdown
$admins = $pdo->query("SELECT id, username FROM Users WHERE role = 'admin'")->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Users</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Create new users and assign them to admins.</p>
        </div>
    </div>
</div>

<!-- Create New User Form -->
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
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New User</h3>
    </div>
    <form id="createUserPageForm" action="<?= BASE_URL ?>/admin/create_user.php" method="POST"
        class="p-6 space-y-4 ajax-submit-form"
        data-confirm-message="Are you sure you want to create this new user account?">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label for="user_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="text" name="username" id="user_username" required
                        class="flex-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-none rounded-l-md focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    <button type="button" onclick="checkUsername()" class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-black dark:border-gray-600 bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-500 text-sm font-medium transition">
                        Check
                    </button>
                </div>
            </div>
            <div>
                <label for="user_full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="full_name" id="user_full_name" required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="">
            </div>
            <div>
                <label for="user_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" id="user_email" required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="user_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <input type="password" name="password" id="user_password" required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="user_confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                <input type="password" name="confirm_password" id="user_confirm_password" required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" title="Create User"
                class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2">
                <ion-icon name="add-outline"></ion-icon> <span class="hidden sm:inline">Create User</span></button>
        </div>
    </form>
</div>

<script>
function checkUsername() {
    const input = document.getElementById('user_username');
    let val = input.value.replace(/[^a-zA-Z0-9]/g, '');
    if (!val) return;
    
    // If it already ends with exactly 4 digits, remove them first
    if (/\d{4}$/.test(val)) {
        val = val.replace(/\d{4}$/, '');
    }
    
    // Generate and add a new set of 4 unique numbers
    val = val + Math.floor(1000 + Math.random() * 9000);
    
    input.value = val;
}
</script>

<!-- Users List -->
<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">All Users</h3>
    </div>
    <div class="overflow-x-auto w-full">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Username</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Email</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Assigned Admin</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status</th>
                    <th
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No user accounts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
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
                                    <span class="h-2 w-2 rounded-full mr-2 <?= $status_color_class ?>" title="<?= htmlspecialchars($status_title) ?>"></span>
                                    <?= htmlspecialchars($user['username']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars($user['email']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php if ($user['admin_username']): ?>
                                    <?= htmlspecialchars($user['admin_username']) ?>
                                <?php else: ?>
                                    <span class="italic">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $user['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Disabled' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= BASE_URL ?>/admin/edit_user.php?id=<?= $user['id'] ?>" class="text-brand-blue hover:text-brand-blueDark">Edit</a>
                                    <a href="manage_users.php?delete=<?= $user['id'] ?>" class="text-red-600 hover:text-red-900 confirm-delete-link" data-confirm-message="Are you sure you want to delete this user? This will also delete all their associated orders.">Delete</a>
                                    <form action="manage_users.php" method="POST" class="inline-flex ajax-toggle-form"
                                        data-confirm-title="Confirm Status Change"
                                        data-confirm-message="Are you sure you want to <?= $user['is_active'] ? 'disable' : 'enable' ?> this user account?">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="toggle_active" value="1">
                                        <button type="submit"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-blue focus:ring-offset-2 <?= $user['is_active'] ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' ?>"
                                            title="<?= $user['is_active'] ? 'Disable user account' : 'Enable user account' ?>">
                                            <span class="sr-only">Toggle Active Status</span>
                                            <span aria-hidden="true"
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out <?= $user['is_active'] ? 'translate-x-5' : 'translate-x-0' ?>"></span>
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