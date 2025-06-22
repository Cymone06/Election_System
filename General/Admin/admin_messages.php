<?php
require_once '../config/session_config.php';
require_once '../config/connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'super_admin')) {
    header('Location: admin_login.php');
    exit();
}

$page_title = 'Inbox';
include 'admin_header.php';
$admin_id = $_SESSION['user_id'];

// Mark all unread admin messages as read when the page is loaded
$conn->query("UPDATE admin_messages SET is_read = 1 WHERE recipient_admin_id = $admin_id AND is_read = 0");

// Fetch messages sent to this admin (from other admins)
$admin_msgs = $conn->query("SELECT am.*, u1.first_name AS sender_first, u1.last_name AS sender_last FROM admin_messages am JOIN users u1 ON am.sender_admin_id = u1.id WHERE am.recipient_admin_id = $admin_id ORDER BY am.created_at DESC");

// Fetch messages sent to students, for which this admin can reply (optional: only if super_admin)
$student_msgs = $conn->query("SELECT m.*, s.first_name, s.last_name FROM messages m JOIN students s ON m.student_id = s.id ORDER BY m.created_at DESC LIMIT 50");

// Handle reply submission
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message_id'])) {
    $msg_type = $_POST['reply_message_type'];
    $msg_id = intval($_POST['reply_message_id']);
    $reply_content = trim($_POST['reply_content']);
    if ($reply_content) {
        $stmt = $conn->prepare("INSERT INTO message_replies (message_type, message_id, sender_admin_id, content) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $msg_type, $msg_id, $admin_id, $reply_content);
        $stmt->execute();
        $stmt->close();
        $success = 'Reply sent!';
    } else {
        $error = 'Reply cannot be empty.';
    }
}

// Helper: fetch replies for a message
function fetch_replies($conn, $msg_type, $msg_id) {
    $stmt = $conn->prepare("SELECT r.*, u.first_name, u.last_name FROM message_replies r JOIN users u ON r.sender_admin_id = u.id WHERE r.message_type = ? AND r.message_id = ? ORDER BY r.created_at ASC");
    $stmt->bind_param("si", $msg_type, $msg_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $replies = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $replies;
}
?>
<div class="container main-content">
    <section class="welcome-section mb-4">
        <div class="row align-items-center">
            <div class="col-12">
                <h2><i class="fas fa-inbox me-2"></i>Inbox</h2>
                <p>View all messages and reply. Only admins can reply.</p>
            </div>
        </div>
    </section>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card p-3">
                <h5>Messages from Admins</h5>
                <?php while ($msg = $admin_msgs->fetch_assoc()): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div><b>From:</b> <?php echo htmlspecialchars($msg['sender_first'] . ' ' . $msg['sender_last']); ?></div>
                        <div><b>Title:</b> <?php echo htmlspecialchars($msg['title']); ?></div>
                        <div><b>Message:</b> <?php echo nl2br(htmlspecialchars($msg['content'])); ?></div>
                        <div class="text-muted small mb-1"><?php echo $msg['created_at']; ?></div>
                        <div class="ps-3 border-start">
                            <?php foreach (fetch_replies($conn, 'admin', $msg['id']) as $reply): ?>
                                <div class="mb-1"><b><?php echo htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?>:</b> <?php echo nl2br(htmlspecialchars($reply['content'])); ?> <span class="text-muted small ms-2"><?php echo $reply['created_at']; ?></span></div>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="reply_message_type" value="admin">
                            <input type="hidden" name="reply_message_id" value="<?php echo $msg['id']; ?>">
                            <div class="input-group">
                                <input type="text" name="reply_content" class="form-control" placeholder="Reply..." required>
                                <button class="btn btn-admin" type="submit">Reply</button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card p-3">
                <h5>Messages to Students (for reference)</h5>
                <?php while ($msg = $student_msgs->fetch_assoc()): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div><b>To:</b> <?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?></div>
                        <div><b>Title:</b> <?php echo htmlspecialchars($msg['title']); ?></div>
                        <div><b>Message:</b> <?php echo nl2br(htmlspecialchars($msg['content'])); ?></div>
                        <div class="text-muted small mb-1"><?php echo $msg['created_at']; ?></div>
                        <div class="ps-3 border-start">
                            <?php foreach (fetch_replies($conn, 'student', $msg['id']) as $reply): ?>
                                <div class="mb-1"><b><?php echo htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?>:</b> <?php echo nl2br(htmlspecialchars($reply['content'])); ?> <span class="text-muted small ms-2"><?php echo $reply['created_at']; ?></span></div>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="reply_message_type" value="student">
                            <input type="hidden" name="reply_message_id" value="<?php echo $msg['id']; ?>">
                            <div class="input-group">
                                <input type="text" name="reply_content" class="form-control" placeholder="Reply..." required>
                                <button class="btn btn-admin" type="submit">Reply</button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
<?php include 'admin_footer.php'; ?> 