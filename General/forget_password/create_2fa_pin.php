<?php
session_start();
require_once '../config/connect.php';

if (!isset($_SESSION['fp_user_id'])) {
    header('Location: forgot_password.php');
    exit();
}

$user_id = $_SESSION['fp_user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['two_fa_pin'])) {
    $pin = trim($_POST['two_fa_pin']);
    if (!preg_match('/^\d{8}$/', $pin)) {
        $error = 'Your 2FA pin must be exactly 8 digits.';
    } else {
        // Hash the pin and store in DB
        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE students SET two_fa_pin = ? WHERE id = ?');
        $stmt->bind_param('si', $hashed_pin, $user_id);
        $stmt->execute();
        $stmt->close();
        $success = 'Your 2FA pin has been set successfully!';
        // Redirect to reset password
        header('Location: reset_password.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create 2FA Pin - STVC Election System</title>
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
        .instructions { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px; text-align: left; }
        .instructions h3 { color: #333; margin-bottom: 10px; font-size: 1rem; }
        .instructions p { color: #666; font-size: 0.9rem; line-height: 1.5; }
        @media (max-width: 480px) { .container { padding: 30px 20px; } .logo h1 { font-size: 1.5rem; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-shield-alt"></i>
            <h1>STVC Election System</h1>
            <p>Create 2FA Pin</p>
        </div>
        <?php if ($error): ?><div class="message error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="message success"><?php echo $success; ?></div><?php endif; ?>
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Instructions</h3>
            <p>For extra security, please create an 8-digit 2FA pin. You will use this pin for additional verification during password resets and sensitive actions.</p>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <label for="two_fa_pin">
                    <i class="fas fa-key"></i> 2FA Pin (8 digits)
                </label>
                <input type="password" id="two_fa_pin" name="two_fa_pin" maxlength="8" pattern="\d{8}" placeholder="Enter 8-digit pin" required autocomplete="off">
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-check"></i> Set 2FA Pin
            </button>
        </form>
    </div>
</body>
</html> 