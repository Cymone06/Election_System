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
        .star-rating {
            direction: rtl;
            display: inline-flex;
            font-size: 1.5rem;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input[type="radio"]:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #FFD600;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #2c3e50;">
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
                        <?php
                        $is_logged_in = isset($_SESSION['student_db_id']) && is_numeric($_SESSION['student_db_id']);
                        $student_name = '';
                        $student_id = '';
                        if ($is_logged_in) {
                            require_once 'config/connect.php';
                            $student_db_id = (int)$_SESSION['student_db_id'];
                            $stmt = $conn->prepare('SELECT first_name, last_name, student_id FROM students WHERE id = ?');
                            $stmt->bind_param('i', $student_db_id);
                            $stmt->execute();
                            $row = $stmt->get_result()->fetch_assoc();
                            $stmt->close();
                            if ($row) {
                                $student_name = $row['first_name'] . ' ' . $row['last_name'];
                                $student_id = $row['student_id'];
                            }
                        }
                        ?>
                        <?php if ($is_logged_in): ?>
                        <form method="POST" class="mb-4" action="">
                            <div class="row g-3 align-items-center justify-content-center">
                                <div class="col-md-4">
                                    <input type="text" name="student_name" class="form-control" placeholder="Your Name" required value="<?php echo htmlspecialchars($student_name); ?>" readonly>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="student_id" class="form-control" placeholder="Student ID" required value="<?php echo htmlspecialchars($student_id); ?>" readonly>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="content" class="form-control" placeholder="Your Review" required>
                                </div>
                                <div class="col-12 text-center mt-2">
                                    <div class="mb-2">
                                        <label class="form-label mb-1">Your Rating:</label><br>
                                        <span class="star-rating">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>">
                                                <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars"><i class="fas fa-star"></i></label>
                                            <?php endfor; ?>
                                        </span>
                                    </div>
                                    <button type="submit" name="submit_review" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Post Review</button>
                                </div>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-info text-center mb-4">You must be logged in as a student to post a review.</div>
                        <?php endif; ?>
                        <?php
                        require_once 'config/connect.php';
                        // Handle review submission
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
                            $content = trim($_POST['content'] ?? '');
                            $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int)$_POST['rating'] : null;
                            $student_db_id = $_SESSION['student_db_id'];
                            $student_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
                            if (!empty($content)) {
                                $stmt = $conn->prepare('INSERT INTO reviews (student_id, student_name, content, rating, status, created_at) VALUES (?, ?, ?, ?, "pending", NOW())');
                                $stmt->bind_param('issi', $student_db_id, $student_name, $content, $rating);
                                $stmt->execute();
                                $stmt->close();
                                echo '<div class="alert alert-success text-center">Thank you for your review! It will be visible after admin approval.</div>';
                            }
                        }
                        // Auto-delete reviews older than 20 days
                        $conn->query("DELETE FROM reviews WHERE created_at < (NOW() - INTERVAL 20 DAY)");
                        // Show approved reviews and average rating
                        $stmt = $conn->prepare('SELECT r.*, s.first_name, s.last_name, s.profile_picture, s.student_id AS reg_student_id FROM reviews r LEFT JOIN students s ON r.student_id = s.id WHERE r.status = "approved" ORDER BY r.created_at DESC');
                        $stmt->execute();
                        $reviews = $stmt->get_result();
                        $stmt->close();

                        // Calculate average rating and total reviews
                        $avg_rating = 0;
                        $total_reviews = 0;
                        $rating_sum = 0;
                        $review_rows = [];
                        if ($reviews && $reviews->num_rows > 0) {
                            while ($row = $reviews->fetch_assoc()) {
                                $review_rows[] = $row;
                                $rating_sum += (int)$row['rating'];
                                $total_reviews++;
                            }
                            if ($total_reviews > 0) {
                                $avg_rating = round($rating_sum / $total_reviews, 2);
                            }
                        }
                        ?>
                        <div class="text-center mb-3">
                            <span class="fs-4 fw-bold">Average Rating:</span>
                            <span class="fs-4 text-warning">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo ($i <= round($avg_rating)) ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </span>
                            <span class="ms-2 text-muted">(<?php echo $avg_rating; ?>/5 from <?php echo $total_reviews; ?> reviews)</span>
                        </div>
                        <?php if ($total_reviews > 0): ?>
                            <div class="mt-4">
                                <?php foreach ($review_rows as $review): ?>
                                    <div class="card mb-3 shadow-sm">
                                        <div class="card-body d-flex align-items-center">
                                            <div class="me-3">
                                                <img src="<?php echo !empty($review['profile_picture']) && file_exists($review['profile_picture']) ? htmlspecialchars($review['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode(trim(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? ''))) . '&background=3498db&color=fff&size=64'; ?>" alt="Profile" style="width:48px;height:48px;border-radius:50%;object-fit:cover;box-shadow:0 2px 6px rgba(52,152,219,0.12);">
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="fw-bold me-2"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></span>
                                                    <span class="badge bg-secondary ms-1" style="font-size:0.85em;">ID: <?php echo !empty($review['reg_student_id']) ? htmlspecialchars($review['reg_student_id']) : 'Unknown'; ?></span>
                                                </div>
                                                <div class="mb-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? ' text-warning' : ' text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                    <span class="text-muted ms-2" style="font-size:0.95em;"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                                </div>
                                                <div><?php echo nl2br(htmlspecialchars($review['content'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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