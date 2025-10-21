<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPDebug = 0; // Disable debug output for now
    $mail->Debugoutput = 'error_log';
    $mail->CharSet = 'UTF-8';

    // Gmail SMTP configuration
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Gmail credentials
    $mail->Username = 'noreplyneoexclusive@gmail.com';
    $mail->Password = 'cgfc ktij ytbo wlgu';

    // Basic SSL configuration
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Additional settings
    $mail->Timeout = 30;
    $mail->AuthType = 'LOGIN'; // Back to LOGIN auth
    
    $mail->isHTML(true);

    // Don't test connection during initialization - let it connect when sending

    return $mail;
} catch (Exception $e) {
    error_log('Mailer initialization error: ' . $e->getMessage());
    throw $e;
}

?>
