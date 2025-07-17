<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

function sendSystemEmail($to, $subject, $body, $toName = '') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'semetvcs@gmail.com';
        $mail->Password = 'vtfoklbfskrsjlms';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('semetvcs@gmail.com', 'STVC Election System');
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Optionally log $mail->ErrorInfo
        return false;
    }
}

function sendNewsletterToAllSubscribers($conn, $subject, $body) {
    $result = $conn->query('SELECT email FROM newsletter_subscribers');
    if (!$result) return 0;
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        if (sendSystemEmail($row['email'], $subject, $body)) {
            $count++;
        }
    }
    return $count;
} 