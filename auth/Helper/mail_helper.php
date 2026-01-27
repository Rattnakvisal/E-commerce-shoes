<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

function send_mail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $cfg = require __DIR__ . '/../../config/mail.php';
    $mail = new PHPMailer(true);

    try {
        if (!empty($cfg['use_smtp'])) {
            $mail->isSMTP();
            $mail->Host       = (string)$cfg['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = (string)$cfg['smtp_username'];
            $mail->Password   = (string)$cfg['smtp_password'];
            $mail->Port       = (int)$cfg['smtp_port'];

            $secure = (string)($cfg['smtp_secure'] ?? 'tls');
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        }

        $fromEmail = (string)$cfg['from_email'];
        $fromName  = (string)$cfg['from_name'];

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        if ($textBody !== '') {
            $mail->AltBody = $textBody;
        } else {
            $mail->AltBody = strip_tags($htmlBody);
        }

        return $mail->send();
    } catch (Exception $e) {
        error_log('[MAIL] ' . $e->getMessage());
        return false;
    }
}
