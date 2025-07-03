<?php
require_once '../General/config/session_config.php';
require_once '../General/config/connect.php';

// Get election ID from query
$election_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$election = null;
$results = [];

if ($election_id) {
    // Fetch election details
    $stmt = $conn->prepare("SELECT * FROM election_periods WHERE id = ?");
    $stmt->bind_param('i', $election_id);
    $stmt->execute();
    $election = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch election results
    $sql = "SELECT p.position_name, c.first_name, c.last_name, c.image1, COUNT(v.id) as vote_count
            FROM votes v
            JOIN applications c ON v.candidate_id = c.id
            JOIN positions p ON v.position_id = p.id
            WHERE v.election_id = ?
            GROUP BY p.position_name, c.id
            ORDER BY p.position_name, vote_count DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $election_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[$row['position_name']][] = $row;
    }
    $stmt->close();
}

// If no specific election ID, get latest ended election
if (!$election) {
    $stmt = $conn->prepare("SELECT * FROM election_periods WHERE status = 'ended' ORDER BY end_date DESC LIMIT 1");
    $stmt->execute();
    $latest_election = $stmt->get_result()->fetch_assoc();
    if ($latest_election) {
        header("Location: results.php?id=" . $latest_election['id']);
        exit();
    }
    $stmt->close();
}

// Fetch candidates with student info
$stmt = $conn->prepare('SELECT c.*, s.first_name, s.last_name, s.profile_picture FROM candidates c LEFT JOIN students s ON c.student_id = s.id ORDER BY c.position_id ASC, c.id ASC');
$stmt->execute();
$candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: #f8f9fa;">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-4"><i class="fas fa-chart-bar me-2"></i>Election Results</h1>
            <?php if ($election): ?>
                <h2 class="text-muted"><?php echo htmlspecialchars($election['title']); ?></h2>
                <p class="lead">Ended on <?php echo date('M d, Y', strtotime($election['end_date'])); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($results)): ?>
            <?php foreach ($results as $position => $candidates): ?>
                <?php
                // Determine winner(s)
                $winner_votes = 0;
                if (!empty($candidates)) {
                    $winner_votes = max(array_column($candidates, 'vote_count'));
                }
                $winners = [];
                if ($winner_votes > 0) {
                    foreach ($candidates as $candidate) {
                        if ($candidate['vote_count'] == $winner_votes) {
                            $winners[] = $candidate;
                        }
                    }
                }
                ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($position); ?></h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($candidates as $candidate): ?>
                                <?php
                                $is_winner = false;
                                if ($winner_votes > 0 && $candidate['vote_count'] == $winner_votes) {
                                    $is_winner = true;
                                }
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $is_winner ? 'list-group-item-success' : ''; ?>">
                                    <div>
                                        <img src="<?php echo !empty($candidate['profile_picture']) && file_exists($candidate['profile_picture']) ? htmlspecialchars($candidate['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode(trim(($candidate['first_name'] ?? '') . ' ' . ($candidate['last_name'] ?? ''))) . '&background=3498db&color=fff&size=64'; ?>" alt="Candidate" class="candidate-avatar me-3">
                                        <span class="fw-bold"><?php echo htmlspecialchars((!empty($candidate['first_name']) && !empty($candidate['last_name'])) ? $candidate['first_name'] . ' ' . $candidate['last_name'] : $candidate['name']); ?></span>
                                    </div>
                                    <div>
                                        <?php if ($is_winner): ?>
                                            <?php if (count($winners) > 1): ?>
                                                <span class="badge bg-warning text-dark me-2">Tie</span>
                                            <?php else: ?>
                                                <span class="badge bg-success me-2"><i class="fas fa-crown me-1"></i>Winner</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <span class="badge bg-primary rounded-pill fs-6"><?php echo $candidate['vote_count']; ?> Votes</span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                Results for this election are not yet available.
            </div>
        <?php endif; ?>
        <div class="text-center mt-4">
            <a href="../General/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 