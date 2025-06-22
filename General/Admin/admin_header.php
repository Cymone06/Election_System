<?php
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

// Count unread admin messages for the logged-in admin
function get_unread_admin_message_count($conn, $admin_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM admin_messages WHERE recipient_admin_id = ? AND is_read = 0");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['unread_count'] ?? 0);
}
$unread_admin_count = get_unread_admin_message_count($conn, $admin_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Dashboard - STVC Election System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
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
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .action-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
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

        .btn-create {
            background: linear-gradient(135deg, var(--success-color), #2ecc71);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #20c997);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .user-type-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .modal-content {
            border-radius: 15px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .filter-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
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

        .sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            height: fit-content;
        }

        .sidebar .nav-link {
            color: var(--primary-color);
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--secondary-color);
            color: white;
        }

        .main-content {
            flex: 1;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .pagination .page-link {
            border-radius: 10px;
            margin: 0 2px;
        }

        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }

        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            color: var(--secondary-color);
        }

        .breadcrumb-item a {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--primary-color);
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }
        .form-select-sm {
            margin-right: 0.5rem;
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
                        <a class="nav-link" href="manage_accounts.php"><i class="fas fa-users me-1"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_messages.php"><i class="fas fa-inbox me-1"></i> Inbox<?php if ($unread_admin_count > 0): ?><span class="badge bg-danger ms-1"><?php echo $unread_admin_count; ?></span><?php endif; ?></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-<?php echo $is_super_admin ? 'crown' : 'user-shield'; ?> me-1"></i>
                            <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                            <?php if ($is_super_admin): ?>
                                <span class="badge bg-warning ms-1">Super Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($is_super_admin): ?>
                            <li><a class="dropdown-item" href="manage_accounts.php"><i class="fas fa-user-check me-1"></i> Admin Approvals</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-1"></i> Settings</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="../index.php"><i class="fas fa-home me-2"></i>View Site</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav> 