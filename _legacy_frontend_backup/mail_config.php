<?php
// includes/mail_config.php
// Gmail SMTP Configuration for sending password reset emails
// IMPORTANT: Do NOT commit this file to Git. Add it to .gitignore.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// --- SMTP Settings ---
// Replace these with your actual Gmail credentials.
// To get a Gmail App Password:
// 1. Go to Google Account → Security → 2-Step Verification (turn ON)
// 2. Go to Security → App Passwords → Select "Mail" → Generate
// 3. Copy the 16-character password and paste below.

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'pearlwaves2004@gmail.com');     
define('SMTP_PASSWORD', 'clpdltswiwyfwiao');    
define('SMTP_FROM_NAME', 'Pearl Store');              

/**
 * Send a password reset code via email.
 *
 * @param string $toEmail Recipient email address
 * @param string $code    The 6-digit reset code
 * @return bool True on success, false on failure
 */
function sendResetCode(string $toEmail, string $code): bool
{
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Sender & Recipient
        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($toEmail);

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code - Pearl Store';
        $mail->Body    = '
        <div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto; padding: 30px; background: #f8fafc; border-radius: 12px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="color: #1e293b; margin: 0;">Password Reset</h2>
                <p style="color: #64748b; margin-top: 8px;">Pearl Store</p>
            </div>
            <div style="background: white; padding: 24px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                <p style="color: #475569; margin-bottom: 16px;">Your password reset verification code is:</p>
                <div style="background: #1e293b; color: #ffffff; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 16px 24px; border-radius: 8px; display: inline-block;">
                    ' . htmlspecialchars($code) . '
                </div>
                <p style="color: #94a3b8; font-size: 13px; margin-top: 16px;">This code will expire in <strong>15 minutes</strong>.</p>
                <p style="color: #94a3b8; font-size: 13px;">If you did not request this, please ignore this email.</p>
            </div>
            <p style="color: #94a3b8; font-size: 11px; text-align: center; margin-top: 20px;">
                &copy; ' . date('Y') . ' Pearl Store. All rights reserved.
            </p>
        </div>';
        $mail->AltBody = "Your password reset code is: $code. This code will expire in 15 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>

