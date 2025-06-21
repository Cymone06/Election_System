<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['student_db_id']);
$user_info = null;
$profile_pic_path = '';
if ($is_logged_in) {
    $student_db_id = $_SESSION['student_db_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, email, department, student_id, profile_picture FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $profile_pic_path = (!empty($user_info['profile_picture']) && file_exists($user_info['profile_picture'])) ? $user_info['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_info['first_name'] . ' ' . $user_info['last_name']) . '&background=3498db&color=fff&size=128';
}

// Fetch gallery images and descriptions from database
global $conn;
$images = [];
if ($stmt = $conn->prepare("SELECT filename, description, uploaded_at FROM gallery ORDER BY uploaded_at DESC")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
        }
        .navbar {
            background-color: #2c3e50 !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .navbar .navbar-brand, .navbar .nav-link, .navbar .nav-link.active {
            color: #fff !important;
        }
        .navbar .nav-link:hover, .navbar .nav-link.active:hover {
            color: #fff !important;
            text-shadow: 0 1px 4px rgba(44,62,80,0.18);
        }
        .gallery-hero {
            background: linear-gradient(135deg, #3498db 60%, #2c3e50 100%), url('https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;
            color: #fff;
            padding: 3.5rem 0 2.5rem 0;
            text-align: center;
            position: relative;
        }
        .gallery-hero h1 {
            font-weight: 700;
            font-size: 2.7rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 8px rgba(44,62,80,0.18);
        }
        .gallery-hero p {
            font-size: 1.2rem;
            color: #e0eafc;
            margin-bottom: 0;
            text-shadow: 0 1px 4px rgba(44,62,80,0.12);
        }
        .section-title {
            color: #2c3e50;
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
            background-color: #3498db;
            border-radius: 2px;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2rem;
        }
        .gallery-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.08);
            background: #fff;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
            position: relative;
        }
        .gallery-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 8px 24px rgba(44,62,80,0.16);
        }
        .gallery-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            transition: filter 0.3s;
        }
        .gallery-card:hover .gallery-img {
            filter: brightness(0.92) saturate(1.1);
        }
        .gallery-desc {
            color: #34495e;
            font-size: 1.05rem;
            margin-top: 0.5rem;
            min-height: 48px;
        }
        .gallery-date {
            font-size: 0.9rem;
            color: #888;
        }
        .no-images {
            color: #aaa;
            font-size: 1.2rem;
            padding: 2rem 0;
        }
        @media (max-width: 600px) {
            .gallery-img { height: 140px; }
            .gallery-hero h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-vote-yea me-2"></i>
                STVC Election System
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
                        <a class="nav-link" href="news.php"><i class="fas fa-newspaper me-1"></i> News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="positions.php"><i class="fas fa-list me-1"></i> Positions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="gallery.php"><i class="fas fa-images me-1"></i> Gallery</a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
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
    <!-- Hero Section -->
    <div class="gallery-hero">
        <h1><i class="fas fa-images me-2"></i>Election Gallery</h1>
        <p>Explore memorable moments from our student elections, campaigns, and events.</p>
    </div>
    <div class="container py-5">
        <h2 class="text-center section-title">Gallery</h2>
        <div class="gallery-grid">
            <?php if (!empty($images)): ?>
                <?php foreach ($images as $img): ?>
                    <div class="gallery-card">
                        <img src="uploads/gallery/<?php echo htmlspecialchars($img['filename']); ?>" class="gallery-img" alt="Gallery Image">
                        <div class="p-3">
                            <div class="gallery-desc"><?php echo htmlspecialchars($img['description']); ?></div>
                            <div class="gallery-date"><i class="far fa-calendar-alt me-1"></i>Uploaded: <?php echo date('M d, Y', strtotime($img['uploaded_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-images text-center w-100">No gallery images yet.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .footer {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="%232c3e50"></path></svg>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            margin-top: 4rem;
            padding-top: 3rem;
            padding-bottom: 2rem;
        }

        .footer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.95), rgba(52, 152, 219, 0.9));
            z-index: 1;
        }

        .footer .container {
            position: relative;
            z-index: 2;
        }

        .footer h5, .footer h6 {
            color: white;
            font-weight: 600;
        }

        .footer p, .footer a {
            color: rgba(255, 255, 255, 0.8);
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: white;
        }

        .footer .social-links a {
            display: inline-block;
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            transition: all 0.3s ease;
        }

        .footer .social-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .footer {
                text-align: center;
            }
            
            .footer .col-md-4 {
                margin-bottom: 2rem;
            }
        }
    </style> 
</body>
</html> 