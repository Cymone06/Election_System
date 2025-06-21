<?php
header('Content-Type: application/json');
require_once '../General/config/connect.php';

$election_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$election_id) {
    echo json_encode([]);
    exit();
}

$sql = "SELECT 
            p.id AS position_id,
            p.position_name,
            a.id AS candidate_id,
            a.first_name,
            a.last_name,
            a.image1,
            (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = a.id AND v.election_id = ?) as vote_count
        FROM applications a
        JOIN positions p ON a.position_id = p.id
        WHERE 
            a.status = 'approved' AND a.vetting_status = 'verified'
        ORDER BY p.id, vote_count DESC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $election_id);
$stmt->execute();
$result = $stmt->get_result();
$candidates_results = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group results by position
$results_by_position = [];
foreach ($candidates_results as $row) {
    $results_by_position[$row['position_name']][] = $row;
}

// Calculate total votes for percentage
$live_results = [];
foreach ($results_by_position as $position => $candidates) {
    $total_votes = 0;
    foreach ($candidates as $candidate) {
        $total_votes += $candidate['vote_count'];
    }
    
    // Only include positions that have candidates
    if (!empty($candidates)) {
        $live_results[$position]['total_votes'] = $total_votes;
        $live_results[$position]['candidates'] = $candidates;
    }
}

echo json_encode($live_results); 