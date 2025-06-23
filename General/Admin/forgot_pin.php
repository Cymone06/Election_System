<?php
require_once '../config/session_config.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/email_helper.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT email FROM users WHERE id = ?');
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$email = $row['email'];

$step = $_GET['step'] ?? 'request';
$error = '';
$success = '';

if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limit (3 per day)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pin_resets WHERE user_id = ? AND user_type = 'admin' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result['count'] >= 3) {
        $error = 'You have reached the maximum number of PIN reset requests for today.';
    } else {
        // Log the attempt
        $stmt = $conn->prepare("INSERT INTO pin_resets (user_id, user_type) VALUES (?, 'admin')");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->close();
        
        $code = random_int(100000, 999999);
        $_SESSION['forgot_pin_code'] = $code;
        $_SESSION['forgot_pin_code_expires'] = time() + 600;
        sendSystemEmail($email, 'Admin 2FA PIN Reset Code', "Your 2FA PIN reset code is: <b>$code</b> (valid for 10 minutes)");
        $success = 'A reset code has been sent to your email.';
        $step = 'verify';
    }
}

if ($step === 'verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_code = trim($_POST['code'] ?? '');
    if (!isset($_SESSION['forgot_pin_code'], $_SESSION['forgot_pin_code_expires'])) {
        $error = 'Session expired. Please try again.';
        $step = 'request';
    } elseif (time() > $_SESSION['forgot_pin_code_expires']) {
        $error = 'The code has expired. Please request a new one.';
        $step = 'request';
    } elseif ($entered_code != $_SESSION['forgot_pin_code']) {
        $error = 'Invalid code.';
    } else {
        $step = 'reset';
    }
}

if ($step === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pin = trim($_POST['new_pin'] ?? '');
    $confirm_pin = trim($_POST['confirm_pin'] ?? '');
    if (!preg_match('/^\d{6}$/', $new_pin)) {
        $error = 'PIN must be exactly 6 digits.';
    } elseif ($new_pin !== $confirm_pin) {
        $error = 'PINs do not match.';
    } else {
        $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET two_factor_pin = ? WHERE id = ?');
        $stmt->bind_param('si', $hashed_pin, $admin_id);
        $stmt->execute();
        $stmt->close();
        unset($_SESSION['forgot_pin_code'], $_SESSION['forgot_pin_code_expires']);
        $success = 'Your 2FA PIN has been reset. You can now log in.';
        $step = 'done';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot 2FA PIN - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-custom {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            color: white;
            width: 100%;
        }
    </style>
</head>
<body class="bg-light">
    <div class="login-container">
        <div class="login-header">
            <h4><i class="fas fa-key me-2"></i>Forgot 2FA PIN</h4>
        </div>
        <div class="login-body">
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($step === 'request'): ?>
                <p class="text-muted text-center mb-3">Enter your email to receive a PIN reset code.</p>
                <form method="POST" action="?step=request">
                    <div class="mb-3">
                        <label class="form-label">Your Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>
                    <button type="submit" class="btn btn-custom">Send Reset Code</button>
                </form>
            <?php elseif ($step === 'verify'): ?>
                <p class="text-muted text-center mb-3">Check your email for the 6-digit code.</p>
                <form method="POST" action="?step=verify">
                    <div class="mb-3">
                        <label class="form-label">Enter Code</label>
                        <input type="text" class="form-control" name="code" maxlength="6" pattern="\d{6}" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-custom">Verify Code</button>
                </form>
            <?php elseif ($step === 'reset'): ?>
                <p class="text-muted text-center mb-3">Create a new 6-digit PIN.</p>
                <form method="POST" action="?step=reset">
                    <div class="mb-3">
                        <label class="form-label">New 2FA PIN</label>
                        <input type="password" class="form-control" name="new_pin" maxlength="6" pattern="\d{6}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New PIN</label>
                        <input type="password" class="form-control" name="confirm_pin" maxlength="6" pattern="\d{6}" required>
                    </div>
                    <button type="submit" class="btn btn-custom">Set New PIN</button>
                </form>
            <?php elseif ($step === 'done'): ?>
                <div class="text-center">
                    <a href="admin_login.php" class="btn btn-success w-100">Back to Login</a>
                </div>
            <?php endif; ?>
             <div class="text-center mt-3">
                <a href="verify_pin.php" class="text-secondary">Back to PIN entry</a>
            </div>
        </div>
    </div>
</body>
</html> 