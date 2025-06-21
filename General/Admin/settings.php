<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin or super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'update_profile') {
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (password_verify($current_password, $user['password'])) {
                // Update profile
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
                $stmt->bind_param("sssi", $first_name, $last_name, $email, $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();
                
                // Update password if provided
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                    $stmt->execute();
                    $stmt->close();
                }
                
                header('Location: settings.php?success=profile_updated');
                exit();
            } else {
                header('Location: settings.php?error=invalid_password');
                exit();
            }
        } elseif ($action === 'update_system') {
            // Update system settings (this would typically be stored in a settings table)
            header('Location: settings.php?success=system_updated');
            exit();
        }
    }
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
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

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .nav-pills .nav-link {
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }

        .nav-pills .nav-link.active {
            background-color: var(--secondary-color);
        }

        .profile-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .system-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }

        /* Footer adjustments for admin */
        .footer-section {
            margin-top: 0 !important;
            position: relative;
            background: url('https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=1500&q=80') center center/cover no-repeat;
            color: #fff;
            padding: 2.5rem 0 1.5rem 0;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }
        .footer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(44, 62, 80, 0.85);
            z-index: 1;
        }
        .footer-content {
            position: relative;
            z-index: 2;
        }
        .footer-brand i {
            color: #3498db;
            margin-right: 0.5rem;
        }
        .footer-brand span {
            font-weight: 700;
            letter-spacing: 1px;
            color: #e0eafc;
        }
        .footer-section h5, .footer-section h6 {
            color: #e0eafc;
            font-weight: 600;
        }
        .footer-section p, .footer-section a, .footer-section li, .footer-contact {
            color: #e0eafc;
            font-size: 1rem;
            margin-bottom: 0;
            text-shadow: 0 1px 4px rgba(44,62,80,0.12);
        }
        .footer-links a {
            color: #e0eafc;
            text-decoration: none;
            transition: color 0.2s;
            font-weight: 500;
            padding: 0 0.3rem;
        }
        .footer-links a:hover {
            color: #3498db;
            text-decoration: underline;
        }
        .footer-social a {
            color: #e0eafc;
            font-size: 1.3rem;
            margin-right: 0.5rem;
            transition: color 0.2s, transform 0.2s;
            display: inline-block;
        }
        .footer-social a:hover {
            color: #3498db;
            transform: scale(1.15);
        }
        .footer-divider {
            border: none;
            border-top: 1.5px solid rgba(255,255,255,0.15);
            margin: 1.5rem 0;
        }
        .footer-contact i {
            color: #3498db;
        }
        @media (max-width: 768px) {
            .footer-section {
                padding: 2rem 0 1rem 0;
            }
            .footer-content .row > div {
                margin-bottom: 1.5rem;
            }
            .footer-contact {
                display: block;
                margin-bottom: 0.5rem;
            }
            .footer-divider {
                margin: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-vote-yea me-2"></i>
                STVC Election System - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_applications.php"><i class="fas fa-file-alt me-1"></i> Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_positions.php"><i class="fas fa-list me-1"></i> Positions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php"><i class="fas fa-users me-1"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php"><i class="fas fa-cog me-1"></i> Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2"><i class="fas fa-cog me-2"></i>Settings</h1>
                    <p class="mb-0">Manage your profile and system preferences</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <i class="fas fa-cog fa-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    $message = $_GET['success'] === 'profile_updated' ? 'Profile updated successfully!' : 'System settings updated successfully!';
                    echo $message;
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_password'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Current password is incorrect!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Section -->
        <div class="profile-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="mb-2">Welcome, <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>!</h3>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($admin['email']); ?></p>
                    <p class="mb-0"><i class="fas fa-calendar me-2"></i>Admin since <?php echo date('M d, Y', strtotime($admin['created_at'])); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <i class="fas fa-user-shield fa-3x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <!-- Settings Navigation -->
                <div class="settings-card">
                    <h5 class="mb-3"><i class="fas fa-list me-2"></i>Settings</h5>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                        <button class="nav-link active" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab">
                            <i class="fas fa-user me-2"></i>Profile
                        </button>
                        <button class="nav-link" id="v-pills-system-tab" data-bs-toggle="pill" data-bs-target="#v-pills-system" type="button" role="tab">
                            <i class="fas fa-cog me-2"></i>System
                        </button>
                        <button class="nav-link" id="v-pills-security-tab" data-bs-toggle="pill" data-bs-target="#v-pills-security" type="button" role="tab">
                            <i class="fas fa-shield-alt me-2"></i>Security
                        </button>
                        <button class="nav-link" id="v-pills-notifications-tab" data-bs-toggle="pill" data-bs-target="#v-pills-notifications" type="button" role="tab">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <!-- Settings Content -->
                <div class="tab-content" id="v-pills-tabContent">
                    <!-- Profile Settings -->
                    <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
                        <div class="settings-card">
                            <h5 class="mb-3"><i class="fas fa-user me-2"></i>Profile Settings</h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                </div>
                                
                                <button type="submit" class="btn btn-admin">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="tab-pane fade" id="v-pills-system" role="tabpanel">
                        <div class="settings-card">
                            <h5 class="mb-3"><i class="fas fa-cog me-2"></i>System Settings</h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_system">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="site_name" class="form-label">Site Name</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name" value="STVC Election System">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="admin_email" class="form-label">Admin Email</label>
                                            <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@stvc.edu">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_applications" class="form-label">Max Applications per User</label>
                                            <input type="number" class="form-control" id="max_applications" name="max_applications" value="3" min="1" max="10">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="application_deadline" class="form-label">Default Application Deadline (days)</label>
                                            <input type="number" class="form-control" id="application_deadline" name="application_deadline" value="30" min="1" max="365">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="welcome_message" class="form-label">Welcome Message</label>
                                    <textarea class="form-control" id="welcome_message" name="welcome_message" rows="4">Welcome to the STVC Election System. Apply for positions and participate in student elections.</textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" checked>
                                        <label class="form-check-label" for="email_notifications">
                                            Enable Email Notifications
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode">
                                        <label class="form-check-label" for="maintenance_mode">
                                            Maintenance Mode
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-admin">
                                    <i class="fas fa-save me-2"></i>Save System Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="tab-pane fade" id="v-pills-security" role="tabpanel">
                        <div class="settings-card">
                            <h5 class="mb-3"><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                            
                            <div class="mb-4">
                                <h6>Password Policy</h6>
                                <div class="system-info">
                                    <p class="mb-1"><strong>Minimum Length:</strong> 6 characters</p>
                                    <p class="mb-1"><strong>Required:</strong> Letters and numbers</p>
                                    <p class="mb-0"><strong>Expiration:</strong> 90 days</p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6>Session Management</h6>
                                <div class="mb-3">
                                    <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" value="30" min="5" max="480">
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="force_logout" name="force_logout">
                                    <label class="form-check-label" for="force_logout">
                                        Force logout on password change
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6>Login Security</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="two_factor" name="two_factor">
                                    <label class="form-check-label" for="two_factor">
                                        Enable Two-Factor Authentication
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="login_attempts" name="login_attempts" checked>
                                    <label class="form-check-label" for="login_attempts">
                                        Limit login attempts
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ip_whitelist" name="ip_whitelist">
                                    <label class="form-check-label" for="ip_whitelist">
                                        IP Address Whitelist
                                    </label>
                                </div>
                            </div>
                            
                            <button class="btn btn-admin">
                                <i class="fas fa-save me-2"></i>Save Security Settings
                            </button>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="tab-pane fade" id="v-pills-notifications" role="tabpanel">
                        <div class="settings-card">
                            <h5 class="mb-3"><i class="fas fa-bell me-2"></i>Notification Settings</h5>
                            
                            <div class="mb-4">
                                <h6>Email Notifications</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="new_application" name="new_application" checked>
                                    <label class="form-check-label" for="new_application">
                                        New application submitted
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="application_status" name="application_status" checked>
                                    <label class="form-check-label" for="application_status">
                                        Application status changes
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="system_alerts" name="system_alerts" checked>
                                    <label class="form-check-label" for="system_alerts">
                                        System alerts and maintenance
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="weekly_reports" name="weekly_reports">
                                    <label class="form-check-label" for="weekly_reports">
                                        Weekly activity reports
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6>Dashboard Notifications</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="pending_reviews" name="pending_reviews" checked>
                                    <label class="form-check-label" for="pending_reviews">
                                        Pending application reviews
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="low_positions" name="low_positions" checked>
                                    <label class="form-check-label" for="low_positions">
                                        Low application positions
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="system_health" name="system_health" checked>
                                    <label class="form-check-label" for="system_health">
                                        System health alerts
                                    </label>
                                </div>
                            </div>
                            
                            <button class="btn btn-admin">
                                <i class="fas fa-save me-2"></i>Save Notification Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


         <!-- Footer -->
         <footer class="footer mt-5">
        <div class="footer-overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="text-white mb-3">
                        <i class="fas fa-vote-yea me-2"></i>
                        STVC Election System
                    </h5>
                    <p class="text-white-50">
                        Empowering students to participate in democratic processes through secure and transparent online voting.
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="admin_dashboard.php" class="text-white-50 text-decoration-none">Dashboard</a></li>
                        <li><a href="manage_applications.php" class="text-white-50 text-decoration-none">Applications</a></li>
                        <li><a href="manage_positions.php" class="text-white-50 text-decoration-none">Positions</a></li>
                        <li><a href="manage_accounts.php" class="text-white-50 text-decoration-none">Users</a></li>
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
</body>
</html> 