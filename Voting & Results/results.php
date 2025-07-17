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
    $sql = "SELECT p.position_name, a.first_name, a.last_name, a.image1, COUNT(v.id) as vote_count
            FROM votes v
            JOIN applications a ON v.candidate_id = a.id
            JOIN positions p ON v.position_id = p.id
            WHERE v.election_id = ?
            GROUP BY p.position_name, a.id
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
$stmt = $conn->prepare('SELECT c.*, a.first_name, a.last_name, a.image1 as profile_picture FROM candidates c LEFT JOIN applications a ON c.application_id = a.id ORDER BY c.position_id ASC, c.id ASC');
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
    <style>
        body { background-color: #f4f8fb; }
        .results-header {
            background: linear-gradient(90deg, #2c3e50 60%, #3498db 100%);
            color: #fff;
            padding: 2.5rem 0 1.5rem 0;
            border-radius: 0 0 2rem 2rem;
            box-shadow: 0 4px 16px rgba(44,62,80,0.08);
        }
        .results-header h1 {
            font-weight: 700;
            letter-spacing: 1px;
        }
        .results-header h2 {
            font-weight: 400;
            color: #e0eaf6;
        }
        .results-summary {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(44,62,80,0.06);
            padding: 1.5rem 2rem;
            margin-top: -2rem;
            margin-bottom: 2rem;
        }
        .card.position-card {
            border: none;
            border-radius: 1.2rem;
            box-shadow: 0 2px 12px rgba(44,62,80,0.07);
            margin-bottom: 2rem;
        }
        .card-header.bg-primary {
            border-radius: 1.2rem 1.2rem 0 0;
            background: linear-gradient(90deg, #3498db 60%, #2c3e50 100%);
        }
        .candidate-avatar {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #3498db;
            background: #eaf1fa;
        }
        .winner-badge {
            background: linear-gradient(90deg, #27ae60 60%, #2ecc71 100%);
            color: #fff;
            font-weight: 600;
            border-radius: 1em;
            padding: 0.4em 1.2em;
            font-size: 1em;
        }
        .tie-badge {
            background: #f1c40f;
            color: #222;
            font-weight: 600;
            border-radius: 1em;
            padding: 0.4em 1.2em;
            font-size: 1em;
        }
        .votes-badge {
            background: #2980b9;
            color: #fff;
            font-weight: 500;
            border-radius: 1em;
            padding: 0.4em 1.2em;
            font-size: 1em;
        }
        .list-group-item {
            background: #fafdff;
            border: none;
            border-bottom: 1px solid #eaf1fa;
        }
        .list-group-item:last-child {
            border-bottom: none;
        }
        .candidate-name {
            font-size: 1.1em;
            font-weight: 600;
            color: #2c3e50;
        }
        .no-results {
            background: #fff3cd;
            color: #856404;
            border-radius: 1rem;
            padding: 2rem;
            font-size: 1.2em;
            margin-top: 2rem;
        }
        @media (max-width: 768px) {
            .results-header, .results-summary { padding: 1.2rem 1rem; }
        }
    </style>
</head>
<body>
    <div class="results-header text-center mb-0">
        <h1><i class="fas fa-chart-bar me-2"></i>Election Results</h1>
        <?php if ($election): ?>
            <h2 class="mb-2"><?php echo htmlspecialchars($election['title']); ?></h2>
            <p class="lead mb-0">Ended on <?php echo date('M d, Y', strtotime($election['end_date'])); ?></p>
        <?php endif; ?>
    </div>
    <div class="container">
        <div class="results-summary text-center">
            <h4 class="mb-2"><i class="fas fa-trophy text-warning me-2"></i>Official Results</h4>
            <p class="mb-0">Below are the final results for each position. Winners are highlighted. In case of a tie, all top candidates are marked as winners.</p>
        </div>
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $position => $candidates): ?>
                <?php
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
                <div class="card position-card">
                    <div class="card-header bg-primary text-white d-flex align-items-center">
                        <i class="fas fa-user-tie me-2"></i>
                        <h4 class="mb-0 flex-grow-1"><?php echo htmlspecialchars($position); ?></h4>
                        <span class="badge bg-light text-primary ms-2">Total Candidates: <?php echo count($candidates); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($candidates as $candidate): ?>
                                <?php
                                $is_winner = false;
                                if ($winner_votes > 0 && $candidate['vote_count'] == $winner_votes) {
                                    $is_winner = true;
                                }
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $is_winner ? 'list-group-item-success' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($candidate['profile_picture']) && file_exists($candidate['profile_picture']) ? htmlspecialchars($candidate['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode(trim(($candidate['first_name'] ?? '') . ' ' . ($candidate['last_name'] ?? ''))) . '&background=3498db&color=fff&size=64'; ?>" alt="Candidate" class="candidate-avatar me-3">
                                        <span class="candidate-name"><?php echo htmlspecialchars((!empty($candidate['first_name']) && !empty($candidate['last_name'])) ? $candidate['first_name'] . ' ' . $candidate['last_name'] : $candidate['name']); ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($is_winner): ?>
                                            <?php if (count($winners) > 1): ?>
                                                <span class="tie-badge"><i class="fas fa-balance-scale me-1"></i>Tie</span>
                                            <?php else: ?>
                                                <span class="winner-badge"><i class="fas fa-crown me-1"></i>Winner</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <span class="votes-badge"><i class="fas fa-vote-yea me-1"></i><?php echo $candidate['vote_count']; ?> Votes</span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results text-center">
                <i class="fas fa-info-circle me-2"></i>
                Results for this election are not yet available.
            </div>
        <?php endif; ?>
        <div class="text-center mt-4 mb-5">
            <a href="../General/dashboard.php" class="btn btn-lg btn-outline-primary px-4 py-2"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 