<?php
/**
 * Forgot Password System Index
 * Guides users to set up the webhook email system
 */

// Check if webhook is configured
$webhook_configured = false;
$webhook_url = '';

if (file_exists(__DIR__ . '/webhook_email_helper.php')) {
    $webhook_helper_content = file_get_contents(__DIR__ . '/webhook_email_helper.php');
    if (preg_match("/private \$webhook_url = '([^']+)';/", $webhook_helper_content, $matches)) {
        $webhook_url = $matches[1];
        $webhook_configured = ($webhook_url !== 'https://webhook.site/YOUR_UNIQUE_ID');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password System - STVC Election System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 40px; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { color: #333; font-size: 2.5rem; margin-bottom: 10px; }
        .header p { color: #666; font-size: 1.1rem; }
        .status { padding: 20px; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        .status.success { background: #e6ffe6; color: #00b894; border: 2px solid #a8e6cf; }
        .status.warning { background: #fff3cd; color: #856404; border: 2px solid #ffeaa7; }
        .webhook-info { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #667eea; }
        .webhook-info a { color: #667eea; text-decoration: none; font-weight: bold; }
        .webhook-info a:hover { text-decoration: underline; }
        .steps { background: #e3f2fd; padding: 25px; border-radius: 10px; margin-bottom: 30px; }
        .steps h3 { color: #1976d2; margin-bottom: 20px; }
        .step { display: flex; align-items: flex-start; margin-bottom: 15px; }
        .step-number { background: #1976d2; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; flex-shrink: 0; }
        .step-content { flex: 1; }
        .step-title { font-weight: 600; color: #333; margin-bottom: 5px; }
        .step-description { color: #666; font-size: 0.9rem; }
        .action-buttons { text-align: center; margin-top: 30px; }
        .btn { display: inline-block; padding: 15px 30px; margin: 0 10px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a6fd8; transform: translateY(-2px); }
        .btn-secondary { background: #f8f9fa; color: #667eea; border: 2px solid #667eea; }
        .btn-secondary:hover { background: #667eea; color: white; }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }
        .feature { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .feature i { font-size: 2rem; color: #667eea; margin-bottom: 15px; }
        .feature h4 { color: #333; margin-bottom: 10px; }
        .feature p { color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-key"></i> Forgot Password System</h1>
            <p>Professional email verification system with instant testing</p>
        </div>

        <?php if ($webhook_configured): ?>
            <div class="status success">
                <i class="fas fa-check-circle"></i>
                <strong>✅ Email System Ready!</strong><br>
                Your webhook email system is configured and ready to use.
            </div>
            
            <div class="webhook-info">
                <strong>📧 View Emails:</strong> 
                <a href="<?php echo htmlspecialchars($webhook_url); ?>" target="_blank">
                    <?php echo htmlspecialchars($webhook_url); ?>
                </a>
                <p>All verification emails will appear on this webhook page instantly!</p>
            </div>
        <?php else: ?>
            <div class="status warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>⚠️ Setup Required</strong><br>
                Please configure the webhook email system before using the forgot password feature.
            </div>
        <?php endif; ?>

        <div class="steps">
            <h3><i class="fas fa-list-ol"></i> Quick Setup Steps</h3>
            
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <div class="step-title">Configure Webhook</div>
                    <div class="step-description">Set up your unique webhook URL to receive emails instantly</div>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <div class="step-title">Test the System</div>
                    <div class="step-description">Send a test email to verify everything is working</div>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <div class="step-title">Use Forgot Password</div>
                    <div class="step-description">Try the complete forgot password flow with real users</div>
                </div>
            </div>
        </div>

        <div class="features">
            <div class="feature">
                <i class="fas fa-rocket"></i>
                <h4>Instant Setup</h4>
                <p>No registration or complex configuration required</p>
            </div>
            <div class="feature">
                <i class="fas fa-eye"></i>
                <h4>See All Emails</h4>
                <p>Every email appears on your webhook page instantly</p>
            </div>
            <div class="feature">
                <i class="fas fa-shield-alt"></i>
                <h4>Secure Codes</h4>
                <p>6-digit verification codes with 10-minute expiry</p>
            </div>
            <div class="feature">
                <i class="fas fa-mobile-alt"></i>
                <h4>Mobile Friendly</h4>
                <p>Professional responsive design works on all devices</p>
            </div>
        </div>

        <div class="action-buttons">
            <?php if ($webhook_configured): ?>
                <a href="test_webhook.php" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Test Email System
                </a>
                <a href="forgot_password.php" class="btn btn-secondary">
                    <i class="fas fa-key"></i> Try Forgot Password
                </a>
            <?php else: ?>
                <a href="webhook_setup.php" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Setup Webhook
                </a>
            <?php endif; ?>
            
            <a href="../index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html> 