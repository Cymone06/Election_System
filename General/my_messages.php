<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

// Check if student is logged in
if (!isset($_SESSION['student_db_id'])) {
    header("Location: login.php");
    exit();
}

$student_db_id = $_SESSION['student_db_id'];
$show_undo_snackbar = false;

// Handle actions: mark as read, delete, undo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_selected']) && !empty($_POST['selected_messages'])) {
        $ids = array_map('intval', $_POST['selected_messages']);
        $id_list = implode(',', $ids);
        // Store deleted messages for undo
        $result = $conn->query("SELECT * FROM messages WHERE id IN ($id_list) AND student_id = $student_db_id");
        $_SESSION['deleted_messages'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $conn->query("DELETE FROM messages WHERE id IN ($id_list) AND student_id = $student_db_id");
        $show_undo_snackbar = true;
    }
    if (isset($_POST['mark_read']) && !empty($_POST['selected_messages'])) {
        $ids = array_map('intval', $_POST['selected_messages']);
        $id_list = implode(',', $ids);
        $conn->query("UPDATE messages SET is_read = 1 WHERE id IN ($id_list) AND student_id = $student_db_id");
    }
    if (isset($_POST['mark_all_read'])) {
        $conn->query("UPDATE messages SET is_read = 1 WHERE student_id = $student_db_id");
    }
    if (isset($_POST['undo']) && !empty($_SESSION['deleted_messages'])) {
        foreach ($_SESSION['deleted_messages'] as $msg) {
            $stmt = $conn->prepare("INSERT INTO messages (id, student_id, type, title, content, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssis", $msg['id'], $msg['student_id'], $msg['type'], $msg['title'], $msg['content'], $msg['is_read'], $msg['created_at']);
            $stmt->execute();
            $stmt->close();
        }
        unset($_SESSION['deleted_messages']);
    }
    if (isset($_POST['undo_snackbar']) && !empty($_SESSION['deleted_messages'])) {
        foreach ($_SESSION['deleted_messages'] as $msg) {
            $stmt = $conn->prepare("INSERT INTO messages (id, student_id, type, title, content, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssis", $msg['id'], $msg['student_id'], $msg['type'], $msg['title'], $msg['content'], $msg['is_read'], $msg['created_at']);
            $stmt->execute();
            $stmt->close();
        }
        unset($_SESSION['deleted_messages']);
    }
}

// Fetch messages for the student from the database
$stmt = $conn->prepare("SELECT id, type, title, content, is_read, created_at FROM messages WHERE student_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $student_db_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count unread messages for the student
function get_unread_message_count($conn, $student_db_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE student_id = ? AND is_read = 0");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['unread_count'] ?? 0);
}
$unread_count = get_unread_message_count($conn, $student_db_id);

// Mark all unread messages as read when the page is loaded
$conn->query("UPDATE messages SET is_read = 1 WHERE student_id = $student_db_id AND is_read = 0");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .student-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .main-content {
            flex: 1;
        }
        .messages-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.08);
            padding: 2rem 2.5rem;
        }
        .messages-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .search-bar-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.2rem;
        }
        .search-bar {
            flex: 1;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            transition: border 0.2s;
        }
        .search-bar:focus {
            border-color: #667eea;
            outline: none;
        }
        .dropdown {
            position: relative;
        }
        .dropdown-toggle {
            background: none;
            border: none;
            font-size: 1.7rem;
            color: #667eea;
            cursor: pointer;
            padding: 0 0.5rem;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .dropdown-toggle:hover {
            background: #f0f4ff;
        }
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 120%;
            min-width: 220px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(44,62,80,0.12);
            padding: 0.5rem 0;
            z-index: 10;
            display: none;
        }
        .dropdown.show .dropdown-menu {
            display: block;
        }
        .dropdown-menu button {
            width: 100%;
            background: none;
            border: none;
            text-align: left;
            padding: 0.7rem 1.2rem;
            font-size: 1rem;
            color: #2c3e50;
            border-radius: 0;
            transition: background 0.2s;
        }
        .dropdown-menu button:hover {
            background: #f0f4ff;
            color: #667eea;
        }
        .dropdown-menu .btn-danger {
            color: #e74c3c;
        }
        .message-list {
            margin-top: 1rem;
        }
        .message-card {
            border-left: 5px solid #3498db;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 2px 8px rgba(52,152,219,0.05);
            transition: box-shadow 0.2s, background 0.2s;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        .message-card.info { border-color: #3498db; }
        .message-card.success { border-color: #27ae60; }
        .message-card.warning { border-color: #f1c40f; }
        .message-card.danger { border-color: #e74c3c; }
        .message-card.unread {
            background: #eaf6ff;
            font-weight: 600;
        }
        .message-checkbox {
            margin-top: 0.3rem;
            accent-color: #667eea;
        }
        .message-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        .message-content {
            color: #555;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .message-date {
            font-size: 0.92rem;
            color: #888;
            text-align: right;
        }
        .no-messages {
            text-align: center;
            color: #aaa;
            font-size: 1.1rem;
            margin-top: 2rem;
        }
        .snackbar {
            visibility: hidden;
            min-width: 320px;
            background-color: #323232;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 18px 24px;
            position: fixed;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%);
            z-index: 9999;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            box-shadow: 0 4px 16px rgba(44,62,80,0.18);
        }
        .snackbar.show {
            visibility: visible;
            animation: fadein 0.5s, fadeout 0.5s 3.5s;
        }
        .snackbar button {
            background: none;
            border: none;
            color: #4fc3f7;
            font-weight: 600;
            font-size: 1.1rem;
            margin-left: 1rem;
            cursor: pointer;
            border-radius: 4px;
            padding: 4px 10px;
            transition: background 0.2s;
        }
        .snackbar button:hover {
            background: #222;
        }
        @keyframes fadein {
            from { bottom: 0; opacity: 0; }
            to { bottom: 30px; opacity: 1; }
        }
        @keyframes fadeout {
            from { bottom: 30px; opacity: 1; }
            to { bottom: 0; opacity: 0; }
        }
        @media (max-width: 600px) {
            .messages-container { padding: 1rem 0.5rem; }
            .search-bar-row { flex-direction: column; gap: 0.5rem; }
            .snackbar { min-width: 90vw; font-size: 1rem; }
        }
    </style>
    <script>
        function selectAllMessages() {
            document.querySelectorAll('.message-checkbox').forEach(cb => cb.checked = true);
        }
        function deselectAllMessages() {
            document.querySelectorAll('.message-checkbox').forEach(cb => cb.checked = false);
        }
        function toggleDropdown() {
            document.getElementById('dropdownMenu').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            var dropdown = document.getElementById('dropdownMenu');
            if (!dropdown.contains(event.target) && event.target.id !== 'dropdownToggle') {
                dropdown.classList.remove('show');
            }
        });
        function filterMessages() {
            var input = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.message-card').forEach(function(card) {
                var text = card.innerText.toLowerCase();
                card.style.display = text.includes(input) ? '' : 'none';
            });
        }
        function showSnackbar() {
            var sb = document.getElementById('undoSnackbar');
            sb.classList.add('show');
            setTimeout(function() { sb.classList.remove('show'); }, 4000);
        }
    </script>
</head>
<body <?php if ($show_undo_snackbar) echo 'onload="showSnackbar()"'; ?>>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-vote-yea me-2"></i>
                STVC Election System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_messages.php"><i class="fas fa-envelope me-1"></i> My Messages<?php if ($unread_count > 0): ?><span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span><?php endif; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Header -->
    <div class="student-header">
        <div class="container">
            <h2 class="mb-2"><i class="fas fa-envelope-open-text me-2"></i>My Messages</h2>
            <p class="mb-0">View your notifications, system updates, and important announcements here.</p>
        </div>
    </div>
    <div class="container main-content">
        <div class="messages-container">
            <div class="messages-title"><i class="fas fa-envelope-open-text me-2"></i>My Messages</div>
            <form method="POST">
                <div class="search-bar-row">
                    <input type="text" id="searchInput" class="search-bar" placeholder="Search messages..." onkeyup="filterMessages()">
                    <div class="dropdown" id="dropdownMenu">
                        <button type="button" class="dropdown-toggle" id="dropdownToggle" onclick="toggleDropdown()">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu">
                            <button type="button" onclick="selectAllMessages()"><i class="fas fa-check-square me-1"></i>Select All</button>
                            <button type="button" onclick="deselectAllMessages()"><i class="fas fa-square me-1"></i>Deselect All</button>
                            <button type="submit" name="undo" class="btn btn-secondary"><i class="fas fa-undo me-1"></i>Undo</button>
                            <button type="submit" name="mark_all_read" class="btn btn-success"><i class="fas fa-envelope-open me-1"></i>Mark All as Read</button>
                            <button type="submit" name="mark_read" class="btn btn-primary"><i class="fas fa-envelope-open-text me-1"></i>Mark Selected as Read</button>
                            <button type="submit" name="delete_selected" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Delete Selected</button>
                        </div>
                    </div>
                </div>
                <div class="message-list">
                    <?php if (empty($messages)): ?>
                        <div class="no-messages"><i class="fas fa-inbox"></i> No messages yet.</div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-card <?php echo htmlspecialchars($msg['type']); ?><?php if (!$msg['is_read']) echo ' unread'; ?>">
                                <input type="checkbox" class="message-checkbox" name="selected_messages[]" value="<?php echo $msg['id']; ?>">
                                <div style="flex:1;">
                                    <div class="message-title">
                                        <?php if ($msg['type'] === 'success'): ?><i class="fas fa-check-circle text-success me-1"></i><?php endif; ?>
                                        <?php if ($msg['type'] === 'info'): ?><i class="fas fa-info-circle text-primary me-1"></i><?php endif; ?>
                                        <?php if ($msg['type'] === 'warning'): ?><i class="fas fa-exclamation-triangle text-warning me-1"></i><?php endif; ?>
                                        <?php if ($msg['type'] === 'danger'): ?><i class="fas fa-times-circle text-danger me-1"></i><?php endif; ?>
                                        <?php echo htmlspecialchars($msg['title']); ?>
                                    </div>
                                    <div class="message-content"><?php echo htmlspecialchars($msg['content']); ?></div>
                                    <div class="message-date"><i class="far fa-clock me-1"></i><?php echo date('Y-m-d h:i A', strtotime($msg['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php if ($show_undo_snackbar): ?>
        <form method="POST" style="margin:0;">
            <div class="snackbar show" id="undoSnackbar">
                Message(s) deleted.
                <button type="submit" name="undo_snackbar">Undo</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html> 