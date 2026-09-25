<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
// Authorization check for superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($user_id === 0) {
    header('Location: manage_users.php');
    exit;
}

// Fetch the user's data
$stmt = $pdo->prepare("SELECT id, username, full_name, email, assigned_admin_id FROM Users WHERE id = ? AND role = 'user'");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header('Location: manage_users.php');
    exit;
}

// Fetch all admins for the dropdown
$admins = $pdo->query("SELECT id, username FROM Users WHERE role = 'admin'")->fetchAll();

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    if ($_SESSION['role'] === 'admin') {
        $assigned_admin_id = $_SESSION['user_id'];
    } else {
        $assigned_admin_id = isset($_POST['assigned_admin_id']) && $_POST['assigned_admin_id'] == '0' ? null : (int) $_POST['assigned_admin_id'];
    }

    if (empty($username) || empty($email)) {
        $error = 'Username and Email are required.';
    } else {
        // Check if email or username is taken by ANOTHER user
        $stmt = $pdo->prepare("SELECT id FROM Users WHERE (email = ? OR username = ?) AND id != ?");
        $stmt->execute([$email, $username, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Email or Username is already in use by another account.';
        } else {
            $params = [$full_name, $username, $email, $assigned_admin_id];
            $sql = "UPDATE Users SET full_name = ?, username = ?, email = ?, assigned_admin_id = ?";

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
                $params[] = $user_id;

                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    $_SESSION['success_message'] = 'User updated successfully.';
                    header('Location: manage_users.php');
                    exit;
                } else {
                    $error = 'An error occurred while updating the user.';
                }
            }
        }
    }
    // If there was an error, repopulate the user object with the submitted data
    $user['username'] = $username;
    $user['full_name'] = $full_name;
    $user['email'] = $email;
    $user['assigned_admin_id'] = $assigned_admin_id;
}

require_once '../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit User</h1>
    <p class="text-gray-500 dark:text-gray-400 mt-2">Modify details for <?= htmlspecialchars($user['username']) ?>.</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow">
    <form action="edit_user.php?id=<?= $user_id ?>" method="POST" class="p-6 space-y-6 confirm-submit-form"
        data-confirm-title="Confirm Save" data-confirm-message="Are you sure you want to save changes for this user?">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="full_name"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
            </div>
            <div>
                <label for="username"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>"
                    required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required
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
                <label for="assigned_admin_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign
                    to Admin</label>
                <select name="assigned_admin_id" id="assigned_admin_id"
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="0">-- Unassigned --</option>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?= $admin['id'] ?>" <?= $user['assigned_admin_id'] == $admin['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($admin['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex justify-between items-center pt-4">
            <a href="manage_users.php"
                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Cancel</a>
            <button type="submit" title="Save Changes"
                class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2">
                <ion-icon name="save-outline"></ion-icon> <span class="hidden sm:inline">Save Changes</span>
            </button>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>