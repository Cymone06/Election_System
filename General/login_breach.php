<?php
require_once 'config/session_config.php';
require_once 'config/database.php';

if (!isset($_SESSION['pending_breach']) || !isset($_SESSION['breach_info'])) {
    header('Location: login.php');
    exit();
}

$breach = $_SESSION['breach_info'];
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['not_me'])) {
        // Mark as breach, force password change
        $stmt = $conn->prepare("UPDATE user_sessions SET breach_flag = 1, is_active = 0 WHERE user_id = ? AND user_type = ?");
        $stmt->bind_param('is', $user_id, $user_type);
        $stmt->execute();
        $stmt->close();
        session_destroy();
        session_start();
        $_SESSION['force_password_change'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_type'] = $user_type;
        header('Location: forget_password/reset_password.php');
        exit();
    } elseif (isset($_POST['yes_me'])) {
        // Log out previous session, allow this one
        $session_id = session_id();
        $stmt = $conn->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND user_type = ? AND session_id != ?");
        $stmt->bind_param('iss', $user_id, $user_type, $session_id);
        $stmt->execute();
        $stmt->close();
        unset($_SESSION['pending_breach'], $_SESSION['breach_info']);
        if ($user_type === 'student') {
            header('Location: dashboard.php');
        } else {
            header('Location: Admin/admin_dashboard.php');
        }
        exit();
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Breach Detected</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h3 class="mb-3 text-danger"><i class="fas fa-exclamation-triangle"></i> Account Activity Detected</h3>
                        <p class="mb-4">A login was detected from another device or browser.</p>
                        <ul class="list-group mb-4">
                            <li class="list-group-item"><strong>IP Address:</strong> <?php echo htmlspecialchars($breach['ip_address']); ?></li>
                            <li class="list-group-item"><strong>Device Info:</strong> <?php echo htmlspecialchars($breach['user_agent']); ?></li>
                        </ul>
                        <form method="POST">
                            <button type="submit" name="not_me" class="btn btn-danger me-2">This was NOT me</button>
                            <button type="submit" name="yes_me" class="btn btn-success">This was me, continue</button>
                        </form>
                        <p class="mt-4 text-muted">If you did not initiate this login, your account may be compromised. You will be required to change your password.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html> 