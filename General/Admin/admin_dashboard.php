<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'super_admin')) {
    header('Location: admin_login.php');
    exit();
}

// Check if user is super admin
$is_super_admin = ($_SESSION['user_type'] === 'super_admin');

// Get admin information
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get statistics
try {
    $applications_count = $conn->query("SELECT COUNT(*) as total_applications FROM applications")->fetch_assoc();
    $positions_count = $conn->query("SELECT COUNT(*) as total_positions FROM positions WHERE status = 'active'")->fetch_assoc();
    $students_count = $conn->query("SELECT COUNT(*) as total_students FROM students")->fetch_assoc();
    $pending_count = $conn->query("SELECT COUNT(*) as pending_applications FROM applications WHERE status = 'pending'")->fetch_assoc();
} catch (Exception $e) {
    // Fallback if tables don't exist
    $applications_count = ['total_applications' => 0];
    $positions_count = ['total_positions' => 0];
    $students_count = ['total_students' => 0];
    $pending_count = ['pending_applications' => 0];
}

$pending_admins_count = 0;
if ($is_super_admin) {
    try {
        $pending_admins_result = $conn->query("SELECT COUNT(*) as pending_admins FROM users WHERE role = 'admin' AND status = 'pending'")->fetch_assoc();
        $pending_admins_count = $pending_admins_result['pending_admins'];
    } catch (Exception $e) {
        $pending_admins_count = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - STVC Election System</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .stats-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: #6c757d;
            font-size: 1.1rem;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .btn-admin {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: white;
        }

        .welcome-section {
            background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(44, 62, 80, 0.9)),
                        url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .main-content {
            flex: 1;
            min-height: calc(100vh - 200px);
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container main-content">
        <!-- Welcome Section -->
        <section class="welcome-section">
            <div class="row align-items-center">
                <div class="col-12">
                    <h2>Admin Dashboard</h2>
                    <p>Welcome back, <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>. Here is a summary of the election system.</p>
                </div>
            </div>
        </section>

        <!-- Stats Row -->
        <section class="mb-4">
            <div class="row">
                <!-- Total Applications -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="stats-card">
                        <i class="fas fa-file-alt stats-icon text-primary"></i>
                        <div class="stats-number"><?php echo $applications_count['total_applications']; ?></div>
                        <div class="stats-label">Total Applications</div>
                    </div>
                </div>
                <!-- Active Positions -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="stats-card">
                        <i class="fas fa-sitemap stats-icon text-success"></i>
                        <div class="stats-number"><?php echo $positions_count['total_positions']; ?></div>
                        <div class="stats-label">Active Positions</div>
                    </div>
                </div>
                <!-- Total Students -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="stats-card">
                        <i class="fas fa-users stats-icon text-info"></i>
                        <div class="stats-number"><?php echo $students_count['total_students']; ?></div>
                        <div class="stats-label">Total Students</div>
                    </div>
                </div>
                <!-- Pending Applications -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="stats-card">
                        <i class="fas fa-clock stats-icon text-warning"></i>
                        <div class="stats-number"><?php echo $pending_count['pending_applications']; ?></div>
                        <div class="stats-label">Pending Applications</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Management Actions -->
        <div class="row">
            <div class="col-lg-12">
                <h4 class="mb-4">Management Actions</h4>
                <div class="row">
                    <!-- Manage Applications -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                                <h5>Manage Applications</h5>
                                <p class="text-muted">Review, approve, or reject candidate applications.</p>
                            </div>
                            <a href="manage_applications.php" class="btn btn-admin mt-3 align-self-start">Go to Applications</a>
                        </div>
                    </div>

                    <!-- Manage Positions -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-layer-group fa-3x text-primary mb-3"></i>
                                <h5>Manage Positions</h5>
                                <p class="text-muted">Create, edit, or remove election positions.</p>
                            </div>
                            <a href="manage_positions.php" class="btn btn-admin mt-3 align-self-start">Go to Positions</a>
                        </div>
                    </div>

                    <?php if ($is_super_admin): ?>
                    <div class="col-md-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-cogs fa-3x text-primary mb-3"></i>
                                <h5>Manage Elections</h5>
                                <p class="text-muted">Create, start, and stop election periods.</p>
                            </div>
                            <a href="manage_elections.php" class="btn btn-admin mt-3 align-self-start">Manage Elections</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-users-cog fa-3x text-primary mb-3"></i>
                                <h5>Manage Accounts</h5>
                                <p class="text-muted">Handle student and admin accounts.</p>
                            </div>
                            <a href="manage_accounts.php" class="btn btn-admin mt-3 align-self-start">Go to Accounts</a>
                        </div>
                    </div>

                    <!-- Manage Current Leaders -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-user-tie fa-3x text-warning mb-3"></i>
                                <h5>Manage Current Leaders</h5>
                                <p class="text-muted">Update the list of the institution's current leaders.</p>
                            </div>
                            <a href="manage_current_candidates.php" class="btn btn-admin mt-3 align-self-start">Go to Leaders</a>
                        </div>
                    </div>

                    <!-- Manage Gallery -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-images fa-3x text-primary mb-3"></i>
                                <h5>Manage Gallery</h5>
                                <p class="text-muted">Upload or remove images from the gallery.</p>
                            </div>
                            <a href="manage_gallery.php" class="btn btn-admin mt-3 align-self-start">Go to Gallery</a>
                        </div>
                    </div>

                    <!-- Manage Reviews -->
                    <div class="col-md-6 col-lg-4 mb-4">
                         <div class="action-card text-center d-flex flex-column h-100">
                             <div class="flex-grow-1">
                                 <i class="fas fa-star fa-3x text-primary mb-3"></i>
                                 <h5>Manage Reviews</h5>
                                 <p class="text-muted">View and manage submitted reviews.</p>
                             </div>
                             <a href="manage_reviews.php" class="btn btn-admin mt-3 align-self-start">Go to Reviews</a>
                         </div>
                    </div>

                    <!-- Remove My Messages card and add Send Message and Inbox cards -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-paper-plane fa-3x text-primary mb-3"></i>
                                <h5>Send Message</h5>
                                <p class="text-muted">Send announcements or notifications to students, admins, or all users.</p>
                            </div>
                            <a href="send_message.php" class="btn btn-admin mt-3 align-self-start">Go to Send Message</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-inbox fa-3x text-primary mb-3"></i>
                                <h5>Inbox</h5>
                                <p class="text-muted">View and reply to all received messages and conversations.</p>
                            </div>
                            <a href="admin_messages.php" class="btn btn-admin mt-3 align-self-start">Go to Inbox</a>
                        </div>
                    </div>

                    <?php if ($is_super_admin): ?>
                    <div class="col-md-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-database fa-3x text-danger mb-3"></i>
                                <h5>Data Recovery</h5>
                                <p class="text-muted">Access data recovery options.</p>
                            </div>
                            <a href="data_recovery.php" class="btn btn-danger mt-3 align-self-start">Go to Recovery</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Recent News Column -->
            <div class="col-lg-12">
                <h4 class="mb-4">Recent News &amp; Activity</h4>
                <!-- Add recent news and activity content here -->
            </div>
        </div>
    </div>

        <!-- Footer -->
        <footer class="footer mt-5">
        <div class="footer-overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="footer-brand d-flex align-items-center justify-content-center justify-content-md-start">
                        <img src="../uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
                        <span class="h5 mb-0">STVC Election System - Admin</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="admin_dashboard.php" class="text-white-50 text-decoration-none">Dashboard</a></li>
                        <li><a href="manage_applications.php" class="text-white-50 text-decoration-none">Applications</a></li>
                        <li><a href="manage_positions.php" class="text-white-50 text-decoration-none">Positions</a></li>
                        <li><a href="manage_users.php" class="text-white-50 text-decoration-none">Users</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white mb-3">Contact</h6>
                    <p class="text-white-50 mb-1">
                        <i class="fas fa-envelope me-2"></i>
                        admin@stvc.edu
                    </p>
                    <p class="text-white-50 mb-1">
                        <i class="fas fa-phone me-2"></i>
                        +1 (555) 123-4567
                    </p>
                    <p class="text-white-50">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        STVC Campus
                    </p>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-white-50 mb-0">
                        &copy; 2024 STVC Election System. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white-50"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 