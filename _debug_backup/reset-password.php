<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = 'Please fill in all fields.';
        header('Location: reset-password.php');
        exit;
    } elseif (strlen($new_password) < 4) {
        $_SESSION['error_message'] = 'New password must be at least 4 characters long.';
        header('Location: reset-password.php');
        exit;
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = 'New passwords do not match.';
        header('Location: reset-password.php');
        exit;
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM Users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password_hash'])) {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $update_stmt = $pdo->prepare("UPDATE Users SET password_hash = ? WHERE id = ?");
            if ($update_stmt->execute([$hash, $user_id])) {
                $_SESSION['success_message'] = 'Password updated successfully.';
                header('Location: reset-password.php');
                exit;
            } else {
                $_SESSION['error_message'] = 'An error occurred while updating your password.';
                header('Location: reset-password.php');
                exit;
            }
        } else {
            $_SESSION['error_message'] = 'Incorrect current password.';
            header('Location: reset-password.php');
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl card-shadow w-full max-w-md">
        <h2 class="text-center text-3xl font-extrabold text-gray-900 dark:text-white mb-6">Change Password</h2>

        <form class="space-y-6" action="reset-password.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current
                    Password</label>
                <input id="current_password" name="current_password" type="password" required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New
                    Password</label>
                <input id="new_password" name="new_password" type="password" required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm
                    New Password</label>
                <input id="confirm_password" name="confirm_password" type="password" required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-blue hover:bg-brand-blueDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition-colors">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>