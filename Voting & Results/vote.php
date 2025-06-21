<?php
require_once '../General/config/session_config.php';
require_once '../General/config/connect.php';

// Check if student is logged in
if (!isset($_SESSION['student_db_id'])) {
    header("Location: ../General/login.php");
    exit();
}

// Get student information
$student_db_id = $_SESSION['student_db_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email, department, student_id, profile_picture FROM students WHERE id = ?");
$stmt->bind_param("i", $student_db_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();
$profile_pic_path = (!empty($user_info['profile_picture']) && file_exists('../General/' . $user_info['profile_picture'])) ? '../General/' . $user_info['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_info['first_name'] . ' ' . $user_info['last_name']) . '&background=3498db&color=fff&size=128';


// Get election ID from query
$election_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$election = null;

if ($election_id) {
    // Check if election is active
    $stmt = $conn->prepare("SELECT * FROM election_periods WHERE id = ? AND status = 'active' AND start_date <= NOW() AND end_date >= NOW()");
    $stmt->bind_param('i', $election_id);
    $stmt->execute();
    $election = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// If no active election, redirect to dashboard
if (!$election) {
    header("Location: ../General/dashboard.php?error=no_active_election");
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

// Fetch candidates grouped by position
$candidates_by_position = [];
$sql = "SELECT p.id as position_id, p.position_name, c.id as candidate_id, c.first_name, c.last_name, c.image1
        FROM applications c
        JOIN positions p ON c.position_id = p.id
        WHERE c.status = 'approved' AND c.vetting_status = 'verified'
        ORDER BY p.id, c.last_name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $candidates_by_position[$row['position_id']]['name'] = $row['position_name'];
        $candidates_by_position[$row['position_id']]['candidates'][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - <?php echo htmlspecialchars($election['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: #f8f9fa;">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../General/index.php">
                <i class="fas fa-vote-yea me-2"></i> STVC Election System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../General/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../General/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="../General/positions.php">Positions</a></li>
                    <li class="nav-item"><a class="nav-link" href="../General/news.php">News</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo $profile_pic_path; ?>" alt="Profile" class="rounded-circle" style="width:30px; height:30px; object-fit:cover;">
                            <?php echo htmlspecialchars($user_info['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../General/profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="../General/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-5">
        <div class="text-center mb-4">
            <h1 class="display-4"><i class="fas fa-vote-yea me-2"></i>Cast Your Vote</h1>
            <h2 class="text-muted"><?php echo htmlspecialchars($election['title']); ?></h2>
        </div>

        <!-- Voting Instructions -->
        <div class="card bg-light border-primary shadow-sm mb-5 instructions-card">
            <div class="card-body p-4">
                <h4 class="card-title text-center text-primary mb-4"><i class="fas fa-info-circle me-2"></i>How to Vote</h4>
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3">
                            <div class="instruction-icon mb-3">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <h5>Step 1: Review Candidates</h5>
                            <p class="text-muted">Carefully review the candidates for each position.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <div class="instruction-icon mb-3">
                                <i class="fas fa-mouse-pointer fa-2x"></i>
                            </div>
                            <h5>Step 2: Make Your Selection</h5>
                            <p class="text-muted">Click the "Select" button for one candidate per position.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <div class="instruction-icon mb-3">
                                <i class="fas fa-check-double fa-2x"></i>
                            </div>
                            <h5>Step 3: Submit Your Ballot</h5>
                            <p class="text-muted">Once finished, click the "Submit My Vote" button at the bottom.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <form id="voteForm" action="process_vote.php" method="POST">
            <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
            <?php foreach ($candidates_by_position as $position_id => $position_data): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0"><i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($position_data['name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($position_data['candidates'] as $candidate): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 text-center candidate-card">
                                        <div class="card-body">
                                            <img src="../General/uploads/applications/<?php echo htmlspecialchars($candidate['image1']); ?>" class="mb-3" style="width:100px;height:100px;border-radius:50%;object-fit:cover;">
                                            <h5 class="card-title"><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h5>
                                            <input type="radio" class="btn-check" name="votes[<?php echo $position_id; ?>]" id="candidate-<?php echo $candidate['candidate_id']; ?>" value="<?php echo $candidate['candidate_id']; ?>" required>
                                            <label class="btn btn-outline-primary mt-2" for="candidate-<?php echo $candidate['candidate_id']; ?>">Select</label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check-circle me-2"></i>Submit My Vote</button>
            </div>
        </form>
    </div>
    
    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<style>
.candidate-card { transition: all 0.3s ease; border-radius: 15px; }
.candidate-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
.btn-check:checked + .btn-outline-primary { background-color: #198754; border-color: #198754; color: white; transform: scale(1.05); }
.instructions-card { border-radius: 15px; }
.instruction-icon {
    width: 60px;
    height: 60px;
    background-color: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}
.instructions-card .col-md-4:hover .instruction-icon {
    transform: scale(1.1) rotate(5deg);
    background-color: #3498db;
    color: white;
}
.navbar {
    background: linear-gradient(135deg, #2c3e50, #34495e) !important;
}
</style> 