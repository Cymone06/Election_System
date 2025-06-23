<?php
require_once 'config/database.php';

// Get the update ID from the URL
$update_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch the update details from the database
$stmt = $conn->prepare("SELECT * FROM updates WHERE id = ?");
$stmt->bind_param("i", $update_id);
$stmt->execute();
$result = $stmt->get_result();
$update = $result->fetch_assoc();
$stmt->close();

// If update not found, redirect to home page
if (!$update) {
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['student_db_id'])) {
    $student_db_id = $_SESSION['student_db_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, profile_picture FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $profile_pic_path = (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) ? $user['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=3498db&color=fff&size=128';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($update['title']); ?> - STVC Election System</title>
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
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .update-section {
            padding: 80px 0;
        }

        .update-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .update-header {
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .update-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .update-content {
            line-height: 1.8;
        }

        .update-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
        }

        .related-updates {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 30px;
        }

        .related-update-card {
            border-left: 4px solid var(--secondary-color);
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .related-update-card:hover {
            transform: translateX(5px);
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
                <span class="fw-bold" style="color:white;letter-spacing:1px;">STVC Election System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width:36px;height:36px;border-radius:50%;object-fit:cover;margin-right:8px;vertical-align:middle;">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Update Section -->
    <section class="update-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="update-card">
                        <div class="update-header">
                            <h1 class="mb-3"><?php echo htmlspecialchars($update['title']); ?></h1>
                            <div class="update-meta">
                                <i class="fas fa-clock me-2"></i>Posted: <?php echo date('F j, Y g:i A', strtotime($update['created_at'])); ?>
                                <?php if ($update['author']): ?>
                                    <span class="ms-3"><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($update['author']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($update['image']): ?>
                            <img src="<?php echo htmlspecialchars($update['image']); ?>" alt="<?php echo htmlspecialchars($update['title']); ?>" class="update-image">
                        <?php endif; ?>

                        <div class="update-content">
                            <?php echo nl2br(htmlspecialchars($update['content'])); ?>
                        </div>

                        <?php if ($update['additional_info']): ?>
                            <div class="mt-4">
                                <h4>Additional Information</h4>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <?php echo nl2br(htmlspecialchars($update['additional_info'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="related-updates">
                        <h4 class="mb-4">Related Updates</h4>
                        <?php
                        // Fetch related updates
                        $stmt = $conn->prepare("SELECT id, title, created_at FROM updates WHERE id != ? ORDER BY created_at DESC LIMIT 3");
                        $stmt->bind_param("i", $update_id);
                        $stmt->execute();
                        $related_updates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        $stmt->close();

                        foreach ($related_updates as $related):
                        ?>
                            <div class="related-update-card">
                                <h5 class="mb-2">
                                    <a href="updates.php?id=<?php echo $related['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h5>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('M d, Y', strtotime($related['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 