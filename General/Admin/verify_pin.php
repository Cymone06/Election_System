<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT two_factor_pin FROM users WHERE id = ?');
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (empty($row['two_factor_pin'])) {
    // No PIN set, skip
    $_SESSION['2fa_verified'] = true;
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    if (password_verify($pin, $row['two_factor_pin'])) {
        $_SESSION['2fa_verified'] = true;
        header('Location: admin_dashboard.php');
        exit();
    } else {
        $error = 'Incorrect PIN.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin 2FA PIN Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
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
            position: relative;
        }
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }
        .login-body {
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 500;
            color: var(--primary-color);
        }
        .form-control {
            border-radius: 10px;
        }
        .btn-login {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            color: white;
            width: 100%;
        }
        .alert {
            border-radius: 10px;
        }
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        .back-link a {
            color: var(--secondary-color);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-shield-alt me-2"></i>Enter 2FA PIN</h2>
        </div>
        <div class="login-body">
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="pin" class="form-label">6-digit PIN</label>
                    <input type="password" class="form-control" id="pin" name="pin" maxlength="6" pattern="\d{6}" required autofocus placeholder="Enter your PIN">
                </div>
                <button type="submit" class="btn btn-login"><i class="fas fa-check me-2"></i>Verify</button>
            </form>
            <div class="back-link">
                <a href="forgot_pin.php?step=request">Forgot PIN?</a>
            </div>
        </div>
    </div>
</body>
</html> 