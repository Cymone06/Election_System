<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['student_db_id']);
$user_info = null;
$active_election_id = null;
$active_election_title = '';

if ($is_logged_in) {
    // Get user information
    $student_db_id = $_SESSION['student_db_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, email, department, student_id, profile_picture FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $profile_pic_path = (!empty($user_info['profile_picture']) && file_exists($user_info['profile_picture'])) ? $user_info['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_info['first_name'] . ' ' . $user_info['last_name']) . '&background=3498db&color=fff&size=128';
}

// Get active election for the entire page
$stmt = $conn->prepare("SELECT id, title FROM election_periods WHERE status = 'active' AND start_date <= NOW() AND end_date >= NOW() LIMIT 1");
$stmt->execute();
$active_election = $stmt->get_result()->fetch_assoc();
if ($active_election) {
    $active_election_id = $active_election['id'];
    $active_election_title = $active_election['title'];
}
$stmt->close();

// Fetch live results if an election is active
$live_results = [];
if ($active_election_id) {
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
    $stmt->bind_param("i", $active_election_id);
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
    foreach ($results_by_position as $position => $candidates) {
        $total_votes = 0;
        foreach ($candidates as $candidate) {
            $total_votes += $candidate['vote_count'];
        }
        $live_results[$position]['total_votes'] = $total_votes;
        $live_results[$position]['candidates'] = $candidates;
    }
}

// Background images for the live results carousel
$background_images = [
    'https://images.unsplash.com/photo-1557683316-973673baf926?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
    'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1471&q=80',
    'https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
    'https://images.unsplash.com/photo-1509023464722-18d996393ca8?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80'
];

// Fetch the next upcoming election (soonest start_date in the future)
$next_upcoming_election = null;
$next_upcoming_result = $conn->query("SELECT * FROM election_periods WHERE status = 'upcoming' AND start_date > NOW() ORDER BY start_date ASC LIMIT 1");
if ($next_upcoming_result && $next_upcoming_result->num_rows > 0) {
    $next_upcoming_election = $next_upcoming_result->fetch_assoc();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seme Technical and Vocational College - Election System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --text-color: #2c3e50;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            background-color: var(--light-bg);
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: var(--card-shadow);
        }

        .navbar-brand {
            font-weight: 600;
            color: white !important;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            transition: color var(--transition-speed);
        }

        .nav-link:hover {
            color: white !important;
        }

        .user-dropdown {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .user-dropdown:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 0.5rem;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        /* Common Card Styles */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            background: white;
            height: 100%;
            overflow: hidden;
            position: relative;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .card:hover::before {
            transform: translateX(100%);
        }

        .card-body {
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .card-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .card-text {
            color: #34495e;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .card-icon {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 1rem;
            transition: transform 0.3s;
        }

        .card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
        }

        /* Button Styles */
        .btn-primary {
            background-color: #3498db;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            background-color: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-primary:hover::after {
            width: 300px;
            height: 300px;
        }

        /* News Section Styles */
        #news {
            background-color: #f8f9fa;
            padding: 4rem 0;
        }

        .news-marquee-container {
            overflow: hidden;
            position: relative;
            padding: 1rem 0;
        }

        .news-marquee {
            display: flex;
            gap: 1.5rem;
            animation: scroll 30s linear infinite;
        }

        .news-marquee:hover {
            animation-play-state: paused;
        }

        .news-item {
            flex: 0 0 300px;
        }

        .news-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .news-card .card-body {
            padding: 1.5rem;
        }

        .news-card .card-title {
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .news-card .card-text {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .news-card .text-muted {
            font-size: 0.8rem;
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-300px * 3));
            }
        }

        @media (max-width: 768px) {
            .news-item {
                flex: 0 0 250px;
            }
            
            .news-card .card-body {
                padding: 1rem;
            }
        }

        /* Footer Styles */
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 3rem 0;
        }

        .footer h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.8);
        }

        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color var(--transition-speed);
        }

        .footer a:hover {
            color: white;
        }

        /* Animation Classes */
        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }

            .card-title {
                font-size: 1.3rem;
            }

            .news-item {
                flex: 0 0 250px;
            }
        }

        /* Register and Login Section Styles */
        #register-login {
            background-color: #f8f9fa;
            padding: 4rem 0;
        }

        #register-login .section-title {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }

        #register-login .lead {
            color: #666;
            margin-bottom: 2rem;
        }

        #register-login .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        #register-login .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #register-login .btn-outline-primary {
            color: #3498db;
            border-color: #3498db;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        #register-login .btn-outline-primary:hover {
            background-color: #3498db;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .welcome-section {
            min-height: 350px;
            background: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1500&q=80') center center/cover no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 0 40px 0;
        }

        .welcome-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(44, 62, 80, 0.55); /* dark blue overlay */
            z-index: 1;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-section h1 {
            color: #fff;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 3rem;
            text-shadow: 0 2px 8px rgba(44,62,80,0.18);
        }

        .welcome-section .lead {
            color: #e0eafc;
            margin-bottom: 30px;
            font-size: 1.2rem;
            text-shadow: 0 1px 4px rgba(44,62,80,0.12);
        }

        .btn-register {
            background-color: #3498db;
            color: #fff;
            padding: 0.8rem 2.5rem;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 500;
            border: none;
            transition: background 0.3s, transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.08);
        }

        .btn-register:hover {
            background-color: #217dbb;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 6px 18px rgba(52, 152, 219, 0.15);
        }

        .footer-section {
            position: relative;
            background: url('https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=1500&q=80') center center/cover no-repeat;
            color: #fff;
            padding: 2.5rem 0 1.5rem 0;
            margin-top: 40px;
        }

        .footer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(44, 62, 80, 0.7); /* dark blue overlay */
            z-index: 1;
        }

        .footer-content {
            position: relative;
            z-index: 2;
        }

        .footer-section p {
            color: #e0eafc;
            font-size: 1rem;
            margin-bottom: 0;
            text-shadow: 0 1px 4px rgba(44,62,80,0.12);
        }

        /* Live Results Section */
        #live-results {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .live-results-card {
            background: transparent;
            border-radius: 20px;
            box-shadow: none;
            overflow: hidden;
            border: none;
            min-height: 400px;
            display: flex;
        }
        .live-results-carousel {
            position: relative;
            flex: 1;
        }
        .live-results-carousel .carousel-item {
            padding: 2rem;
            opacity: 0;
            transition: opacity 0.6s ease-in-out;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            border-radius: 20px;
            color: white;
            z-index: 1;
        }
        .carousel-item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            border-radius: 20px;
            z-index: -1;
        }
         .live-results-carousel h4, .live-results-carousel strong {
             color: white;
             text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }
        .live-results-carousel .fw-bold.fs-5 {
            color: #f1f1f1;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }
        .live-results-carousel .carousel-item.active {
            opacity: 1;
        }
        .candidate-result-row {
            margin-bottom: 1rem;
        }
        .candidate-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }
        .progress {
            height: 25px;
            border-radius: 15px;
            background-color: rgba(255,255,255,0.3);
        }
        .progress-bar {
            background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%);
            border-radius: 15px;
            font-weight: bold;
        }
        .live-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            background-color: #e74c3c;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
            100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
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
                                <div class="user-avatar" style="padding:0;overflow:hidden;width:36px;height:36px;display:inline-block;vertical-align:middle;">
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

    <!-- Welcome Section -->
    <section id="welcome" class="welcome-section">
        <div class="welcome-overlay"></div>
        <div class="container text-center welcome-content">
            <?php if ($is_logged_in): ?>
                <h1 class="display-4">Welcome back, <?php echo htmlspecialchars($user_info['first_name']); ?>!</h1>
                <p class="lead">Ready to participate in the democratic process? Check your dashboard for active elections.</p>
                <a href="dashboard.php" class="btn btn-register">Go to Dashboard</a>
            <?php else: ?>
                <h1 class="display-4">Welcome to STVC Election System</h1>
                <p class="lead">Exercise your right to vote and shape the future of our institution</p>
                <a href="register.php" class="btn btn-register">Register Now</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Upcoming Election Countdown Section -->
    <?php if ($next_upcoming_election): ?>
        <?php
            $start_time = strtotime($next_upcoming_election['start_date']);
            $end_time = strtotime($next_upcoming_election['end_date']);
            $now = time();
            $diff = $start_time - $now;
            $less_than_24h = $diff > 0 && $diff <= 86400;
        ?>
        <section class="py-4 animate-fadeInUp">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow border-primary" style="background: linear-gradient(90deg, #e3f2fd 60%, #bbdefb 100%);">
                            <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-2 text-primary"><i class="fas fa-hourglass-start me-2"></i>Upcoming Election</h5>
                                    <div class="fw-bold fs-5 mb-1"><?php echo htmlspecialchars($next_upcoming_election['title']); ?></div>
                                    <div class="text-muted">Starts: <?php echo date('M d, Y H:i', $start_time); ?></div>
                                    <div class="text-muted">Ends: <span class="fw-semibold text-danger"><?php echo date('M d, Y H:i', $end_time); ?></span></div>
                                </div>
                                <?php if ($less_than_24h): ?>
                                    <div class="countdown-box text-center mt-3 mt-md-0">
                                        <div class="fw-bold text-secondary mb-1">Election starts in:</div>
                                        <div id="election-countdown" class="display-6 fw-bold text-primary"></div>
                                    </div>
                                    <script>
                                    function startCountdown(targetTime) {
                                        var lastSpoken = null;
                                        var spokenDone = false;
                                        function getKenyanMaleVoice() {
                                            var voices = window.speechSynthesis.getVoices();
                                            // Try to find a Kenyan English male voice
                                            for (var i = 0; i < voices.length; i++) {
                                                if ((voices[i].lang === 'en-KE' || voices[i].lang === 'en-GB' || voices[i].lang === 'en-US') && voices[i].name.toLowerCase().includes('male')) {
                                                    return voices[i];
                                                }
                                            }
                                            // Fallback: any Kenyan English
                                            for (var i = 0; i < voices.length; i++) {
                                                if (voices[i].lang === 'en-KE') return voices[i];
                                            }
                                            // Fallback: any English
                                            for (var i = 0; i < voices.length; i++) {
                                                if (voices[i].lang.startsWith('en')) return voices[i];
                                            }
                                            return null;
                                        }
                                        function updateCountdown() {
                                            var now = new Date().getTime();
                                            var distance = targetTime - now;
                                            if (distance < 0) {
                                                document.getElementById('election-countdown').innerHTML = 'Starting soon...';
                                                clearInterval(timer);
                                                if (!spokenDone) {
                                                    // Optionally, announce that the election has started
                                                    if ('speechSynthesis' in window) {
                                                        var utter = new SpeechSynthesisUtterance('The election has started');
                                                        var voice = getKenyanMaleVoice();
                                                        if (voice) utter.voice = voice;
                                                        utter.rate = 1.0;
                                                        utter.pitch = 0.7;
                                                        window.speechSynthesis.speak(utter);
                                                    }
                                                    spokenDone = true;
                                                }
                                                // Show live results section if hidden
                                                var liveResultsSection = document.getElementById('live-results');
                                                if (liveResultsSection) liveResultsSection.style.display = '';
                                                return;
                                            }
                                            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                                            document.getElementById('election-countdown').innerHTML = hours + 'h ' + minutes + 'm ' + seconds + 's';
                                            // Voice countdown for last 10 seconds
                                            if (distance <= 10000 && distance > 0 && seconds !== lastSpoken) {
                                                if ('speechSynthesis' in window) {
                                                    var utter = new SpeechSynthesisUtterance(seconds.toString());
                                                    var voice = getKenyanMaleVoice();
                                                    if (voice) utter.voice = voice;
                                                    utter.rate = 1.0;
                                                    utter.pitch = 0.7;
                                                    window.speechSynthesis.speak(utter);
                                                    lastSpoken = seconds;
                                                }
                                            }
                                        }
                                        // Wait for voices to be loaded
                                        if ('speechSynthesis' in window && typeof window.speechSynthesis.onvoiceschanged !== 'undefined') {
                                            window.speechSynthesis.onvoiceschanged = function() {
                                                updateCountdown();
                                            };
                                        }
                                        updateCountdown();
                                        var timer = setInterval(updateCountdown, 1000);
                                    }
                                    document.addEventListener('DOMContentLoaded', function() {
                                        var countdownElem = document.getElementById('election-countdown');
                                        if (countdownElem) {
                                            var targetTime = <?php echo isset($start_time) ? ($start_time * 1000) : 'null'; ?>;
                                            if (targetTime) startCountdown(targetTime);
                                        }
                                        // Hide live results section if election hasn't started
                                        var liveResultsSection = document.getElementById('live-results');
                                        if (liveResultsSection && <?php echo isset($next_upcoming_election) && $next_upcoming_election ? 'true' : 'false'; ?>) {
                                            liveResultsSection.style.display = 'none';
                                        }
                                    });
                                    </script>
                                <?php else: ?>
                                    <div class="text-info fw-bold fs-6 mt-3 mt-md-0">Election starts in more than 24 hours.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Active Election Ending Countdown Section -->
    <?php if ($active_election): ?>
        <?php
            $end_time = strtotime($active_election['end_date']);
            $now = time();
            $time_left = $end_time - $now;
            $less_than_10min = $time_left > 0 && $time_left <= 600;
        ?>
        <?php if ($less_than_10min): ?>
        <section class="py-4 animate-fadeInUp">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow border-danger" style="background: linear-gradient(90deg, #fff3e0 60%, #ffccbc 100%);">
                            <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-2 text-danger"><i class="fas fa-hourglass-end me-2"></i>Election Ending Soon</h5>
                                    <div class="fw-bold fs-5 mb-1"><?php echo htmlspecialchars($active_election['title']); ?></div>
                                    <div class="text-muted">Ends: <span class="fw-semibold text-danger"><?php echo date('M d, Y H:i', $end_time); ?></span></div>
                                </div>
                                <div class="countdown-box text-center mt-3 mt-md-0">
                                    <div class="fw-bold text-danger mb-1">Election ends in:</div>
                                    <div id="election-end-countdown" class="display-6 fw-bold text-danger"></div>
                                </div>
                                <script>
                                function startEndCountdown(targetTime) {
                                    var lastSpokenEnd = null;
                                    function updateCountdown() {
                                        var now = new Date().getTime();
                                        var distance = targetTime - now;
                                        if (distance < 0) {
                                            document.getElementById('election-end-countdown').innerHTML = 'Election Over';
                                            clearInterval(timer);
                                            return;
                                        }
                                        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                                        document.getElementById('election-end-countdown').innerHTML = minutes + 'm ' + seconds + 's';
                                        // Voice countdown for last 10 seconds
                                        if (distance <= 10000 && distance > 0 && seconds !== lastSpokenEnd) {
                                            if ('speechSynthesis' in window) {
                                                var utter = new SpeechSynthesisUtterance(seconds.toString());
                                                utter.rate = 1.1;
                                                utter.pitch = 1.2;
                                                window.speechSynthesis.speak(utter);
                                                lastSpokenEnd = seconds;
                                            }
                                        }
                                    }
                                    updateCountdown();
                                    var timer = setInterval(updateCountdown, 1000);
                                }
                                document.addEventListener('DOMContentLoaded', function() {
                                    var endCountdownElem = document.getElementById('election-end-countdown');
                                    if (endCountdownElem) {
                                        var targetTime = <?php echo isset($end_time) ? ($end_time * 1000) : 'null'; ?>;
                                        if (targetTime) startEndCountdown(targetTime);
                                    }
                                });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Live Results Section -->
    <?php if ($active_election_id && !empty($live_results)): ?>
    <section id="live-results" class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title"><span class="live-dot me-2"></span>Live Election Results</h2>
                <p class="lead text-muted">Showing results for: <strong><?php echo htmlspecialchars($active_election_title); ?></strong></p>
            </div>
            <div class="card live-results-card">
                <div id="live-results-carousel" class="live-results-carousel">
                    <?php 
                    $is_first = true;
                    $bg_index = 0;
                    foreach ($live_results as $position => $data): 
                    $bg_image_url = $background_images[$bg_index % count($background_images)];
                    ?>
                        <div class="carousel-item <?php if ($is_first) { echo 'active'; $is_first = false; } ?>" data-position="<?php echo htmlspecialchars($position); ?>" style="background-image: url('<?php echo $bg_image_url; ?>');">
                            <div class="carousel-item-overlay"></div>
                            <h4 class="text-center mb-4"><?php echo htmlspecialchars($position); ?></h4>
                            <?php foreach ($data['candidates'] as $candidate): 
                                $percentage = ($data['total_votes'] > 0) ? ($candidate['vote_count'] / $data['total_votes']) * 100 : 0;
                            ?>
                                <div class="candidate-result-row align-items-center row mb-3">
                                    <div class="col-auto">
                                        <img src="uploads/applications/<?php echo htmlspecialchars($candidate['image1']); ?>" alt="<?php echo htmlspecialchars($candidate['first_name']); ?>" class="candidate-avatar">
                                    </div>
                                    <div class="col">
                                        <strong><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></strong>
                                        <div class="progress mt-1">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo round($percentage); ?>%</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <span class="fw-bold fs-5"><?php echo $candidate['vote_count']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php 
                    $bg_index++;
                    endforeach; 
                    ?>
                </div>
            </div>
             <div class="text-center mt-4">
                <a href="../Voting & Results/live_results.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-chart-line me-2"></i>View Full Live Results
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- News Section -->
    <section id="news" class="py-5">
        <div class="container">
            <h2 class="text-center section-title" data-aos="fade-up">Latest Updates</h2>
            <div class="news-marquee-container">
                <div class="news-marquee">
                    <div class="news-item">
                        <div class="card news-card">
                        <div class="card-body">
                            <h5 class="card-title">Election Schedule Released</h5>
                            <p class="card-text">Important dates and deadlines for the upcoming elections have been announced.</p>
                            <small class="text-muted">Posted: 2 hours ago</small>
                                <a href="updates.php?id=1" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <div class="news-item">
                        <div class="card news-card">
                            <div class="card-body">
                                <h5 class="card-title">Candidate Registration Open</h5>
                                <p class="card-text">Students interested in running for positions can now submit their applications.</p>
                                <small class="text-muted">Posted: 1 day ago</small>
                                <a href="updates.php?id=2" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <div class="news-item">
                        <div class="card news-card">
                            <div class="card-body">
                                <h5 class="card-title">Voter Education Program</h5>
                                <p class="card-text">Learn about the voting process and your rights as a voter.</p>
                                <small class="text-muted">Posted: 2 days ago</small>
                                <a href="updates.php?id=3" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <!-- Duplicate items for continuous scrolling -->
                    <div class="news-item">
                        <div class="card news-card">
                            <div class="card-body">
                                <h5 class="card-title">Election Schedule Released</h5>
                                <p class="card-text">Important dates and deadlines for the upcoming elections have been announced.</p>
                                <small class="text-muted">Posted: 2 hours ago</small>
                                <a href="updates.php?id=1" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
                    <div class="news-item">
                        <div class="card news-card">
                        <div class="card-body">
                            <h5 class="card-title">Candidate Registration Open</h5>
                            <p class="card-text">Students interested in running for positions can now submit their applications.</p>
                            <small class="text-muted">Posted: 1 day ago</small>
                                <a href="updates.php?id=2" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <div class="news-item">
                        <div class="card news-card">
                        <div class="card-body">
                            <h5 class="card-title">Voter Education Program</h5>
                            <p class="card-text">Learn about the voting process and your rights as a voter.</p>
                            <small class="text-muted">Posted: 2 days ago</small>
                                <a href="updates.php?id=3" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Election Details Section -->
    <section id="elections" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title" data-aos="fade-up">
                <?php if ($is_logged_in): ?>
                    Quick Actions
                <?php else: ?>
                    Election Details
                <?php endif; ?>
            </h2>
            <div class="row">
                <?php if ($is_logged_in): ?>
                    <!-- Logged in user actions -->
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-tachometer-alt fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">Dashboard</h3>
                                <p class="card-text">Access your personalized dashboard with active elections and voting options.</p>
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-2"></i>Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-edit fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">Apply for Position</h3>
                                <p class="card-text">Submit your application to run for a student government position.</p>
                                <a href="application.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-2"></i>Apply Now
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-list fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">View Positions</h3>
                                <p class="card-text">Explore available positions and their requirements.</p>
                                <a href="positions.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-2"></i>View Positions
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="350">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">View Applicants</h3>
                                <p class="card-text">See all vetted and approved candidates for the current election.</p>
                                <a href="applicants.php" class="btn btn-primary">
                                    <i class="fas fa-users me-2"></i>View Applicants
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="400">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-vote-yea fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">Cast Your Vote</h3>
                                <p class="card-text">Participate in active elections and vote for your preferred candidates.</p>
                                <a href="../Voting & Results/vote.php?id=<?php echo $active_election_id; ?>" class="btn btn-primary <?php if (!$active_election_id) { echo 'disabled'; } ?>">
                                    <i class="fas fa-arrow-right me-2"></i>Vote Now
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="500">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-newspaper fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">News & Updates</h3>
                                <p class="card-text">Stay informed with the latest election news and announcements.</p>
                                <a href="news.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-2"></i>Read News
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="600">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">View Results</h3>
                                <p class="card-text">Check the latest election results and statistics.</p>
                                <a href="../Voting & Results/results.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-2"></i>See Results
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="375">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">View Current Leaders</h3>
                                <p class="card-text">See the current leaders of the institution and their roles.</p>
                                <a href="current_candidates.php" class="btn btn-primary">
                                    <i class="fas fa-users me-2"></i>View Current Leaders
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Non-logged in user content -->
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-tie fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">View Positions</h3>
                                <p class="card-text">Explore available positions in the student government and find the perfect role for you.</p>
                                <a href="positions.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-2"></i>View Positions
                                </a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card election-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Meet the Contenders</h5>
                            <p class="card-text">Learn about the candidates running for various positions.</p>
                            <a href="applicants.php" class="btn btn-primary">View Candidates</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="card election-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Election Schedule</h5>
                            <p class="card-text">Important dates and deadlines for the election process.</p>
                            <a href="#" class="btn btn-primary">View Schedule</a>
                        </div>
                    </div>
                </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-vote-yea fa-3x mb-3 text-primary"></i>
                            <h3 class="card-title">Cast Your Vote</h3>
                                <p class="card-text">Participate in the democratic process by voting for your preferred candidates.</p>
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Vote
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">View Results</h3>
                                <p class="card-text">Stay updated with the latest election results and statistics.</p>
                                <a href="../Voting & Results/results.php" class="btn btn-primary">
                                    <i class="fas fa-chart-line me-2"></i>See Results
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="600">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-user-plus fa-3x mb-3 text-primary"></i>
                                <h3 class="card-title">Register</h3>
                                <p class="card-text">Create your account to participate in the election system.</p>
                                <a href="register.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Register Now
                                </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- View Applicants Section -->
    <section id="applicants" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title" data-aos="fade-up">View Applicants</h2>
            <?php
            $applicants_by_position = [];
            $sql = "SELECT a.id, a.first_name, a.last_name, a.image1, a.status, a.vetting_status, p.position_name 
                    FROM applications a 
                    JOIN positions p ON a.position_id = p.id 
                    WHERE a.status != 'rejected' AND a.vetting_status != 'rejected'
                    ORDER BY p.position_name, a.created_at DESC";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $applicants_by_position[$row['position_name']][] = $row;
                }
            }
            ?>
            <?php if (!empty($applicants_by_position)): ?>
                <?php 
                // Limit to 3 positions
                $positions_shown = 0;
                ?>
                <?php foreach ($applicants_by_position as $position => $applicants): ?>
                    <?php if ($positions_shown >= 3) break; ?>
                    <div class="mb-4">
                        <h4 class="mb-3"><i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($position); ?></h4>
                        <div class="d-flex flex-row flex-wrap gap-3 justify-content-center">
                            <?php foreach ($applicants as $applicant): ?>
                                <div class="card text-center p-2" style="width: 260px; box-shadow: 0 4px 12px rgba(44,62,80,0.10);">
                                    <img src="uploads/applications/<?php echo htmlspecialchars($applicant['image1']); ?>" class="candidate-img-marquee mt-3 mx-auto" alt="Applicant Image" style="width:90px;height:90px;object-fit:cover;border-radius:50%;">
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></h6>
                                        <?php
                                        $status = strtolower($applicant['status']);
                                        $vetting = strtolower($applicant['vetting_status']);
                                        if ($status === 'pending') {
                                            echo '<span class="badge bg-secondary badge-status">Pending Approval</span>';
                                        } elseif ($status === 'approved' && $vetting === 'pending') {
                                            echo '<span class="badge bg-warning badge-status">Waiting Vetting Approval</span>';
                                        } elseif ($status === 'approved' && $vetting === 'verified') {
                                            echo '<span class="badge bg-success badge-status">Approved</span>';
                                        } elseif ($status === 'rejected' || $vetting === 'rejected') {
                                            echo '<span class="badge bg-danger badge-status">Rejected</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary badge-status">' . ucfirst($status) . '</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $positions_shown++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted">No applicants found.</div>
            <?php endif; ?>
            <div class="text-center mt-3">
                <?php if ($is_logged_in && isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['admin', 'super_admin'])): ?>
                    <a href="Admin/manage_applications.php" class="btn btn-primary"><i class="fas fa-cogs me-2"></i>Manage Applications</a>
                <?php endif; ?>
                <a href="applicants.php" class="btn btn-outline-primary ms-2"><i class="fas fa-eye me-2"></i>View All Applicants</a>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="py-5 bg-white">
        <div class="container">
            <h2 class="text-center section-title" data-aos="fade-up">Gallery</h2>
            <div class="row justify-content-center g-4">
                <?php
                // Display up to 3 gallery images as a preview
                $gallery_images = [];
                $stmt = $conn->prepare("SELECT filename, description, uploaded_at FROM gallery ORDER BY uploaded_at DESC LIMIT 3");
                if ($stmt) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $gallery_images[] = $row;
                    }
                    $stmt->close();
                }
                ?>
                <?php if (!empty($gallery_images)): ?>
                    <?php foreach ($gallery_images as $i => $img): ?>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card h-100 shadow-sm border-0 gallery-card position-relative" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#galleryModal" data-img="uploads/gallery/<?php echo htmlspecialchars($img['filename']); ?>" data-desc="<?php echo htmlspecialchars($img['description']); ?>" data-date="<?php echo date('M d, Y', strtotime($img['uploaded_at'])); ?>">
                                <div class="gallery-img-wrapper position-relative">
                                    <img src="uploads/gallery/<?php echo htmlspecialchars($img['filename']); ?>" class="card-img-top gallery-img" alt="Gallery Image" style="height:180px;object-fit:cover;">
                                    <div class="gallery-overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background:rgba(44,62,80,0.65);color:#fff;opacity:0;transition:opacity 0.3s;">
                                        <i class="fas fa-search-plus fa-2x mb-2"></i>
                                        <span>View</span>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <div class="gallery-desc text-muted small mb-1 text-truncate" title="<?php echo htmlspecialchars($img['description']); ?>"><?php echo htmlspecialchars($img['description']); ?></div>
                                    <div class="gallery-date text-secondary" style="font-size:0.85em;"><i class="far fa-calendar-alt me-1"></i><?php echo date('M d, Y', strtotime($img['uploaded_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-muted">No gallery images yet.</div>
                <?php endif; ?>
            </div>
            <div class="text-center mt-3">
                <a href="gallery.php" class="btn btn-primary"><i class="fas fa-images me-2"></i>View Full Gallery</a>
            </div>
            <?php if ($is_logged_in && isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['admin', 'super_admin'])): ?>
                <div class="text-center mt-4">
                    <a href="Admin/manage_gallery.php" class="btn btn-outline-primary"><i class="fas fa-cog me-2"></i>Manage Gallery</a>
                </div>
            <?php endif; ?>
        </div>
        <!-- Gallery Modal -->
        <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="galleryModalLabel">Gallery Image</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="" id="galleryModalImg" class="img-fluid rounded mb-3" alt="Gallery Image">
                        <div id="galleryModalDesc" class="mb-2 text-muted"></div>
                        <div id="galleryModalDate" class="text-secondary small"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- About Us Section -->
    <section id="about-us" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title" data-aos="fade-up">About Us</h2>
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <p class="lead mb-4">The STVC Election System is dedicated to promoting transparency, fairness, and active participation in student leadership. Our platform empowers students to engage in the democratic process, ensuring every voice is heard and every vote counts. Learn more about our mission, vision, and the team behind the system.</p>
                    <a href="about_us.php" class="btn btn-primary"><i class="fas fa-info-circle me-2"></i>View More</a>
                </div>
            </div>
        </div>
    </section>
    <script>
    // Gallery modal logic
    document.addEventListener('DOMContentLoaded', function() {
        var galleryModal = document.getElementById('galleryModal');
        var galleryImg = document.getElementById('galleryModalImg');
        var galleryDesc = document.getElementById('galleryModalDesc');
        var galleryDate = document.getElementById('galleryModalDate');
        document.querySelectorAll('.gallery-card').forEach(function(card) {
            card.addEventListener('click', function() {
                galleryImg.src = card.getAttribute('data-img');
                galleryDesc.textContent = card.getAttribute('data-desc');
                galleryDate.textContent = card.getAttribute('data-date');
            });
            card.querySelector('.gallery-img-wrapper').addEventListener('mouseenter', function() {
                card.querySelector('.gallery-overlay').style.opacity = 1;
            });
            card.querySelector('.gallery-img-wrapper').addEventListener('mouseleave', function() {
                card.querySelector('.gallery-overlay').style.opacity = 0;
            });
        });
    });
    </script>
    <style>
    .gallery-card { transition: box-shadow 0.3s, transform 0.3s; border-radius: 16px; }
    .gallery-card:hover { box-shadow: 0 8px 24px rgba(44,62,80,0.18); transform: translateY(-4px) scale(1.03); }
    .gallery-img-wrapper { position: relative; overflow: hidden; border-radius: 12px 12px 0 0; }
    .gallery-img { border-radius: 12px 12px 0 0; transition: transform 0.3s; }
    .gallery-card:hover .gallery-img { transform: scale(1.05); }
    .gallery-overlay { opacity: 0; transition: opacity 0.3s; border-radius: 12px 12px 0 0; }
    .gallery-card:hover .gallery-overlay { opacity: 1; }
    </style>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // Live Results Carousel
        const carouselContainer = document.getElementById('live-results-carousel');
        if (carouselContainer) {
            const items = carouselContainer.querySelectorAll('.carousel-item');
            let currentIndex = 0;
            
            function showNextItem() {
                items[currentIndex].classList.remove('active');
                currentIndex = (currentIndex + 1) % items.length;
                items[currentIndex].classList.add('active');
            }
            
            setInterval(showNextItem, 5000); // Switch every 5 seconds
        }
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
</html> 