<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

// Check if student is logged in
if (!isset($_SESSION['student_db_id'])) {
    header("Location: login.php");
    exit();
}

// Get student information (including profile_picture)
$student_db_id = $_SESSION['student_db_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email, department, student_id, profile_picture FROM students WHERE id = ?");
$stmt->bind_param("i", $student_db_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$profile_pic_path = (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) ? $user['profile_picture'] : '';

function getInitials($first, $last) {
    $f = !empty($first) ? strtoupper($first[0]) : '';
    $l = !empty($last) ? strtoupper($last[0]) : '';
    $initials = $f . $l;
    if (empty($initials)) return 'U';
    return $initials;
}

// Get upcoming elections (using election_periods table)
$stmt = $conn->prepare("SELECT * FROM election_periods WHERE status = 'upcoming' ORDER BY start_date ASC");
$stmt->execute();
$upcoming_elections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get active elections (using election_periods table)
$stmt = $conn->prepare("SELECT * FROM election_periods WHERE start_date <= NOW() AND end_date >= NOW() AND status = 'active' ORDER BY end_date ASC");
$stmt->execute();
$active_elections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's voting history
$stmt = $conn->prepare("SELECT COUNT(*) as votes_cast FROM votes WHERE voter_id = ?");
$stmt->bind_param("i", $student_db_id);
$stmt->execute();
$voting_history = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get total positions available
$stmt = $conn->prepare("SELECT COUNT(*) as total_positions FROM positions WHERE status = 'active'");
$stmt->execute();
$positions_count = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get student's application status
$stmt = $conn->prepare("SELECT a.id, a.status, a.vetting_status, a.created_at, p.position_name, p.description 
                        FROM applications a 
                        JOIN positions p ON a.position_id = p.id 
                        WHERE a.student_id = ? 
                        ORDER BY a.created_at DESC");
$stmt->bind_param("s", $user['student_id']);
$stmt->execute();
$student_applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get the last ended election
$stmt = $conn->prepare("SELECT id, title FROM election_periods WHERE status = 'ended' ORDER BY end_date DESC LIMIT 1");
$stmt->execute();
$past_election = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - STVC Election System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .sidebar {
            background-color: white;
            min-height: calc(100vh - 56px);
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: var(--primary-color);
            padding: 0.8rem 1rem;
            border-radius: 0.25rem;
            margin: 0.2rem 0;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background-color: var(--secondary-color);
            color: white;
        }

        .sidebar .nav-link i {
            width: 20px;
        }

        .main-content {
            padding: 2rem;
        }

        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            border-radius: 10px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .profile-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .election-card {
            border-left: 4px solid var(--secondary-color);
        }

        .election-card.active {
            border-left: 4px solid var(--accent-color);
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .welcome-section {
            background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(44, 62, 80, 0.9)),
                        url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stats-card .card-body {
            text-align: center;
        }

        .stats-card .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stats-card .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .department-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        /* Application Tracking Styles */
        .application-tracking-card {
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }

        .application-tracking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .status-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0;
            position: relative;
        }

        .status-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 20px;
            right: 20px;
            height: 2px;
            background-color: #e9ecef;
            z-index: 1;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            color: #6c757d;
        }

        .step.completed .step-icon {
            background-color: #28a745;
            color: white;
            transform: scale(1.1);
        }

        .step-label {
            font-size: 0.75rem;
            text-align: center;
            color: #6c757d;
            font-weight: 500;
        }

        .step.completed .step-label {
            color: #28a745;
            font-weight: 600;
        }

        .progress {
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .progress-bar {
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
        }

        /* Animation for progress bars */
        @keyframes progressAnimation {
            from { width: 0%; }
            to { width: var(--progress-width); }
        }

        .progress-bar {
            animation: progressAnimation 1s ease-out;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
                <span class="fw-bold" style="color:white;letter-spacing:1px;">STVC Election System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="positions.php"><i class="fas fa-briefcase me-1"></i> Positions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gallery.php"><i class="fas fa-images me-1"></i> Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="news.php"><i class="fas fa-newspaper me-1"></i> News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_messages.php"><i class="fas fa-envelope me-1"></i> My Messages</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle user-dropdown" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar" style="padding:0;overflow:hidden;width:36px;height:36px;display:inline-block;vertical-align:middle;background:#3498db;color:#fff;font-weight:bold;font-size:1.1rem;text-align:center;line-height:36px;border-radius:50%;">
                                <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                <?php else: ?>
                                    <span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-size:1.1rem;font-weight:bold;color:#fff;">
                                        <?php echo htmlspecialchars(getInitials($user['first_name'], $user['last_name'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="profile-name" style="margin-left:8px;vertical-align:middle; color:#fff; font-weight:500;">
                                <?php 
                                // DEBUG: Output the raw values for troubleshooting
                                echo '<!-- first_name: ' . var_export($user['first_name'], true) . ' last_name: ' . var_export($user['last_name'], true) . ' -->';
                                echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); 
                                ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="application.php"><i class="fas fa-edit me-2"></i>Apply for Position</a></li>
                            <li><a class="dropdown-item" href="positions.php"><i class="fas fa-list me-2"></i>View Positions</a></li>
                            <li><a class="dropdown-item" href="news.php"><i class="fas fa-newspaper me-2"></i>News & Q&A</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-list"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="positions.php">
                                <i class="fas fa-list"></i> Positions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="application.php">
                                <i class="fas fa-edit"></i> Apply
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="news.php">
                                <i class="fas fa-newspaper"></i> News
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h2>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                    <p class="mb-0">Exercise your right to vote and shape the future of our institution.</p>
                </div>

                <div class="row">
                    <!-- Profile Card -->
                    <div class="col-md-4 mb-4">
                        <div class="card profile-card">
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center mb-3">
                                    <div class="user-avatar" style="padding:0;overflow:hidden;width:64px;height:64px;display:inline-block;vertical-align:middle;background:#3498db;color:#fff;font-weight:bold;font-size:2rem;text-align:center;line-height:64px;border-radius:50%;margin-bottom:10px;">
                                        <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                        <?php else: ?>
                                            <span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-size:2rem;font-weight:bold;color:#fff;">
                                                <?php echo htmlspecialchars(getInitials($user['first_name'], $user['last_name'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                    <p class="card-text mb-0"><?php echo htmlspecialchars($user['student_id']); ?></p>
                                </div>
                                <hr class="bg-light">
                                <p class="card-text mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
                                <p class="card-text mb-0">
                                    <i class="fas fa-building me-2"></i>
                                    <span class="department-badge"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['department']))); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="col-md-8 mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card stats-card">
                                    <div class="card-body">
                                        <div class="stats-number"><?php echo $voting_history['votes_cast']; ?></div>
                                        <div class="stats-label">Votes Cast</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card stats-card">
                                    <div class="card-body">
                                        <div class="stats-number"><?php echo $positions_count['total_positions']; ?></div>
                                        <div class="stats-label">Available Positions</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Voting Progress Section -->
                    <?php if (!empty($active_elections)): ?>
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-vote-yea me-2"></i>Your Voting Progress</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get all active positions
                                $positions = [];
                                $result = $conn->query("SELECT id, position_name FROM positions WHERE status = 'active' ORDER BY id ASC");
                                while ($row = $result->fetch_assoc()) {
                                    $positions[] = $row;
                                }
                                // Get user's votes for the current active election (if any)
                                $active_election_id = null;
                                if (!empty($active_elections)) {
                                    $active_election_id = $active_elections[0]['id'];
                                }
                                $user_votes = [];
                                if ($active_election_id) {
                                    $stmt = $conn->prepare("SELECT position_id FROM votes WHERE voter_id = ? AND election_id = ?");
                                    $stmt->bind_param("ii", $student_db_id, $active_election_id);
                                    $stmt->execute();
                                    $vote_result = $stmt->get_result();
                                    while ($row = $vote_result->fetch_assoc()) {
                                        $user_votes[] = $row['position_id'];
                                    }
                                    $stmt->close();
                                }
                                $total_positions = count($positions);
                                $voted_positions = count($user_votes);
                                $progress_percent = $total_positions > 0 ? round(($voted_positions / $total_positions) * 100) : 0;
                                ?>
                                <div class="mb-3">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percent; ?>%;" aria-valuenow="<?php echo $progress_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $progress_percent; ?>%
                                        </div>
                                    </div>
                                </div>
                                <ul class="list-group mb-3">
                                    <?php foreach ($positions as $pos): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo htmlspecialchars($pos['position_name']); ?>
                                            <?php if (in_array($pos['id'], $user_votes)): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Voted</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-times"></i> Not Voted</span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if ($progress_percent < 100 && $active_election_id): ?>
                                    <a href="../Voting & Results/vote.php?election_id=<?php echo $active_election_id; ?>" class="btn btn-success btn-lg">
                                        <i class="fas fa-vote-yea me-2"></i>Go Vote Now
                                    </a>
                                <?php elseif ($progress_percent == 100): ?>
                                    <div class="alert alert-success mb-0"><i class="fas fa-trophy me-2"></i>Congratulations! You have voted for all positions.</div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">No active election to vote in at the moment.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Application Tracking Section -->
                    <?php if (!empty($student_applications)): ?>
                    <div class="col-12 mb-4">
                        <h4 class="mb-3">
                            <i class="fas fa-clipboard-list me-2"></i>
                            My Applications
                        </h4>
                            <div class="row">
                            <?php foreach ($student_applications as $application): ?>
                                    <div class="col-md-6 mb-3">
                                    <div class="card application-tracking-card">
                                            <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($application['position_name']); ?></h5>
                                                    <p class="card-text text-muted small mb-0">Applied: <?php echo date('M d, Y', strtotime($application['created_at'])); ?></p>
                                                </div>
                                                <?php
                                                $status = strtolower($application['status']);
                                                $vetting = strtolower($application['vetting_status']);
                                                $status_class = '';
                                                $status_text = '';
                                                $progress = 0;
                                                
                                                if ($status === 'pending') {
                                                    $status_class = 'bg-secondary';
                                                    $status_text = 'Pending Review';
                                                    $progress = 25;
                                                } elseif ($status === 'approved' && $vetting === 'pending') {
                                                    $status_class = 'bg-warning';
                                                    $status_text = 'Under Vetting';
                                                    $progress = 50;
                                                } elseif ($status === 'approved' && $vetting === 'verified') {
                                                    $status_class = 'bg-success';
                                                    $status_text = 'Approved & Verified';
                                                    $progress = 100;
                                                } elseif ($status === 'rejected' || $vetting === 'rejected') {
                                                    $status_class = 'bg-danger';
                                                    $status_text = 'Rejected';
                                                    $progress = 0;
                                                } else {
                                                    $status_class = 'bg-secondary';
                                                    $status_text = ucfirst($status);
                                                    $progress = 25;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </div>
                                            
                                            <!-- Progress Bar -->
                                            <div class="progress mb-3" style="height: 8px;">
                                                <div class="progress-bar <?php echo $status_class; ?>" role="progressbar" 
                                                     style="width: <?php echo $progress; ?>%" 
                                                     aria-valuenow="<?php echo $progress; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                            
                                            <!-- Status Steps -->
                                            <div class="status-steps">
                                                <div class="step <?php echo $progress >= 25 ? 'completed' : ''; ?>">
                                                    <div class="step-icon">
                                                        <i class="fas fa-file-alt"></i>
                                                    </div>
                                                    <div class="step-label">Application Submitted</div>
                                                </div>
                                                <div class="step <?php echo $progress >= 50 ? 'completed' : ''; ?>">
                                                    <div class="step-icon">
                                                        <i class="fas fa-check-circle"></i>
                                                    </div>
                                                    <div class="step-label">Initial Approval</div>
                                                </div>
                                                <div class="step <?php echo $progress >= 100 ? 'completed' : ''; ?>">
                                                    <div class="step-icon">
                                                        <i class="fas fa-user-check"></i>
                                                    </div>
                                                    <div class="step-label">Vetting Complete</div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <a href="application_details.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                    </div>
                    <?php endif; ?>

                    <!-- Application Status Summary -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Application Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <i class="fas fa-user-graduate fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">Student Information</h6>
                                                <p class="mb-0 text-muted">ID: <?php echo htmlspecialchars($user['student_id']); ?></p>
                                                <p class="mb-0 text-muted">Department: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['department']))); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <i class="fas fa-clipboard-list fa-2x text-success"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">Application Status</h6>
                                                <p class="mb-0">
                                                    <span class="badge bg-primary"><?php echo count($student_applications); ?> Application(s)</span>
                                                </p>
                                                <?php if (!empty($student_applications)): ?>
                                                    <p class="mb-0 text-muted small">
                                                        <?php 
                                                        $pending = 0;
                                                        $approved = 0;
                                                        $rejected = 0;
                                                        foreach ($student_applications as $app) {
                                                            if ($app['status'] === 'pending' || $app['vetting_status'] === 'pending') $pending++;
                                                            elseif ($app['status'] === 'approved' && $app['vetting_status'] === 'verified') $approved++;
                                                            elseif ($app['status'] === 'rejected' || $app['vetting_status'] === 'rejected') $rejected++;
                                                        }
                                                        ?>
                                                        <?php if ($pending > 0): ?><span class="text-warning"><?php echo $pending; ?> Pending</span><?php endif; ?>
                                                        <?php if ($approved > 0): ?><span class="text-success ms-2"><?php echo $approved; ?> Approved</span><?php endif; ?>
                                                        <?php if ($rejected > 0): ?><span class="text-danger ms-2"><?php echo $rejected; ?> Rejected</span><?php endif; ?>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="mb-0 text-muted small">No applications submitted yet</p>
                        <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                    </div>

                                <?php if (!empty($student_applications)): ?>
                                <hr>
                        <div class="row">
                                <div class="col-12">
                                        <h6 class="mb-3"><i class="fas fa-list me-2"></i>Quick Overview</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Position</th>
                                                        <th>Applied Date</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($student_applications as $app): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($app['position_name']); ?></strong></td>
                                                            <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                                            <td>
                                                                <?php
                                                                $status = strtolower($app['status']);
                                                                $vetting = strtolower($app['vetting_status']);
                                                                if ($status === 'pending') {
                                                                    echo '<span class="badge bg-secondary">Pending Review</span>';
                                                                } elseif ($status === 'approved' && $vetting === 'pending') {
                                                                    echo '<span class="badge bg-warning">Under Vetting</span>';
                                                                } elseif ($status === 'approved' && $vetting === 'verified') {
                                                                    echo '<span class="badge bg-success">Approved & Verified</span>';
                                                                } elseif ($status === 'rejected' || $vetting === 'rejected') {
                                                                    echo '<span class="badge bg-danger">Rejected</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <a href="application_details.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye me-1"></i>View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <hr>
                                <div class="text-center py-3">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No Applications Yet</h6>
                                    <p class="text-muted mb-3">You haven't submitted any applications for positions yet.</p>
                                    <a href="application.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Apply for a Position
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                                                </div>
                                            </div>

                    <!-- Elections Row -->
                    <div class="row">
                        <!-- Active Elections -->
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-vote-yea me-2"></i>Active Elections</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($active_elections)): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($active_elections as $election): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($election['title']); ?></h6>
                                                        <small class="text-muted">Ends on: <?php echo date('F d, Y \a\t h:i A', strtotime($election['end_date'])); ?></small>
                                                    </div>
                                                    <a href="../Voting & Results/vote.php?id=<?php echo $election['id']; ?>" class="btn btn-success">Vote Now</a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="text-center text-muted p-3">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                                            <p>There are no active elections at the moment.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Elections -->
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-calendar-alt me-2"></i>Upcoming Elections</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($upcoming_elections)): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($upcoming_elections as $election): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($election['title']); ?></h6>
                                                        <small class="text-muted">Starts on: <?php echo date('F d, Y \a\t h:i A', strtotime($election['start_date'])); ?></small>
                                    </div>
                                                    <span class="badge bg-info rounded-pill">Upcoming</span>
                                                </li>
                                <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="text-center text-muted p-3">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                                            <p>No upcoming elections scheduled at the moment. Check back later!</p>
                                        </div>
                            <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add click effects to sidebar links
            const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Application tracking enhancements
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.setProperty('--progress-width', width);
            });

            // Auto-refresh application status every 30 seconds
            let refreshInterval;
            const applicationSection = document.querySelector('.application-tracking-card');
            if (applicationSection) {
                refreshInterval = setInterval(function() {
                    // Show a subtle notification that status is being checked
                    const notification = document.createElement('div');
                    notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
                    notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; max-width: 300px;';
                    notification.innerHTML = `
                        <i class="fas fa-sync-alt me-2"></i>
                        Checking application status...
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(notification);
                    
                    // Remove notification after 2 seconds
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 2000);
                    
                    // Reload the page to get updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                }, 30000); // 30 seconds
            }

            // Add smooth transitions for status changes
            const statusBadges = document.querySelectorAll('.badge');
            statusBadges.forEach(badge => {
                badge.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                });
                
                badge.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Add tooltip functionality for status steps
            const stepIcons = document.querySelectorAll('.step-icon');
            stepIcons.forEach(icon => {
                const step = icon.closest('.step');
                const label = step.querySelector('.step-label').textContent;
                
                icon.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip-custom';
                    tooltip.textContent = label;
                    tooltip.style.cssText = `
                        position: absolute;
                        background: #333;
                        color: white;
                        padding: 5px 10px;
                        border-radius: 5px;
                        font-size: 12px;
                        z-index: 1000;
                        top: -40px;
                        left: 50%;
                        transform: translateX(-50%);
                        white-space: nowrap;
                    `;
                    step.appendChild(tooltip);
                });
                
                icon.addEventListener('mouseleave', function() {
                    const tooltip = step.querySelector('.tooltip-custom');
                    if (tooltip) {
                        tooltip.remove();
                    }
                });
            });
        });
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
</html> 