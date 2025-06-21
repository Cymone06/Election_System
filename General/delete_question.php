<?php
require_once 'config/session_config.php';
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    header('Location: news.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $questionId = (int)($_GET['id'] ?? 0);

    // Validate input
    if ($questionId <= 0) {
        $_SESSION['error'] = 'Invalid question ID.';
        header('Location: news.php');
        exit();
    }

    try {
        // Check if question exists
        $stmt = $conn->prepare("SELECT id FROM questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error'] = 'Question not found.';
            header('Location: news.php');
            exit();
        }
        $stmt->close();

        // Begin transaction
        $conn->begin_transaction();

        // --- Start Secret Archival ---
        // 1. Fetch the question to be deleted
        $q_stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
        $q_stmt->bind_param("i", $questionId);
        $q_stmt->execute();
        $question_data = $q_stmt->get_result()->fetch_assoc();
        $q_stmt->close();

        // 2. Fetch all associated answers
        $a_stmt = $conn->prepare("SELECT * FROM answers WHERE question_id = ?");
        $a_stmt->bind_param("i", $questionId);
        $a_stmt->execute();
        $answers_data = $a_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $a_stmt->close();

        $admin_id = $_SESSION['user_id'] ?? null;
        $admin_name = $_SESSION['user_name'] ?? 'Unknown Admin';
        $archive_stmt = $conn->prepare("INSERT INTO deleted_items (item_type, item_identifier, item_data, deleted_by_user_id, deleted_by_user_name) VALUES (?, ?, ?, ?, ?)");

        // Archive the question
        if ($question_data) {
            $item_data_json = json_encode($question_data);
            $item_type = 'question';
            $archive_stmt->bind_param('sssis', $item_type, $questionId, $item_data_json, $admin_id, $admin_name);
            $archive_stmt->execute();
        }

        // Archive each answer
        foreach ($answers_data as $answer) {
            $item_data_json = json_encode($answer);
            $item_type = 'answer';
            $answer_id = $answer['id'];
            $archive_stmt->bind_param('sssis', $item_type, $answer_id, $item_data_json, $admin_id, $admin_name);
            $archive_stmt->execute();
        }
        $archive_stmt->close();
        // --- End Secret Archival ---

        // Delete associated answers first (due to foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM answers WHERE question_id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $stmt->close();

        // Delete the question
        $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        
        if ($stmt->execute()) {
            // Commit transaction
            $conn->commit();
            $_SESSION['success'] = 'Question deleted successfully!';
        } else {
            throw new Exception("Failed to delete question");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = 'An error occurred while deleting the question. Please try again.';
    }
    
    header('Location: news.php');
    exit();
} else {
    // If not a GET request, redirect to news page
    header('Location: news.php');
    exit();
}
?> 