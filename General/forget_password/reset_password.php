<?php
session_start();
require_once '../config/connect.php';

$message = '';
$message_type = '';
$token_valid = false;
$user_id = null;
$user_type = null;

// Check if token is provided
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT user_id, user_type, expires_at FROM password_resets WHERE token = ? AND used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reset_data = $result->fetch_assoc();
        $expires_at = new DateTime($reset_data['expires_at']);
        $now = new DateTime();
        
        if ($now < $expires_at) {
            $token_valid = true;
            $user_id = $reset_data['user_id'];
            $user_type = $reset_data['user_type'];
        } else {
            $message = 'This reset link has expired. Please request a new one.';
            $message_type = 'error';
        }
    } else {
        $message = 'Invalid or expired reset link. Please request a new one.';
        $message_type = 'error';
    }
} else {
    $message = 'No reset token provided.';
    $message_type = 'error';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valid) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($password)) {
        $message = 'Please enter a new password.';
        $message_type = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user password based on user type
        if ($user_type === 'student') {
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        }
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            // Mark reset token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $message = 'Password successfully reset! You can now login with your new password.';
            $message_type = 'success';
            $token_valid = false; // Hide the form
        } else {
            $message = 'An error occurred while resetting your password. Please try again.';
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
    <title>Reset Password - STVC Election System</title>
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

        .password-toggle {
            position: relative;
        }

        .password-toggle input {
            padding-right: 50px;
        }

        .password-toggle i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .password-toggle i:hover {
            color: #667eea;
        }

        .password-strength {
            margin-top: 10px;
            font-size: 0.8rem;
        }

        .strength-bar {
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #ff6b6b; }
        .strength-medium { background: #feca57; }
        .strength-strong { background: #48dbfb; }
        .strength-very-strong { background: #1dd1a1; }

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

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

        .requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
            font-size: 0.85rem;
            color: #666;
        }

        .requirements h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .requirements ul {
            list-style: none;
            padding: 0;
        }

        .requirements li {
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }

        .requirements li:before {
            content: '•';
            color: #667eea;
            font-weight: bold;
            position: absolute;
            left: 0;
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
            <i class="fas fa-key"></i>
            <h1>STVC Election System</h1>
            <p>Reset Password</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($token_valid): ?>
            <div class="requirements">
                <h4><i class="fas fa-shield-alt"></i> Password Requirements</h4>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Use a combination of letters, numbers, and symbols</li>
                    <li>Avoid common passwords and personal information</li>
                </ul>
            </div>

            <form method="POST" action="" id="resetForm">
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> New Password
                    </label>
                    <div class="password-toggle">
                        <input type="password" id="password" name="password" placeholder="Enter your new password" required>
                        <i class="fas fa-eye" onclick="togglePassword('password')"></i>
                    </div>
                    <div class="password-strength">
                        <span id="strengthText">Password strength: </span>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <div class="password-toggle">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
                        <i class="fas fa-eye" onclick="togglePassword('confirm_password')"></i>
                    </div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
        <?php else: ?>
            <div class="links">
                <a href="forgot_password.php">
                    <i class="fas fa-arrow-left"></i> Request New Reset Link
                </a>
            </div>
        <?php endif; ?>

        <div class="links">
            <a href="../login.php">
                <i class="fas fa-sign-in-alt"></i> Back to Login
            </a>
        </div>

        <a href="../index.php" class="back-link">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';

            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;

            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');

            strengthFill.className = 'strength-fill';
            
            if (strength === 0) {
                strengthFill.style.width = '0%';
                strengthText.textContent = 'Password strength: ';
            } else if (strength <= 2) {
                strengthFill.style.width = '25%';
                strengthFill.classList.add('strength-weak');
                strengthText.textContent = 'Password strength: Weak';
            } else if (strength <= 3) {
                strengthFill.style.width = '50%';
                strengthFill.classList.add('strength-medium');
                strengthText.textContent = 'Password strength: Medium';
            } else if (strength <= 4) {
                strengthFill.style.width = '75%';
                strengthFill.classList.add('strength-strong');
                strengthText.textContent = 'Password strength: Strong';
            } else {
                strengthFill.style.width = '100%';
                strengthFill.classList.add('strength-very-strong');
                strengthText.textContent = 'Password strength: Very Strong';
            }
        }

        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');

            if (password.length >= 8 && password === confirmPassword) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                    validateForm();
                });
            }

            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', validateForm);
            }

            // Form validation
            const form = document.getElementById('resetForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long.');
                        return;
                    }

                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match.');
                        return;
                    }

                    // Show loading state
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
                    submitBtn.disabled = true;
                });
            }
        });
    </script>
</body>
</html> 