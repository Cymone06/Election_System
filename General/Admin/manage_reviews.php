<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle approve/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['action'])) {
    $review_id = (int)$_POST['review_id'];
    if ($_POST['action'] === 'approve') {
        $stmt = $conn->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->bind_param('i', $review_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] === 'delete') {
        // --- Start Secret Archival ---
        // 1. Fetch the full record before deleting
        $select_stmt = $conn->prepare("SELECT * FROM reviews WHERE id = ?");
        $select_stmt->bind_param('i', $review_id);
        $select_stmt->execute();
        $review_data = $select_stmt->get_result()->fetch_assoc();
        $select_stmt->close();

        if ($review_data) {
            // 2. Convert data to JSON
            $item_data_json = json_encode($review_data);
            $item_type = 'review';
            $item_identifier = $review_data['id'];
            
            // Get admin details for logging
            $admin_id = $_SESSION['user_id'] ?? null;
            $admin_name = $_SESSION['user_name'] ?? 'Unknown Admin'; // Assuming user_name is stored in session

            // 3. Insert into the deleted_items table
            $archive_stmt = $conn->prepare("INSERT INTO deleted_items (item_type, item_identifier, item_data, deleted_by_user_id, deleted_by_user_name) VALUES (?, ?, ?, ?, ?)");
            $archive_stmt->bind_param('sssis', $item_type, $item_identifier, $item_data_json, $admin_id, $admin_name);
            $archive_stmt->execute();
            $archive_stmt->close();
        }
        // --- End Secret Archival ---

        // 4. Proceed with the original deletion
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->bind_param('i', $review_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: manage_reviews.php');
    exit();
}

// Auto-delete reviews older than 20 days
$conn->query("DELETE FROM reviews WHERE created_at < (NOW() - INTERVAL 20 DAY)");

// Fetch all reviews
$result = $conn->query("SELECT * FROM reviews ORDER BY created_at DESC");
$reviews = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Admin</title>
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
    </style>
</head>
<body>
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
                    <li class="nav-item"><a class="nav-link active" href="manage_reviews.php"><i class="fas fa-comments me-1"></i> Reviews</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-5">
        <h2 class="mb-4 text-center section-title">Manage Student Reviews</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No reviews found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $i => $review): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td><?php echo htmlspecialchars($review['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($review['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($review['content']); ?></td>
                                <td><span class="status-badge status-<?php echo htmlspecialchars($review['status']); ?>"><?php echo ucfirst($review['status']); ?></span></td>
                                <td><?php echo date('M d, Y H:i', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <?php if ($review['status'] === 'pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success mb-1">Approve</button>
                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger mb-1">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger mb-1">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 