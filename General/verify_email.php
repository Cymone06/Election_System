<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['pending_registration'])) {
    header('Location: index.php');
    exit;
}

$pending = $_SESSION['pending_registration'];
$type = $pending['type'];
$data = $pending['data'];

$error = '';
$success = '';
$resend_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_code = trim($_POST['verification_code'] ?? '');
    $email = ($type === 'student') ? $data['email'] : $data['email'];
    $stmt = $conn->prepare('SELECT id, code, expires_at, is_verified FROM email_verifications WHERE email = ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    if (!$row) {
        $error = 'No verification code found. Please register again.';
    } elseif ($row['is_verified']) {
        $error = 'This email is already verified.';
    } elseif (strtotime($row['expires_at']) < time()) {
        $error = 'Verification code expired. Please register again.';
    } elseif ($row['code'] !== $input_code) {
        $error = 'Invalid verification code.';
    } else {
        // Mark as verified
        $stmt = $conn->prepare('UPDATE email_verifications SET is_verified = 1 WHERE id = ?');
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        $stmt->close();
        // Insert user into DB
        if ($type === 'student') {
            $stmt = $conn->prepare('INSERT INTO students (first_name, last_name, student_id, email, id_number, phone_number, gender, department, course_level, password, agreed_terms, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $status = 'pending';
            $stmt->bind_param('ssssssssssis', $data['first_name'], $data['last_name'], $data['student_id'], $data['email'], $data['id_number'], $data['phone_number'], $data['gender'], $data['department'], $data['course_level'], $data['password'], $data['agreed_terms'], $status);
            $stmt->execute();
            $stmt->close();
            $success = 'Email verified and registration complete! You may now log in.';
        } else if ($type === 'admin') {
            // All new admins are regular admins, pending approval
            $stmt = $conn->prepare('INSERT INTO users (admin_id, email, phone_number, password, first_name, last_name, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, "admin", "pending", NOW())');
            $stmt->bind_param('ssssss', $data['adminId'], $data['email'], $data['phone'], $data['password'], $data['firstName'], $data['lastName']);
            $stmt->execute();
            $stmt->close();
            $success = 'Email verified and registration complete! Your account is pending approval.';
        }
        unset($_SESSION['pending_registration']);
    }
}

if (isset($_POST['resend_code'])) {
    $email = $data['email'];
    // Count resends in the last 24 hours
    $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM email_verifications WHERE email = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    if ($row['cnt'] >= 3) {
        $error = 'You have reached the maximum number of verification code requests for today. Please try again tomorrow.';
    } else {
        // Generate new code
        $new_code = random_int(100000, 999999);
        $new_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        // Insert new code into email_verifications
        $stmt = $conn->prepare('INSERT INTO email_verifications (email, code, expires_at) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $email, $new_code, $new_expires);
        $stmt->execute();
        $stmt->close();
        // Resend email
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'kennedywambia6@gmail.com';
            $mail->Password = 'otqa hnfo pjil ifdc';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('no-reply@yourdomain.com', 'STVC Election System');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Email Verification Code (Resent)';
            $mail->Body = "Your new verification code is: <b>$new_code</b> (valid for 10 minutes)";
            $mail->send();
            $resend_message = 'A new verification code has been sent to your email.';
        } catch (Exception $e) {
            $error = 'Failed to resend verification email. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - STVC Election System</title>
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
        .message.info {
            background: #e6f0ff;
            color: #2980b9;
            border: 1px solid #b2d1ff;
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
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            .logo h1 {
                font-size: 1.5rem;
            }
        }
        .btn-back {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%) !important;
            color: #fff !important;
            margin-top: 15px;
            margin-bottom: 0;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            box-shadow: 0 2px 8px rgba(231,76,60,0.10);
        }
        .btn-back:hover, .btn-back:focus {
            background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%) !important;
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(231,76,60,0.18);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height: 60px; margin-bottom: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(102,126,234,0.15);">
            <h1>Email Verification</h1>
            <p>Enter the code sent to your email to verify your account</p>
            <div style="margin-top: 10px;">
                <span style="font-weight: bold; color: #667eea; font-size: 1.1rem; letter-spacing: 1px;">STVC Election System</span>
            </div>
        </div>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($resend_message): ?>
            <div class="message info"><?php echo htmlspecialchars($resend_message); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="verification_code">Verification Code</label>
                <input type="text" id="verification_code" name="verification_code" maxlength="6" required autofocus pattern="\d{6}" placeholder="Enter 6-digit code">
            </div>
            <button type="submit" class="btn">Verify Email</button>
        </form>
        <form method="POST" style="margin-bottom: 0;">
            <button type="submit" name="resend_code" class="btn" style="background: #764ba2;">Resend Code</button>
        </form>
        <a href="index.php" class="btn btn-back">Back</a>
        <div class="links">
            <a href="login.php">Already have an account? Log in</a>
        </div>
    </div>
</body>
</html> 