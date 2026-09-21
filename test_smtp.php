<?php
require_once "includes/mail_config.php";

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
    $mail->addAddress('geethadithya004@gmail.com'); // Send to self for test

    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test';

    $mail->send();
    echo "Message has been sent successfully\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>
