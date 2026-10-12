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
$is_embedded = ($_GET['embedded'] ?? '') === '1';

// Get admin information
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

$unread_admin_count = 0;
try {
    $unread_result = $conn->prepare('SELECT COUNT(*) AS unread_count FROM admin_messages WHERE recipient_admin_id = ? AND is_read = 0');
    $unread_result->bind_param('i', $admin_id);
    $unread_result->execute();
    $unread_admin_count = (int)($unread_result->get_result()->fetch_assoc()['unread_count'] ?? 0);
    $unread_result->close();
} catch (Throwable $e) {
    $unread_admin_count = 0;
}

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
            border: 1px solid #e3e9eb;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(28, 52, 62, 0.06);
            padding: 1.25rem;
            text-align: left;
            transition: border-color 150ms ease, box-shadow 150ms ease;
            margin-bottom: 2rem;
        }

        .stats-card:hover {
            border-color: #bdcdd3;
            box-shadow: 0 3px 9px rgba(28, 52, 62, 0.1);
        }

        .stats-icon {
            float: left;
            margin: 0.15rem 1rem 0 0;
            font-size: 1.4rem;
        }

        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 0.35rem;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.88rem;
        }

        .action-card {
            background: white;
            border: 1px solid #e3e9eb;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(28, 52, 62, 0.06);
            padding: 1.35rem;
            transition: border-color 150ms ease, box-shadow 150ms ease;
            margin-bottom: 2rem;
        }

        .action-card:hover {
            border-color: #bdcdd3;
            box-shadow: 0 3px 9px rgba(28, 52, 62, 0.1);
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
            position: relative;
            color: #203740;
            padding: 1.5rem 0 1.25rem;
            border-bottom: 1px solid #dfe6e8;
            margin-bottom: 2rem;
        }

        .welcome-section h2 {
            margin-bottom: 0.45rem;
            font-size: 1.65rem;
            font-weight: 700;
        }

        .welcome-section p {
            margin: 0;
            color: #63747b;
        }

        .main-content h4 {
            color: #203740;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .btn-admin {
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .main-content {
            flex: 1;
            min-height: calc(100vh - 200px);
        }

        body {
            display: block;
            background: #f3f6f7;
        }

        .dashboard-shell {
            min-height: 100vh;
        }

        .dashboard-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            width: 264px;
            padding: 1.25rem 1rem 1rem;
            background: #fff;
            border-right: 1px solid #dfe6e8;
            box-shadow: 2px 0 12px rgba(31, 52, 61, 0.05);
        }

        .mobile-sidebar-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }

        .sidebar-brand {
            padding-right: 3rem;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.25rem 0.5rem 1.25rem;
            color: var(--primary-color);
            text-decoration: none;
            border-bottom: 1px solid #e8edef;
        }

        .sidebar-brand img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .sidebar-brand span {
            font-weight: 700;
            line-height: 1.2;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0.1rem;
        }

        .sidebar-section-label {
            margin: 1rem 0 0.4rem;
            padding: 0 0.7rem;
            color: #77858b;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-height: 42px;
            margin: 0.15rem 0;
            padding: 0.55rem 0.7rem;
            color: #40545d;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 150ms ease, color 150ms ease;
        }

        .sidebar-link i {
            width: 1.15rem;
            color: #75858c;
            text-align: center;
        }

        .sidebar-link:hover {
            color: var(--primary-color);
            background: #f0f5f6;
        }

        .sidebar-link.active {
            color: #145b83;
            background: #e8f3f8;
            font-weight: 600;
        }

        .sidebar-link.active i {
            color: #1682b4;
        }

        .sidebar-link .badge {
            margin-left: auto;
        }

        .sidebar-account {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 1rem 0.45rem 0;
            border-top: 1px solid #e8edef;
        }

        .sidebar-account-icon {
            display: grid;
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            place-items: center;
            color: #fff;
            background: var(--primary-color);
            border-radius: 50%;
        }

        .sidebar-account-name {
            overflow: hidden;
            color: #263a43;
            font-size: 0.9rem;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-account-role {
            color: #708087;
            font-size: 0.75rem;
        }

        .dashboard-main {
            min-width: 0;
            min-height: 100vh;
            margin-left: 264px;
        }

        .admin-page-viewport {
            position: relative;
            min-height: 100vh;
            background: #f3f6f7;
        }

        .admin-page-frame {
            display: block;
            width: 100%;
            min-height: 100vh;
            border: 0;
            background: #f3f6f7;
        }

        .admin-frame-loading {
            position: absolute;
            inset: 0;
            z-index: 5;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 0.75rem;
            padding-top: 5rem;
            color: #51636c;
            background: rgba(243, 246, 247, 0.94);
        }

        .admin-frame-loading[hidden] {
            display: none;
        }

        .mobile-sidebar-bar {
            display: none;
        }

        .sidebar-backdrop {
            display: none;
        }

        @media (max-width: 991.98px) {
            .dashboard-sidebar {
                width: min(84vw, 300px);
                transform: translateX(-105%);
                transition: transform 220ms ease;
            }

            .dashboard-sidebar.is-open {
                transform: translateX(0);
            }

            .dashboard-main {
                margin-left: 0;
            }

            .admin-page-viewport,
            .admin-page-frame {
                min-height: calc(100vh - 58px);
            }

            .mobile-sidebar-bar {
                position: sticky;
                top: 0;
                z-index: 1020;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                min-height: 58px;
                padding: 0 1rem;
                background: #fff;
                border-bottom: 1px solid #dfe6e8;
            }

            .mobile-sidebar-toggle,
            .mobile-sidebar-close {
                display: inline-grid;
                width: 40px;
                height: 40px;
                place-items: center;
                color: var(--primary-color);
                background: #f0f5f6;
                border: 0;
                border-radius: 6px;
            }

            .sidebar-backdrop:not([hidden]) {
                position: fixed;
                inset: 0;
                z-index: 1030;
                display: block;
                background: rgba(14, 28, 34, 0.48);
                border: 0;
            }
        }

        @media (min-width: 992px) {
            .mobile-sidebar-close {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php if (!$is_embedded): ?>
    <?php $hide_admin_navbar = true; ?>
    <?php include 'admin_header.php'; ?>

    <div class="dashboard-shell">
        <aside class="dashboard-sidebar" id="adminSidebar" aria-label="Administrator navigation">
            <a class="sidebar-brand" href="admin_dashboard.php">
                <img src="../uploads/gallery/seme.jpg" alt="">
                <span>STVC Election System<br><small class="text-muted">Administration</small></span>
            </a>
            <button type="button" class="mobile-sidebar-close" id="sidebarClose" aria-label="Close navigation">
                <i class="fas fa-times"></i>
            </button>
            <nav class="sidebar-nav">
                <p class="sidebar-section-label">Workspace</p>
                <a class="sidebar-link active" href="admin_dashboard.php" aria-current="page"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
                <a class="sidebar-link" href="manage_applications.php"><i class="fas fa-file-lines"></i><span>Applications</span></a>
                <a class="sidebar-link" href="manage_positions.php"><i class="fas fa-list-check"></i><span>Positions</span></a>
                <?php if ($is_super_admin): ?>
                    <a class="sidebar-link" href="manage_elections.php"><i class="fas fa-landmark"></i><span>Elections</span></a>
                <?php endif; ?>
                <a class="sidebar-link" href="manage_current_candidates.php"><i class="fas fa-user-tie"></i><span>Current Leaders</span></a>
                <a class="sidebar-link" href="elected_officials.php"><i class="fas fa-user-check"></i><span>Elected Officials</span></a>
                <a class="sidebar-link" href="manage_gallery.php"><i class="fas fa-images"></i><span>Gallery</span></a>

                <p class="sidebar-section-label">People</p>
                <a class="sidebar-link" href="manage_accounts.php"><i class="fas fa-users"></i><span>Accounts</span><?php if ($is_super_admin && $pending_admins_count > 0): ?><span class="badge rounded-pill text-bg-warning"><?php echo (int)$pending_admins_count; ?></span><?php endif; ?></a>
                <a class="sidebar-link" href="manage_reviews.php"><i class="fas fa-star"></i><span>Reviews</span></a>

                <p class="sidebar-section-label">Communication</p>
                <a class="sidebar-link" href="admin_messages.php"><i class="fas fa-inbox"></i><span>Inbox</span><?php if ($unread_admin_count > 0): ?><span class="badge rounded-pill text-bg-danger"><?php echo $unread_admin_count; ?></span><?php endif; ?></a>
                <a class="sidebar-link" href="send_message.php"><i class="fas fa-paper-plane"></i><span>Send Message</span></a>
                <a class="sidebar-link" href="manage_contents.php"><i class="fas fa-newspaper"></i><span>News &amp; Updates</span></a>
                <?php if ($is_super_admin): ?>
                    <a class="sidebar-link" href="newsletter_subscribers.php"><i class="fas fa-envelope-open-text"></i><span>Newsletter</span></a>
                <?php endif; ?>

                <p class="sidebar-section-label">System</p>
                <a class="sidebar-link" href="reports.php"><i class="fas fa-chart-column"></i><span>Reports</span></a>
                <a class="sidebar-link" href="settings.php"><i class="fas fa-gear"></i><span>Settings</span></a>
                <?php if ($is_super_admin): ?>
                    <a class="sidebar-link" href="data_recovery.php"><i class="fas fa-database"></i><span>Data Recovery</span></a>
                <?php endif; ?>
                <a class="sidebar-link" href="profile.php"><i class="fas fa-user-circle"></i><span>My Profile</span></a>
                <a class="sidebar-link" href="../index.php" target="_top"><i class="fas fa-arrow-up-right-from-square"></i><span>View Site</span></a>
                <a class="sidebar-link" href="../logout.php" target="_top"><i class="fas fa-right-from-bracket"></i><span>Sign Out</span></a>
            </nav>
            <div class="sidebar-account">
                <span class="sidebar-account-icon"><i class="fas fa-<?php echo $is_super_admin ? 'crown' : 'user-shield'; ?>"></i></span>
                <div class="min-w-0">
                    <div class="sidebar-account-name"><?php echo htmlspecialchars(trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''))); ?></div>
                    <div class="sidebar-account-role"><?php echo $is_super_admin ? 'Super Administrator' : 'Administrator'; ?></div>
                </div>
            </div>
        </aside>
        <button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Close navigation" hidden></button>

        <main class="dashboard-main">
            <div class="mobile-sidebar-bar">
                <button type="button" class="mobile-sidebar-toggle" id="sidebarToggle" aria-controls="adminSidebar" aria-expanded="false" aria-label="Open navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="fw-semibold">Admin Dashboard</span>
                <?php if ($is_super_admin): ?><span class="badge text-bg-warning ms-auto">Super Admin</span><?php endif; ?>
            </div>

            <div class="admin-page-viewport" id="adminPageViewport">
                <div class="admin-frame-loading" id="adminFrameLoading" role="status" aria-live="polite" hidden>
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    <span>Loading page...</span>
                </div>
                <iframe class="admin-page-frame" id="adminPageFrame" title="Admin page" hidden></iframe>
                <section id="dashboardHome">

    <div class="container-fluid main-content px-4 px-xl-5">
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

                    <!-- Send Message Card (now with Inbox link) -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-paper-plane fa-3x text-primary mb-3"></i>
                                <h5>Send Message</h5>
                                <p class="text-muted">Send announcements or notifications to students, admins, or all users.</p>
                            </div>
                            <a href="send_message.php" class="btn btn-admin mt-3 align-self-start mb-2">Go to Send Message</a>
                            <a href="admin_messages.php" class="btn btn-outline-primary mt-1 align-self-start">Go to Inbox</a>
                        </div>
                    </div>
                    <!-- Manage News & Updates Card (replaces Inbox card) -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="action-card text-center d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <i class="fas fa-newspaper fa-3x text-success mb-3"></i>
                                <h5>Manage News & Updates</h5>
                                <p class="text-muted">Create, edit, and publish news or updates for the system.</p>
                            </div>
                            <a href="manage_contents.php" class="btn btn-admin mt-3 align-self-start">Go to News & Updates</a>
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
                    <!-- Newsletter Management Card -->
                    <div class="col-md-4 mb-4">
                        <div class="action-card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="mb-3"><i class="fas fa-envelope-open-text text-primary me-2"></i>Newsletter Management</h5>
                                <p class="text-muted">Manage your newsletter subscribers, send important news, and keep your audience engaged.</p>
                            </div>
                            <div class="mt-3">
                                <a href="newsletter_subscribers.php" class="btn btn-admin mb-2 w-100"><i class="fas fa-users me-2"></i>View Subscribers</a>
                                <a href="publish_newsletter_on_important_news.php" class="btn btn-admin mb-2 w-100"><i class="fas fa-paper-plane me-2"></i>Send Important News</a>
                                <a href="#" class="btn btn-outline-secondary w-100 disabled" tabindex="-1"><i class="fas fa-info-circle me-2"></i>How Newsletter Works</a>
                            </div>
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
    <?php if (!$is_embedded): ?>
                </section>
            </div>
    <?php endif; ?>

        <!-- Footer -->
        <footer class="footer mt-5">
        <div class="footer-overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="footer-brand d-flex align-items-center justify-content-center justify-content-md-start">
                        <img src="../uploads/gallery/seme.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
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
    <?php if (!$is_embedded): ?>
    <script>
        const adminSidebar = document.getElementById('adminSidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const adminFrame = document.getElementById('adminPageFrame');
        const dashboardHome = document.getElementById('dashboardHome');
        const frameLoading = document.getElementById('adminFrameLoading');
        const adminPrefix = window.location.pathname.replace(/admin_dashboard\.php$/, '');
        let frameResizeObserver = null;

        function closeAdminSidebar() {
            adminSidebar.classList.remove('is-open');
            sidebarBackdrop.hidden = true;
            sidebarToggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('sidebar-open');
        }

        sidebarToggle.addEventListener('click', function () {
            adminSidebar.classList.add('is-open');
            sidebarBackdrop.hidden = false;
            sidebarToggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('sidebar-open');
            sidebarClose.focus();
        });
        sidebarClose.addEventListener('click', closeAdminSidebar);
        sidebarBackdrop.addEventListener('click', closeAdminSidebar);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeAdminSidebar();
        });

        function setActiveSidebarLink(url) {
            const destination = new URL(url, window.location.href);
            adminSidebar.querySelectorAll('.sidebar-link').forEach(function (link) {
                const active = new URL(link.href, window.location.href).pathname === destination.pathname;
                link.classList.toggle('active', active);
                if (active) link.setAttribute('aria-current', 'page');
                else link.removeAttribute('aria-current');
            });
        }

        function loadAdminPage(rawUrl, updateHistory) {
            const destination = new URL(rawUrl, window.location.href);
            if (destination.origin !== window.location.origin || !destination.pathname.startsWith(adminPrefix)) {
                window.location.assign(destination.href);
                return;
            }

            if (updateHistory) {
                const shellUrl = new URL(window.location.href);
                shellUrl.hash = destination.pathname.endsWith('/admin_dashboard.php')
                    ? ''
                    : encodeURIComponent(destination.pathname + destination.search);
                window.history.pushState({ adminUrl: destination.href }, '', shellUrl.href);
            }

            if (frameResizeObserver) frameResizeObserver.disconnect();
            if (destination.pathname.endsWith('/admin_dashboard.php')) {
                adminFrame.hidden = true;
                dashboardHome.hidden = false;
                frameLoading.hidden = true;
                setActiveSidebarLink(destination.href);
                document.title = 'Admin Dashboard - STVC Election System';
                if (window.matchMedia('(max-width: 991.98px)').matches) closeAdminSidebar();
                return;
            }

            dashboardHome.hidden = true;
            adminFrame.hidden = false;
            frameLoading.hidden = false;
            setActiveSidebarLink(destination.href);
            if (window.matchMedia('(max-width: 991.98px)').matches) closeAdminSidebar();
            destination.searchParams.set('embedded', '1');
            adminFrame.src = destination.href;
        }

        function handleShellNavigation(event) {
            const link = event.target.closest('a[href]');
            if (!link || link.target === '_blank' || link.target === '_top' || link.hasAttribute('download') || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.getAttribute('href').startsWith('#')) return;
            const destination = new URL(link.href, window.location.href);
            if (destination.origin !== window.location.origin || !destination.pathname.startsWith(adminPrefix)) return;
            event.preventDefault();
            loadAdminPage(destination.href, true);
        }

        document.querySelector('.dashboard-shell').addEventListener('click', handleShellNavigation);

        adminFrame.addEventListener('load', function () {
            frameLoading.hidden = true;
            try {
                const frameDocument = adminFrame.contentDocument;
                const loadedUrl = new URL(frameDocument.location.href);
                const frameStyle = frameDocument.createElement('style');
                frameStyle.textContent = 'nav.navbar { display: none !important; } body { padding-top: 0 !important; min-height: 100vh; } body > .container, body > .container-fluid { max-width: none; }';
                frameDocument.head.appendChild(frameStyle);
                adminFrame.style.height = Math.max(window.innerHeight - (window.matchMedia('(max-width: 991.98px)').matches ? 58 : 0), frameDocument.documentElement.scrollHeight) + 'px';
                if (loadedUrl.origin === window.location.origin && loadedUrl.pathname.startsWith(adminPrefix)) {
                    setActiveSidebarLink(loadedUrl.href);
                    const shellUrl = new URL(window.location.href);
                    shellUrl.hash = encodeURIComponent(loadedUrl.pathname + loadedUrl.search);
                    window.history.replaceState({ adminUrl: loadedUrl.href }, '', shellUrl.href);
                }
                document.title = frameDocument.title || 'Admin Dashboard - STVC Election System';
                frameDocument.addEventListener('click', handleShellNavigation);
                frameResizeObserver = new ResizeObserver(function () {
                    adminFrame.style.height = Math.max(window.innerHeight - (window.matchMedia('(max-width: 991.98px)').matches ? 58 : 0), frameDocument.documentElement.scrollHeight) + 'px';
                });
                frameResizeObserver.observe(frameDocument.documentElement);
            } catch (error) {
                window.location.assign(adminFrame.src);
            }
            if (!adminFrame.contentWindow.location.pathname.startsWith(adminPrefix)) {
                window.location.assign(adminFrame.contentWindow.location.href);
            }
        });

        function restoreShellRoute() {
            const route = window.location.hash.slice(1);
            if (!route) {
                adminFrame.hidden = true;
                dashboardHome.hidden = false;
                frameLoading.hidden = true;
                if (frameResizeObserver) frameResizeObserver.disconnect();
                setActiveSidebarLink(window.location.pathname);
                document.title = 'Admin Dashboard - STVC Election System';
                return;
            }
            loadAdminPage(decodeURIComponent(route), false);
        }

        window.addEventListener('popstate', restoreShellRoute);

        const initialRoute = window.location.hash.slice(1);
        if (initialRoute) loadAdminPage(decodeURIComponent(initialRoute), false);

        window.addEventListener('resize', function () {
            if (!adminFrame.hidden && !frameResizeObserver && adminFrame.contentDocument) {
                adminFrame.style.height = Math.max(window.innerHeight - (window.matchMedia('(max-width: 991.98px)').matches ? 58 : 0), adminFrame.contentDocument.documentElement.scrollHeight) + 'px';
            }
        });
    </script>
    <?php endif; ?>
        </main>
    </div>
    <?php endif; ?>
</body>
</html> 