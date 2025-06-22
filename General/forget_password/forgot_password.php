<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php';
session_start();
require_once '../config/connect.php';
require_once __DIR__ . '/../includes/email_helper.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Check if email exists in students table
        $stmt = $conn->prepare("SELECT id, first_name, last_name FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Check resend limit (3 per day)
            $stmt2 = $conn->prepare("SELECT COUNT(*) as cnt FROM password_resets WHERE email = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $row2 = $res2->fetch_assoc();
            $stmt2->close();
            if ($row2['cnt'] >= 3) {
                $message = 'You have reached the maximum number of password reset requests for today. Please try again tomorrow.';
                $message_type = 'error';
            } else {
                $user = $result->fetch_assoc();
                $user_name = $user['first_name'] . ' ' . $user['last_name'];
                // Generate 6-digit code and expiry (10 min)
                $code = generateVerificationCode();
                $expires = time() + 600; // 10 minutes
                // Store in session
                $_SESSION['fp_code'] = $code;
                $_SESSION['fp_code_expires'] = $expires;
                $_SESSION['fp_user_id'] = $user['id'];
                $_SESSION['fp_user_email'] = $email;
                $_SESSION['fp_user_name'] = $user_name;
                $_SESSION['fp_user_type'] = 'student';
                // Log the reset request in password_resets
                $stmt3 = $conn->prepare("INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())");
                $stmt3->bind_param("ss", $email, $code);
                $stmt3->execute();
                $stmt3->close();
                // Send code to email
                sendVerificationCodeSMTP($email, $code, $user_name);
                header('Location: verify_code.php');
                exit();
            }
        } else {
            $message = 'No account found with that email address.';
            $message_type = 'error';
        }
    }
}

function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendVerificationCodeSMTP($to_email, $code, $user_name) {
    $subject = 'Verification Code - STVC Election System';
    $message = getVerificationCodeTemplate($code, $user_name);
    return sendSystemEmail($to_email, $subject, $message, $user_name);
}

function getVerificationCodeTemplate($code, $user_name) {
    // Implement the logic to generate the verification code email template
    // This is a placeholder and should be replaced with the actual implementation
    return "Dear $user_name,<br><br>Your verification code is: $code<br><br>This code will expire in 10 minutes.<br><br>Thank you!<br><br>STVC Election System";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - STVC Election System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .logo h1 {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.error {
            background: #ffe6e6;
            color: #d63031;
            border: 1px solid #fab1a0;
        }

        .message.success {
            background: #e6ffe6;
            color: #00b894;
            border: 1px solid #a8e6cf;
        }

        .links {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e1e5e9;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #764ba2;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #667eea;
        }

        .instructions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
        }

        .instructions h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .instructions p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            .logo h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-vote-yea"></i>
            <h1>STVC Election System</h1>
            <p>Forgot Password</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Instructions</h3>
            <p>Enter your registered email address below. We'll send you a link to reset your password. The link will expire in 1 hour for security reasons.</p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="Enter your email address" required>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <div class="links">
            <a href="../login.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>

        <a href="../index.php" class="back-link">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            const btn = document.querySelector('.btn');

            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                
                if (!email) {
                    e.preventDefault();
                    alert('Please enter your email address.');
                    return;
                }

                if (!isValidEmail(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address.');
                    return;
                }

                // Show loading state
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                btn.disabled = true;
            });

            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
        });
    </script>
</body>
</html> 