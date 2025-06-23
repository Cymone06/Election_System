<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_code']) || !isset($_SESSION['2fa_email'])) {
    header('Location: profile.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$code_sent = $_SESSION['2fa_code'];
$email = $_SESSION['2fa_email'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['two_fa_code'])) {
    $entered_code = trim($_POST['two_fa_code']);
    if ($entered_code == $code_sent) {
        // Enable 2FA in DB
        $stmt = $conn->prepare("UPDATE users SET two_factor_enabled = 1 WHERE id = ?");
        $stmt->bind_param('i', $admin_id);
        $stmt->execute();
        $stmt->close();
        unset($_SESSION['2fa_code'], $_SESSION['2fa_email']);
        $success = 'Two-Factor Authentication has been enabled for your account!';
    } else {
        $error = 'Invalid verification code. Please check your email and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Two-Factor Authentication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .verify-2fa-container { max-width: 500px; margin: 60px auto; background: #fff; border-radius: 15px; box-shadow: 0 4px 16px rgba(44,62,80,0.08); padding: 2.5rem 2rem; }
        .verify-2fa-title { text-align: center; font-weight: 600; color: #2c3e50; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="verify-2fa-container">
        <h2 class="verify-2fa-title"><i class="fas fa-shield-alt me-2 text-warning"></i>Two-Factor Authentication</h2>
        <p>For enhanced security, you are enabling Two-Factor Authentication (2FA) on your admin account. This means that after entering your password, you will also be required to enter a verification code sent to your email each time you log in.</p>
        <ul>
            <li>Check your email (<b><?php echo htmlspecialchars($email); ?></b>) for a 6-digit verification code.</li>
            <li>Enter the code below to complete 2FA setup.</li>
            <li>After enabling, you will be prompted for a code on every login.</li>
        </ul>
        <?php if ($success): ?>
            <div class="alert alert-success mt-3"> <?php echo $success; ?> <a href="profile.php" class="btn btn-success btn-sm ms-2">Back to Profile</a></div>
        <?php else: ?>
            <?php if ($error): ?><div class="alert alert-danger mt-3"><?php echo $error; ?></div><?php endif; ?>
            <form method="POST" class="mt-4">
                <div class="mb-3">
                    <label for="two_fa_code" class="form-label">Verification Code</label>
                    <input type="text" class="form-control" id="two_fa_code" name="two_fa_code" maxlength="6" required placeholder="Enter 6-digit code">
                </div>
                <button type="submit" class="btn btn-warning w-100">Verify & Enable 2FA</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 