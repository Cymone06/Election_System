<?php
require_once '../config/session_config.php';
require_once '../config/database.php';
require_once '../includes/email_helper.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Fetch latest important, unsent news
$stmt = $conn->prepare('SELECT id, title, content FROM news WHERE is_important = 1 AND newsletter_sent = 0 ORDER BY created_at DESC LIMIT 1');
$stmt->execute();
$stmt->bind_result($news_id, $title, $content);
if ($stmt->fetch()) {
    $stmt->close();
    // Compose email
    $subject = 'Important News: ' . $title;
    $body = '<h2>' . htmlspecialchars($title) . '</h2><p>' . nl2br(htmlspecialchars($content)) . '</p>';
    $sent_count = sendNewsletterToAllSubscribers($conn, $subject, $body);
    // Mark as sent
    $stmt2 = $conn->prepare('UPDATE news SET newsletter_sent = 1 WHERE id = ?');
    $stmt2->bind_param('i', $news_id);
    $stmt2->execute();
    $stmt2->close();
    $msg = "Newsletter sent to $sent_count subscribers.";
} else {
    $stmt->close();
    $msg = 'No new important news to send.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Important News - Newsletter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4">Send Important News to Newsletter Subscribers</h2>
        <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
        <a href="manage_news.php" class="btn btn-secondary">Back to News Management</a>
    </div>
</body>
</html> 