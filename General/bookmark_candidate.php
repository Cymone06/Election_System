<?php
require_once 'config/connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['student_db_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to bookmark.']);
    exit;
}
if (!isset($_POST['candidate_id'])) {
    echo json_encode(['success' => false, 'message' => 'No candidate specified.']);
    exit;
}
$candidate_id = intval($_POST['candidate_id']);
$user_id = intval($_SESSION['student_db_id']);

// Check if already bookmarked
$check_sql = "SELECT id FROM candidate_bookmarks WHERE candidate_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $candidate_id, $user_id);
$check_stmt->execute();
$check_stmt->store_result();
$already_bookmarked = $check_stmt->num_rows > 0;
$check_stmt->close();

if ($already_bookmarked) {
    // Unbookmark
    $del_sql = "DELETE FROM candidate_bookmarks WHERE candidate_id = ? AND user_id = ?";
    $del_stmt = $conn->prepare($del_sql);
    $del_stmt->bind_param('ii', $candidate_id, $user_id);
    $del_stmt->execute();
    $del_stmt->close();
    $bookmarked = false;
} else {
    // Bookmark
    $ins_sql = "INSERT INTO candidate_bookmarks (candidate_id, user_id) VALUES (?, ?)";
    $ins_stmt = $conn->prepare($ins_sql);
    $ins_stmt->bind_param('ii', $candidate_id, $user_id);
    $ins_stmt->execute();
    $ins_stmt->close();
    $bookmarked = true;
}
// Get new bookmark count
$count_sql = "SELECT COUNT(*) AS bookmark_count FROM candidate_bookmarks WHERE candidate_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('i', $candidate_id);
$count_stmt->execute();
$bookmark_count = $count_stmt->get_result()->fetch_assoc()['bookmark_count'];
$count_stmt->close();
echo json_encode(['success' => true, 'bookmarked' => $bookmarked, 'bookmark_count' => $bookmark_count]); 