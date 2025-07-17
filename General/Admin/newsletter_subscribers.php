<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="newsletter_subscribers.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Email', 'Subscribed At']);
    $result = $conn->query('SELECT email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC');
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [$row['email'], $row['subscribed_at']]);
    }
    fclose($output);
    exit();
}

// Fetch all subscribers
$subscribers = [];
$result = $conn->query('SELECT email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC');
if ($result) {
    $subscribers = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Subscribers - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4">Newsletter Subscribers</h2>
        <div class="mb-3">
            <a href="newsletter_subscribers.php?export=csv" class="btn btn-success"><i class="fas fa-file-csv me-1"></i>Export as CSV</a>
            <a href="admin_dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
        </div>
        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($subscribers)): ?>
                    <div class="alert alert-info">No newsletter subscribers found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Email</th>
                                    <th>Subscribed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscribers as $i => $row): ?>
                                    <tr>
                                        <td><?php echo $i+1; ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['subscribed_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html> 