<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    $errors = [];
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    // If no errors, proceed with login
    if (empty($errors)) {
        // Check admin credentials in users table (allow both admin and super_admin roles)
        $stmt = $conn->prepare('SELECT id, email, password, first_name, last_name, role, status FROM users WHERE email = ? AND (role = "admin" OR role = "super_admin") AND status = "active"');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['first_name'] = $admin['first_name'];
                $_SESSION['last_name'] = $admin['last_name'];
                $_SESSION['user_type'] = $admin['role'];
                $_SESSION['is_admin'] = true;
                $_SESSION['is_super_admin'] = ($admin['role'] === 'super_admin');
                // Enforce single session
                logUserSession($conn, $admin['id'], $admin['role']);
                $other_session = checkActiveSession($conn, $admin['id'], $admin['role']);
                if ($other_session) {
                    $_SESSION['pending_breach'] = true;
                    $_SESSION['breach_info'] = $other_session;
                    header('Location: ../login_breach.php');
                    exit;
                }
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid email or password.';
            }
        } else {
            // Always show a generic error for login failure
            $errors[] = 'Invalid email or password.';
        }
        $stmt->close();
    }

    // If there are errors, store them in session and redirect back to login page
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: admin_login.php');
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header('Location: admin_login.php');
    exit;
}
?> 