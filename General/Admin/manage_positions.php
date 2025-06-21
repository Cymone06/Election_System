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
$positions = [];
$total_positions = 0;
$active_positions = 0;
$total_applications = 0;

// Handle position actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'create') {
            $position_name = $_POST['position_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $requirements = $_POST['requirements'] ?? '';
            $responsibilities = $_POST['responsibilities'] ?? '';
            $deadline = $_POST['deadline'] ?? '';
            
            $stmt = $conn->prepare("INSERT INTO positions (position_name, description, requirements, responsibilities, deadline, status, created_by) VALUES (?, ?, ?, ?, ?, 'active', ?)");
            if ($stmt) {
                $stmt->bind_param("sssssi", $position_name, $description, $requirements, $responsibilities, $deadline, $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            header('Location: manage_positions.php?success=created');
            exit();
        } elseif ($action === 'update') {
            $position_id = $_POST['position_id'] ?? 0;
            $position_name = $_POST['position_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $requirements = $_POST['requirements'] ?? '';
            $responsibilities = $_POST['responsibilities'] ?? '';
            $deadline = $_POST['deadline'] ?? '';
            $status = $_POST['status'] ?? 'active';
            
            $stmt = $conn->prepare("UPDATE positions SET position_name = ?, description = ?, requirements = ?, responsibilities = ?, deadline = ?, status = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssssssi", $position_name, $description, $requirements, $responsibilities, $deadline, $status, $position_id);
                $stmt->execute();
                $stmt->close();
            }
            
            header('Location: manage_positions.php?success=updated');
            exit();
        } elseif ($action === 'delete') {
            $position_id = $_POST['position_id'] ?? 0;

            // --- Start Secret Archival ---
            $select_stmt = $conn->prepare("SELECT * FROM positions WHERE id = ?");
            $select_stmt->bind_param('i', $position_id);
            $select_stmt->execute();
            $position_data = $select_stmt->get_result()->fetch_assoc();
            $select_stmt->close();

            if ($position_data) {
                $item_data_json = json_encode($position_data);
                $item_type = 'position';
                $item_identifier = $position_data['id'];
                $admin_id = $_SESSION['user_id'] ?? null;
                $admin_name = $_SESSION['user_name'] ?? 'Unknown Admin';

                $archive_stmt = $conn->prepare("INSERT INTO deleted_items (item_type, item_identifier, item_data, deleted_by_user_id, deleted_by_user_name) VALUES (?, ?, ?, ?, ?)");
                $archive_stmt->bind_param('sssis', $item_type, $item_identifier, $item_data_json, $admin_id, $admin_name);
                $archive_stmt->execute();
                $archive_stmt->close();

                // Also consider what to do with applications for this position.
                // For now, we'll just delete the position as requested.
                // A more robust solution might be to prevent deletion if applications exist.
            }
            // --- End Secret Archival ---

            $stmt = $conn->prepare("DELETE FROM positions WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $position_id);
                $stmt->execute();
                $stmt->close();
            }
            
            header('Location: manage_positions.php?success=deleted');
            exit();
        }
    }
}

// Get positions with application counts - with error handling
try {
    $query = "
        SELECT 
            p.*,
            COUNT(a.id) as application_count,
            u.first_name as created_by_name
        FROM positions p
        LEFT JOIN applications a ON p.id = a.position_id
        LEFT JOIN users u ON p.created_by = u.id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ";

    $result = $conn->query($query);
    if ($result) {
        $positions = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $positions = [];
    }
} catch (Exception $e) {
    $positions = [];
}

// Get statistics with comprehensive error handling
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM positions");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $total_positions = $row ? (int)$row['total'] : 0;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $total_positions = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM positions WHERE status = 'active'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $active_positions = $row ? (int)$row['active'] : 0;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $active_positions = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_applications FROM applications");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $total_applications = $row ? (int)$row['total_applications'] : 0;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $total_applications = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Positions - Admin Dashboard</title>
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

        .position-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .position-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
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

        .btn-create {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .btn-edit {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .btn-delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .status-badge {
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
                        <a class="nav-link active" href="manage_positions.php"><i class="fas fa-list me-1"></i> Positions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php"><i class="fas fa-users me-1"></i> Users</a>
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
                    <h1 class="mb-2"><i class="fas fa-list me-2"></i>Position Management</h1>
                    <p class="mb-0">Create and manage election positions</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#createPositionModal">
                        <i class="fas fa-plus me-2"></i>Create Position
                    </button>
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
                    $message = $_GET['success'] === 'created' ? 'Position created successfully!' : 
                               ($_GET['success'] === 'updated' ? 'Position updated successfully!' : 'Position deleted successfully!');
                    echo $message;
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="text-primary mb-2">
                        <i class="fas fa-list fa-2x"></i>
                    </div>
                    <h3><?php echo $total_positions; ?></h3>
                    <p class="text-muted mb-0">Total Positions</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="text-success mb-2">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h3><?php echo $active_positions; ?></h3>
                    <p class="text-muted mb-0">Active Positions</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="text-info mb-2">
                        <i class="fas fa-file-alt fa-2x"></i>
                    </div>
                    <h3><?php echo $total_applications; ?></h3>
                    <p class="text-muted mb-0">Total Applications</p>
                </div>
            </div>
        </div>

        <!-- Positions List -->
        <div class="positions-list">
            <?php if (empty($positions)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-list fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No positions found</h4>
                    <p class="text-muted">Create your first position to get started.</p>
                    <button class="btn btn-admin btn-create" data-bs-toggle="modal" data-bs-target="#createPositionModal">
                        <i class="fas fa-plus me-2"></i>Create Position
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($positions as $position): ?>
                    <div class="position-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2">
                                    <h5 class="mb-0 me-3"><?php echo htmlspecialchars($position['position_name']); ?></h5>
                                    <span class="status-badge badge bg-<?php echo $position['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($position['status']); ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-file-alt me-1"></i><?php echo $position['application_count']; ?> applications
                                </p>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-calendar me-1"></i>Deadline: <?php echo date('M d, Y', strtotime($position['deadline'])); ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-user me-1"></i>Created by: <?php echo htmlspecialchars($position['created_by_name']); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="d-flex flex-column gap-2">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewPositionModal<?php echo $position['id']; ?>">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                    <button class="btn btn-sm btn-edit" data-bs-toggle="modal" data-bs-target="#editPositionModal<?php echo $position['id']; ?>">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-delete" onclick="confirmDelete(<?php echo $position['id']; ?>, '<?php echo htmlspecialchars($position['position_name']); ?>')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- View Position Modal -->
                    <div class="modal fade" id="viewPositionModal<?php echo $position['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Position Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Basic Information</h6>
                                            <p><strong>Title:</strong> <?php echo htmlspecialchars($position['position_name']); ?></p>
                                            <p><strong>Status:</strong> 
                                                <span class="badge bg-<?php echo $position['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($position['status']); ?>
                                                </span>
                                            </p>
                                            <p><strong>Deadline:</strong> <?php echo date('M d, Y', strtotime($position['deadline'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Statistics</h6>
                                            <p><strong>Applications:</strong> <?php echo $position['application_count']; ?></p>
                                            <p><strong>Created by:</strong> <?php echo htmlspecialchars($position['created_by_name']); ?></p>
                                            <p><strong>Created on:</strong> <?php echo date('M d, Y', strtotime($position['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <h6>Description</h6>
                                            <div class="bg-light p-3 rounded">
                                                <?php echo nl2br(htmlspecialchars($position['description'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <h6>Requirements</h6>
                                            <div class="bg-light p-3 rounded">
                                                <?php echo nl2br(htmlspecialchars($position['requirements'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Position Modal -->
                    <div class="modal fade" id="editPositionModal<?php echo $position['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Position</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="position_id" value="<?php echo $position['id']; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="position_name<?php echo $position['id']; ?>" class="form-label">Position Title</label>
                                                    <input type="text" class="form-control" id="position_name<?php echo $position['id']; ?>" name="position_name" value="<?php echo htmlspecialchars($position['position_name']); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description<?php echo $position['id']; ?>" class="form-label">Description</label>
                                            <textarea class="form-control" id="description<?php echo $position['id']; ?>" name="description" rows="4" required><?php echo htmlspecialchars($position['description']); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="requirements<?php echo $position['id']; ?>" class="form-label">Requirements</label>
                                            <textarea class="form-control" id="requirements<?php echo $position['id']; ?>" name="requirements" rows="4" required><?php echo htmlspecialchars($position['requirements']); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="responsibilities<?php echo $position['id']; ?>" class="form-label">Responsibilities</label>
                                            <textarea class="form-control" id="responsibilities<?php echo $position['id']; ?>" name="responsibilities" rows="4" required><?php echo htmlspecialchars($position['responsibilities']); ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="deadline<?php echo $position['id']; ?>" class="form-label">Application Deadline</label>
                                                    <input type="date" class="form-control" id="deadline<?php echo $position['id']; ?>" name="deadline" value="<?php echo $position['deadline']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status<?php echo $position['id']; ?>" class="form-label">Status</label>
                                                    <select class="form-select" id="status<?php echo $position['id']; ?>" name="status" required>
                                                        <option value="active" <?php echo $position['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $position['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-admin">Update Position</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Position Modal -->
    <div class="modal fade" id="createPositionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="position_name" class="form-label">Position Title</label>
                                    <input type="text" class="form-control" id="position_name" name="position_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="requirements" class="form-label">Requirements</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="responsibilities" class="form-label">Responsibilities</label>
                            <textarea class="form-control" id="responsibilities" name="responsibilities" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Application Deadline</label>
                            <input type="date" class="form-control" id="deadline" name="deadline" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-admin btn-create">Create Position</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="position_id" id="deletePositionId">
    </form>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmDelete(positionId, positionTitle) {
            if (confirm(`Are you sure you want to delete the position "${positionTitle}"? This action cannot be undone.`)) {
                document.getElementById('deletePositionId').value = positionId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Set minimum date for deadline to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('deadline').min = today;
    </script>

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
</body>
</html> 