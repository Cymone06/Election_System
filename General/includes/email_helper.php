<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../../vendor/autoload.php';

function sendSystemEmail($to, $subject, $body, $toName = '') {
    try {
        $smtpConfigPath = __DIR__ . '/../config/smtp.local.php';
        $smtpConfig = is_file($smtpConfigPath) ? require $smtpConfigPath : [];
        $smtpUsername = getenv('SMTP_USERNAME') ?: ($_SERVER['SMTP_USERNAME'] ?? ($smtpConfig['username'] ?? ''));
        $smtpPassword = getenv('SMTP_PASSWORD') ?: ($_SERVER['SMTP_PASSWORD'] ?? ($smtpConfig['password'] ?? ''));
        if ($smtpUsername === '' || $smtpPassword === '') {
            throw new \RuntimeException('SMTP_USERNAME and SMTP_PASSWORD are not configured.');
        }
        $smtpPassword = preg_replace('/\s+/', '', $smtpPassword);

        $fromEmail = getenv('SMTP_FROM_EMAIL') ?: ($_SERVER['SMTP_FROM_EMAIL'] ?? $smtpUsername);
        $fromName = getenv('SMTP_FROM_NAME') ?: ($_SERVER['SMTP_FROM_NAME'] ?? 'STVC Election System');
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: ($_SERVER['SMTP_HOST'] ?? 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)(getenv('SMTP_PORT') ?: ($_SERVER['SMTP_PORT'] ?? 587));
        $mail->Timeout = 15;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('System email delivery failed: ' . $e->getMessage());
        return false;
    }
}

function sendNewsletterToAllSubscribers($conn, $subject, $body) {
    $result = $conn->query('SELECT email FROM newsletter_subscribers');
    if (!$result) return ['total' => 0, 'sent' => 0, 'failed' => 0];
    $delivery = ['total' => $result->num_rows, 'sent' => 0, 'failed' => 0];
    while ($row = $result->fetch_assoc()) {
        if (sendSystemEmail($row['email'], $subject, $body)) {
            $delivery['sent']++;
        } else {
            $delivery['failed']++;
        }
    }
    return $delivery;
} 