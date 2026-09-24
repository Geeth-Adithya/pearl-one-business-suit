<?php
$file = 'forgot_password.php';
$content = file_get_contents($file);

$replace = <<<'EOD'
        // Send the email
        require_once 'includes/mail_config.php';
        
        // --- LOCALHOST DEV WORKAROUND ---
        if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
            $_SESSION['reset_email'] = $email;
            $_SESSION['success_message'] = 'LOCAL DEV MODE: Your verification code is: ' . $code;
            header('Location: verify_code.php');
            exit;
        }
        // --------------------------------

        if (sendResetCode($email, $code)) {
EOD;

$content = str_replace("        // Send the email\n        require_once 'includes/mail_config.php';\n        if (sendResetCode(\$email, \$code)) {", $replace, $content);
file_put_contents($file, $content);
echo "Added localhost bypass to forgot_password.php";
?>
