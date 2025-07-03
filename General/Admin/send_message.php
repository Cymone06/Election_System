<?php
require_once '../config/session_config.php';
require_once '../config/connect.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'super_admin')) {
    header('Location: admin_login.php');
    exit();
}

$page_title = 'Send Message';
include 'admin_header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_type = $_POST['recipient_type'] ?? '';
    $message_title = trim($_POST['message_title'] ?? '');
    $message_content = trim($_POST['message_content'] ?? '');
    $selected_users = $_POST['selected_users'] ?? [];
    $type = $_POST['type'] ?? 'info';
    $send_email = isset($_POST['send_email']) ? true : false;

    if (empty($message_title) || empty($message_content)) {
        $error = 'Title and content are required.';
    } else {
        $recipients = [];
        $emails = [];
        if ($recipient_type === 'all') {
            // All students and all admins
            $result = $conn->query("SELECT id, email FROM students");
            while ($row = $result->fetch_assoc()) {
                $recipients[] = ['type' => 'student', 'id' => $row['id']];
                $emails[] = $row['email'];
            }
            $result = $conn->query("SELECT id, email FROM users WHERE role = 'admin'");
            while ($row = $result->fetch_assoc()) {
                $recipients[] = ['type' => 'admin', 'id' => $row['id']];
                $emails[] = $row['email'];
            }
        } elseif ($recipient_type === 'admins') {
            $result = $conn->query("SELECT id, email FROM users WHERE role = 'admin'");
            while ($row = $result->fetch_assoc()) {
                $recipients[] = ['type' => 'admin', 'id' => $row['id']];
                $emails[] = $row['email'];
            }
        } elseif ($recipient_type === 'applicants') {
            $result = $conn->query("SELECT DISTINCT student_id FROM applications");
            while ($row = $result->fetch_assoc()) {
                $recipients[] = ['type' => 'student', 'id' => $row['student_id']];
                // Fetch email for each student
                $stu = $conn->query("SELECT email FROM students WHERE id = " . intval($row['student_id']));
                if ($stu && $stu_row = $stu->fetch_assoc()) {
                    $emails[] = $stu_row['email'];
                }
            }
        } elseif ($recipient_type === 'candidates') {
            $result = $conn->query("SELECT DISTINCT student_id FROM current_candidates");
            while ($row = $result->fetch_assoc()) {
                $recipients[] = ['type' => 'student', 'id' => $row['student_id']];
                $stu = $conn->query("SELECT email FROM students WHERE id = " . intval($row['student_id']));
                if ($stu && $stu_row = $stu->fetch_assoc()) {
                    $emails[] = $stu_row['email'];
                }
            }
        } elseif ($recipient_type === 'individual' && !empty($selected_users)) {
            foreach ($selected_users as $uid) {
                // Check if user is admin or student
                $uid = intval($uid);
                $stu = $conn->query("SELECT email FROM students WHERE id = $uid");
                if ($stu && $stu_row = $stu->fetch_assoc()) {
                    $recipients[] = ['type' => 'student', 'id' => $uid];
                    $emails[] = $stu_row['email'];
                } else {
                    $adm = $conn->query("SELECT email FROM users WHERE id = $uid AND role = 'admin'");
                    if ($adm && $adm_row = $adm->fetch_assoc()) {
                        $recipients[] = ['type' => 'admin', 'id' => $uid];
                        $emails[] = $adm_row['email'];
                    }
                }
            }
        }
        // Remove duplicate emails
        $emails = array_unique($emails);
        // Insert messages
        $admin_id = $_SESSION['user_id'];
        foreach ($recipients as $rec) {
            if ($rec['type'] === 'student') {
                if ($recipient_type === 'individual') {
                    $stmt = $conn->prepare("INSERT INTO messages (student_id, recipient_admin_id, type, title, content) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisss", $rec['id'], $admin_id, $type, $message_title, $message_content);
                } else {
                    $stmt = $conn->prepare("INSERT INTO messages (student_id, type, title, content) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $rec['id'], $type, $message_title, $message_content);
                }
                $stmt->execute();
                $stmt->close();
            } elseif ($rec['type'] === 'admin') {
                $stmt = $conn->prepare("INSERT INTO admin_messages (sender_admin_id, recipient_admin_id, type, title, content) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $admin_id, $rec['id'], $type, $message_title, $message_content);
                $stmt->execute();
                $stmt->close();
            }
        }
        // Send email if requested
        if ($send_email && !empty($emails)) {
            require_once '../includes/email_helper.php';
            foreach ($emails as $to_email) {
                sendSystemEmail($to_email, $message_title, $message_content);
            }
        }
        $success = 'Message sent successfully!';
    }
}

// Fetch users for individual selection
$students = $conn->query("SELECT id, first_name, last_name, student_id FROM students ORDER BY first_name, last_name");
?>

<div class="container main-content">
    <section class="welcome-section mb-4">
        <div class="row align-items-center">
            <div class="col-12">
                <h2><i class="fas fa-paper-plane me-2"></i>Send Message</h2>
                <p>Send a message to all users, admins, applicants, current candidates, or selected individuals.</p>
            </div>
        </div>
    </section>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card p-4 mb-4">
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="recipient_type" class="form-label">Send To</label>
                        <select name="recipient_type" id="recipient_type" class="form-select" onchange="toggleUserList()" required>
                            <option value="">Select recipient group</option>
                            <option value="all">All Users</option>
                            <option value="admins">Admins</option>
                            <option value="applicants">Applicants</option>
                            <option value="candidates">Current Candidates</option>
                            <option value="individual">Individual</option>
                        </select>
                    </div>
                    <div class="mb-3" id="userList" style="display:none;">
                        <label class="form-label">Select Users</label>
                        <div class="user-list" style="max-height: 180px; overflow-y: auto; border: 1px solid #e1e5e9; border-radius: 8px; padding: 0.5rem; margin-bottom: 1rem;">
                            <?php while ($stu = $students->fetch_assoc()): ?>
                                <div><input type="checkbox" name="selected_users[]" value="<?php echo $stu['id']; ?>"> <?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name'] . ' (' . $stu['student_id'] . ')'); ?></div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Message Type</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Danger</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message_title" class="form-label">Title</label>
                        <input type="text" name="message_title" id="message_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="message_content" class="form-label">Message</label>
                        <textarea name="message_content" id="message_content" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="send_email" name="send_email">
                        <label class="form-check-label" for="send_email">Send Email Notification</label>
                    </div>
                    <button type="submit" class="btn btn-admin"><i class="fas fa-paper-plane me-1"></i>Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    function toggleUserList() {
        document.getElementById('userList').style.display = document.getElementById('recipient_type').value === 'individual' ? 'block' : 'none';
    }
</script>
<?php include 'admin_footer.php'; ?> 