<?php
require_once 'config/session_config.php';
require_once 'config/database.php';

// Check if user is logged in and is admin or super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    $_SESSION['error'] = 'Access denied. Admin or Super Admin privileges required.';
    header('Location: news.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $questionId = (int)($_POST['question_id'] ?? 0);
    $answerText = trim($_POST['answer_text'] ?? '');
    $adminId = $_SESSION['user_id'];

    // Validate input
    if ($questionId <= 0) {
        $_SESSION['error'] = 'Invalid question ID.';
        header('Location: news.php');
        exit();
    }

    if (empty($answerText)) {
        $_SESSION['error'] = 'Answer text is required.';
        header('Location: news.php');
        exit();
    }

    if (strlen($answerText) < 10) {
        $_SESSION['error'] = 'Answer must be at least 10 characters long.';
        header('Location: news.php');
        exit();
    }

    if (strlen($answerText) > 2000) {
        $_SESSION['error'] = 'Answer must be less than 2000 characters.';
        header('Location: news.php');
        exit();
    }

    try {
        // Check if question exists and is active
        $stmt = $conn->prepare("SELECT id FROM questions WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error'] = 'Question not found or has been deleted.';
            header('Location: news.php');
            exit();
        }
        $stmt->close();

        // Check if question already has an answer
        $stmt = $conn->prepare("SELECT id FROM answers WHERE question_id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['error'] = 'This question already has an answer.';
            header('Location: news.php');
            exit();
        }
        $stmt->close();

        // Insert answer into database
        $stmt = $conn->prepare("
            INSERT INTO answers (question_id, author_id, answer_text, answered_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("iis", $questionId, $adminId, $answerText);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Answer submitted successfully!';
        } else {
            throw new Exception("Failed to submit answer");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'An error occurred while submitting your answer. Please try again.';
    }
    
    header('Location: news.php');
    exit();
} else {
    // If not a POST request, redirect to news page
    header('Location: news.php');
    exit();
}
?> 