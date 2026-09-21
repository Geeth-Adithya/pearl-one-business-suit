<?php
$file = 'forgot_password.php';
$content = file_get_contents($file);

$replace = <<<'EOD'
        // --- LOCALHOST DEV WORKAROUND (FORCE ENABLED) ---
        $_SESSION['reset_email'] = $email;
        $_SESSION['success_message'] = 'LOCAL DEV MODE: Your verification code is: ' . $code;
        header('Location: verify_code.php');
        exit;
        // --------------------------------
EOD;

$content = preg_replace(
    '/(\/\/ --- LOCALHOST DEV WORKAROUND ---.*?)(?=if \(sendResetCode)/s',
    $replace . "\n\n        ",
    $content
);

file_put_contents($file, $content);
echo "Force enabled bypass";
?>
