<?php
require_once "includes/mail_config.php";

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->SMTPDebug = 3; 
    $mail->Debugoutput = function($str, $level) {
        file_put_contents('smtp_debug.log', $str . "\n", FILE_APPEND);
    };
    $mail->Timeout = 15;
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
    $mail->addAddress('geethadithya004@gmail.com');

    $mail->Subject = 'Test Email XAMPP';
    $mail->Body    = 'This is a test';

    $mail->send();
    file_put_contents('smtp_debug.log', "SUCCESS\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents('smtp_debug.log', "ERROR: " . $mail->ErrorInfo . "\n", FILE_APPEND);
}
?>
