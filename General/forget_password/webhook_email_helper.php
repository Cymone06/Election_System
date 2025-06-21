<?php
/**
 * Webhook Email Helper
 * Uses webhook.site for testing - you get a unique URL to see all emails
 * Perfect for development and testing without setting up email servers
 */

class WebhookEmailHelper {
    private $webhook_url = 'https://webhook.site/YOUR_UNIQUE_ID'; // Get from webhook.site
    private $from_email = 'noreply@stvc.edu';
    private $from_name = 'STVC Election System';
    
    /**
     * Send verification code email using webhook
     */
    public function sendVerificationCode($to_email, $code, $user_name) {
        $subject = 'Verification Code - STVC Election System';
        $message = $this->getVerificationCodeTemplate($code, $user_name);
        
        // For development, log the email
        if ($this->isDevelopment()) {
            return $this->logEmailForDevelopment($to_email, $subject, $message);
        }
        
        return $this->sendWithWebhook($to_email, $subject, $message);
    }
    
    /**
     * Send email using webhook.site
     */
    private function sendWithWebhook($to_email, $subject, $message) {
        $data = array(
            'to' => $to_email,
            'from' => $this->from_email,
            'from_name' => $this->from_name,
            'subject' => $subject,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'code' => $code ?? 'N/A'
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->webhook_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code == 200;
    }
    
    /**
     * Get verification code email template
     */
    private function getVerificationCodeTemplate($code, $user_name) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verification Code</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 2rem; }
                .header p { margin: 10px 0 0 0; opacity: 0.9; }
                .content { padding: 40px; background: #ffffff; }
                .code { background: #667eea; color: white; font-size: 2.5em; font-weight: bold; padding: 25px; text-align: center; border-radius: 10px; margin: 30px 0; letter-spacing: 8px; font-family: monospace; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 25px 0; }
                .warning strong { color: #856404; }
                .warning ul { margin: 10px 0; padding-left: 20px; }
                .warning li { margin: 5px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; background: #f8f9fa; border-top: 1px solid #e9ecef; }
                .webhook-notice { background: #e3f2fd; border: 1px solid #2196f3; padding: 15px; border-radius: 8px; margin: 20px 0; }
                @media (max-width: 600px) { .container { margin: 10px; } .content { padding: 20px; } .code { font-size: 2em; letter-spacing: 5px; } }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>STVC Election System</h1>
                    <p>Verification Code</p>
                </div>
                <div class="content">
                    <h2>Hello ' . htmlspecialchars($user_name) . ',</h2>
                    <p>You have requested to reset your password. Please use the verification code below:</p>
                    
                    <div class="code">' . $code . '</div>
                    
                    <div class="warning">
                        <strong>Important Security Information:</strong>
                        <ul>
                            <li>This code will expire in 10 minutes</li>
                            <li>If you didn\'t request this code, please ignore this email</li>
                            <li>Never share this code with anyone</li>
                            <li>This code can only be used once</li>
                        </ul>
                    </div>
                    
                    <div class="webhook-notice">
                        <strong>🔧 Development Notice:</strong><br>
                        This email was sent via webhook.site for testing purposes.<br>
                        In production, this would be sent to your actual email address.
                    </div>
                    
                    <p>Enter this code on the verification page to proceed with password reset.</p>
                    
                    <p style="margin-top: 30px;">
                        <strong>Best regards,</strong><br>
                        STVC Election System Team
                    </p>
                </div>
                <div class="footer">
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; ' . date('Y') . ' STVC Election System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Check if we're in development mode
     */
    private function isDevelopment() {
        return in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) || 
               strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
    }
    
    /**
     * Log email for development instead of sending
     */
    private function logEmailForDevelopment($to_email, $subject, $message) {
        $log_file = __DIR__ . '/email_log.txt';
        $log_entry = "=== WEBHOOK EMAIL LOG ===\n";
        $log_entry .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $log_entry .= "To: $to_email\n";
        $log_entry .= "Subject: $subject\n";
        $log_entry .= "Webhook URL: $this->webhook_url\n";
        $log_entry .= "Message:\n$message\n";
        $log_entry .= str_repeat('=', 50) . "\n\n";
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        return true;
    }
    
    /**
     * Generate a 6-digit verification code
     */
    public function generateVerificationCode() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
?> 