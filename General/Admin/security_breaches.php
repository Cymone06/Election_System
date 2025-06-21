<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin or super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Fetch account breaches
$breaches = [];
$stmt = $conn->prepare("SELECT us.*, s.first_name, s.last_name, s.email, s.student_id FROM user_sessions us LEFT JOIN students s ON us.user_id = s.id AND us.user_type = 'student' WHERE us.breach_flag = 1 ORDER BY us.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $breaches[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Breaches - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card mb-4 shadow border-danger" style="border-width:2px;">
                    <div class="card-header bg-danger text-white d-flex align-items-center">
                        <i class="fas fa-shield-alt fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-0">Security Breaches Detected</h5>
                            <small>Monitor suspicious account activity and take action if needed.</small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($breaches)): ?>
                            <div class="alert alert-success rounded-0 mb-0">
                                <i class="fas fa-check-circle me-2"></i>No security breaches detected.
                            </div>
                        <?php else: ?>
                        <div class="alert alert-danger rounded-0 mb-0" style="border-left:4px solid #c0392b;">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i><?php echo count($breaches); ?> breach<?php echo count($breaches) > 1 ? 'es' : ''; ?> detected!</strong> Review the details below.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped mb-0 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Student/Admin ID</th>
                                        <th>User Type</th>
                                        <th>IP Address</th>
                                        <th>Device</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($breaches as $b): ?>
                                    <tr>
                                        <td><span class="fw-bold text-danger"><?php echo htmlspecialchars(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')); ?></span></td>
                                        <td><?php echo htmlspecialchars($b['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($b['student_id'] ?? ''); ?></td>
                                        <td><span class="badge bg-danger"><?php echo htmlspecialchars($b['user_type']); ?></span></td>
                                        <td><span class="text-muted"><?php echo htmlspecialchars($b['ip_address']); ?></span></td>
                                        <td style="max-width:200px;overflow-x:auto;"><small><?php echo htmlspecialchars($b['user_agent']); ?></small></td>
                                        <td><span class="text-secondary"><?php echo htmlspecialchars($b['created_at']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html> 