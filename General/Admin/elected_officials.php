<?php
require_once '../config/session_config.php';
require_once '../config/connect.php';
require_once '../config/database.php';

// Check if the table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'elected_officials'");
if ($check_table && $check_table->num_rows == 1) {
    $table_exists = true;
}

// Fetch elected officials from the database
$elected_officials = [];
$error_message = '';

if ($table_exists) {
    $sql = "SELECT first_name, last_name, position_name, election_title, term_start_date, term_end_date, elected_at FROM elected_officials ORDER BY term_start_date DESC, position_name ASC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $elected_officials[] = $row;
        }
    }
} else {
    $error_message = "The 'elected_officials' table does not exist yet. Please finalize an election to create it and view the results here.";
}

require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-user-check me-2"></i>Elected Officials</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error_message; ?>
                            </div>
                        <?php elseif (!empty($elected_officials)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Full Name</th>
                                            <th>Position</th>
                                            <th>Election</th>
                                            <th>Term Start Date</th>
                                            <th>Term End Date</th>
                                            <th>Elected On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($elected_officials as $official): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($official['first_name'] . ' ' . $official['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($official['position_name']); ?></td>
                                                <td><?php echo htmlspecialchars($official['election_title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($official['term_start_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($official['term_end_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($official['elected_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                No elected officials found. This table may be empty.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?> 