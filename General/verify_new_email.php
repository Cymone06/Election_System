<?php
require_once 'config/session_config.php';
require_once 'config/database.php';
require_once __DIR__ . '/includes/email_helper.php';

if (!isset($_SESSION['pending_email_update'])) {
    header('Location: index.php');
    exit();
}

$pending = $_SESSION['pending_email_update'];
$user_type = $pending['user_type'];
$user_id = $pending['user_id'];
$new_email = $pending['new_email'];
$code = $pending['code'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_code = trim($_POST['verification_code'] ?? '');
    if ($input_code != $code) {
        $error = 'Invalid verification code.';
    } else {
        // Update email and other fields
        if ($user_type === 'student') {
            $sql = 'UPDATE students SET first_name=?, last_name=?, email=?, phone_number=?, department=?';
            $params = [$pending['first_name'], $pending['last_name'], $new_email, $pending['phone_number'], $pending['department']];
            $types = 'sssss';
            if (!empty($pending['new_password'])) {
                $sql .= ', password=?';
                $params[] = $pending['new_password'];
                $types .= 's';
            }
            $sql .= ' WHERE id=?';
            $params[] = $user_id;
            $types .= 'i';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $_SESSION['first_name'] = $pending['first_name'];
                    $_SESSION['last_name'] = $pending['last_name'];
                    $_SESSION['email'] = $new_email;
                    unset($_SESSION['pending_email_update']);
                    $success = 'Email updated and verified successfully!';
                    $stmt->close();
                    header('Location: profile.php?success=email_verified');
                    exit();
                } else {
                    $error = 'Failed to update email. Please try again.';
                    $stmt->close();
                }
            }
        } elseif ($user_type === 'admin') {
            $sql = 'UPDATE users SET first_name=?, last_name=?, email=?, id_number=?, gender=?';
            $params = [$pending['first_name'], $pending['last_name'], $new_email, $pending['id_number'], $pending['gender']];
            $types = 'sssss';
            if (!empty($pending['new_password'])) {
                $sql .= ', password=?';
                $params[] = $pending['new_password'];
                $types .= 's';
            }
            $sql .= ' WHERE id=?';
            $params[] = $user_id;
            $types .= 'i';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $_SESSION['first_name'] = $pending['first_name'];
                    $_SESSION['last_name'] = $pending['last_name'];
                    $_SESSION['email'] = $new_email;
                    unset($_SESSION['pending_email_update']);
                    $success = 'Email updated and verified successfully!';
                    $stmt->close();
                    header('Location: Admin/profile.php?success=email_verified');
                    exit();
                } else {
                    $error = 'Failed to update email. Please try again.';
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify New Email - STVC Election System</title>
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
            <h1>Verify New Email</h1>
            <p>Enter the code sent to your new email to verify your account</p>
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
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="verification_code">Verification Code</label>
                <input type="text" id="verification_code" name="verification_code" maxlength="6" required autofocus pattern="\d{6}" placeholder="Enter 6-digit code">
            </div>
            <button type="submit" class="btn">Verify & Update Email</button>
        </form>
        <a href="index.php" class="btn btn-back">Back</a>
    </div>
</body>
</html> 