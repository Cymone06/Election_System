<?php
require_once 'config/connect.php';
session_start();

$user_id = isset($_SESSION['student_db_id']) ? intval($_SESSION['student_db_id']) : null;
$ip_address = $_SERVER['REMOTE_ADDR'];

// Fetch all approved and verified candidates
$sql = "SELECT a.*, s.first_name, s.last_name, s.department, s.student_id AS reg_student_id, p.position_name
        FROM applications a
        JOIN positions p ON a.position_id = p.id
        LEFT JOIN students s ON a.student_id = s.student_id
        WHERE a.status = 'approved' AND a.vetting_status = 'verified' ORDER BY a.created_at DESC";
$result = $conn->query($sql);
$candidates = [];
while ($row = $result->fetch_assoc()) {
    $candidate_id = $row['id'];
    // Like count
    $like_sql = "SELECT COUNT(*) AS like_count FROM candidate_likes WHERE candidate_id = ?";
    $like_stmt = $conn->prepare($like_sql);
    $like_stmt->bind_param('i', $candidate_id);
    $like_stmt->execute();
    $like_count = $like_stmt->get_result()->fetch_assoc()['like_count'];
    $like_stmt->close();
    // Bookmark count
    $bookmark_sql = "SELECT COUNT(*) AS bookmark_count FROM candidate_bookmarks WHERE candidate_id = ?";
    $bookmark_stmt = $conn->prepare($bookmark_sql);
    $bookmark_stmt->bind_param('i', $candidate_id);
    $bookmark_stmt->execute();
    $bookmark_count = $bookmark_stmt->get_result()->fetch_assoc()['bookmark_count'];
    $bookmark_stmt->close();
    // User like status
    $user_liked = false;
    if ($user_id) {
        $ul_sql = "SELECT 1 FROM candidate_likes WHERE candidate_id = ? AND user_id = ?";
        $ul_stmt = $conn->prepare($ul_sql);
        $ul_stmt->bind_param('ii', $candidate_id, $user_id);
        $ul_stmt->execute();
        $ul_stmt->store_result();
        $user_liked = $ul_stmt->num_rows > 0;
        $ul_stmt->close();
    } else {
        $ul_sql = "SELECT 1 FROM candidate_likes WHERE candidate_id = ? AND ip_address = ?";
        $ul_stmt = $conn->prepare($ul_sql);
        $ul_stmt->bind_param('is', $candidate_id, $ip_address);
        $ul_stmt->execute();
        $ul_stmt->store_result();
        $user_liked = $ul_stmt->num_rows > 0;
        $ul_stmt->close();
    }
    // User bookmark status
    $user_bookmarked = false;
    if ($user_id) {
        $ub_sql = "SELECT 1 FROM candidate_bookmarks WHERE candidate_id = ? AND user_id = ?";
        $ub_stmt = $conn->prepare($ub_sql);
        $ub_stmt->bind_param('ii', $candidate_id, $user_id);
        $ub_stmt->execute();
        $ub_stmt->store_result();
        $user_bookmarked = $ub_stmt->num_rows > 0;
        $ub_stmt->close();
    }
    $row['like_count'] = $like_count;
    $row['bookmark_count'] = $bookmark_count;
    $row['user_liked'] = $user_liked;
    $row['user_bookmarked'] = $user_bookmarked;
    $candidates[] = $row;
}
header('Content-Type: application/json');
echo json_encode(['candidates' => $candidates]); 