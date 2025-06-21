<?php
require_once 'config/session_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/css/style.css">
    <style>
        .about-hero {
            background: linear-gradient(90deg, #33506a 0%, #3498db 100%);
            color: #fff;
            padding: 70px 0 50px 0;
            text-align: center;
        }
        .about-hero h1 {
            font-weight: 700;
            font-size: 2.8rem;
        }
        .about-hero p {
            font-size: 1.2rem;
            color: #e0eafc;
        }
        .about-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.08);
            padding: 2.5rem 2rem;
            margin-bottom: 2.5rem;
        }
        .about-icon {
            font-size: 2.2rem;
            color: #3498db;
            margin-bottom: 0.7rem;
        }
        .about-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .about-value-list li {
            margin-bottom: 0.5rem;
        }
        .about-team-img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #3498db;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #2c3e50;">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-vote-yea me-2"></i>STVC Election System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="news.php">News</a></li>
                    <li class="nav-item"><a class="nav-link" href="positions.php">Positions</a></li>
                    <li class="nav-item"><a class="nav-link" href="current_candidates.php">Current Leaders</a></li>
                    <li class="nav-item"><a class="nav-link" href="applicants.php">Applicants</a></li>
                    <li class="nav-item"><a class="nav-link" href="gallery.php">Gallery</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <h1>About STVC Election System</h1>
            <p class="lead">Empowering students. Ensuring transparency. Building future leaders.</p>
        </div>
    </section>
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="about-section mb-4">
                        <div class="text-center">
                            <i class="fas fa-bullseye about-icon"></i>
                            <h3 class="about-title">Our Mission</h3>
                        </div>
                        <p class="text-center">To provide a secure, transparent, and user-friendly platform for student elections, fostering democratic values and leadership development within the STVC community.</p>
                    </div>
                    <div class="about-section mb-4">
                        <div class="text-center">
                            <i class="fas fa-eye about-icon"></i>
                            <h3 class="about-title">Our Vision</h3>
                        </div>
                        <p class="text-center">To be a model for digital democracy in educational institutions, inspiring trust and active participation in student governance.</p>
                    </div>
                    <div class="about-section mb-4">
                        <div class="text-center">
                            <i class="fas fa-heart about-icon"></i>
                            <h3 class="about-title">Our Values</h3>
                        </div>
                        <ul class="about-value-list list-unstyled text-center">
                            <li><strong>Transparency:</strong> Open processes and clear communication.</li>
                            <li><strong>Integrity:</strong> Upholding fairness and honesty in all activities.</li>
                            <li><strong>Inclusivity:</strong> Ensuring every student has a voice and opportunity.</li>
                            <li><strong>Innovation:</strong> Leveraging technology for better engagement.</li>
                            <li><strong>Empowerment:</strong> Supporting student leadership and growth.</li>
                        </ul>
                    </div>
                    <div class="about-section mb-4">
                        <div class="text-center">
                            <i class="fas fa-users about-icon"></i>
                            <h3 class="about-title">Our Team</h3>
                        </div>
                        <div class="row justify-content-center g-4">
                            <div class="col-md-4 text-center">
                                <img src="https://ui-avatars.com/api/?name=System+Admin&background=3498db&color=fff&size=128" class="about-team-img mb-2" alt="System Admin">
                                <h6 class="mb-0">System Admin</h6>
                                <small class="text-muted">Lead Developer</small>
                            </div>
                            <div class="col-md-4 text-center">
                                <img src="https://ui-avatars.com/api/?name=Support+Team&background=3498db&color=fff&size=128" class="about-team-img mb-2" alt="Support Team">
                                <h6 class="mb-0">Support Team</h6>
                                <small class="text-muted">Technical & User Support</small>
                            </div>
                            <div class="col-md-4 text-center">
                                <img src="https://ui-avatars.com/api/?name=Student+Leaders&background=3498db&color=fff&size=128" class="about-team-img mb-2" alt="Student Leaders">
                                <h6 class="mb-0">Student Leaders</h6>
                                <small class="text-muted">Election Committee</small>
                            </div>
                        </div>
                    </div>
                    <div class="about-section mb-4">
                        <div class="text-center">
                            <i class="fas fa-envelope about-icon"></i>
                            <h3 class="about-title">Contact Us</h3>
                        </div>
                        <p class="text-center mb-1">Have questions, feedback, or need support? Reach out to us:</p>
                        <div class="text-center">
                            <a href="mailto:support@stvc.edu" class="btn btn-outline-primary"><i class="fas fa-envelope me-2"></i>support@stvc.edu</a>
                        </div>
                    </div>
                    <div class="about-section mb-4">
                        <div class="text-center">
                            <i class="fas fa-comments about-icon"></i>
                            <h3 class="about-title">Student Reviews</h3>
                        </div>
                        <p class="text-center mb-3">Share your experience with the STVC Election System! Reviews are visible after admin approval and are auto-deleted after 20 days.</p>
                        <form method="POST" class="mb-4" action="">
                            <div class="row g-3 align-items-center justify-content-center">
                                <div class="col-md-4">
                                    <input type="text" name="student_name" class="form-control" placeholder="Your Name" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="student_id" class="form-control" placeholder="Student ID" required>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="content" class="form-control" placeholder="Your Review" required>
                                </div>
                                <div class="col-12 text-center mt-2">
                                    <button type="submit" name="submit_review" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Post Review</button>
                                </div>
                            </div>
                        </form>
                        <?php
                        require_once 'config/connect.php';
                        // Handle review submission
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
                            $name = trim($_POST['student_name']);
                            $sid = trim($_POST['student_id']);
                            $content = trim($_POST['content']);
                            if ($name && $sid && $content) {
                                $stmt = $conn->prepare("INSERT INTO reviews (student_name, student_id, content) VALUES (?, ?, ?)");
                                $stmt->bind_param('sss', $name, $sid, $content);
                                $stmt->execute();
                                $stmt->close();
                                echo '<div class="alert alert-success text-center">Thank you! Your review will be visible after admin approval.</div>';
                            }
                        }
                        // Auto-delete reviews older than 20 days
                        $conn->query("DELETE FROM reviews WHERE created_at < (NOW() - INTERVAL 20 DAY)");
                        // Show approved reviews
                        $result = $conn->query("SELECT student_name, content, created_at FROM reviews WHERE status = 'approved' ORDER BY created_at DESC");
                        if ($result && $result->num_rows > 0): ?>
                            <div class="mt-4">
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <div class="border rounded p-3 mb-3 bg-light shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-primary"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                            <span class="text-muted small"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                                        </div>
                                        <div class="text-secondary"><?php echo htmlspecialchars($row['content']); ?></div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">No reviews yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 