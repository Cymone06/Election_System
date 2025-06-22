<?php
require_once '../config/session_config.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/email_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errors'] = ['Invalid CSRF token. Please refresh the page and try again.'];
        header('Location: admin_register.php');
        exit;
    }

    // Get and sanitize form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adminId = trim($_POST['adminId'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $agreeTerms = isset($_POST['agreeTerms']);
    $phone = trim($_POST['phone'] ?? '');

    $errors = [];
    // Validate fields
    if (empty($firstName) || strlen($firstName) < 2 || !preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
        $errors[] = 'First name is required and must be at least 2 letters.';
    }
    if (empty($lastName) || strlen($lastName) < 2 || !preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
        $errors[] = 'Last name is required and must be at least 2 letters.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (empty($adminId) || strlen($adminId) < 3 || !preg_match('/^[A-Z0-9]+$/', $adminId)) {
        $errors[] = 'Admin ID is required and must be at least 3 uppercase letters/numbers.';
    }
    if (empty($password) || strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/\d/', $password) ||
        !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must be at least 8 characters, with upper, lower, number, and special character.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$agreeTerms) {
        $errors[] = 'You must agree to the Terms of Service and Privacy Policy.';
    }
    if (empty($phone) || !preg_match('/^[0-9\-\+\s]{7,20}$/', $phone)) {
        $errors[] = 'A valid phone number is required.';
    }

    // Check for duplicates
    if (empty($errors)) {
        $emailExists = record_exists('users', 'email', $email);
        $adminIdExists = record_exists('users', 'admin_id', $adminId);
        // Check for duplicate phone number if column exists
        // $phoneExists = record_exists('users', 'phone_number', $phone); // Uncomment if column exists
        // if ($phoneExists) {
        //     $errors[] = 'An account with this phone number already exists.';
        // }
        if ($emailExists) {
            $errors[] = 'An account with this email already exists.';
        }
        if ($adminIdExists) {
            $errors[] = 'An account with this Admin ID already exists.';
        }
    }

    // Registration logic
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
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
        if (!sendSystemEmail($email, $subject, $body, $firstName)) {
            $errors[] = 'Failed to send verification email. Please try again.';
            $_SESSION['errors'] = $errors;
            header('Location: admin_register.php');
            exit;
        }
        // Store registration data in session
        $_SESSION['pending_registration'] = [
            'type' => 'admin',
            'data' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'adminId' => $adminId,
                'phone' => $phone,
                'password' => $hashedPassword
            ]
        ];
        header('Location: ../verify_email.php');
        exit;
    }

    // If errors, store and redirect
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: admin_register.php');
        exit;
    }
} else {
    // Not a POST request
    header('Location: admin_register.php');
    exit;
}
?> 