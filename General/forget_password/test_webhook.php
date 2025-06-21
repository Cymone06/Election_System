<?php
/**
 * Test Webhook Email System
 * Tests the webhook email functionality
 */

require_once 'webhook_email_helper.php';

$message = '';
$message_type = '';
$webhook_url = '';

// Get current webhook URL
$webhook_helper_content = file_get_contents(__DIR__ . '/webhook_email_helper.php');
if (preg_match("/private \$webhook_url = '([^']+)';/", $webhook_helper_content, $matches)) {
    $webhook_url = $matches[1];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_email = trim($_POST['test_email'] ?? '');
    
    if (empty($test_email)) {
        $message = 'Please enter a test email address.';
        $message_type = 'error';
    } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        try {
            $emailHelper = new WebhookEmailHelper();
            $code = $emailHelper->generateVerificationCode();
            
            // Send test email
            $result = $emailHelper->sendVerificationCode($test_email, $code, 'Test User');
            
            if ($result) {
                $message = "✅ Test email sent successfully! Check your webhook page for the verification code: $code";
                $message_type = 'success';
            } else {
                $message = '❌ Failed to send test email. Check your webhook configuration.';
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = '❌ Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Webhook Email - STVC Election System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #333; font-size: 2rem; margin-bottom: 10px; }
        .message { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .message.success { background: #e6ffe6; color: #00b894; border: 1px solid #a8e6cf; }
        .message.error { background: #ffe6e6; color: #d63031; border: 1px solid #fab1a0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 1rem; }
        .btn { background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: #5a6fd8; }
        .info { background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .info h3 { color: #1976d2; margin-bottom: 15px; }
        .webhook-link { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
        .webhook-link a { color: #667eea; text-decoration: none; font-weight: bold; }
        .webhook-link a:hover { text-decoration: underline; }
        .links { text-align: center; margin-top: 30px; }
        .links a { color: #667eea; text-decoration: none; margin: 0 10px; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-paper-plane"></i> Test Webhook Email</h1>
            <p>Verify that your webhook email system is working</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($webhook_url && $webhook_url !== 'https://webhook.site/YOUR_UNIQUE_ID'): ?>
            <div class="webhook-link">
                <strong>📧 View Emails:</strong> 
                <a href="<?php echo htmlspecialchars($webhook_url); ?>" target="_blank">
                    <?php echo htmlspecialchars($webhook_url); ?>
                </a>
                <p>Click the link above to see all emails sent to your webhook!</p>
            </div>
        <?php endif; ?>

        <div class="info">
            <h3><i class="fas fa-info-circle"></i> How to Test</h3>
            <p>Enter any email address below to send a test verification code. The email will appear on your webhook page instantly.</p>
            <ul>
                <li>Enter any email address (it doesn't need to be real)</li>
                <li>Click "Send Test Email"</li>
                <li>Check your webhook page to see the email</li>
                <li>The verification code will be displayed in the email</li>
            </ul>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="test_email">
                    <i class="fas fa-envelope"></i> Test Email Address
                </label>
                <input type="email" id="test_email" name="test_email" 
                       value="<?php echo htmlspecialchars($_POST['test_email'] ?? 'test@example.com'); ?>" 
                       placeholder="any-email@example.com" required>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Send Test Email
                </button>
            </div>
        </form>

        <div class="links">
            <a href="webhook_setup.php">
                <i class="fas fa-cog"></i> Webhook Setup
            </a>
            <a href="forgot_password.php">
                <i class="fas fa-key"></i> Forgot Password
            </a>
            <a href="../index.php">
                <i class="fas fa-home"></i> Home
            </a>
        </div>
    </div>
</body>
</html> 