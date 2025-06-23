<?php
require_once 'config/session_config.php';
require_once 'config/database.php';

// Fetch all active positions
$stmt = $conn->prepare("
    SELECT p.*, 
           COUNT(a.id) as applicant_count 
    FROM positions p 
    LEFT JOIN applications a ON p.id = a.position_id 
    WHERE p.status = 'active' 
    GROUP BY p.id 
    ORDER BY p.position_name
");
$stmt->execute();
$positions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (isset($_SESSION['student_db_id'])) {
    $student_db_id = $_SESSION['student_db_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, profile_picture FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $profile_pic_path = (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) ? $user['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=3498db&color=fff&size=128';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Positions - STVC Election System</title>
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

        .user-info {
            display: flex;
            align-items: center;
            color: white;
            margin-right: 15px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
            color: white;
        }

        .user-name {
            font-size: 0.9rem;
            margin-right: 10px;
        }

        .positions-section {
            padding: 80px 0;
        }

        .position-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .position-card.animate {
            opacity: 1;
            transform: translateY(0);
        }

        .position-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .position-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            position: relative;
            overflow: hidden;
        }

        .position-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        .position-card:hover .position-header::after {
            left: 100%;
        }

        .position-body {
            padding: 25px;
        }

        .position-footer {
            background-color: #f8f9fa;
            padding: 15px 25px;
            border-radius: 0 0 10px 10px;
            border-top: 1px solid #dee2e6;
        }

        .requirements-list {
            list-style: none;
            padding-left: 0;
        }

        .requirements-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .requirements-list li:hover {
            padding-left: 10px;
            background-color: #f8f9fa;
        }

        .requirements-list li:last-child {
            border-bottom: none;
        }

        .requirements-list i {
            color: var(--secondary-color);
            margin-right: 10px;
            transition: transform 0.3s ease;
        }

        .requirements-list li:hover i {
            transform: scale(1.2);
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            padding: 10px 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
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

        .applicant-count {
            background-color: var(--secondary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .position-card:hover .applicant-count {
            transform: scale(1.05);
        }

        .position-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--secondary-color);
            transition: all 0.3s ease;
        }

        .position-card:hover .position-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .section-title {
            color: var(--primary-color);
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: var(--secondary-color);
            transition: width 0.3s ease;
        }

        .section-title:hover::after {
            width: 100px;
        }

        .apply-now-btn {
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .apply-now-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
            z-index: -1;
        }

        .apply-now-btn:hover::before {
            left: 100%;
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

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease forwards;
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
                        <a class="nav-link active" href="positions.php"><i class="fas fa-list me-1"></i> Positions</a>
                    </li>
                    <?php if (isset($_SESSION['student_db_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="application.php"><i class="fas fa-edit me-1"></i> Apply</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-dropdown" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar" style="padding:0;overflow:hidden;width:36px;height:36px;display:inline-block;vertical-align:middle;">
                                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                </div>
                                <?php echo htmlspecialchars($user['first_name']); ?>
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
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Positions Section -->
    <section class="positions-section">
        <div class="container">
            <h2 class="section-title animate-fadeInUp">Available Positions</h2>
            <div class="row">
                <?php foreach ($positions as $index => $position): ?>
                    <div class="col-lg-6">
                        <div class="position-card" style="animation-delay: <?php echo $index * 0.2; ?>s">
                            <div class="position-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="mb-0"><?php echo htmlspecialchars($position['position_name']); ?></h3>
                                    <span class="applicant-count">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo $position['applicant_count']; ?> Applicants
                                    </span>
                                </div>
                            </div>
                            <div class="position-body">
                                <div class="text-center mb-4">
                                    <i class="fas fa-user-tie position-icon"></i>
                                </div>
                                <p class="mb-4"><?php echo nl2br(htmlspecialchars($position['description'])); ?></p>
                                
                                <?php if (!empty($position['requirements'])): ?>
                                    <h5 class="mb-3">Requirements:</h5>
                                    <ul class="requirements-list">
                                        <?php 
                                        $requirements = explode("\n", $position['requirements']);
                                        foreach ($requirements as $requirement):
                                            if (trim($requirement)):
                                        ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i>
                                                <?php echo htmlspecialchars(trim($requirement)); ?>
                                            </li>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if (!empty($position['responsibilities'])): ?>
                                    <h5 class="mb-3 mt-4">Responsibilities:</h5>
                                    <ul class="requirements-list">
                                        <?php 
                                        $responsibilities = explode("\n", $position['responsibilities']);
                                        foreach ($responsibilities as $responsibility):
                                            if (trim($responsibility)):
                                        ?>
                                            <li>
                                                <i class="fas fa-tasks"></i>
                                                <?php echo htmlspecialchars(trim($responsibility)); ?>
                                            </li>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <div class="position-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Posted: <?php echo date('M d, Y', strtotime($position['created_at'])); ?>
                                    </small>
                                    <a href="application.php?position=<?php echo $position['id']; ?>" class="btn btn-primary apply-now-btn">
                                        <i class="fas fa-paper-plane me-2"></i>Apply Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animate cards on scroll
        function animateCards() {
            const cards = document.querySelectorAll('.position-card');
            cards.forEach(card => {
                const cardTop = card.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                if (cardTop < windowHeight - 100) {
                    card.classList.add('animate');
                }
            });
        }

        // Initial animation
        window.addEventListener('load', animateCards);
        // Animate on scroll
        window.addEventListener('scroll', animateCards);
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
</html> 