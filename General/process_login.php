<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';
require_once 'config/database.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $login = trim($_POST['login'] ?? ''); // can be student_id or email
    $password = $_POST['password'] ?? '';

    // Validate input
    $errors = [];
    if (empty($login)) {
        $errors[] = 'Student ID or Email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    // If no errors, proceed with login
    if (empty($errors)) {
        // Allow login with student_id or email
        $stmt = $conn->prepare('SELECT id, student_id, first_name, last_name, department, password, status FROM students WHERE student_id = ? OR email = ?');
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();

            // Check student status before verifying password
            if ($student['status'] === 'pending') {
                $errors[] = 'Your account is pending approval. Please wait for an administrator to activate your account.';
            } elseif ($student['status'] === 'rejected') {
                $errors[] = 'Your registration has been rejected. Please contact support for more information.';
            } elseif ($student['status'] !== 'active') {
                $errors[] = 'Your account is inactive. Please contact support.';
            } else {
                // Verify password only if status is active
                if (password_verify($password, $student['password'])) {
                    // Check if 2FA PIN is set (only if the column exists)
                    $two_factor_pin = null;
                    if ($conn->query("SHOW COLUMNS FROM students LIKE 'two_factor_pin'")->num_rows) {
                    $stmt2 = $conn->prepare('SELECT two_factor_pin FROM students WHERE id = ?');
                    $stmt2->bind_param('i', $student['id']);
                    $stmt2->execute();
                    $row2 = $stmt2->get_result()->fetch_assoc();
                    $stmt2->close();
                        $two_factor_pin = $row2['two_factor_pin'] ?? null;
                    }
                    if ($two_factor_pin) {
                        // Redirect to 2FA verification page (implement this page if not present)
                        $_SESSION['pending_2fa_student_id'] = $student['id'];
                        header('Location: verify_pin.php');
                        exit;
                    }
                    // Set basic session
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['first_name'] = $student['first_name'];
                    $_SESSION['last_name'] = $student['last_name'];
                    $_SESSION['department'] = $student['department'];
                    $_SESSION['student_db_id'] = $student['id'];
                    // Set user_id for compatibility with other files
                    $_SESSION['user_id'] = $student['id'];
                    $_SESSION['user_type'] = 'student';
                    // Enforce single session
                    logUserSession($conn, $student['id'], 'student');
                    $other_session = checkActiveSession($conn, $student['id'], 'student');
                    if ($other_session) {
                        $_SESSION['pending_breach'] = true;
                        $_SESSION['breach_info'] = $other_session;
                        header('Location: login_breach.php');
                        exit;
                    }
                    // After successful login, check for new device
                    $device = $_SERVER['HTTP_USER_AGENT'];
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $location = getLocationFromIP($ip);
                    $time = date('Y-m-d h:i A');
                    $student_id = $_SESSION['student_db_id'];
                    $session_id = session_id();
                    // Check if this device/session is new (not in user_sessions)
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
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $errors[] = 'Invalid credentials.'; // Use a generic message for security
                }
            }
        } else {
            $errors[] = 'Invalid credentials.'; // Use a generic message for security
        }
        $stmt->close();
    }

    // If there are errors, store them in session and redirect back to login page
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: login.php');
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header('Location: login.php');
    exit;
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