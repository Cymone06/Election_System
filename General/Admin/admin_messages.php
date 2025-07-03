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

// Fetch messages with student and admin info
$stmt = $conn->prepare('SELECT m.*, s.first_name AS student_first, s.last_name AS student_last, s.profile_picture AS student_profile, u.first_name AS admin_first, u.last_name AS admin_last FROM messages m LEFT JOIN students s ON m.sender_student_id = s.id LEFT JOIN users u ON m.recipient_admin_id = u.id ORDER BY m.created_at DESC');
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all individual messages sent by this admin to students (parent messages)
$student_threads = $conn->query("SELECT m.*, s.first_name AS student_first, s.last_name AS student_last, s.profile_picture FROM messages m JOIN students s ON m.student_id = s.id WHERE m.recipient_admin_id = $admin_id AND m.parent_message_id IS NULL ORDER BY m.created_at DESC");

// Handle reply submission
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message_id'])) {
    $msg_type = $_POST['reply_message_type'];
    $msg_id = intval($_POST['reply_message_id']);
    $reply_content = trim($_POST['reply_content']);
    if ($reply_content && $msg_type === 'admin') { // Only allow replies to admin messages
        $stmt = $conn->prepare("INSERT INTO message_replies (message_type, message_id, sender_admin_id, content) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $msg_type, $msg_id, $admin_id, $reply_content);
        $stmt->execute();
        $stmt->close();
        $success = 'Reply sent!';
    } elseif ($reply_content && $msg_type === 'student_thread') {
        // Admin replying to a student thread
        // Get the original message to find student_id
        $stmt = $conn->prepare("SELECT student_id FROM messages WHERE id = ?");
        $stmt->bind_param("i", $msg_id);
        $stmt->execute();
        $stmt->bind_result($student_id);
        $stmt->fetch();
        $stmt->close();
        if ($student_id) {
            $stmt = $conn->prepare("INSERT INTO messages (student_id, recipient_admin_id, parent_message_id, type, title, content) VALUES (?, ?, ?, 'info', ?, ?)");
            $reply_title = 'Reply';
            $stmt->bind_param("iiiss", $student_id, $admin_id, $msg_id, $reply_title, $reply_content);
            $stmt->execute();
            $stmt->close();
        }
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

// Helper: fetch student replies to a message
function fetch_student_replies_admin($conn, $parent_message_id) {
    $stmt = $conn->prepare("SELECT * FROM messages WHERE parent_message_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $parent_message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $replies = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $replies;
}

// Helper: fetch all messages in a thread (admin and student)
function fetch_full_thread($conn, $root_id) {
    $stmt = $conn->prepare("SELECT m.*, s.first_name AS student_first, s.last_name AS student_last, s.profile_picture, u.first_name AS admin_first, u.last_name AS admin_last FROM messages m LEFT JOIN students s ON m.sender_student_id = s.id LEFT JOIN users u ON m.recipient_admin_id = u.id WHERE m.id = ? OR m.parent_message_id = ? ORDER BY m.created_at ASC");
    $stmt->bind_param("ii", $root_id, $root_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $messages;
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
        <div class="col-lg-12 mb-4">
            <div class="card p-3">
                <h5>Admin-to-Admin Messages</h5>
                <?php foreach ($messages as $msg): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div><b>From:</b> <?php echo htmlspecialchars($msg['admin_first'] . ' ' . $msg['admin_last']); ?></div>
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
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card p-3">
                <h5>Admin-Student Conversations</h5>
                <?php while ($thread = $student_threads->fetch_assoc()): ?>
                    <div class="border rounded mb-4 p-3" style="background:#f8f9fa;">
                        <div class="d-flex align-items-center mb-2">
                            <img src="<?php echo !empty($msg['student_profile']) && file_exists($msg['student_profile']) ? htmlspecialchars($msg['student_profile']) : 'https://ui-avatars.com/api/?name=' . urlencode(trim(($msg['student_first'] ?? '') . ' ' . ($msg['student_last'] ?? ''))) . '&background=3498db&color=fff&size=64'; ?>" alt="Student" class="rounded-circle me-2" style="width:36px;height:36px;object-fit:cover;">
                            <span class="fw-bold me-2"><?php echo htmlspecialchars((!empty($msg['student_first']) && !empty($msg['student_last'])) ? $msg['student_first'] . ' ' . $msg['student_last'] : $msg['student_first']); ?></span>
                            <span class="badge bg-secondary ms-1" style="font-size:0.85em;">ID: <?php echo !empty($msg['reg_student_id']) ? htmlspecialchars($msg['reg_student_id']) : htmlspecialchars($msg['student_id']); ?></span>
                        </div>
                        <div class="conversation-thread px-2 py-1">
                            <?php $messages = fetch_full_thread($conn, $thread['id']); ?>
                            <?php foreach ($messages as $msg): ?>
                                <?php if ($msg['sender_student_id']): ?>
                                    <div class="d-flex align-items-start mb-2">
                                        <img src="<?php echo !empty($msg['profile_picture']) ? htmlspecialchars($msg['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($msg['student_first'] . ' ' . $msg['student_last']); ?>" alt="Student" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                                        <div>
                                            <div class="bg-light rounded px-3 py-2 mb-1"><b><?php echo htmlspecialchars($msg['student_first'] . ' ' . $msg['student_last']); ?>:</b> <?php echo nl2br(htmlspecialchars($msg['content'])); ?></div>
                                            <div class="text-muted small ms-1"><?php echo $msg['created_at']; ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex align-items-start mb-2 flex-row-reverse">
                                        <img src="../uploads/gallery/STVC logo.jpg" alt="Admin" class="rounded-circle ms-2" style="width:32px;height:32px;object-fit:cover;">
                                        <div>
                                            <div class="bg-primary text-white rounded px-3 py-2 mb-1"><b>You:</b> <?php echo nl2br(htmlspecialchars($msg['content'])); ?></div>
                                            <div class="text-muted small ms-1 text-end"><?php echo $msg['created_at']; ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="reply_message_type" value="student_thread">
                            <input type="hidden" name="reply_message_id" value="<?php echo $thread['id']; ?>">
                            <div class="input-group">
                                <input type="text" name="reply_content" class="form-control" placeholder="Reply to student..." required>
                                <button class="btn btn-admin" type="submit">Send</button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
<style>
.conversation-thread { background: #f9f9f9; border-radius: 8px; margin-bottom: 1rem; }
.bg-primary { background-color: #2c3e50 !important; }
</style>
<?php include 'admin_footer.php'; ?> 