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
// Fetch applicants with student info
$stmt = $conn->prepare('SELECT a.*, s.first_name, s.last_name, s.department, s.student_id AS reg_student_id FROM applications a LEFT JOIN students s ON a.student_id = s.student_id ORDER BY a.created_at DESC');
$stmt->execute();
$applicants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicants - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
        }
        .footer-section {
            flex-shrink: 0;
        }
    </style>
    <link rel="stylesheet" href="includes/css/style.css">
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
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="news.php"><i class="fas fa-newspaper me-1"></i> News</a></li>
                    <li class="nav-item"><a class="nav-link" href="positions.php"><i class="fas fa-list me-1"></i> Positions</a></li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
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
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Header -->
    <section class="py-5" style="background: linear-gradient(90deg, #33506a 0%, #3498db 100%); box-shadow: 0 -2px 8px rgba(44,62,80,0.10) inset;">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-3" style="color: #fff;"><i class="fas fa-users me-2"></i>Applicants</h1>
            <p class="lead" style="color: #e0eafc; font-size: 1.2rem;">Browse all applicants for student government positions. Status is shown for each applicant.</p>
        </div>
    </section>
    <main>
        <section class="applicants-section" style="background: #f8f9fa; padding: 60px 0;">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="registration-card" style="background: #fff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 30px;">
                            <?php if (!empty($applicants)): ?>
                                <div class="row justify-content-center">
                                    <?php foreach ($applicants as $app): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card shadow-sm h-100">
                                                <div class="card-header bg-primary text-white">
                                                    <h4 class="mb-0"><i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars((!empty($app['first_name']) && !empty($app['last_name'])) ? $app['first_name'] . ' ' . $app['last_name'] : $app['first_name']); ?></h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="d-flex flex-column align-items-center gap-3">
                                                        <span class="badge bg-secondary ms-1">ID: <?php echo !empty($app['reg_student_id']) ? htmlspecialchars($app['reg_student_id']) : htmlspecialchars($app['student_id']); ?></span>
                                                        <span class="badge bg-info ms-1">Dept: <?php echo !empty($app['department']) ? htmlspecialchars($app['department']) : htmlspecialchars($app['department']); ?></span>
                                                        <?php
                                                        $status = strtolower($app['status']);
                                                        $vetting = strtolower($app['vetting_status']);
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
                                                        <a href="application_details.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-eye me-1"></i>View Details</a>
                                                        <div class="mt-3 d-flex justify-content-center align-items-center gap-3">
                                                            <button class="btn btn-outline-success btn-like" data-id="<?php echo $app['id']; ?>"><i class="fas fa-thumbs-up"></i> <span class="like-count" id="like-count-<?php echo $app['id']; ?>">0</span></button>
                                                            <button class="btn btn-outline-danger btn-dislike" data-id="<?php echo $app['id']; ?>"><i class="fas fa-thumbs-down"></i> <span class="dislike-count" id="dislike-count-<?php echo $app['id']; ?>">0</span></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="col-12 text-center text-muted">No applicants found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Simple like/dislike tally using localStorage (not secure, demo only)
    document.querySelectorAll('.btn-like, .btn-dislike').forEach(function(btn) {
        const id = btn.getAttribute('data-id');
        const likeKey = 'like-' + id;
        const dislikeKey = 'dislike-' + id;
        // Load counts
        document.getElementById('like-count-' + id).textContent = localStorage.getItem(likeKey) || 0;
        document.getElementById('dislike-count-' + id).textContent = localStorage.getItem(dislikeKey) || 0;
        btn.addEventListener('click', function() {
            let isLike = btn.classList.contains('btn-like');
            let countKey = isLike ? likeKey : dislikeKey;
            let countSpan = document.getElementById((isLike ? 'like-count-' : 'dislike-count-') + id);
            // Prevent multiple likes/dislikes per session
            let votedKey = 'voted-' + id;
            if (localStorage.getItem(votedKey)) return;
            let count = parseInt(localStorage.getItem(countKey) || '0', 10) + 1;
            localStorage.setItem(countKey, count);
            localStorage.setItem(votedKey, isLike ? 'like' : 'dislike');
            countSpan.textContent = count;
        });
    });
    </script>
</body>
</html> 