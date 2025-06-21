<?php
require_once 'config/session_config.php';
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['student_db_id'])) {
    $_SESSION['error'] = 'Please log in to ask questions.';
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $questionText = trim($_POST['question'] ?? '');
    $userId = $_SESSION['student_db_id'];

    // Validate input
    if (empty($questionText)) {
        $_SESSION['error'] = 'Question text is required.';
        header('Location: news.php');
        exit();
    }

    if (strlen($questionText) < 10) {
        $_SESSION['error'] = 'Question must be at least 10 characters long.';
        header('Location: news.php');
        exit();
    }

    if (strlen($questionText) > 1000) {
        $_SESSION['error'] = 'Question must be less than 1000 characters.';
        header('Location: news.php');
        exit();
    }

    try {
        // Insert question into database (using a default user_id for now)
        $stmt = $conn->prepare("
            INSERT INTO questions (user_id, question_text, status, created_at) 
            VALUES (1, ?, 'active', NOW())
        ");
        
        $stmt->bind_param("s", $questionText);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Your question has been submitted successfully! Admins will review and respond soon.';
        } else {
            throw new Exception("Failed to submit question");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'An error occurred while submitting your question. Please try again.';
    }
    
    header('Location: news.php');
    exit();
} else {
    // If not a POST request, redirect to news page
    header('Location: news.php');
    exit();
}
?> 