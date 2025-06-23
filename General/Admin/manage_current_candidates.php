<?php
require_once '../config/session_config.php';
require_once '../config/database.php';
include 'admin_header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle add, edit, delete actions
$success = '';
$error = '';

// Add new leader
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_leader'])) {
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $hierarchy_order = (int)($_POST['hierarchy_order'] ?? 0);
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/current_candidates/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'leader_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $filepath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
            $image = $filename;
        } else {
            $error = 'Failed to upload image.';
        }
    }
    if ($name && $position && $department && $hierarchy_order && $image) {
        $stmt = $conn->prepare("INSERT INTO current_candidates (name, position, department, hierarchy_order, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssis', $name, $position, $department, $hierarchy_order, $image);
        if ($stmt->execute()) {
            $success = 'Current leader added successfully!';
        } else {
            $error = 'Failed to add leader.';
        }
        $stmt->close();
    } else if (!$error) {
        $error = 'All fields are required.';
    }
}

// Delete leader
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // --- Start Secret Archival ---
    $select_stmt = $conn->prepare("SELECT * FROM current_candidates WHERE id = ?");
    $select_stmt->bind_param('i', $id);
    $select_stmt->execute();
    $leader_data = $select_stmt->get_result()->fetch_assoc();
    $select_stmt->close();
    
    if ($leader_data) {
        $item_data_json = json_encode($leader_data);
        $item_type = 'current_leader';
        $item_identifier = $leader_data['id'];
        $admin_id = $_SESSION['user_id'] ?? null;
        $admin_name = $_SESSION['user_name'] ?? 'Unknown Admin';

        $archive_stmt = $conn->prepare("INSERT INTO deleted_items (item_type, item_identifier, item_data, deleted_by_user_id, deleted_by_user_name) VALUES (?, ?, ?, ?, ?)");
        $archive_stmt->bind_param('sssis', $item_type, $item_identifier, $item_data_json, $admin_id, $admin_name);
        $archive_stmt->execute();
        $archive_stmt->close();
        
        // Proceed with original deletion
        if (!empty($leader_data['image']) && file_exists('../uploads/current_candidates/' . $leader_data['image'])) {
            unlink('../uploads/current_candidates/' . $leader_data['image']);
        }
        $stmt = $conn->prepare("DELETE FROM current_candidates WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $success = 'Leader deleted.';
        } else {
            $error = 'Failed to delete leader.';
        }
        $stmt->close();
    } else {
        $error = 'Leader not found.';
    }
}

// Fetch all current leaders
$current_leaders = [];
$sql = "SELECT * FROM current_candidates ORDER BY hierarchy_order ASC, created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $current_leaders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Current Leaders - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../includes/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
  
    <div class="container py-4" style="margin-bottom: 0; padding-bottom: 0;">
        <?php if ($success): ?><div class="alert alert-success"> <?php echo $success; ?> </div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"> <?php echo $error; ?> </div><?php endif; ?>
        <div class="action-card text-center d-flex flex-column h-100 mb-4 p-0 overflow-hidden" style="box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 15px;">
            <div style="background: linear-gradient(135deg, #2c3e50, #3498db); padding: 2rem 1rem 1.5rem 1rem;">
                <img src="../uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:48px;width:auto;margin-bottom:10px;">
                <h4 class="text-white mb-0">STVC Election System - Admin</h4>
            </div>
            <div class="flex-grow-1 p-4">
                <i class="fas fa-user-tie fa-3x text-warning mb-3"></i>
                <h5>Manage Current Leaders</h5>
                <p class="text-muted">Upload, view, and manage the current leaders of the institution. The reign start date is shown for each leader.</p>
                <form method="POST" enctype="multipart/form-data" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Department/Office</label>
                            <input type="text" name="department" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Hierarchy Order</label>
                            <input type="number" name="hierarchy_order" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" name="add_leader" class="btn btn-primary">Add</button>
                        </div>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Department/Office</th>
                                <th>Hierarchy</th>
                                <th>Reign Started</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_leaders as $leader): ?>
                                <tr>
                                    <td><img src="../uploads/current_candidates/<?php echo htmlspecialchars($leader['image']); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:50%;"></td>
                                    <td><?php echo htmlspecialchars($leader['name']); ?></td>
                                    <td><?php echo htmlspecialchars($leader['position']); ?></td>
                                    <td><?php echo htmlspecialchars($leader['department']); ?></td>
                                    <td><?php echo (int)$leader['hierarchy_order']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($leader['created_at'])); ?></td>
                                    <td>
                                        <a href="?delete=<?php echo $leader['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this leader?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        html, body {
            height: 100%;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container {
            flex: 1 0 auto;
        }
        .footer {
            margin-top: auto !important;
            background-image: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1200 120\" preserveAspectRatio=\"none\"><path d=\"M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z\" fill=\"%232c3e50\"></path></svg>');
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