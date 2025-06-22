<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';
require_once __DIR__ . '/includes/email_helper.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data - match the field names from register.php
    $first_name = trim($_POST['firstName'] ?? '');
    $last_name = trim($_POST['lastName'] ?? '');
    $student_id = trim($_POST['studentId'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    $agreed_terms = isset($_POST['terms']) ? 1 : 0;

    // Debug: Log the received data
    error_log("Registration attempt - First Name: $first_name, Last Name: $last_name, Student ID: $student_id, Email: $email");

    // Validate input
    $errors = [];
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    }
    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    }
    if (empty($student_id)) {
        $errors[] = 'Student ID is required.';
    }
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    if (empty($id_number)) {
        $errors[] = 'ID number is required and cannot be empty.';
    }
    if (empty($phone_number)) {
        $errors[] = 'Phone number is required.';
    }
    if (empty($department)) {
        $errors[] = 'Department is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$agreed_terms) {
        $errors[] = 'You must agree to the terms and conditions.';
    }

    // Check if student ID, email, or ID number already exists
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM students WHERE student_id = ? OR email = ? OR id_number = ?');
        $stmt->bind_param('sss', $student_id, $email, $id_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = 'Student ID, email, or ID number already exists.';
        }
        $stmt->close();
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Generate verification code
        $verification_code = random_int(100000, 999999);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        // Store code in email_verifications
        $stmt = $conn->prepare('INSERT INTO email_verifications (email, code, expires_at) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $email, $verification_code, $expires_at);
        $stmt->execute();
        $stmt->close();
        // Send code via email (PHPMailer)
        $subject = 'Your Email Verification Code';
        $body = "Your verification code is: <b>$verification_code</b> (valid for 10 minutes)";
        if (!sendSystemEmail($email, $subject, $body, $first_name)) {
            $errors[] = 'Failed to send verification email. Please try again.';
            $_SESSION['registration_errors'] = $errors;
            header('Location: register.php');
            exit;
        }
        // Store registration data in session
        $_SESSION['pending_registration'] = [
            'type' => 'student',
            'data' => [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'student_id' => $student_id,
                'email' => $email,
                'id_number' => $id_number,
                'phone_number' => $phone_number,
                'department' => $department,
                'agreed_terms' => $agreed_terms,
                'password' => $hashed_password
            ]
        ];
        header('Location: verify_email.php');
        exit;
    }

    // If there are errors, store them in session and redirect back to registration page
    if (!empty($errors)) {
        error_log("Registration errors: " . implode(', ', $errors));
        $_SESSION['registration_errors'] = $errors;
        // Store form data for repopulation
        $_SESSION['form_data'] = [
            'firstName' => $first_name,
            'lastName' => $last_name,
            'studentId' => $student_id,
            'email' => $email,
            'department' => $department
        ];
        header('Location: register.php');
        exit;
    }
} else {
    // If not a POST request, redirect to registration page
    header('Location: register.php');
    exit;
}

// Debug: Uncomment to see all POST data if needed
// var_dump($_POST); exit;
?> 