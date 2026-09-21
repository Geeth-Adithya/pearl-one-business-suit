<?php
require_once "includes/mail_config.php";

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->SMTPDebug = 2; 
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // Try implicit SSL
    $mail->Port       = 465;

    $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
    $mail->addAddress('geethadithya004@gmail.com');

    $mail->Subject = 'Test Email 465';
    $mail->Body    = 'This is a test';

    $mail->send();
    echo "Message has been sent successfully on 465\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>
