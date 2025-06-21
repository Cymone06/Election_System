<?php
require_once '../config/connect.php';

if (!isset($_GET['election_id']) || empty($_GET['election_id'])) {
    die("No election selected.");
}

$election_id = (int)$_GET['election_id'];

// Fetch election details
$stmt = $conn->prepare("SELECT title, start_date, end_date FROM election_periods WHERE id = ?");
$stmt->bind_param("i", $election_id);
$stmt->execute();
$election = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$election) {
    die("Election not found.");
}

// Fetch voter turnout
$stmt = $conn->prepare("SELECT COUNT(id) as total_students FROM students");
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['total_students'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(DISTINCT voter_id) as total_voters FROM votes WHERE election_id = ?");
$stmt->bind_param("i", $election_id);
$stmt->execute();
$total_voters = $stmt->get_result()->fetch_assoc()['total_voters'];
$stmt->close();

$turnout_percentage = ($total_students > 0) ? round(($total_voters / $total_students) * 100, 2) : 0;

// Fetch results
$sql = "SELECT 
            p.position_name,
            a.first_name,
            a.last_name,
            (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = a.id AND v.election_id = ?) as vote_count
        FROM applications a
        JOIN positions p ON a.position_id = p.id
        WHERE a.status = 'approved' AND a.vetting_status = 'verified'
        ORDER BY p.id, vote_count DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $election_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group results by position
$results_by_position = [];
foreach ($results as $row) {
    $results_by_position[$row['position_name']][] = $row;
}

// Set headers for Word document download
$filename = "election_report_" . str_replace(' ', '_', $election['title']) . ".doc";
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"$filename\"");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Report</title>
    <style>
        body { font-family: 'Times New Roman', serif; }
        h1, h2, h3 { font-family: 'Arial', sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div style="text-align:center;">
        <h1>Election Report</h1>
        <h2><?php echo htmlspecialchars($election['title']); ?></h2>
        <p>
            <strong>Election Period:</strong> 
            <?php echo date('M d, Y', strtotime($election['start_date'])); ?> - <?php echo date('M d, Y', strtotime($election['end_date'])); ?>
        </p>
    </div>

    <h3>Voter Turnout Summary</h3>
    <table>
        <tr>
            <th>Total Registered Students</th>
            <td><?php echo $total_students; ?></td>
        </tr>
        <tr>
            <th>Total Students Who Voted</th>
            <td><?php echo $total_voters; ?></td>
        </tr>
        <tr>
            <th>Voter Turnout Percentage</th>
            <td><?php echo $turnout_percentage; ?>%</td>
        </tr>
    </table>

    <h3>Detailed Election Results</h3>
    <?php foreach ($results_by_position as $position => $candidates): ?>
        <h4><?php echo htmlspecialchars($position); ?></h4>
        <table>
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Vote Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidates as $candidate): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></td>
                        <td><?php echo $candidate['vote_count']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</body>
</html> 