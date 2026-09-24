<?php
require_once 'includes/db.php';

// Must have come from forgot_password.php
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('CSRF Token validation failed.');
    }

    $entered_code = trim($_POST['code'] ?? '');

    if (empty($entered_code) || strlen($entered_code) !== 6) {
        $_SESSION['error_message'] = 'Please enter the 6-digit code.';
        header('Location: verify_code.php');
        exit;
    }

    // Check the code against the database
    $stmt = $pdo->prepare("SELECT * FROM Password_Resets WHERE email = ? AND reset_code = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email, $entered_code]);
    $reset = $stmt->fetch();

    if ($reset) {
        // Code is valid! Generate a secure token for the password reset step
        $_SESSION['reset_verified'] = true;
        $_SESSION['reset_token'] = bin2hex(random_bytes(32));
        $_SESSION['success_message'] = 'Code verified! Please set your new password.';
        header('Location: new_password.php');
        exit;
    } else {
        $_SESSION['error_message'] = 'Invalid or expired code. Please try again.';
        header('Location: verify_code.php');
        exit;
    }
}

$is_space_page = true;
require_once 'includes/header.php';
?>

<div class="flex flex-col items-center justify-center min-h-[70vh] px-4 sm:px-6 lg:px-8">
    <div class="bg-white/90 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200 dark:border-gray-700 p-8 rounded-xl shadow-2xl w-full max-w-md">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-green-500/10 dark:bg-green-500/20 rounded-full flex items-center justify-center mb-4">
                <ion-icon name="shield-checkmark-outline" class="text-3xl text-green-500"></ion-icon>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Verify Code</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Enter the 6-digit code sent to<br>
                <strong class="text-gray-700 dark:text-gray-300"><?= htmlspecialchars($email) ?></strong>
            </p>
        </div>

        <form class="space-y-6" action="verify_code.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Verification Code</label>
                <div class="mt-1">
                    <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}"
                        maxlength="6" required autocomplete="one-time-code"
                        placeholder="000000"
                        class="appearance-none block w-full px-3 py-3 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue text-center text-2xl font-mono tracking-[0.5em] bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Code expires in 15 minutes</p>
            </div>

            <div>
                <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                    <ion-icon name="checkmark-circle-outline" class="mr-2 text-lg"></ion-icon>
                    Verify Code
                </button>
            </div>
        </form>

        <div class="mt-6 flex flex-col items-center gap-3">
            <form action="forgot_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <button type="submit" class="text-sm text-brand-blue hover:text-brand-blueDark font-medium">
                    <ion-icon name="refresh-outline" class="align-middle mr-1"></ion-icon>
                    Resend Code
                </button>
            </form>
            <a href="<?= BASE_URL ?>/forgot_password.php" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                <ion-icon name="arrow-back-outline" class="align-middle mr-1"></ion-icon>
                Use a different email
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

