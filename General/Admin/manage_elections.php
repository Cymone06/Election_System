<?php
require_once '../config/session_config.php';
require_once '../config/connect.php';

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'super_admin') {
    header('Location: admin_login.php?error=unauthorized');
    exit();
}

/**
 * Clears all data related to applications to prepare for a new cycle.
 * This is a destructive action and will remove all votes, logs, and candidates.
 * @param mysqli $conn The database connection object.
 */
function clearAllApplicantData(mysqli $conn): void {
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $conn->query("TRUNCATE TABLE votes");
    $conn->query("TRUNCATE TABLE application_logs");
    $conn->query("TRUNCATE TABLE candidates");
    $conn->query("TRUNCATE TABLE applications");
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
}

// Handle form submissions for creating/updating elections
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_election'])) {
        $title = $_POST['title'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = 'upcoming';
        
        $stmt = $conn->prepare("INSERT INTO election_periods (title, start_date, end_date, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $start_date, $end_date, $status);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update_status'])) {
        $election_id = $_POST['election_id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE election_periods SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $election_id);
        $stmt->execute();
        $stmt->close();

        // If the election status is set to 'ended', clear the applications table
        if ($status === 'ended') {
            clearAllApplicantData($conn);
        }
    } elseif (isset($_POST['delete_election'])) {
        $election_id = $_POST['election_id'];
        
        // Soft delete the election by updating its status
        $stmt = $conn->prepare("UPDATE election_periods SET status = 'deleted' WHERE id = ?");
        $stmt->bind_param("i", $election_id);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to avoid form resubmission on refresh
        header("Location: manage_elections.php");
        exit();
    } elseif (isset($_POST['clear_applicants'])) {
        clearAllApplicantData($conn);
        header("Location: manage_elections.php?clear_success=1");
        exit();
    } elseif (isset($_POST['set_portal_status'])) {
        $portal_status = $_POST['portal_status'];
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'application_portal_status'");
        $stmt->bind_param("s", $portal_status);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all non-deleted elections
$elections = $conn->query("SELECT * FROM election_periods WHERE status != 'deleted' ORDER BY start_date DESC")->fetch_all(MYSQLI_ASSOC);

// Check for upcoming elections to conditionally show the manual clear button
$upcoming_elections_result = $conn->query("SELECT COUNT(*) as count FROM election_periods WHERE status = 'upcoming'");
$upcoming_elections_count = $upcoming_elections_result->fetch_assoc()['count'] ?? 0;

// Fetch application portal status
$portal_status_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'application_portal_status'");
$application_portal_status = $portal_status_result->fetch_assoc()['setting_value'] ?? 'closed';

require_once 'admin_header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-cogs me-2"></i>Manage Elections</h4>
                        <a href="data_recovery.php" class="btn btn-light btn-sm">
                            <i class="fas fa-trash-restore me-2"></i>Data Recovery
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($upcoming_elections_count == 0): ?>
                        <!-- Manual Data Management -->
                        <div class="card mb-4 border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Manual Data Management</h5>
                            </div>
                            <div class="card-body">
                                <p>There are no upcoming elections scheduled. You can clear the current list of applicants to prepare for a new application period.</p>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete all applicants? This action cannot be undone.');">
                                    <button type="submit" name="clear_applicants" class="btn btn-warning">
                                        <i class="fas fa-users-slash me-2"></i>Clear All Applicants
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Application Portal Control -->
                        <div class="card mb-4 border-secondary">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Application Portal Control</h5>
                            </div>
                            <div class="card-body text-center">
                                <p class="lead">The application portal is currently: 
                                    <span class="fw-bold text-<?php echo $application_portal_status === 'open' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($application_portal_status); ?>
                                    </span>
                                </p>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="portal_status" value="open">
                                    <button type="submit" name="set_portal_status" class="btn btn-success" <?php if ($application_portal_status === 'open') echo 'disabled'; ?>>
                                        <i class="fas fa-check-circle me-2"></i>Open Portal
                                    </button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="portal_status" value="closed">
                                    <button type="submit" name="set_portal_status" class="btn btn-danger" <?php if ($application_portal_status === 'closed') echo 'disabled'; ?>>
                                        <i class="fas fa-times-circle me-2"></i>Close Portal
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Create Election Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create New Election</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Election Title</label>
                                            <input type="text" name="title" class="form-control" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Start Date</label>
                                            <input type="datetime-local" name="start_date" class="form-control" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">End Date</label>
                                            <input type="datetime-local" name="end_date" class="form-control" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" name="create_election" class="btn btn-primary w-100">Create</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Elections List -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($elections as $election): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($election['title']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($election['start_date'])); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($election['end_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $election['status'] === 'active' ? 'success' : ($election['status'] === 'ended' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($election['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm d-inline" style="width: auto;">
                                                        <option value="upcoming" <?php if($election['status'] === 'upcoming') echo 'selected'; ?>>Upcoming</option>
                                                        <option value="active" <?php if($election['status'] === 'active') echo 'selected'; ?>>Active</option>
                                                        <option value="ended" <?php if($election['status'] === 'ended') echo 'selected'; ?>>Ended</option>
                                                    </select>
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-outline-primary">Update</button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to move this election to the recycle bin?');">
                                                    <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                                    <button type="submit" name="delete_election" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Footer -->
<footer class="footer mt-auto">
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