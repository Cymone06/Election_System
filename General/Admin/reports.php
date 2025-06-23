<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

// Check if database connection is working
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection not established'));
}

// Check if user is logged in and is admin or super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Initialize variables with default values
$total_applications = 0;
$pending_applications = 0;
$approved_applications = 0;
$rejected_applications = 0;
$total_users = 0;
$total_positions = 0;
$applications_by_month = [];
$top_positions = [];
$recent_activity = [];
$all_positions = [];

// Get statistics for charts with comprehensive error handling
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM applications");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $total_applications = $row ? (int)$row['total'] : 0;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $total_applications = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM applications WHERE status = 'pending'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $pending_applications = $row ? (int)$row['pending'] : 0;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $pending_applications = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as approved FROM applications WHERE status = 'approved'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $approved_applications = $row ? (int)$row['approved'] : 0;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $approved_applications = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as rejected FROM applications WHERE status = 'rejected'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $rejected_applications = $row ? (int)$row['rejected'] : 0;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $rejected_applications = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $total_users = $row ? (int)$row['total_users'] : 0;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $total_users = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_positions FROM positions");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $total_positions = $row ? (int)$row['total_positions'] : 0;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $total_positions = 0;
}

// Get applications by month for the last 6 months
try {
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
        if ($stmt) {
            $stmt->bind_param("s", $month);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $applications_by_month[] = [
                    'month' => date('M Y', strtotime($month . '-01')),
                    'count' => $row ? (int)$row['count'] : 0
                ];
            }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    $applications_by_month = [];
}

// Get all positions data for charts (dynamic)
try {
    $stmt = $conn->prepare("
        SELECT p.title, p.department, p.status, COUNT(a.id) as application_count,
               COUNT(CASE WHEN a.status = 'approved' THEN 1 END) as approved_count,
               COUNT(CASE WHEN a.status = 'rejected' THEN 1 END) as rejected_count,
               COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_count
        FROM positions p
        LEFT JOIN applications a ON p.id = a.position_id
        GROUP BY p.id, p.title, p.department, p.status
        ORDER BY application_count DESC, p.title ASC
    ");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $all_positions = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $all_positions = [];
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $all_positions = [];
}

// Get top 5 positions for the chart (keeping this for the chart)
try {
    $stmt = $conn->prepare("
        SELECT p.title, COUNT(a.id) as application_count
        FROM positions p
        LEFT JOIN applications a ON p.id = a.position_id
        GROUP BY p.id, p.title
        ORDER BY application_count DESC
        LIMIT 5
    ");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $top_positions = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $top_positions = [];
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $top_positions = [];
}

// Get recent activity
try {
    $stmt = $conn->prepare("
        SELECT 'application' as type, a.created_at, u.first_name, u.last_name, p.title as position_title
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN positions p ON a.position_id = p.id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $recent_activity = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $recent_activity = [];
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $recent_activity = [];
}

// Fetch all election periods for the report generator
$election_periods = [];
try {
    $stmt = $conn->prepare("SELECT id, title, status FROM election_periods ORDER BY start_date DESC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $election_periods = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Handle exception if needed
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .chart-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .activity-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .btn-admin {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: white;
        }

        .export-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .main-content {
            padding-bottom: 3rem;
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
                        <a class="nav-link active" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a>
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
                    <h1 class="mb-2"><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h1>
                    <p class="mb-0">Comprehensive insights into the election system</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <i class="fas fa-chart-line fa-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Report Generator Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-file-word me-2"></i>Generate Election Report</h4>
            </div>
            <div class="card-body">
                <form action="generate_word_report.php" method="GET" target="_blank">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label for="election_id" class="form-label">Select Election Period:</label>
                            <select name="election_id" id="election_id" class="form-select" required>
                                <option value="">-- Choose an Election --</option>
                                <?php foreach ($election_periods as $election): ?>
                                    <option value="<?php echo $election['id']; ?>">
                                        <?php echo htmlspecialchars($election['title']); ?> (<?php echo ucfirst($election['status']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-download me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Key Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-primary mb-2">
                        <i class="fas fa-file-alt fa-2x"></i>
                    </div>
                    <h3><?php echo $total_applications; ?></h3>
                    <p class="text-muted mb-0">Total Applications</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-success mb-2">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h3><?php echo $total_users; ?></h3>
                    <p class="text-muted mb-0">Registered Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-warning mb-2">
                        <i class="fas fa-list fa-2x"></i>
                    </div>
                    <h3><?php echo $total_positions; ?></h3>
                    <p class="text-muted mb-0">Active Positions</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-info mb-2">
                        <i class="fas fa-percentage fa-2x"></i>
                    </div>
                    <h3><?php echo $total_applications > 0 ? round(($approved_applications / $total_applications) * 100, 1) : 0; ?>%</h3>
                    <p class="text-muted mb-0">Approval Rate</p>
                </div>
            </div>
        </div>

        <!-- Export Section -->
        <div class="export-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="fas fa-download me-2"></i>Export Reports</h5>
                </div>
                <div class="col-md-6 text-md-end">
                    <button class="btn btn-admin me-2" onclick="exportData('applications')">
                        <i class="fas fa-file-excel me-1"></i>Export Applications
                    </button>
                    <button class="btn btn-admin me-2" onclick="exportData('users')">
                        <i class="fas fa-file-excel me-1"></i>Export Users
                    </button>
                    <button class="btn btn-admin" onclick="exportData('positions')">
                        <i class="fas fa-file-excel me-1"></i>Export Positions
                    </button>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-card">
                    <h5><i class="fas fa-chart-pie me-2"></i>Application Status Distribution</h5>
                    <div style="height: 200px; width: 100%;">
                        <canvas id="applicationStatusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-card">
                    <h5><i class="fas fa-chart-line me-2"></i>Applications Over Time</h5>
                    <div style="height: 200px; width: 100%;">
                        <canvas id="applicationsOverTimeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Reports Button -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <button class="btn btn-primary btn-lg" onclick="generateReport()">
                    <i class="fas fa-file-alt me-2"></i>Generate Complete Report
                </button>
            </div>
        </div>

        <!-- Text-Based Reports -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-card">
                    <h5><i class="fas fa-list me-2"></i>Positions Summary</h5>
                    <div class="report-content">
                        <?php 
                        // Get positions summary
                        $positions_summary = [];
                        try {
                            $stmt = $conn->prepare("
                                SELECT p.title, p.department, COUNT(a.id) as total_apps,
                                       COUNT(CASE WHEN a.status = 'approved' THEN 1 END) as approved,
                                       COUNT(CASE WHEN a.status = 'rejected' THEN 1 END) as rejected,
                                       COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending
                                FROM positions p
                                LEFT JOIN applications a ON p.id = a.position_id
                                GROUP BY p.id, p.title, p.department
                                ORDER BY total_apps DESC
                            ");
                            if ($stmt) {
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result) {
                                    $positions_summary = $result->fetch_all(MYSQLI_ASSOC);
                                }
                                $stmt->close();
                            }
                        } catch (Exception $e) {
                            $positions_summary = [];
                        }
                        ?>
                        
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Position</th>
                                        <th>Total</th>
                                        <th>Approved</th>
                                        <th>Rejected</th>
                                        <th>Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($positions_summary, 0, 10) as $pos): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pos['title']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo $pos['total_apps']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $pos['approved']; ?></span></td>
                                            <td><span class="badge bg-danger"><?php echo $pos['rejected']; ?></span></td>
                                            <td><span class="badge bg-warning"><?php echo $pos['pending']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (count($positions_summary) > 10): ?>
                            <p class="text-muted small">Showing top 10 of <?php echo count($positions_summary); ?> positions</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="chart-card">
                    <h5><i class="fas fa-info-circle me-2"></i>System Statistics</h5>
                    <div class="report-content">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center mb-3">
                                    <h4 class="text-primary"><?php echo $total_applications; ?></h4>
                                    <p class="text-muted small">Total Applications</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center mb-3">
                                    <h4 class="text-success"><?php echo $approved_applications; ?></h4>
                                    <p class="text-muted small">Approved</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center mb-3">
                                    <h4 class="text-danger"><?php echo $rejected_applications; ?></h4>
                                    <p class="text-muted small">Rejected</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center mb-3">
                                    <h4 class="text-warning"><?php echo $pending_applications; ?></h4>
                                    <p class="text-muted small">Pending</p>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 class="text-info"><?php echo $total_positions; ?></h4>
                                    <p class="text-muted small">Active Positions</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 class="text-secondary"><?php echo $total_users; ?></h4>
                                    <p class="text-muted small">Total Users</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Summary -->
        <div class="row">
            <div class="col-md-12">
                <div class="chart-card">
                    <h5><i class="fas fa-building me-2"></i>Department Summary</h5>
                    <div class="report-content">
                        <?php 
                        // Get department summary
                        $department_summary = [];
                        try {
                            $stmt = $conn->prepare("
                                SELECT p.department, COUNT(DISTINCT p.id) as positions,
                                       COUNT(a.id) as total_apps,
                                       COUNT(CASE WHEN a.status = 'approved' THEN 1 END) as approved
                                FROM positions p
                                LEFT JOIN applications a ON p.id = a.position_id
                                GROUP BY p.department
                                ORDER BY total_apps DESC
                            ");
                            if ($stmt) {
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result) {
                                    $department_summary = $result->fetch_all(MYSQLI_ASSOC);
                                }
                                $stmt->close();
                            }
                        } catch (Exception $e) {
                            $department_summary = [];
                        }
                        ?>
                        
                        <div class="row">
                            <?php foreach ($department_summary as $dept): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <h6 class="card-title"><?php echo htmlspecialchars($dept['department']); ?></h6>
                                            <div class="row">
                                                <div class="col-4">
                                                    <small class="text-muted">Positions</small>
                                                    <div class="fw-bold"><?php echo $dept['positions']; ?></div>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">Applications</small>
                                                    <div class="fw-bold"><?php echo $dept['total_apps']; ?></div>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">Success Rate</small>
                                                    <div class="fw-bold">
                                                        <?php 
                                                            $rate = $dept['total_apps'] > 0 ? round(($dept['approved'] / $dept['total_apps']) * 100, 1) : 0;
                                                            echo $rate . '%';
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Application Status Chart
        const statusCtx = document.getElementById('applicationStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Rejected'],
                datasets: [{
                    data: [<?php echo $pending_applications; ?>, <?php echo $approved_applications; ?>, <?php echo $rejected_applications; ?>],
                    backgroundColor: ['#f39c12', '#27ae60', '#e74c3c'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 10,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });

        // Applications Over Time Chart
        const timeCtx = document.getElementById('applicationsOverTimeChart').getContext('2d');
        new Chart(timeCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($applications_by_month, 'month')); ?>,
                datasets: [{
                    label: 'Applications',
                    data: <?php echo json_encode(array_column($applications_by_month, 'count')); ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Export functionality
        function exportData(type) {
            // This would typically make an AJAX call to generate and download the file
            alert(`Exporting ${type} data... This feature would generate a CSV/Excel file.`);
        }

        // Generate Complete Report function
        function generateReport() {
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating Report...';
            button.disabled = true;
            
            // Simulate report generation (in real implementation, this would make an AJAX call)
            setTimeout(() => {
                // Create a comprehensive report
                const reportData = {
                    totalApplications: <?php echo $total_applications; ?>,
                    approvedApplications: <?php echo $approved_applications; ?>,
                    rejectedApplications: <?php echo $rejected_applications; ?>,
                    pendingApplications: <?php echo $pending_applications; ?>,
                    totalPositions: <?php echo $total_positions; ?>,
                    totalUsers: <?php echo $total_users; ?>,
                    generatedAt: new Date().toLocaleString()
                };
                
                // Create downloadable report
                const reportText = `
ELECTION SYSTEM REPORT
Generated on: ${reportData.generatedAt}

SUMMARY STATISTICS:
- Total Applications: ${reportData.totalApplications}
- Approved Applications: ${reportData.approvedApplications}
- Rejected Applications: ${reportData.rejectedApplications}
- Pending Applications: ${reportData.pendingApplications}
- Total Positions: ${reportData.totalPositions}
- Total Users: ${reportData.totalUsers}

POSITIONS SUMMARY:
${<?php echo json_encode(array_map(function($pos) {
    return "- " . $pos['title'] . ": " . $pos['total_apps'] . " applications (" . 
           round(($pos['approved'] / max($pos['total_apps'], 1)) * 100, 1) . "% success rate)";
}, array_slice($positions_summary, 0, 10))); ?>.join('\n')}

DEPARTMENT SUMMARY:
${<?php echo json_encode(array_map(function($dept) {
    $rate = $dept['total_apps'] > 0 ? round(($dept['approved'] / $dept['total_apps']) * 100, 1) : 0;
    return "- " . $dept['department'] . ": " . $dept['positions'] . " positions, " . 
           $dept['total_apps'] . " applications, " . $rate . "% success rate";
}, $department_summary)); ?>.join('\n')}
                `;
                
                // Create and download file
                const blob = new Blob([reportText], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `election_report_${new Date().toISOString().split('T')[0]}.txt`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                // Reset button
                button.innerHTML = originalText;
                button.disabled = false;
                
                // Show success message
                alert('Report generated successfully! Check your downloads folder.');
            }, 2000);
        }
    </script>

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
</body>
</html> 