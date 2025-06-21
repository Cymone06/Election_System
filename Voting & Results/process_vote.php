<?php
require_once '../General/config/session_config.php';
require_once '../General/config/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit();
}

// Check if student is logged in
if (!isset($_SESSION['student_db_id'])) {
    header("Location: ../General/login.php?error=not_logged_in");
    exit();
}

$student_db_id = $_SESSION['student_db_id'];
$election_id = isset($_POST['election_id']) ? (int)$_POST['election_id'] : 0;
$votes = isset($_POST['votes']) ? $_POST['votes'] : [];

if (empty($election_id) || empty($votes)) {
    header("Location: vote.php?id=$election_id&error=invalid_vote");
    exit();
}

// Check if student has already voted in this election
$stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM votes WHERE voter_id = ? AND election_id = ?");
$stmt->bind_param("ii", $student_db_id, $election_id);
$stmt->execute();
$voted_check = $stmt->get_result()->fetch_assoc();
if ($voted_check['vote_count'] > 0) {
    header("Location: ../General/dashboard.php?message=already_voted");
    exit();
}
$stmt->close();

// Insert votes into the database
$stmt = $conn->prepare("INSERT INTO votes (voter_id, election_id, position_id, candidate_id) VALUES (?, ?, ?, ?)");
foreach ($votes as $position_id => $candidate_id) {
    $stmt->bind_param("iiii", $student_db_id, $election_id, $position_id, $candidate_id);
    $stmt->execute();
}
$stmt->close();

header("Location: ../General/dashboard.php?message=vote_success");
exit();
?> 