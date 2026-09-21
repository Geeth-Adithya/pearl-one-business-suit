<?php
require_once 'includes/db.php';

// Must have verified the code first
if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['reset_email']) || !isset($_SESSION['reset_token'])) {
    header('Location: forgot_password.php');
    exit;
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('CSRF Token validation failed.');
    }

    // Verify token matches
    if (!isset($_POST['reset_token']) || $_POST['reset_token'] !== $_SESSION['reset_token']) {
        $_SESSION['error_message'] = 'Invalid reset session. Please start over.';
        unset($_SESSION['reset_email'], $_SESSION['reset_verified'], $_SESSION['reset_token']);
        header('Location: forgot_password.php');
        exit;
    }

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = 'Please fill in all fields.';
        header('Location: new_password.php');
        exit;
    }

    if (strlen($new_password) < 4) {
        $_SESSION['error_message'] = 'Password must be at least 4 characters long.';
        header('Location: new_password.php');
        exit;
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = 'Passwords do not match.';
        header('Location: new_password.php');
        exit;
    }

    // Update the password
    $hash = password_hash($new_password, PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare("UPDATE Users SET password_hash = ? WHERE email = ?");

    if ($updateStmt->execute([$hash, $email])) {
        // Delete all reset codes for this email
        $deleteStmt = $pdo->prepare("DELETE FROM Password_Resets WHERE email = ?");
        $deleteStmt->execute([$email]);

        // Also clear the force_password_change flag if it exists
        $clearForceStmt = $pdo->prepare("UPDATE Users SET force_password_change = 0 WHERE email = ?");
        $clearForceStmt->execute([$email]);

        // Clean up session
        unset($_SESSION['reset_email'], $_SESSION['reset_verified'], $_SESSION['reset_token']);

        $_SESSION['success_message'] = 'Your password has been reset successfully! Please sign in with your new password.';
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error_message'] = 'An error occurred. Please try again.';
        header('Location: new_password.php');
        exit;
    }
}

$is_space_page = true;
require_once 'includes/header.php';
?>

<div class="flex flex-col items-center justify-center min-h-[70vh] px-4 sm:px-6 lg:px-8">
    <div class="bg-white/90 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200 dark:border-gray-700 p-8 rounded-xl shadow-2xl w-full max-w-md">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-brand-blue/10 dark:bg-brand-blue/20 rounded-full flex items-center justify-center mb-4">
                <ion-icon name="lock-open-outline" class="text-3xl text-brand-blue"></ion-icon>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Set New Password</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Create a new password for<br>
                <strong class="text-gray-700 dark:text-gray-300"><?= htmlspecialchars($email) ?></strong>
            </p>
        </div>

        <form class="space-y-6" action="new_password.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="reset_token" value="<?= htmlspecialchars($_SESSION['reset_token'] ?? '') ?>">

            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
                <div class="mt-1">
                    <input id="new_password" name="new_password" type="password" required minlength="4"
                        placeholder="Enter new password"
                        class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm New Password</label>
                <div class="mt-1">
                    <input id="confirm_password" name="confirm_password" type="password" required minlength="4"
                        placeholder="Confirm new password"
                        class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-blue hover:bg-brand-blueDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition-colors">
                    <ion-icon name="key-outline" class="mr-2 text-lg"></ion-icon>
                    Reset Password
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="<?= BASE_URL ?>/login.php" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                <ion-icon name="arrow-back-outline" class="align-middle mr-1"></ion-icon>
                Back to Sign In
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

