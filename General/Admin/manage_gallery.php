<?php
require_once '../config/session_config.php';
require_once '../config/connect.php';
require_once 'admin_header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $desc = trim($_POST['description'] ?? '');
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($ext, $allowed)) {
            $filename = uniqid('gallery_', true) . '.' . $ext;
            $target = '../uploads/gallery/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $stmt = $conn->prepare("INSERT INTO gallery (filename, description, uploaded_at) VALUES (?, ?, NOW())");
                $stmt->bind_param('ss', $filename, $desc);
                $stmt->execute();
                $stmt->close();
                $success = 'Image uploaded successfully!';
            } else {
                $error = 'Failed to upload image.';
            }
        } else {
            $error = 'Invalid file type.';
        }
    } else {
        $error = 'No image selected or upload error.';
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("UPDATE gallery SET status = 'deleted' WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $success = 'Image moved to recovery archive.';
    } else {
        $error = 'Failed to delete image.';
    }
    $stmt->close();
}

// Handle description update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_desc' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $desc = trim($_POST['description'] ?? '');
    $stmt = $conn->prepare("UPDATE gallery SET description = ? WHERE id = ?");
    $stmt->bind_param('si', $desc, $id);
    $stmt->execute();
    $stmt->close();
    $success = 'Description updated.';
}

// Fetch all gallery images
$images = [];
$res = $conn->query("SELECT id, filename, description, uploaded_at FROM gallery WHERE status = 'active' ORDER BY uploaded_at DESC");
while ($row = $res->fetch_assoc()) {
    $images[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gallery-img { width: 100px; height: 70px; object-fit: cover; border-radius: 8px; }
        .desc-form { display: flex; gap: 0.5rem; align-items: center; }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">Manage Gallery</h2>
    <?php if (!empty($success)): ?><div class="alert alert-success"> <?php echo $success; ?> </div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger"> <?php echo $error; ?> </div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <input type="hidden" name="action" value="upload">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="file" name="image" class="form-control" required accept="image/*">
            </div>
            <div class="col-md-5">
                <input type="text" name="description" class="form-control" placeholder="Description" maxlength="255">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-2"></i>Upload</button>
            </div>
        </div>
    </form>
    <div class="row">
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $img): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <img src="../uploads/gallery/<?php echo htmlspecialchars($img['filename']); ?>" class="card-img-top gallery-img" style="width:100%;height:180px;object-fit:cover;">
                        <div class="card-body">
                            <form method="POST" class="desc-form mb-2">
                                <input type="hidden" name="action" value="update_desc">
                                <input type="hidden" name="id" value="<?php echo $img['id']; ?>">
                                <input type="text" name="description" value="<?php echo htmlspecialchars($img['description']); ?>" class="form-control form-control-sm mb-2" maxlength="255" placeholder="Description">
                                <button type="submit" class="btn btn-sm btn-success w-100"><i class="fas fa-save me-1"></i>Save</button>
                            </form>
                            <div class="text-muted small mb-2"><i class="far fa-calendar-alt me-1"></i><?php echo date('M d, Y', strtotime($img['uploaded_at'])); ?></div>
                            <a href="?delete=<?php echo $img['id']; ?>" class="btn btn-sm btn-danger w-100" onclick="return confirm('Are you sure you want to move this image to the recovery archive?');"><i class="fas fa-trash me-1"></i>Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted">No images in gallery.</div>
        <?php endif; ?>
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