<?php
require_once 'config/connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_POST['candidate_id'])) {
    echo json_encode(['success' => false, 'message' => 'No candidate specified.']);
    exit;
}
$candidate_id = intval($_POST['candidate_id']);
$user_id = isset($_SESSION['student_db_id']) ? intval($_SESSION['student_db_id']) : null;
$ip_address = $_SERVER['REMOTE_ADDR'];

// Check if already liked
if ($user_id) {
    $check_sql = "SELECT id FROM candidate_likes WHERE candidate_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $candidate_id, $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    $already_liked = $check_stmt->num_rows > 0;
    $check_stmt->close();
} else {
    $check_sql = "SELECT id FROM candidate_likes WHERE candidate_id = ? AND ip_address = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('is', $candidate_id, $ip_address);
    $check_stmt->execute();
    $check_stmt->store_result();
    $already_liked = $check_stmt->num_rows > 0;
    $check_stmt->close();
}

if ($already_liked) {
    // Unlike
    if ($user_id) {
        $del_sql = "DELETE FROM candidate_likes WHERE candidate_id = ? AND user_id = ?";
        $del_stmt = $conn->prepare($del_sql);
        $del_stmt->bind_param('ii', $candidate_id, $user_id);
        $del_stmt->execute();
        $del_stmt->close();
    } else {
        $del_sql = "DELETE FROM candidate_likes WHERE candidate_id = ? AND ip_address = ?";
        $del_stmt = $conn->prepare($del_sql);
        $del_stmt->bind_param('is', $candidate_id, $ip_address);
        $del_stmt->execute();
        $del_stmt->close();
    }
    $liked = false;
} else {
    // Like
    $ins_sql = "INSERT INTO candidate_likes (candidate_id, user_id, ip_address) VALUES (?, ?, ?)";
    $ins_stmt = $conn->prepare($ins_sql);
    $uid = $user_id ? $user_id : null;
    $ip = $user_id ? null : $ip_address;
    $ins_stmt->bind_param('iis', $candidate_id, $uid, $ip);
    $ins_stmt->execute();
    $ins_stmt->close();
    $liked = true;
}
// Get new like count
$count_sql = "SELECT COUNT(*) AS like_count FROM candidate_likes WHERE candidate_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('i', $candidate_id);
$count_stmt->execute();
$like_count = $count_stmt->get_result()->fetch_assoc()['like_count'];
$count_stmt->close();
echo json_encode(['success' => true, 'liked' => $liked, 'like_count' => $like_count]); 