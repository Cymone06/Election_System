<?php
require_once 'config/session_config.php';
require_once 'config/database.php';

// Fetch all news articles (simplified query without join to avoid foreign key issues)
$stmt = $conn->prepare("
    SELECT n.*, 'Admin' as first_name, 'User' as last_name 
    FROM news n 
    WHERE n.status = 'published' 
    ORDER BY n.created_at DESC
");
$stmt->execute();
$news = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all questions and answers (simplified to avoid foreign key issues)
$stmt = $conn->prepare("
    SELECT q.*, 'Student' as first_name, 'User' as last_name, 'student' as user_type,
           a.id as answer_id, a.answer_text, a.answered_at,
           a.author_id as answer_author_id, 
           'Admin' as answer_author_first, 'User' as answer_author_last
    FROM questions q 
    LEFT JOIN answers a ON q.id = a.question_id 
    ORDER BY q.created_at DESC, a.answered_at ASC
");
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get any messages from the session
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);

// Determine user type and fetch user info
$is_admin = false;
$admin = null;
$profile_pic_path = '';
if (isset($_SESSION['user_id']) && (isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'super_admin'))) {
    $is_admin = true;
    $admin_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    // Optionally set admin profile pic if you have it
    $profile_pic_path = 'https://ui-avatars.com/api/?name=' . urlencode($admin['first_name'] . ' ' . $admin['last_name']) . '&background=2c3e50&color=fff&size=128';
} elseif (isset($_SESSION['student_db_id'])) {
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
    <title>News & Q&A - STVC Election System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            color: white;
            margin-right: 15px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
            color: white;
        }

        .user-name {
            font-size: 0.9rem;
            margin-right: 10px;
        }

        .news-section, .qa-section {
            padding: 80px 0;
        }

        .news-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .news-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px;
            position: relative;
        }

        .news-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .news-card:hover .news-header::after {
            left: 100%;
        }

        .news-meta {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 10px;
        }

        .news-content {
            padding: 25px;
            line-height: 1.6;
        }

        .news-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .qa-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .qa-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .question-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            border-bottom: 2px solid #e9ecef;
        }

        .question-content {
            padding: 20px;
        }

        .answer-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 0 0 15px 15px;
            border-top: 1px solid #e9ecef;
        }

        .answer-content {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--success-color);
        }

        .no-answer {
            color: #6c757d;
            font-style: italic;
        }

        .question-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 40px;
        }

        .form-label {
            font-weight: 500;
            color: var(--primary-color);
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            padding: 10px 25px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .section-title {
            color: var(--primary-color);
            margin-bottom: 40px;
            text-align: center;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
            border-radius: 2px;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .navbar-nav .nav-link {
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: var(--secondary-color) !important;
            transform: translateY(-2px);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .dropdown-item {
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateX(5px);
        }

        .admin-badge {
            background-color: var(--accent-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .user-badge {
            background-color: var(--secondary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .question-actions {
            margin-top: 15px;
        }

        .question-actions .btn {
            margin-right: 10px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php if ($is_admin): ?>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="Admin/admin_dashboard.php">
                <img src="uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
                <span class="fw-bold" style="color:white;letter-spacing:1px;">STVC Election System - Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="Admin/admin_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Admin/manage_applications.php"><i class="fas fa-file-alt me-1"></i> Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Admin/manage_positions.php"><i class="fas fa-list me-1"></i> Positions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Admin/manage_accounts.php"><i class="fas fa-users me-1"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Admin/reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i>
                            <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="Admin/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="Admin/security_breaches.php"><i class="fas fa-shield-alt me-2"></i>Security Breaches</a></li>
                            <li><a class="dropdown-item" href="Admin/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="../index.php"><i class="fas fa-home me-2"></i>View Site</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php else: ?>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
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
                        <a class="nav-link" href="positions.php"><i class="fas fa-list me-1"></i> Positions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="news.php"><i class="fas fa-newspaper me-1"></i> News</a>
                    </li>
                    <?php if (isset($_SESSION['student_db_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="application.php"><i class="fas fa-edit me-1"></i> Apply</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-dropdown" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar" style="padding:0;overflow:hidden;width:36px;height:36px;display:inline-block;vertical-align:middle;">
                                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                </div>
                                <?php echo htmlspecialchars($user['first_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="application.php"><i class="fas fa-edit me-2"></i>Apply for Position</a></li>
                                <li><a class="dropdown-item" href="positions.php"><i class="fas fa-list me-2"></i>View Positions</a></li>
                                <li><a class="dropdown-item" href="news.php"><i class="fas fa-newspaper me-2"></i>News & Q&A</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- News Section -->
    <section class="news-section">
        <div class="container">
            <h2 class="section-title">Latest News & Updates</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($news)): ?>
                <div class="text-center">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No news available at the moment</h4>
                    <p class="text-muted">Check back later for updates!</p>
                </div>
            <?php else: ?>
                <?php foreach ($news as $article): ?>
                    <div class="news-card">
                        <div class="news-header">
                            <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                            <div class="news-meta">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($article['first_name'] . ' ' . $article['last_name']); ?>
                                <i class="fas fa-calendar ms-3 me-1"></i>
                                <?php echo date('F j, Y', strtotime($article['created_at'])); ?>
                            </div>
                        </div>
                        <div class="news-content">
                            <?php if ($article['image']): ?>
                                <img src="uploads/news/<?php echo htmlspecialchars($article['image']); ?>" 
                                     alt="News Image" class="news-image">
                            <?php endif; ?>
                            <div class="content-text">
                                <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Q&A Section -->
    <section class="qa-section">
        <div class="container">
            <h2 class="section-title">Questions & Answers</h2>
            
            <!-- Ask Question Form -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="question-form">
                    <h4 class="mb-4"><i class="fas fa-question-circle me-2"></i>Ask a Question</h4>
                    <form action="process_question.php" method="POST">
                        <div class="mb-3">
                            <label for="question" class="form-label">Your Question</label>
                            <textarea class="form-control" id="question" name="question" rows="4" 
                                      placeholder="Type your question here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Question
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Please <a href="login.php" class="alert-link">log in</a> to ask questions.
                </div>
            <?php endif; ?>

            <!-- Questions List -->
            <div class="questions-list">
                <?php if (empty($questions)): ?>
                    <div class="text-center">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No questions yet</h4>
                        <p class="text-muted">Be the first to ask a question!</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $currentQuestionId = null;
                    foreach ($questions as $item): 
                        if ($currentQuestionId !== $item['id']):
                            $currentQuestionId = $item['id'];
                    ?>
                        <div class="qa-card">
                            <div class="question-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-2">
                                            <i class="fas fa-question-circle me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($item['question_text']); ?>
                                        </h5>
                                        <div class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                            <?php if ($item['user_type'] === 'admin'): ?>
                                                <span class="admin-badge">Admin</span>
                                            <?php else: ?>
                                                <span class="user-badge">User</span>
                                            <?php endif; ?>
                                            <i class="fas fa-calendar ms-3 me-1"></i>
                                            <?php echo date('F j, Y g:i A', strtotime($item['created_at'])); ?>
                                        </div>
                                    </div>
                                    <?php if (isset($_SESSION['user_type']) && (($_SESSION['user_type'] === 'admin') || ($_SESSION['user_type'] === 'super_admin'))): ?>
                                        <div class="question-actions">
                                            <button class="btn btn-success btn-sm" onclick="replyQuestion(<?php echo $item['id']; ?>)">
                                                <i class="fas fa-reply me-1"></i>Reply
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteQuestion(<?php echo $item['id']; ?>)">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php 
                            // Check if this question has an answer
                            $hasAnswer = false;
                            foreach ($questions as $answerItem) {
                                if ($answerItem['id'] === $item['id'] && $answerItem['answer_id']) {
                                    $hasAnswer = true;
                                    break;
                                }
                            }
                            ?>
                            
                            <?php if ($hasAnswer): ?>
                                <div class="answer-section">
                                    <h6 class="mb-3"><i class="fas fa-reply me-2 text-success"></i>Admin Response</h6>
                                    <?php foreach ($questions as $answerItem): ?>
                                        <?php if ($answerItem['id'] === $item['id'] && $answerItem['answer_id']): ?>
                                            <div class="answer-content">
                                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($answerItem['answer_text'])); ?></p>
                                                <div class="text-muted small">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($answerItem['answer_author_first'] . ' ' . $answerItem['answer_author_last']); ?>
                                                    <span class="admin-badge">Admin</span>
                                                    <i class="fas fa-calendar ms-3 me-1"></i>
                                                    <?php echo date('F j, Y g:i A', strtotime($answerItem['answered_at'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="answer-section">
                                    <div class="no-answer">
                                        <i class="fas fa-clock me-2"></i>No response yet. Admins will reply soon.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Reply Modal -->
    <div class="modal fade" id="replyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reply to Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_answer.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="questionId" name="question_id">
                        <div class="mb-3">
                            <label for="answerText" class="form-label">Your Answer</label>
                            <textarea class="form-control" id="answerText" name="answer_text" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Submit Answer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function replyQuestion(questionId) {
            document.getElementById('questionId').value = questionId;
            new bootstrap.Modal(document.getElementById('replyModal')).show();
        }

        function deleteQuestion(questionId) {
            if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                window.location.href = 'delete_question.php?id=' + questionId;
            }
        }
    </script>

    <?php include '../includes/footer.php'; ?>

    <style>
        .footer {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="%232c3e50"></path></svg>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            margin-top: 4rem;
            padding-top: 3rem;
            padding-bottom: 2rem;
        }

        .footer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.95), rgba(52, 152, 219, 0.9));
            z-index: 1;
        }

        .footer .container {
            position: relative;
            z-index: 2;
        }

        .footer h5, .footer h6 {
            color: white;
            font-weight: 600;
        }

        .footer p, .footer a {
            color: rgba(255, 255, 255, 0.8);
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: white;
        }

        .footer .social-links a {
            display: inline-block;
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            transition: all 0.3s ease;
        }

        .footer .social-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .footer {
                text-align: center;
            }
            
            .footer .col-md-4 {
                margin-bottom: 2rem;
            }
        }
    </style>
</body>
</html> 