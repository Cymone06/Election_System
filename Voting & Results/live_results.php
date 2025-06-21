<?php
require_once '../General/config/session_config.php';
require_once '../General/config/connect.php';

// Check if student is logged in
$is_logged_in = isset($_SESSION['student_db_id']);
if (!$is_logged_in) {
    header("Location: ../General/login.php");
    exit();
}

// Get user info for navbar
$student_db_id = $_SESSION['student_db_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, profile_picture FROM students WHERE id = ?");
$stmt->bind_param("i", $student_db_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();
$profile_pic_path = (!empty($user_info['profile_picture']) && file_exists('../General/' . $user_info['profile_picture'])) ? '../General/' . $user_info['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_info['first_name'] . ' ' . $user_info['last_name']) . '&background=3498db&color=fff&size=128';

// Get active election
$active_election_id = null;
$active_election_title = 'No Active Election';
$stmt = $conn->prepare("SELECT id, title, end_date FROM election_periods WHERE status = 'active' AND start_date <= NOW() AND end_date >= NOW() LIMIT 1");
$stmt->execute();
$active_election = $stmt->get_result()->fetch_assoc();
if ($active_election) {
    $active_election_id = $active_election['id'];
    $active_election_title = $active_election['title'];
    $election_end_date = $active_election['end_date'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Election Results - STVC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            background-color: #f0f2f5;
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1 0 auto;
        }
        .footer {
            flex-shrink: 0;
        }
        .navbar {
            background: linear-gradient(135deg, #2c3e50, #34495e) !important;
        }
        .header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .live-dot {
            display: inline-block;
            width: 14px;
            height: 14px;
            background-color: #2ecc71;
            border-radius: 50%;
            animation: pulse-green 1.5s infinite;
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.7); }
            70% { box-shadow: 0 0 0 12px rgba(46, 204, 113, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
        }
        .position-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .position-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .candidate-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }
        .progress {
            height: 12px;
            border-radius: 10px;
        }
        .progress-bar {
            background: linear-gradient(to right, #6a82fb, #fc5c7d);
        }
        #results-container {
            transition: opacity 0.5s ease-in-out;
        }
        .countdown-timer {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .last-updated-timer {
            font-size: 0.9rem;
            color: #bdc3c7;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../General/index.php"><i class="fas fa-vote-yea me-2"></i> STVC Election System</a>
            <div class="ms-auto">
                <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile" class="rounded-circle" style="width:30px; height:30px; object-fit:cover;">
                    <?php echo htmlspecialchars($user_info['first_name']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../General/profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="../General/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="main-content">
        <div class="container py-4">
            <div class="card header-card p-4 mb-4 text-center">
                <h1 class="display-5 mb-1"><span class="live-dot me-3"></span>Live Election Results</h1>
                <p class="lead mb-2"><?php echo htmlspecialchars($active_election_title); ?></p>
                <?php if (isset($election_end_date)): ?>
                    <div id="election-countdown" class="countdown-timer"></div>
                    <div id="last-updated" class="last-updated-timer mt-2">Updating results...</div>
                <?php endif; ?>
            </div>

            <?php if ($active_election_id): ?>
                <div id="results-container" class="row g-4">
                    <!-- Results will be loaded here by JavaScript -->
                    <div class="col-12 text-center p-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Fetching latest results...</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center p-5 bg-white rounded-3">
                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No Active Elections</h3>
                    <p>There are currently no active elections to display live results for.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const resultsContainer = document.getElementById('results-container');
        const activeElectionId = <?php echo json_encode($active_election_id); ?>;

        function fetchResults() {
            if (!activeElectionId) return;

            resultsContainer.style.opacity = 0.5;
            fetch(`get_live_results.php?id=${activeElectionId}`)
                .then(response => response.json())
                .then(data => {
                    renderResults(data);
                    updateLastUpdatedTimer();
                    resultsContainer.style.opacity = 1;
                })
                .catch(error => console.error('Error fetching results:', error));
        }

        function renderResults(data) {
            resultsContainer.innerHTML = '';
            if (Object.keys(data).length === 0) {
                resultsContainer.innerHTML = `<div class="col-12 text-center p-5 bg-white rounded-3">
                    <i class="fas fa-poll-h fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Waiting for votes...</h4>
                    <p>Results will appear here live as votes are cast.</p>
                </div>`;
                return;
            }

            for (const position in data) {
                const positionData = data[position];
                let candidatesHtml = '';
                
                positionData.candidates.forEach(candidate => {
                    const percentage = (positionData.total_votes > 0) ? (candidate.vote_count / positionData.total_votes * 100).toFixed(1) : 0;
                    candidatesHtml += `
                        <div class="d-flex align-items-center mb-3">
                            <img src="../General/uploads/applications/${candidate.image1}" class="candidate-avatar me-3">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong>${candidate.first_name} ${candidate.last_name}</strong>
                                    <span class="fw-bold">${candidate.vote_count} Votes</span>
                                </div>
                                <div class="progress mt-1">
                                    <div class="progress-bar" role="progressbar" style="width: ${percentage}%;" aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100">
                                        ${percentage}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                const positionCard = `
                    <div class="col-md-6 col-lg-4">
                        <div class="position-card">
                            <div class="card-body p-4">
                                <h5 class="card-title text-center mb-4">${position}</h5>
                                ${candidatesHtml}
                            </div>
                        </div>
                    </div>
                `;
                resultsContainer.innerHTML += positionCard;
            }
        }
        
        // Update Timers
        const electionEndDate = new Date(<?php echo json_encode($election_end_date ?? null); ?> + 'Z');
        const countdownElement = document.getElementById('election-countdown');
        const lastUpdatedElement = document.getElementById('last-updated');
        let lastUpdatedSeconds = 0;

        function updateElectionCountdown() {
            const now = new Date();
            const diff = electionEndDate - now;

            if (diff <= 0 || !countdownElement) {
                countdownElement.innerHTML = "Election has ended.";
                return;
            }
            const d = Math.floor(diff / (1000 * 60 * 60 * 24));
            const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((diff % (1000 * 60)) / 1000);
            
            countdownElement.innerHTML = `Time Left: ${d}d ${h}h ${m}m ${s}s`;
        }

        function updateLastUpdatedTimer() {
            lastUpdatedSeconds = 0;
            lastUpdatedElement.textContent = `Last updated just now.`;
        }
        
        setInterval(() => {
            lastUpdatedSeconds++;
            lastUpdatedElement.textContent = `Last updated ${lastUpdatedSeconds}s ago.`;
        }, 1000);
        
        if (activeElectionId) {
            fetchResults();
            setInterval(fetchResults, 15000); // Refresh results every 15 seconds
        }
        
        if (<?php echo isset($election_end_date) ? 'true' : 'false'; ?>) {
            updateElectionCountdown();
            setInterval(updateElectionCountdown, 1000);
        }
    });
    </script>
</body>
</html> 