<?php
require_once 'includes/db.php';

// Already logged in? Redirect.
if (isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('CSRF Token validation failed.');
    }

    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Please enter a valid email address.';
        header('Location: forgot_password.php');
        exit;
    }

    // Check if email exists in our system
    $stmt = $pdo->prepare("SELECT id, email FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Delete any existing reset codes for this email
        $deleteStmt = $pdo->prepare("DELETE FROM Password_Resets WHERE email = ?");
        $deleteStmt->execute([$email]);

        // Generate a 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save to database (expires in 15 minutes)
        $insertStmt = $pdo->prepare("INSERT INTO Password_Resets (email, reset_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
        $insertStmt->execute([$email, $code]);

        // Send the email
        require_once 'includes/mail_config.php';
        
                

        if (sendResetCode($email, $code)) {
            $_SESSION['reset_email'] = $email;
            $_SESSION['success_message'] = 'A verification code has been sent to your email.';
            header('Location: verify_code.php');
            exit;
        } else {
            $_SESSION['error_message'] = 'Failed to send email. Please try again later or contact the administrator.';
            header('Location: forgot_password.php');
            exit;
        }
    } else {
        // Don't reveal if email exists or not (security best practice)
        // But for this app's UX, we show a generic success to prevent enumeration
        $_SESSION['reset_email'] = $email;
        $_SESSION['success_message'] = 'If this email is registered, a verification code has been sent.';
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
            <div class="mx-auto w-16 h-16 bg-brand-blue/10 dark:bg-brand-blue/20 rounded-full flex items-center justify-center mb-4">
                <ion-icon name="mail-outline" class="text-3xl text-brand-blue"></ion-icon>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Forgot Password?</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Enter your email address and we'll send you a verification code.</p>
        </div>

        <form class="space-y-6" action="forgot_password.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" autocomplete="email" required
                        placeholder="your@email.com"
                        class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-blue hover:bg-brand-blueDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition-colors">
                    <ion-icon name="send-outline" class="mr-2 text-lg"></ion-icon>
                    Send Verification Code
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="<?= BASE_URL ?>/login.php" class="text-sm text-brand-blue hover:text-brand-blueDark font-medium">
                <ion-icon name="arrow-back-outline" class="align-middle mr-1"></ion-icon>
                Back to Sign In
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

