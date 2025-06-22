<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

if (!isset($_SESSION['student_db_id'])) {
    header("Location: login.php");
    exit();
}
$student_id = $_SESSION['student_db_id'];

// Log out all sessions for this user
$stmt = $conn->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND user_type = 'student'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->close();

// Destroy current session
session_unset();
session_destroy();

// Redirect to password reset page after a short message
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Activity - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.12);
            padding: 2.5rem 2rem;
            max-width: 400px;
            text-align: center;
        }
        .reset-container i {
            font-size: 2.5rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        .reset-container h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .reset-container p {
            color: #555;
            margin-bottom: 2rem;
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-reset:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <i class="fas fa-shield-alt"></i>
        <h2>Account Activity Reset</h2>
        <p>Your account has been logged out from all devices for your security.<br>
        Please change your password to secure your account.</p>
        <a href="forget_password/index.php" class="btn btn-reset">Change Password</a>
    </div>
</body>
</html> 