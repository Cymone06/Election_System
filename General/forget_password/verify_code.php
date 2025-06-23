<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();
require_once '../config/connect.php';

$message = '';
$message_type = '';

// Handle resend code
if (isset($_POST['resend'])) {
    if (isset($_SESSION['fp_user_email'], $_SESSION['fp_user_name'])) {
        $code = generateVerificationCode();
        $expires = time() + 600;
        $_SESSION['fp_code'] = $code;
        $_SESSION['fp_code_expires'] = $expires;
        sendVerificationCodeSMTP($_SESSION['fp_user_email'], $code, $_SESSION['fp_user_name']);
        $message = 'A new verification code has been sent to your email.';
        $message_type = 'success';
    }
}

// Handle code submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['resend'])) {
    $code = trim($_POST['code'] ?? '');
    if (empty($code)) {
        $message = 'Please enter the verification code.';
        $message_type = 'error';
    } elseif (!isset($_SESSION['fp_code'], $_SESSION['fp_code_expires'], $_SESSION['fp_user_id'], $_SESSION['fp_user_email'])) {
        $message = 'Session expired. Please start the process again.';
        $message_type = 'error';
    } elseif (time() > $_SESSION['fp_code_expires']) {
        $message = 'The verification code has expired. Please resend the code.';
        $message_type = 'error';
    } elseif ($code !== $_SESSION['fp_code']) {
        $message = 'Invalid verification code. Please try again.';
        $message_type = 'error';
    } else {
        // Code is valid, generate reset token and store in DB
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $user_id = $_SESSION['fp_user_id'];
        $user_type = $_SESSION['fp_user_type'];
        
        // Ensure password_resets table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'password_resets'");
        if ($table_check->num_rows == 0) {
            $create_table_sql = "CREATE TABLE IF NOT EXISTS password_resets (id INT PRIMARY KEY AUTO_INCREMENT, user_id INT NOT NULL, user_type ENUM('student', 'user') NOT NULL, token VARCHAR(64) UNIQUE NOT NULL, expires_at TIMESTAMP NOT NULL, used TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)";
            $conn->query($create_table_sql);
        }
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, user_type, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $user_type, $token, $expires);
        $stmt->execute();
        // Clear code from session
        unset($_SESSION['fp_code'], $_SESSION['fp_code_expires']);
        // Redirect to create 2FA pin page
        header('Location: create_2fa_pin.php');
        exit();
    }
}

function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendVerificationCodeSMTP($to_email, $code, $user_name) {
    $from_email = 'semetvcs@gmail.com';
    $from_name = 'STVC Election System';
    $smtp_host = 'smtp.gmail.com';
    $smtp_username = 'semetvcs@gmail.com';
    $smtp_password = 'vtfoklbfskrsjlms'; // Inserted Gmail App Password (no spaces)
    $smtp_port = 587;
    $subject = 'Verification Code - STVC Election System';
    $message = getVerificationCodeTemplate($code, $user_name);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port;
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getVerificationCodeTemplate($code, $user_name) {
    return 'Dear ' . htmlspecialchars($user_name) . ',<br><br>Your verification code is: <b>' . $code . '</b><br><br>This code will expire in 10 minutes.<br><br>Thank you!<br><br>STVC Election System';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - STVC Election System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 40px; width: 100%; max-width: 450px; text-align: center; }
        .logo { margin-bottom: 30px; }
        .logo i { font-size: 3rem; color: #667eea; margin-bottom: 10px; }
        .logo h1 { color: #333; font-size: 1.8rem; font-weight: 600; margin-bottom: 5px; }
        .logo p { color: #666; font-size: 0.9rem; }
        .form-group { margin-bottom: 25px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 15px; border: 2px solid #e1e5e9; border-radius: 10px; font-size: 1rem; transition: all 0.3s ease; background: #f8f9fa; }
        .form-group input:focus { outline: none; border-color: #667eea; background: white; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-bottom: 20px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102,126,234,0.3); }
        .btn:active { transform: translateY(0); }
        .message { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .message.error { background: #ffe6e6; color: #d63031; border: 1px solid #fab1a0; }
        .message.success { background: #e6ffe6; color: #00b894; border: 1px solid #a8e6cf; }
        .links { margin-top: 25px; padding-top: 25px; border-top: 1px solid #e1e5e9; }
        .links a { color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
        .links a:hover { color: #764ba2; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin-top: 15px; color: #666; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease; }
        .back-link:hover { color: #667eea; }
        .instructions { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px; text-align: left; }
        .instructions h3 { color: #333; margin-bottom: 10px; font-size: 1rem; }
        .instructions p { color: #666; font-size: 0.9rem; line-height: 1.5; }
        @media (max-width: 480px) { .container { padding: 30px 20px; } .logo h1 { font-size: 1.5rem; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-vote-yea"></i>
            <h1>STVC Election System</h1>
            <p>Verify Code</p>
        </div>
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Instructions</h3>
            <p>Enter the 6-digit code sent to your email address. The code will expire in 10 minutes. If you did not receive the code, you can resend it.</p>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <label for="code">
                    <i class="fas fa-key"></i> Verification Code
                </label>
                <input type="text" id="code" name="code" maxlength="6" pattern="\d{6}" placeholder="Enter 6-digit code" required>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-check"></i> Verify Code
            </button>
        </form>
        <form method="POST" action="" style="margin-top: 10px;">
            <button type="submit" name="resend" class="btn" style="background: #fab1a0; color: #d63031;">
                <i class="fas fa-redo"></i> Resend Code
            </button>
        </form>
        <div class="links">
            <a href="forgot_password.php">
                <i class="fas fa-arrow-left"></i> Back to Forgot Password
            </a>
        </div>
        <a href="../index.php" class="back-link">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>
</body>
</html> 