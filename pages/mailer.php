<?php
/**
 * mailer.php
 * Sends the 6-digit reset code by email.
 *
 * This default implementation uses PHP's built-in mail() function,
 * which works out of the box on most shared hosting but is often
 * unreliable / gets marked as spam. For production use, swap this
 * out for PHPMailer + a real SMTP account (Gmail, SendGrid, Mailgun,
 * etc.) — see the commented example at the bottom of this file.
 */

function sendResetCodeEmail(string $toEmail, string $toName, string $code): bool
{
    $fromEmail = 'no-reply@yourdomain.com';
    $fromName  = 'Your App';

    $subject = 'Your password reset code';

    $safeName = $toName !== '' ? htmlspecialchars($toName) : 'there';

    $htmlBody = "
        <div style='font-family:Arial,sans-serif;max-width:480px;margin:auto'>
            <h2>Password reset requested</h2>
            <p>Hi {$safeName},</p>
            <p>Use the code below to reset your password. It expires in 10 minutes.</p>
            <p style='font-size:32px;font-weight:bold;letter-spacing:8px;
                      background:#f3f4f6;padding:16px;text-align:center;
                      border-radius:8px;'>{$code}</p>
            <p>If you didn't request this, you can safely ignore this email.</p>
        </div>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";

    // mail() returns true if the message was *accepted for delivery*,
    // not that it was actually delivered — always log failures.
    $sent = mail($toEmail, $subject, $htmlBody, $headers);

    if (!$sent) {
        error_log("Failed to send reset code email to {$toEmail}");
    }

    return $sent;
}

/*
-----------------------------------------------------------------
Recommended production version using PHPMailer (composer require
phpmailer/phpmailer):

require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

function sendResetCodeEmail(string $toEmail, string $toName, string $code): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.yourprovider.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_smtp_username';
        $mail->Password   = 'your_smtp_password';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('no-reply@yourdomain.com', 'Your App');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = 'Your password reset code';
        $mail->Body    = "Your code is: <b>{$code}</b> (expires in 10 minutes)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        return false;
    }
}
-----------------------------------------------------------------
*/