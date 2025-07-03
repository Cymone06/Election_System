<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

// Super Admin only page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'super_admin') {
    header('Location: admin_dashboard.php');
    exit();
}

// Handle restore action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_election'])) {
    $election_id = $_POST['election_id'];
    $stmt = $conn->prepare("UPDATE election_periods SET status = 'upcoming' WHERE id = ? AND status = 'deleted'");
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $stmt->close();
    header("Location: data_recovery.php?restored=1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_gallery_item'])) {
    $item_id = $_POST['item_id'];
    $stmt = $conn->prepare("UPDATE gallery SET deleted_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    header("Location: data_recovery.php?restored_gallery=1");
    exit();
}

// Handle permanent delete action for gallery items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_gallery_item'])) {
    $item_id = $_POST['item_id'];
    // Fetch filename to delete the file from disk
    $stmt = $conn->prepare("SELECT filename FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->bind_result($filename);
    $stmt->fetch();
    $stmt->close();
    if ($filename && file_exists("../uploads/gallery/" . $filename)) {
        unlink("../uploads/gallery/" . $filename);
    }
    $stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    header("Location: data_recovery.php?deleted_gallery=1");
    exit();
}

// Handle permanent delete action for elections
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_election'])) {
    $election_id = $_POST['election_id'];
    $stmt = $conn->prepare("DELETE FROM election_periods WHERE id = ? AND status = 'deleted'");
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $stmt->close();
    header("Location: data_recovery.php?deleted_election=1");
    exit();
}

// Auto-purge records older than 30 days
$table_check = $conn->query("SHOW TABLES LIKE 'deleted_items'");
if ($table_check && $table_check->num_rows > 0) {
    $conn->query("DELETE FROM deleted_items WHERE deleted_at < NOW() - INTERVAL 30 DAY");
}

// Fetch all deleted elections
$deleted_elections = [];
$sql_elections = "SELECT id, title, start_date, end_date FROM election_periods WHERE status = 'deleted' ORDER BY updated_at DESC";
$result_elections = $conn->query($sql_elections);
if ($result_elections) {
    $deleted_elections = $result_elections->fetch_all(MYSQLI_ASSOC);
}

// Fetch all deleted gallery items
$deleted_gallery_items = [];
$sql_gallery = "SELECT id, filename, description, uploaded_at, deleted_at FROM gallery WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
$result_gallery = $conn->query($sql_gallery);
if ($result_gallery) {
    $deleted_gallery_items = $result_gallery->fetch_all(MYSQLI_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Recovery - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .navbar { background-color: #2c3e50; }
        .navbar-brand { font-weight: 600; color: white !important; }
        .nav-link { color: rgba(255,255,255,0.9) !important; }
        .nav-link.active, .nav-link:hover { color: #fff !important; }
        .table thead th { background: #d35400; color: #fff; } /* Distinct color for super admin page */
        .table tbody tr { background: #fff; }
        .main-content { min-height: 80vh; }
        .modal-body pre {
            background-color: #2c3e50;
            color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    <div class="container main-content py-5">
        <h2 class="mb-4 text-center section-title">Data Recovery Archive</h2>
        
        <!-- Election Recovery -->
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-vote-yea me-2"></i>Deleted Elections</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Restore elections that have been moved to the recycle bin.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Election Title</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deleted_elections)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No deleted elections found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($deleted_elections as $election): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($election['title']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($election['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($election['end_date'])); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to restore this election?');">
                                                <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                                <button type="submit" name="restore_election" class="btn btn-success btn-sm">
                                                    <i class="fas fa-trash-restore me-1"></i> Restore
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline-block; margin-left: 5px;" onsubmit="return confirm('This will permanently delete the election record. Continue?');">
                                                <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                                <button type="submit" name="delete_election" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash-alt me-1"></i> Delete Permanently
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gallery Recovery -->
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="fas fa-images me-2"></i>Deleted Gallery Images</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Restore gallery images that have been moved to the recycle bin.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Description</th>
                                <th>Deleted At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deleted_gallery_items)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No deleted gallery items found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($deleted_gallery_items as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="../uploads/gallery/<?php echo htmlspecialchars($item['filename']); ?>" alt="Deleted Image" style="width: 100px; height: auto; border-radius: 5px;">
                                        </td>
                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($item['deleted_at'])); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to restore this image?');">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="restore_gallery_item" class="btn btn-success btn-sm">
                                                    <i class="fas fa-trash-restore me-1"></i> Restore
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline-block; margin-left: 5px;" onsubmit="return confirm('This will permanently delete the image record. Continue?');">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="delete_gallery_item" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash-alt me-1"></i> Delete Permanently
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Data Modal -->
    <div class="modal fade" id="viewDataModal" tabindex="-1" aria-labelledby="viewDataModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDataModalLabel">Deleted Item Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="itemDataContent"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var viewDataModal = document.getElementById('viewDataModal');
        viewDataModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var rawData = button.getAttribute('data-item-data');
            var itemDataContent = viewDataModal.querySelector('#itemDataContent');
            
            try {
                // Parse the JSON and format it nicely
                var parsedData = JSON.parse(rawData);
                itemDataContent.textContent = JSON.stringify(parsedData, null, 2);
            } catch (e) {
                // If parsing fails, just show the raw string
                itemDataContent.textContent = rawData;
            }
        });
    });
    </script>
    <?php include 'admin_footer.php'; ?>
</body>
</html> 