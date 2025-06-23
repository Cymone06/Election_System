<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';
require_once 'includes/email_helper.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];
$stmt = $conn->prepare('SELECT email FROM students WHERE id = ?');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$email = $row['email'];

$step = $_GET['step'] ?? 'request';
$error = '';
$success = '';

if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limit (3 per day)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pin_resets WHERE user_id = ? AND user_type = 'student' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result['count'] >= 3) {
        $error = 'You have reached the maximum number of PIN reset requests for today.';
    } else {
        // Log the attempt
        $stmt = $conn->prepare("INSERT INTO pin_resets (user_id, user_type) VALUES (?, 'student')");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();

        $code = random_int(100000, 999999);
        $_SESSION['forgot_pin_code'] = $code;
        $_SESSION['forgot_pin_code_expires'] = time() + 600;
        sendSystemEmail($email, '2FA PIN Reset Code', "Your 2FA PIN reset code is: <b>$code</b> (valid for 10 minutes)");
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
        $stmt = $conn->prepare('UPDATE students SET two_factor_pin = ? WHERE id = ?');
        $stmt->bind_param('si', $hashed_pin, $student_id);
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
    <title>Forgot 2FA PIN - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: var(--primary-color);
        }
        .forgot-pin-section {
            padding: 80px 0;
        }
        .forgot-pin-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 400px;
            margin: 0 auto;
        }
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
                <span class="fw-bold">STVC Election System</span>
            </a>
        </div>
    </nav>
    <section class="forgot-pin-section">
        <div class="container">
            <div class="forgot-pin-card">
                <h2 class="text-center mb-4"><i class="fas fa-key me-2"></i>Forgot 2FA PIN</h2>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                <?php if ($step === 'request'): ?>
                    <p class="text-center text-muted mb-4">Enter your email to receive a PIN reset code.</p>
                    <form method="POST" action="?step=request">
                        <div class="mb-3">
                            <label class="form-label">Your Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" readonly>
                        </div>
                        <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Send Reset Code</button></div>
                    </form>
                <?php elseif ($step === 'verify'): ?>
                    <p class="text-center text-muted mb-4">Check your email for the 6-digit code.</p>
                    <form method="POST" action="?step=verify">
                        <div class="mb-3">
                            <label class="form-label">Enter Code</label>
                            <input type="text" class="form-control" name="code" maxlength="6" pattern="\d{6}" required autofocus>
                        </div>
                        <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Verify Code</button></div>
                    </form>
                <?php elseif ($step === 'reset'): ?>
                    <p class="text-center text-muted mb-4">Create a new 6-digit PIN.</p>
                    <form method="POST" action="?step=reset">
                        <div class="mb-3">
                            <label class="form-label">New 2FA PIN</label>
                            <input type="password" class="form-control" name="new_pin" maxlength="6" pattern="\d{6}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New PIN</label>
                            <input type="password" class="form-control" name="confirm_pin" maxlength="6" pattern="\d{6}" required>
                        </div>
                        <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Set New PIN</button></div>
                    </form>
                <?php elseif ($step === 'done'): ?>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-success btn-lg w-100">Back to Login</a>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="verify_pin.php" class="text-secondary">Back to PIN entry</a>
                </div>
            </div>
        </div>
    </section>
</body>
</html> 