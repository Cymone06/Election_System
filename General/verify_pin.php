<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';
require_once 'config/database.php';

if (!isset($_SESSION['2fa_pending']) || !isset($_SESSION['2fa_student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = (int)$_SESSION['2fa_student_id'];

// Fetch the user's PIN
$stmt = $conn->prepare('SELECT two_factor_pin FROM students WHERE id = ?');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$pin_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (empty($pin_row['two_factor_pin'])) {
    // This shouldn't happen if the flow is correct, but as a safeguard...
    unset($_SESSION['2fa_pending']);
    unset($_SESSION['2fa_student_id']);
    header('Location: login.php?error=2fa_not_set');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    if (password_verify($pin, $pin_row['two_factor_pin'])) {
        // PIN is correct, now complete the login process.
        
        // 1. Fetch student's full details
        $stmt = $conn->prepare('SELECT id, student_id, first_name, last_name, department FROM students WHERE id = ?');
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // 2. Set all necessary session variables
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['first_name'] = $student['first_name'];
        $_SESSION['last_name'] = $student['last_name'];
        $_SESSION['department'] = $student['department'];
        $_SESSION['student_db_id'] = $student['id'];
        $_SESSION['user_id'] = $student['id'];
        $_SESSION['user_type'] = 'student';

        // 3. Clean up 2FA session variables
        unset($_SESSION['2fa_pending']);
        unset($_SESSION['2fa_student_id']);
        
        // 4. Log user session and check for breaches
        logUserSession($conn, $student_id, 'student');
        $other_session = checkActiveSession($conn, $student_id, 'student');
        if ($other_session) {
            $_SESSION['pending_breach'] = true;
            $_SESSION['breach_info'] = $other_session;
            header('Location: login_breach.php');
            exit;
        }

        // 5. Check for new device
        $device = $_SERVER['HTTP_USER_AGENT'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $location = getLocationFromIP($ip);
        $time = date('Y-m-d h:i A');
        $session_id = session_id();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM user_sessions WHERE user_id = ? AND user_type = 'student' AND session_id = ?");
        $stmt->bind_param("is", $student_id, $session_id);
        $stmt->execute();
        $stmt->bind_result($session_count);
        $stmt->fetch();
        $stmt->close();

        if ($session_count == 0) {
            $msg_title = "New Device Login Detected";
            $msg_content = "A login to your account was detected from a new device.<br><b>Device:</b> $device<br><b>Location:</b> $location<br><b>Time:</b> $time<br>If this was not you, <a href='reset_activity.php'>click here</a> to log out from all devices and change your password.";
            $stmt_msg = $conn->prepare("INSERT INTO messages (student_id, type, title, content) VALUES (?, 'warning', ?, ?)");
            $stmt_msg->bind_param("iss", $student_id, $msg_title, $msg_content);
            $stmt_msg->execute();
            $stmt_msg->close();
        }

        // 6. Redirect to dashboard
        header('Location: dashboard.php');
        exit();

    } else {
        $error = 'Incorrect PIN. Please try again.';
    }
}

function getLocationFromIP($ip) {
    $url = "http://ip-api.com/json/" . $ip;
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            return $data['city'] . ', ' . $data['regionName'] . ', ' . $data['country'];
        }
    }
    return 'Unknown Location';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA PIN Verification - STVC Election System</title>
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
        .pin-section {
            padding: 80px 0;
        }
        .pin-card {
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
    <section class="pin-section">
        <div class="container">
            <div class="pin-card">
                <h2 class="text-center mb-4"><i class="fas fa-shield-alt me-2"></i>Enter 2FA PIN</h2>
                <p class="text-center text-muted mb-4">For your security, please enter your 6-digit PIN to continue.</p>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="pin" class="form-label">6-digit PIN</label>
                        <input type="password" class="form-control" id="pin" name="pin" maxlength="6" pattern="\d{6}" required autofocus placeholder="Enter your PIN">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check me-2"></i>Verify</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="forgot_pin.php?step=request" class="text-muted">Forgot PIN?</a>
                </div>
            </div>
        </div>
    </section>
</body>
</html>