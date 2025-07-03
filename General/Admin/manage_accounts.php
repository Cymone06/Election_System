<?php
require_once '../config/session_config.php';
require_once '../config/database.php';
require_once __DIR__ . '/../includes/email_helper.php';

// Check if database connection is working
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection not established'));
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'super_admin')) {
    header('Location: admin_login.php');
    exit();
}

// Check if user is super admin for approval actions
$is_super_admin = ($_SESSION['user_type'] === 'super_admin');

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'delete') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $user_type = $_POST['user_type'] ?? '';

            if ($user_id > 0 && !empty($user_type) && $user_id != $_SESSION['user_id']) {
                $table_name = ($user_type === 'admin') ? 'users' : 'students';
                
                // --- Start Secret Archival ---
                $select_stmt = $conn->prepare("SELECT * FROM {$table_name} WHERE id = ?");
                $select_stmt->bind_param('i', $user_id);
                $select_stmt->execute();
                $user_data = $select_stmt->get_result()->fetch_assoc();
                $select_stmt->close();

                if ($user_data) {
                    $item_data_json = json_encode($user_data);
                    $item_type = $user_type; // 'admin' or 'student'
                    $item_identifier = $user_data['id'];
                    $admin_id = $_SESSION['user_id'] ?? null;
                    $admin_name = $_SESSION['user_name'] ?? 'Unknown Admin';

                    $archive_stmt = $conn->prepare("INSERT INTO deleted_items (item_type, item_identifier, item_data, deleted_by_user_id, deleted_by_user_name) VALUES (?, ?, ?, ?, ?)");
                    $archive_stmt->bind_param('sssis', $item_type, $item_identifier, $item_data_json, $admin_id, $admin_name);
                    $archive_stmt->execute();
                    $archive_stmt->close();
                }
                // --- End Secret Archival ---

                // Proceed with original deletion
                $stmt = $conn->prepare("DELETE FROM {$table_name} WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            header('Location: manage_accounts.php?success=deleted');
            exit();
        } elseif ($action === 'update') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            if ($user_id > 0) {
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("si", $status, $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            header('Location: manage_accounts.php?success=updated');
            exit();
        } elseif ($action === 'approve' && $is_super_admin) {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id > 0) {
                $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'admin'");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                // Fetch admin email and name
                $stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->bind_result($email, $first_name, $last_name);
                $stmt->fetch();
                $stmt->close();
                if ($email) {
                    $subject = 'Your Admin Account Has Been Approved!';
                    $body = "Dear $first_name,<br><br>Your admin account has been approved! You can now log in and access the system.<br><br>Thank you for being part of the STVC Election System.<br><br>Best regards,<br>STVC Election System Team";
                    sendSystemEmail($email, $subject, $body, $first_name);
                }
            }
            header('Location: manage_accounts.php?success=approved');
            exit();
        } elseif ($action === 'reject' && $is_super_admin) {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id > 0) {
                $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ? AND role = 'admin'");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            header('Location: manage_accounts.php?success=rejected');
            exit();
        } elseif ($action === 'approve_student') {
            $student_id = (int)($_POST['student_id'] ?? 0);
            if ($student_id > 0) {
                $stmt = $conn->prepare("UPDATE students SET status = 'active' WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $stmt->close();
                }
                // Fetch student email and name
                $stmt = $conn->prepare("SELECT email, first_name, last_name FROM students WHERE id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $stmt->bind_result($email, $first_name, $last_name);
                $stmt->fetch();
                $stmt->close();
                if ($email) {
                    $subject = 'Your Student Account Has Been Approved!';
                    $body = "Dear $first_name,<br><br>Your student account has been approved! You can now log in and participate in the election system.<br><br>Thank you for being part of the STVC Election System.<br><br>Best regards,<br>STVC Election System Team";
                    sendSystemEmail($email, $subject, $body, $first_name);
                }
            }
            header('Location: manage_accounts.php?success=student_approved');
            exit();
        } elseif ($action === 'reject_student') {
            $student_id = (int)($_POST['student_id'] ?? 0);
            if ($student_id > 0) {
                $stmt = $conn->prepare("UPDATE students SET status = 'rejected' WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            header('Location: manage_accounts.php?success=student_rejected');
            exit();
        } elseif ($action === 'create') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $admin_id = trim($_POST['admin_id'] ?? '');
            $id_number = trim($_POST['id_number'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $user_type = trim($_POST['user_type'] ?? '');
            $password = $_POST['password'] ?? '';
            $status = 'active';
            $errors = [];
            if ($user_type === 'admin') {
                // Validate required fields
                if (!$first_name || !$last_name || !$email || !$admin_id || !$id_number || !$gender || !$password) {
                    header('Location: manage_accounts.php?error=missing_fields'); exit();
                }
                // Check for duplicates
                $exists = record_exists('users', 'email', $email) || record_exists('users', 'admin_id', $admin_id) || record_exists('users', 'id_number', $id_number);
                if ($exists) {
                    header('Location: manage_accounts.php?error=email_exists'); exit();
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (admin_id, id_number, email, phone_number, password, first_name, last_name, gender, role, status, created_at) VALUES (?, ?, ?, '', ?, ?, ?, ?, 'admin', ?, NOW())");
                $stmt->bind_param('ssssssss', $admin_id, $id_number, $email, $hashed_password, $first_name, $last_name, $gender, $status);
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: manage_accounts.php?success=created'); exit();
                } else {
                    $stmt->close();
                    header('Location: manage_accounts.php?error=creation_failed'); exit();
                }
            } elseif ($user_type === 'student') {
                // Validate required fields
                if (!$first_name || !$last_name || !$email || !$id_number || !$gender || !$password) {
                    header('Location: manage_accounts.php?error=missing_fields'); exit();
                }
                // Generate a student_id (or use id_number as student_id if not provided)
                $student_id = trim($_POST['student_id'] ?? $id_number);
                // Check for duplicates
                $exists = record_exists('students', 'email', $email) || record_exists('students', 'student_id', $student_id) || record_exists('students', 'id_number', $id_number);
                if ($exists) {
                    header('Location: manage_accounts.php?error=student_id_exists'); exit();
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $department = trim($_POST['department'] ?? 'General');
                $course_level = trim($_POST['course_level'] ?? '');
                $stmt = $conn->prepare("INSERT INTO students (first_name, last_name, student_id, email, id_number, phone_number, department, gender, password, agreed_terms, status, created_at) VALUES (?, ?, ?, ?, ?, '', ?, ?, ?, 1, 'active', NOW())");
                $stmt->bind_param('sssssssss', $first_name, $last_name, $student_id, $email, $id_number, $department, $gender, $hashed_password, $course_level);
                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: manage_accounts.php?success=created'); exit();
                } else {
                    $stmt->close();
                    header('Location: manage_accounts.php?error=creation_failed'); exit();
                }
            } else {
                header('Location: manage_accounts.php?error=invalid_data'); exit();
            }
        }
    }
}

// Fetch all admins from users table
$admins = [];
try {
    $result = $conn->query("SELECT *, 'admin' as user_type FROM users ORDER BY created_at DESC");
    if ($result && $result->num_rows > 0) {
        $admins = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $admins = [];
}

// Fetch all students from students table
$students = [];
try {
    $result = $conn->query("SELECT *, 'student' as user_type FROM students ORDER BY created_at DESC");
    if ($result && $result->num_rows > 0) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $students = [];
}

// Separate pending students
$pending_students = array_filter($students, function($student) {
    return isset($student['status']) && $student['status'] === 'pending';
});

// Filter out pending students from the main list
$students = array_filter($students, function($student) {
    return !isset($student['status']) || $student['status'] !== 'pending';
});

// Combine all accounts
$users = array_merge($admins, $students);
foreach ($users as &$user) {
    $user['id'] = $user['id'] ?? '';
    $user['first_name'] = $user['first_name'] ?? '';
    $user['last_name'] = $user['last_name'] ?? '';
    $user['email'] = $user['email'] ?? '';
    $user['student_id'] = $user['student_id'] ?? '';
    $user['user_type'] = $user['user_type'] ?? '';
    $user['status'] = $user['status'] ?? '';
    $user['created_at'] = $user['created_at'] ?? '';
    $user['admin_id'] = $user['admin_id'] ?? '';
}
unset($user);

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
    <title>Manage Accounts - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .user-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .btn-admin {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: white;
        }

        .btn-create {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .btn-edit {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .btn-delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .user-type-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .modal-content {
            border-radius: 15px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .filter-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: 10px 10px 0 0;
            margin-right: 5px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            border: none;
        }

        .nav-tabs .nav-link:hover {
            background: rgba(52, 152, 219, 0.1);
            border: none;
        }

        .tab-content {
            background: white;
            border-radius: 0 15px 15px 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        .status-badge.bg-inactive {
            background-color: #95a5a6;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
                <img src="../uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
                <span class="fw-bold" style="color:white;letter-spacing:1px;">STVC Election System - Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_applications.php"><i class="fas fa-file-alt me-1"></i> Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_positions.php"><i class="fas fa-list me-1"></i> Positions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_users.php"><i class="fas fa-users me-1"></i> Accounts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2"><i class="fas fa-users me-2"></i>Manage Accounts</h1>
                    <p class="mb-0">View, suspend, or delete all admin and student accounts</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="fas fa-user-plus me-2"></i>Add User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    $success = $_GET['success'];
                    $message = '';
                    if ($success === 'deleted') {
                        $message = 'Account deleted successfully.';
                    } elseif ($success === 'updated') {
                        $message = 'Account updated successfully.';
                    } elseif ($success === 'approved') {
                        $message = 'Admin account approved successfully.';
                    } elseif ($success === 'rejected') {
                        $message = 'Admin account rejected successfully.';
                    } elseif ($success === 'student_approved') {
                        $message = 'Student account approved successfully.';
                    } elseif ($success === 'student_rejected') {
                        $message = 'Student account rejected successfully.';
                    } elseif ($success === 'created') {
                        $message = 'User created successfully.';
                    } else {
                        $message = 'Action completed successfully.';
                    }
                    echo htmlspecialchars($message);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php 
                    $error = $_GET['error'];
                    if ($error === 'self_delete') {
                        $message = 'You cannot delete your own account!';
                    } elseif ($error === 'missing_fields') {
                        $message = 'Please fill in all required fields!';
                    } elseif ($error === 'email_exists') {
                        $message = 'An account with this email already exists!';
                    } elseif ($error === 'student_id_exists') {
                        $message = 'An account with this student ID already exists!';
                    } elseif ($error === 'creation_failed') {
                        $message = 'Failed to create user. Please try again.';
                    } elseif ($error === 'update_failed') {
                        $message = 'Failed to update user. Please try again.';
                    } elseif ($error === 'delete_failed') {
                        $message = 'Failed to delete user. Please try again.';
                    } elseif ($error === 'approval_failed') {
                        $message = 'Failed to approve admin. Please try again.';
                    } elseif ($error === 'rejection_failed') {
                        $message = 'Failed to reject admin. Please try again.';
                    } elseif ($error === 'invalid_user') {
                        $message = 'Invalid user selected.';
                    } elseif ($error === 'invalid_data') {
                        $message = 'Invalid data provided. Please check your input.';
                    } elseif ($error === 'not_supported') {
                        $message = 'Student account creation is not supported at this time.';
                    } else {
                        $message = 'An error occurred. Please try again.';
                    }
                    echo htmlspecialchars($message);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="text-primary mb-2">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h3><?php echo count($users); ?></h3>
                    <p class="text-muted mb-0">Total Users</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="text-success mb-2">
                        <i class="fas fa-user-graduate fa-2x"></i>
                    </div>
                    <h3><?php echo count(array_filter($users, function($u){ return ($u['user_type'] ?? '') === 'student'; })); ?></h3>
                    <p class="text-muted mb-0">Students</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="text-info mb-2">
                        <i class="fas fa-user-shield fa-2x"></i>
                    </div>
                    <h3><?php echo count(array_filter($users, function($u){ return ($u['user_type'] ?? '') === 'admin'; })); ?></h3>
                    <p class="text-muted mb-0">Admins</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="text-warning mb-2">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <h3><?php echo count(array_filter($users, function($u){ return ($u['status'] ?? '') === 'pending'; })); ?></h3>
                    <p class="text-muted mb-0">Pending Admins</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="text-success mb-2">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h3><?php echo count(array_filter($users, function($u){ return ($u['status'] ?? '') === 'active'; })); ?></h3>
                    <p class="text-muted mb-0">Active Users</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="text-secondary mb-2">
                        <i class="fas fa-database fa-2x"></i>
                    </div>
                    <h3><?php echo count($users); ?></h3>
                    <p class="text-muted mb-0">Loaded Users</p>
                </div>
            </div>
        </div>

        <!-- Debug Information (only show if there are issues) -->
        <?php if (empty($users) && count($users) > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Debug Info:</strong> Database shows <?php echo count($users); ?> users but none were loaded. 
                This might indicate a query issue. Check database connection and table structure.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Pending Approvals Section -->
        <?php if (!empty($pending_students)): ?>
        <div id="pending-approvals" class="mb-5">
            <h3 class="mb-3"><i class="fas fa-user-clock me-2"></i>Pending Student Registrations</h3>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Registered On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $student['department']))); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($student['created_at'])); ?></td>
                                        <td>
                                            <form action="manage_accounts.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="approve_student">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>
                                            </form>
                                            <form action="manage_accounts.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="reject_student">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Reject"><i class="fas fa-times"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Accounts Tabs -->
        <ul class="nav nav-tabs mb-3" id="accountsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">All Accounts</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button" role="tab">Admins</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab">Students</button>
            </li>
        </ul>
        <div class="tab-content" id="accountsTabContent">
            <!-- All Accounts Tab -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <!-- Search Bar -->
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="input-group" style="max-width: 400px;">
                        <input type="text" id="accountSearchInput" class="form-control" placeholder="Search by name, email, or ID...">
                        <button class="btn btn-primary" type="button" id="accountSearchBtn"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle" id="allAccountsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Profile</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Student/Admin ID</th>
                                <th>ID Number</th>
                                <th>Gender</th>
                                <th>Designation</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="7" class="text-center text-muted">No accounts found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <?php if (($user['user_type'] ?? '') === 'student'): ?>
                                                <?php
                                                    $profile_pic_path = (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture']))
                                                        ? '../' . $user['profile_picture']
                                                        : 'https://ui-avatars.com/api/?name=' . urlencode(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) . '&background=3498db&color=fff&size=64';
                                                ?>
                                                <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width:36px;height:36px;border-radius:50%;object-fit:cover;margin-right:8px;vertical-align:middle;">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['student_id'] ?? $user['admin_id'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['id_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['gender'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                                if (($user['role'] ?? '') === 'super_admin') {
                                                    echo '<span class="badge bg-warning text-dark">Super Admin</span>';
                                                } elseif (($user['role'] ?? '') === 'admin') {
                                                    echo '<span class="badge bg-info text-dark">Admin</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">Student</span>';
                                                }
                                            ?>
                                        </td>
                                        <td><span class="badge bg-<?php echo ($user['status'] ?? '') === 'active' ? 'success' : (($user['status'] ?? '') === 'pending' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($user['status'] ?? ''); ?></span></td>
                                        <td><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></td>
                                        <td>
                                            <?php if (($user['role'] ?? '') === 'super_admin'): ?>
                                                <span class="text-muted">Permanent</span>
                                            <?php elseif ($is_super_admin && ($user['status'] ?? '') === 'pending' && ($user['role'] ?? '') === 'admin'): ?>
                                                <!-- Super Admin Approval Actions for Pending Admins -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this admin?');">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this admin?');">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Regular Actions -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this account?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <input type="hidden" name="status" value="<?php echo ($user['status'] ?? '') === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-user-slash"></i> <?php echo ($user['status'] ?? '') === 'active' ? 'Suspend' : 'Activate'; ?>
                                                    </button>
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
            <!-- Admins Tab -->
            <div class="tab-pane fade show active" id="admins" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Admin ID</th>
                                <th>ID Number</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $admins = array_filter($users, function($u){ return ($u['user_type'] ?? '') === 'admin'; }); ?>
                            <?php if (empty($admins)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No admins found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($admins as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['admin_id'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['id_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['gender'] ?? ''); ?></td>
                                        <td><span class="badge bg-<?php echo ($user['status'] ?? '') === 'active' ? 'success' : (($user['status'] ?? '') === 'pending' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($user['status'] ?? ''); ?></span></td>
                                        <td><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></td>
                                        <td>
                                            <?php if (($user['role'] ?? '') === 'super_admin'): ?>
                                                <span class="text-muted">Permanent</span>
                                            <?php elseif ($is_super_admin && ($user['status'] ?? '') === 'pending'): ?>
                                                <!-- Super Admin Approval Actions for Pending Admins -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this admin?');">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this admin?');">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Regular Actions -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this account?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <input type="hidden" name="status" value="<?php echo ($user['status'] ?? '') === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-user-slash"></i> <?php echo ($user['status'] ?? '') === 'active' ? 'Suspend' : 'Activate'; ?>
                                                    </button>
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
            <!-- Students Tab -->
            <div class="tab-pane fade" id="students" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Student ID</th>
                                <th>ID Number</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $students = array_filter($users, function($u){ return ($u['user_type'] ?? '') === 'student'; }); ?>
                            <?php if (empty($students)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No students found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($students as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['student_id'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['id_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['gender'] ?? ''); ?></td>
                                        <td><span class="badge bg-<?php echo ($user['status'] ?? '') === 'active' ? 'success' : (($user['status'] ?? '') === 'pending' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($user['status'] ?? ''); ?></span></td>
                                        <td><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></td>
                                        <td>
                                            <?php if (($user['role'] ?? '') === 'super_admin'): ?>
                                                <span class="text-muted">Permanent</span>
                                            <?php elseif ($is_super_admin && ($user['status'] ?? '') === 'pending'): ?>
                                                <!-- Super Admin Approval Actions for Pending Admins -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this admin?');">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this admin?');">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Regular Actions -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this account?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
                                                    <input type="hidden" name="status" value="<?php echo ($user['status'] ?? '') === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-user-slash"></i> <?php echo ($user['status'] ?? '') === 'active' ? 'Suspend' : 'Activate'; ?>
                                                    </button>
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
        </div>

        <!-- Account Breaches Section -->
        <?php if (!empty($breaches)): ?>
        <div class="card mb-4 mt-4 shadow border-danger" style="border-width:2px;">
            <div class="card-header bg-danger text-white d-flex align-items-center">
                <i class="fas fa-shield-alt fa-2x me-3"></i>
                <div>
                    <h5 class="mb-0">Security Breaches Detected</h5>
                    <small>Monitor suspicious account activity and take action if needed.</small>
                </div>
            </div>
            <div class="card-body p-0">
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
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row admin-fields">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="modal_admin_id" class="form-label">Admin ID</label>
                                    <input type="text" class="form-control" id="modal_admin_id" name="admin_id">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label">ID Number</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number">
                                </div>
                            </div>
                        </div>
                        <div class="row student-fields">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="department" name="department">
                                </div>
                            </div>
                        </div>
                        <div class="row student-fields">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="course_level" class="form-label">Course Level</label>
                                    <input type="text" class="form-control" id="course_level" name="course_level">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_type" class="form-label">User Type</label>
                                    <select class="form-select" id="modal_user_type" name="user_type" required>
                                        <option value="" selected disabled>Select user type</option>
                                        <option value="admin">Admin</option>
                                        <option value="student">Student</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-admin btn-create">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmDelete(userId, userName) {
            if (confirm(`Are you sure you want to delete the user "${userName}"? This action cannot be undone.`)) {
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Filter functionality for students
        document.getElementById('studentStatusFilter').addEventListener('change', filterStudents);

        function filterStudents() {
            const statusFilter = document.getElementById('studentStatusFilter').value;
            const students = document.querySelectorAll('#students .user-card');

            students.forEach(card => {
                const status = card.dataset.status;
                const statusMatch = !statusFilter || status === statusFilter;
                
                if (statusMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Filter functionality for admins
        document.getElementById('adminStatusFilter').addEventListener('change', filterAdmins);

        function filterAdmins() {
            const statusFilter = document.getElementById('adminStatusFilter').value;
            const admins = document.querySelectorAll('#admins .user-card');

            admins.forEach(card => {
                const status = card.dataset.status;
                const statusMatch = !statusFilter || status === statusFilter;
                
                if (statusMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Show admin tab if there are pending admins
        <?php if (count(array_filter($users, function($u){ return ($u['status'] ?? '') === 'pending'; })) > 0): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Show a notification about pending admins
            const adminTab = document.getElementById('admins-tab');
            if (adminTab) {
                adminTab.classList.add('text-danger');
            }
        });
        <?php endif; ?>

        // Search functionality for All Accounts
        document.getElementById('accountSearchBtn').addEventListener('click', function() {
            filterAllAccounts();
        });
        document.getElementById('accountSearchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') filterAllAccounts();
            else filterAllAccounts();
        });
        function filterAllAccounts() {
            const input = document.getElementById('accountSearchInput').value.toLowerCase();
            const table = document.getElementById('allAccountsTable');
            const rows = table.getElementsByTagName('tr');
            for (let i = 1; i < rows.length; i++) { // skip header
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                if (cells.length > 0) {
                    let match = false;
                    for (let j = 0; j < cells.length - 1; j++) { // skip actions
                        if (cells[j].innerText.toLowerCase().includes(input)) {
                            match = true;
                            break;
                        }
                    }
                    row.style.display = match ? '' : 'none';
                }
            }
        }

        // Toggle student fields in Create User modal
        document.addEventListener('DOMContentLoaded', function() {
            var userTypeSelect = document.getElementById('modal_user_type');
            var adminFields = document.querySelectorAll('#createUserModal .admin-fields');
            var studentFields = document.querySelectorAll('#createUserModal .student-fields');
            function toggleFields() {
                if (userTypeSelect.value === 'student') {
                    studentFields.forEach(function(el) { el.style.display = ''; });
                    adminFields.forEach(function(el) { el.style.display = 'none'; });
                } else if (userTypeSelect.value === 'admin') {
                    studentFields.forEach(function(el) { el.style.display = 'none'; });
                    adminFields.forEach(function(el) { el.style.display = ''; });
                } else {
                    studentFields.forEach(function(el) { el.style.display = 'none'; });
                    adminFields.forEach(function(el) { el.style.display = 'none'; });
                }
            }
            userTypeSelect.addEventListener('change', toggleFields);
            toggleFields();
        });
    </script>
</body>
</html>
<?php include 'admin_footer.php'; ?> 