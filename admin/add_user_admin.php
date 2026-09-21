<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('superadmin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill all fields.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $error = 'Email or Username is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                if (!$hash) {
                    $error = 'An error occurred during password hashing.';
                } else {
                    $sql = "INSERT INTO Users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute([$username, $email, $hash, $role])) {
                        $success = 'User/Admin created successfully.';
                    } else {
                        $error = 'An error occurred during user/admin creation.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Add User or Admin</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Create a new user or administrator account.</p>
        </div>
    </div>
</div>

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
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">New User/Admin Details</h3>
    </div>
    <form action="add_user_admin.php" method="POST" class="p-6 space-y-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" name="username" id="username" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
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
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                <select name="role" id="role" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit"
                class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2">
                <ion-icon name="add-outline"></ion-icon> Create
            </button>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
