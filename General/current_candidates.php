<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['student_db_id']);
$user_info = null;
if ($is_logged_in) {
    $student_db_id = $_SESSION['student_db_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, email, department, student_id, profile_picture FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $profile_pic_path = (!empty($user_info['profile_picture']) && file_exists($user_info['profile_picture'])) ? $user_info['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_info['first_name'] . ' ' . $user_info['last_name']) . '&background=3498db&color=fff&size=128';
}

// Fetch current leaders ordered by hierarchy
$current_candidates = [];
$sql = "SELECT * FROM current_candidates ORDER BY hierarchy_order ASC, created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $current_candidates[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Leaders - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/css/style.css">
    <style>
        .leaders-section {
            background: var(--light-bg);
            padding: 3rem 0 4rem 0;
        }
        .leaders-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 2.5rem 2rem;
            justify-content: center;
        }
        .leader-card {
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.10);
            background: #fff;
            transition: box-shadow 0.3s, transform 0.3s;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .leader-card:hover {
            box-shadow: 0 8px 32px rgba(44,62,80,0.16);
            transform: translateY(-4px) scale(1.03);
        }
        .leader-img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            border-radius: 18px 18px 0 0;
        }
        .leader-card-body {
            padding: 1.5rem 1.2rem 1.2rem 1.2rem;
            width: 100%;
            text-align: center;
        }
        .leader-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .leader-label {
            font-weight: 500;
            color: var(--secondary-color);
        }
        .leader-value {
            font-weight: 600;
            color: var(--accent-color);
        }
        .leader-meta {
            font-size: 0.98rem;
            margin-bottom: 0.3rem;
        }
        .leader-date {
            font-size: 0.93rem;
            color: #888;
        }
        @media (max-width: 768px) {
            .leaders-grid { gap: 2rem 0.5rem; }
            .leader-card { max-width: 95vw; }
            .leader-img { height: 180px; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #2c3e50 !important;">
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
                        <a class="nav-link active" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="news.php"><i class="fas fa-newspaper me-1"></i> News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#elections"><i class="fas fa-poll me-1"></i> Elections</a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="positions.php"><i class="fas fa-list me-1"></i> Positions</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-dropdown" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar" style="padding:0;overflow:hidden;width:36px;height:36px;">
                                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                </div>
                                <?php echo htmlspecialchars($user_info['first_name']); ?>
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
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#vote"><i class="fas fa-vote-yea me-1"></i> Vote</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#results"><i class="fas fa-chart-bar me-1"></i> Results</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header Section (blue gradient background) -->
    <section class="py-5" style="background: linear-gradient(90deg, #33506a 0%, #3498db 100%); box-shadow: 0 -2px 8px rgba(44,62,80,0.10) inset;">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-3" style="color: #fff;"><i class="fas fa-users me-2"></i>Current Leaders</h1>
            <p class="lead" style="color: #e0eafc; font-size: 1.2rem;">Meet the current leaders of STVC. These individuals are entrusted with guiding the institution and representing the student body. Their reign start date is shown below each profile.</p>
        </div>
    </section>
    <section class="leaders-section">
        <div class="container">
            <div class="leaders-grid">
                <?php if (!empty($current_candidates)): ?>
                    <?php foreach ($current_candidates as $candidate): ?>
                        <div class="leader-card animate-fadeInUp">
                            <img src="uploads/current_candidates/<?php echo htmlspecialchars($candidate['image']); ?>" class="leader-img" alt="Leader Image">
                            <div class="leader-card-body">
                                <div class="leader-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                                <div class="leader-meta"><span class="leader-label">Position:</span> <span class="leader-value"><?php echo htmlspecialchars($candidate['position']); ?></span></div>
                                <div class="leader-meta"><span class="leader-label">Department:</span> <span class="leader-value"><?php echo htmlspecialchars($candidate['department']); ?></span></div>
                                <div class="leader-meta"><span class="leader-label">Hierarchy:</span> <?php echo (int)$candidate['hierarchy_order']; ?></div>
                                <div class="leader-date">Reign Started: <?php echo date('M d, Y', strtotime($candidate['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-muted">No current leaders available.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Debug: Show computed --primary-color value -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var rootStyles = getComputedStyle(document.documentElement);
        var primaryColor = rootStyles.getPropertyValue('--primary-color');
        console.log('DEBUG: Computed --primary-color is', primaryColor);
        var debugDiv = document.createElement('div');
        debugDiv.style.position = 'fixed';
        debugDiv.style.bottom = '10px';
        debugDiv.style.right = '10px';
        debugDiv.style.background = '#fff';
        debugDiv.style.color = '#2c3e50';
        debugDiv.style.padding = '8px 16px';
        debugDiv.style.border = '1px solid #2c3e50';
        debugDiv.style.zIndex = 9999;
        debugDiv.style.fontSize = '14px';
        debugDiv.innerText = 'DEBUG: --primary-color = ' + primaryColor;
        document.body.appendChild(debugDiv);
        setTimeout(function() { debugDiv.remove(); }, 8000);
    });
    </script>
</body>
</html> 