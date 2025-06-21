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
                    // Set session variables
                    $_SESSION['student_id'] = $student['student_id'];
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
?> 