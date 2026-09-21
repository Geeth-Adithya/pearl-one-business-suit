<?php
require_once "includes/mail_config.php";

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->SMTPDebug = 2; 
    $mail->isSMTP();
    $mail->Host       = gethostbyname(SMTP_HOST); // Force IPv4 resolution
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Also bind to IPv4 specifically if needed, but gethostbyname usually suffices.
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
    $mail->addAddress('geethadithya004@gmail.com');

    $mail->Subject = 'Test Email IPv4';
    $mail->Body    = 'This is a test';

    $mail->send();
    echo "Message has been sent successfully using IPv4\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>
