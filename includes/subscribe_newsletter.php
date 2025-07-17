<?php
session_start();
require_once __DIR__ . '/../General/config/database.php';

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function ajax_response($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['newsletter_email'] ?? '');
    $redirect = '/Election%20System/General/index.php'; // fallback
    if (!empty($_POST['redirect_url'])) {
        $redirect = $_POST['redirect_url'];
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        $redirect = $_SERVER['HTTP_REFERER'];
    }
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if (!$email || !is_valid_email($email)) {
        if ($is_ajax) ajax_response(false, 'Please enter a valid email address.');
        $_SESSION['newsletter_error'] = 'Please enter a valid email address.';
        header('Location: ' . $redirect);
        exit();
    }
    // Check if already subscribed
    $stmt = $conn->prepare('SELECT id FROM newsletter_subscribers WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        if ($is_ajax) ajax_response(false, 'This email is already subscribed.');
        $_SESSION['newsletter_error'] = 'This email is already subscribed.';
        $stmt->close();
        header('Location: ' . $redirect);
        exit();
    }
    $stmt->close();
    // Insert new subscriber
    $stmt = $conn->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?)');
    if ($stmt) {
        $stmt->bind_param('s', $email);
        if ($stmt->execute()) {
            if ($is_ajax) ajax_response(true, 'Thank you for subscribing! You will receive updates in your inbox.');
            $_SESSION['newsletter_success'] = 'Thank you for subscribing! You will receive updates in your inbox.';
        } else {
            if ($is_ajax) ajax_response(false, 'An error occurred. Please try again later.');
            $_SESSION['newsletter_error'] = 'An error occurred. Please try again later.';
        }
        $stmt->close();
    } else {
        if ($is_ajax) ajax_response(false, 'An error occurred. Please try again later.');
        $_SESSION['newsletter_error'] = 'An error occurred. Please try again later.';
    }
    header('Location: ' . $redirect);
    exit();
} else {
    // Show a simple page if accessed directly
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Newsletter Subscription</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
        <div class="card shadow p-4" style="max-width: 400px;">
            <h3 class="mb-3 text-center text-primary">Newsletter Subscription</h3>
            <p class="text-center mb-4">Please use the form in the website footer to subscribe to our newsletter.</p>
            <a href="/General/index.php" class="btn btn-primary w-100">Back to Home</a>
        </div>
    </body>
    </html><?php
    exit();
} 