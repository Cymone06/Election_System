<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle Reset All Applications action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_all_applications') {
    // Re-check privileges for this critical action
    if (in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
        
        // --- Start Secret Archival ---
        // 1. Fetch all application records before deleting
        $applications_to_archive = [];
        $result = $conn->query("SELECT * FROM applications");
        if ($result) {
            $applications_to_archive = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }

        // Get admin details for logging
        $admin_id = $_SESSION['user_id'] ?? null;
        $admin_name = $_SESSION['user_name'] ?? 'Unknown Admin';

        // Prepare archive statement
        $archive_stmt = $conn->prepare("INSERT INTO deleted_items (item_type, item_identifier, item_data, deleted_by_user_id, deleted_by_user_name) VALUES (?, ?, ?, ?, ?)");
        $item_type = 'application';

        foreach ($applications_to_archive as $app_data) {
            $item_identifier = $app_data['id'];
            $item_data_json = json_encode($app_data);
            
            // 2. Insert each application into the deleted_items table
            $archive_stmt->bind_param('sssis', $item_type, $item_identifier, $item_data_json, $admin_id, $admin_name);
            $archive_stmt->execute();
        }
        $archive_stmt->close();
        // --- End Secret Archival ---

        // 3. Get all image filenames to delete them from storage
        $files_to_delete = [];
        foreach ($applications_to_archive as $app_data) {
            if (!empty($app_data['image1'])) {
                $files_to_delete[] = '../uploads/applications/' . $app_data['image1'];
            }
        }

        // 4. Delete all records from the applications and candidates tables
        $conn->query("DELETE FROM candidates");
        $conn->query("TRUNCATE TABLE applications");

        // 5. Delete the actual image files
        foreach ($files_to_delete as $file) {
            if (file_exists($file)) {
                @unlink($file); // Use @ to suppress errors if file not found
            }
        }

        $_SESSION['success_message'] = "All applications have been successfully reset.";
    } else {
        $_SESSION['error_message'] = "You do not have permission to perform this action.";
    }
    header('Location: manage_applications.php');
    exit();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['action'])) {
    $application_id = (int)$_POST['application_id'];
    $action = $_POST['action'];
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE applications SET status = 'approved' WHERE id = ?");
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $stmt->close();
        // Insert into candidates table if not already present
        $check = $conn->prepare("SELECT id FROM candidates WHERE application_id = ?");
        $check->bind_param('i', $application_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            $app = $conn->query("SELECT position_id FROM applications WHERE id = $application_id")->fetch_assoc();
            $insert = $conn->prepare("INSERT INTO candidates (application_id, position_id, status, vetting_status) VALUES (?, ?, 'approved', 'pending')");
            $insert->bind_param('ii', $application_id, $app['position_id']);
            $insert->execute();
            $insert->close();
        }
        $check->close();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE applications SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'verify') {
        // Update vetting status in both applications and candidates
        $stmt = $conn->prepare("UPDATE applications SET vetting_status = 'verified' WHERE id = ?");
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE candidates SET vetting_status = 'verified' WHERE application_id = ?");
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'vet_reject') {
        $stmt = $conn->prepare("UPDATE applications SET vetting_status = 'rejected' WHERE id = ?");
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE candidates SET vetting_status = 'rejected' WHERE application_id = ?");
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: manage_applications.php');
    exit();
}

// Fetch all applications with position name
$applications = [];
$sql = "SELECT a.*, p.position_name FROM applications a JOIN positions p ON a.position_id = p.id ORDER BY a.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    $applications = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .navbar { background-color: #2c3e50; }
        .navbar-brand { font-weight: 600; color: white !important; }
        .nav-link { color: rgba(255,255,255,0.9) !important; }
        .nav-link.active, .nav-link:hover { color: #fff !important; }
        .table thead th { background: #34495e; color: #fff; }
        .table tbody tr { background: #fff; }
        .status-badge { font-size: 0.95em; padding: 0.4em 1em; border-radius: 1em; }
        .status-pending { background: #f1c40f; color: #fff; }
        .status-approved { background: #27ae60; color: #fff; }
        .status-rejected { background: #e74c3c; color: #fff; }
        .vetting-pending { background: #f1c40f; color: #fff; }
        .vetting-verified { background: #2980b9; color: #fff; }
        .vetting-rejected { background: #e74c3c; color: #fff; }
        .app-img { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; border: 2px solid #3498db; }
        .main-content { min-height: 70vh; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-vote-yea me-2"></i>STVC Election System - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage_applications.php"><i class="fas fa-file-alt me-1"></i> Applications</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_positions.php"><i class="fas fa-list me-1"></i> Positions</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_accounts.php"><i class="fas fa-users me-1"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <div class="container main-content py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0 text-center section-title">All Applications</h2>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetConfirmModal">
                <i class="fas fa-trash-alt me-2"></i>Reset All Applications
            </button>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle" id="applicationsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Student ID</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Vetting</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr><td colspan="10" class="text-center text-muted">No applications found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($applications as $i => $app): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td>
                                    <?php if (!empty($app['image1'])): ?>
                                        <img src="../uploads/applications/<?php echo htmlspecialchars($app['image1']); ?>" class="app-img" alt="Applicant Image">
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['email']); ?></td>
                                <td><?php echo htmlspecialchars($app['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['position_name']); ?></td>
                                <td><span class="status-badge status-<?php echo htmlspecialchars($app['status']); ?>"><?php echo ucfirst($app['status']); ?></span></td>
                                <td><span class="status-badge vetting-<?php echo htmlspecialchars($app['vetting_status']); ?>"><?php echo ucfirst($app['vetting_status']); ?></span></td>
                                <td><?php echo date('M d, Y H:i', strtotime($app['created_at'])); ?></td>
                                <td>
                                    <?php if ($app['status'] === 'pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success mb-1">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger mb-1">Reject</button>
                                        </form>
                                    <?php elseif ($app['status'] === 'approved' && $app['vetting_status'] === 'pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <button type="submit" name="action" value="verify" class="btn btn-sm btn-primary mb-1">Verify (Vetting)</button>
                                            <button type="submit" name="action" value="vet_reject" class="btn btn-sm btn-warning mb-1">Reject (Vetting)</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div class="modal fade" id="resetConfirmModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetModalLabel"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Reset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reset all applications?</p>
                    <p class="text-danger"><strong>This action is irreversible.</strong> It will delete all application records, associated candidate entries, and uploaded images permanently.</p>
                    <p>This is typically done to prepare for a new election cycle.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="manage_applications.php" class="d-inline">
                        <input type="hidden" name="action" value="reset_all_applications">
                        <button type="submit" class="btn btn-danger">Yes, Reset All</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 