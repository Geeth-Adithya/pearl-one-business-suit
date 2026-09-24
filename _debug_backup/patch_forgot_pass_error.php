<?php
$file = 'forgot_password.php';
$content = file_get_contents($file);

$oldCode = '} else {
        // Don\'t reveal if email exists or not (security best practice)
        // But for this app\'s UX, we show a generic success to prevent enumeration
        $_SESSION[\'reset_email\'] = $email;
        $_SESSION[\'success_message\'] = \'If this email is registered, a verification code has been sent.\';
        header(\'Location: verify_code.php\');
        exit;
    }';

$newCode = '} else {
        // As requested by user, show error if email doesn\'t exist
        $_SESSION[\'error_message\'] = \'This email is not registered in the system. Please check the email address.\';
        header(\'Location: forgot_password.php\');
        exit;
    }';

$content = str_replace($oldCode, $newCode, $content);

// In case whitespace doesn't match perfectly, use a regex fallback
if (strpos($content, 'This email is not registered') === false) {
    $content = preg_replace('/\} else \{\s*\/\/ Don\'t reveal.*?\$_SESSION\[\'success_message\'\]\s*=\s*\'If this email is registered.*?;.*?header\(\'Location: verify_code\.php\'\);\s*exit;\s*\}/is', $newCode, $content);
}

file_put_contents($file, $content);
echo "Forgot password logic updated!";
?>
