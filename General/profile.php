<?php
require_once 'config/session_config.php';
require_once 'config/database.php';
global $conn;

// Check if user is logged in
if (!isset($_SESSION['student_db_id'])) {
    $_SESSION['error'] = 'Please log in to view your profile.';
    header('Location: login.php');
    exit();
}

$student_db_id = (int)$_SESSION['student_db_id'];

// Handle form submission
$success = '';
$error = '';
$profile_pic_path = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== 4) {
    $uploadDir = 'uploads/profile_pictures/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB
    $file = $_FILES['profile_picture'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Profile picture must be a JPEG or PNG file.';
        } elseif ($file['size'] > $maxFileSize) {
            $error = 'Profile picture must be less than 2MB.';
        } else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'student_' . $student_db_id . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Remove old profile picture if exists
                $stmt = $conn->prepare('SELECT profile_picture FROM students WHERE id = ?');
                $stmt->bind_param('i', $student_db_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row && !empty($row['profile_picture']) && file_exists($row['profile_picture'])) {
                    unlink($row['profile_picture']);
                }
                // Update DB
                $stmt = $conn->prepare('UPDATE students SET profile_picture = ? WHERE id = ?');
                $stmt->bind_param('si', $filepath, $student_db_id);
                $stmt->execute();
                $stmt->close();
                $success = 'Profile picture updated successfully.';
            } else {
                $error = 'Failed to upload profile picture.';
            }
        }
    } else {
        $error = 'Error uploading profile picture.';
    }
}

// Handle delete profile picture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile_picture'])) {
    $stmt = $conn->prepare('SELECT profile_picture FROM students WHERE id = ?');
    $stmt->bind_param('i', $student_db_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && !empty($row['profile_picture']) && file_exists($row['profile_picture'])) {
        unlink($row['profile_picture']);
    }
    $stmt = $conn->prepare('UPDATE students SET profile_picture = NULL WHERE id = ?');
    $stmt->bind_param('i', $student_db_id);
    $stmt->execute();
    $stmt->close();
    $success = 'Profile picture deleted and reset to default.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (empty($first_name)) $errors[] = 'First name is required.';
    if (empty($last_name)) $errors[] = 'Last name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($phone_number)) $errors[] = 'Phone number is required.';
    if (empty($department)) $errors[] = 'Department is required.';

    if (!empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password.';
        } else {
            $stmt = $conn->prepare('SELECT password FROM students WHERE id = ?');
            $stmt->bind_param('i', $student_db_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            if (!$row || !password_verify($current_password, $row['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        }
        if (!empty($new_password) && strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
    }

    if (empty($errors)) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE students SET first_name=?, last_name=?, email=?, phone_number=?, department=?, password=? WHERE id=?');
            $stmt->bind_param('ssssssi', $first_name, $last_name, $email, $phone_number, $department, $hashed_password, $student_db_id);
        } else {
            $stmt = $conn->prepare('UPDATE students SET first_name=?, last_name=?, email=?, phone_number=?, department=? WHERE id=?');
            $stmt->bind_param('sssssi', $first_name, $last_name, $email, $phone_number, $department, $student_db_id);
        }
        if ($stmt->execute()) {
            $success = 'Profile updated successfully.';
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
        $stmt->close();
    } else {
        $error = implode('<br>', $errors);
    }
}

// --- SESSION ACTIVITY SECTION LOGIC ---
$activity_error = '';
$activity_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_activity_action'])) {
    $entered_password = $_POST['activity_password'] ?? '';
    $stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $activity_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!password_verify($entered_password, $activity_user['password'])) {
        $activity_error = 'Incorrect current password for this action.';
    } else {
        if ($_POST['session_activity_action'] === 'reset') {
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND user_type = 'student'");
            $stmt->bind_param('i', $student_db_id);
            $stmt->execute();
            $stmt->close();
            session_destroy();
            header('Location: login.php?reset=1');
            exit();
        } elseif ($_POST['session_activity_action'] === 'rescan') {
            $activity_success = 'Session activity re-scanned.';
        }
    }
}
// Fetch sessions after possible rescan
$sessions = [];
$stmt = $conn->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND user_type = 'student' ORDER BY last_activity DESC LIMIT 10");
$stmt->bind_param('i', $student_db_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}
$stmt->close();
// Always fetch user info for profile display after all POST logic
$stmt = $conn->prepare('SELECT first_name, last_name, email, student_id, phone_number, department, profile_picture FROM students WHERE id = ?');
$stmt->bind_param('i', $student_db_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$profile_pic_path = !empty($user['profile_picture']) && file_exists($user['profile_picture']) ? $user['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=3498db&color=fff&size=128';

// --- 2FA PIN LOGIC ---
$pin_success = '';
$pin_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_2fa_pin'])) {
    $new_pin = trim($_POST['new_2fa_pin'] ?? '');
    $confirm_pin = trim($_POST['confirm_2fa_pin'] ?? '');
    $current_pin = trim($_POST['current_2fa_pin'] ?? '');
    // Fetch current pin from DB
    $stmt = $conn->prepare('SELECT two_factor_pin FROM students WHERE id = ?');
    $stmt->bind_param('i', $student_db_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $has_pin = !empty($row['two_factor_pin']);
    if (!preg_match('/^\d{6}$/', $new_pin)) {
        $pin_error = 'PIN must be exactly 6 digits.';
    } elseif ($new_pin !== $confirm_pin) {
        $pin_error = 'PINs do not match.';
    } elseif ($has_pin && (empty($current_pin) || !password_verify($current_pin, $row['two_factor_pin']))) {
        $pin_error = 'Current PIN is incorrect.';
    } else {
        $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE students SET two_factor_pin = ? WHERE id = ?');
        $stmt->bind_param('si', $hashed_pin, $student_db_id);
        $stmt->execute();
        $stmt->close();
        $pin_success = $has_pin ? '2FA PIN changed successfully.' : '2FA PIN set successfully.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_2fa_pin'])) {
    $current_password = trim($_POST['current_password_for_delete'] ?? '');
    // Verify password
    $stmt = $conn->prepare('SELECT password FROM students WHERE id = ?');
    $stmt->bind_param('i', $student_db_id);
    $stmt->execute();
    $current_student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (empty($current_password) || !password_verify($current_password, $current_student['password'])) {
        $pin_error = 'Incorrect password. PIN was not deleted.';
    } else {
        $stmt = $conn->prepare('UPDATE students SET two_factor_pin = NULL WHERE id = ?');
        $stmt->bind_param('i', $student_db_id);
        $stmt->execute();
        $stmt->close();
        $pin_success = '2FA PIN has been deleted successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            text-align: center;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            margin: 0 auto 1rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(52,152,219,0.12);
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .btn-admin {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: white;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .info-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
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
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_messages.php"><i class="fas fa-envelope me-1"></i> My Messages</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo htmlspecialchars($profile_pic_path); ?>" alt="Profile" style="width:36px;height:36px;border-radius:50%;object-fit:cover;margin-right:8px;vertical-align:middle;">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- End Navigation -->
    <div class="profile-header">
        <h1 class="mb-2"><i class="fas fa-user-graduate me-2"></i>Student Profile</h1>
        <p class="mb-0">View and update your profile information below.</p>
    </div>
        <div class="profile-card">
            <div class="profile-avatar mb-3">
            <img src="<?php echo htmlspecialchars($profile_pic_path); ?>" alt="Profile Picture">
            </div>
        <div class="text-center mb-3">
                <form method="POST" enctype="multipart/form-data" style="display:inline-block;">
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display:none;" onchange="this.form.submit()">
                <label for="profile_picture" class="btn btn-admin mb-2"><i class="fas fa-camera"></i> Change Profile Picture</label>
                </form>
                <form method="POST" style="display:inline-block;margin-left:10px;">
                    <input type="hidden" name="delete_profile_picture" value="1">
                <button type="submit" class="btn btn-outline-danger mb-2" onclick="return confirm('Are you sure you want to delete your profile picture?');">
                        <i class="fas fa-trash"></i> Delete Profile Picture
                    </button>
                </form>
            </div>
        <h3 class="text-center mb-3"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($user['student_id']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($user['department']); ?>" required>
                </div>
                <hr class="mt-4">
                <h5 class="mb-2">Change Password <small class="text-muted">(optional)</small></h5>
                <div class="col-md-4">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password">
                </div>
                <div class="col-md-4">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password">
                </div>
                <div class="col-md-4">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                </div>
                <div class="col-12 mt-4 text-center">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
                </div>
            </form>
            <!-- Session Activity Card -->
            <div class="card mt-4 mb-4 shadow border-primary">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fas fa-history fa-lg me-2"></i>
                    <span>Active Sessions & Login Activity</span>
                </div>
                <div class="card-body">
                    <?php if ($activity_error): ?>
                        <div class="alert alert-danger py-2 mb-2"><?php echo $activity_error; ?></div>
                    <?php endif; ?>
                    <?php if ($activity_success): ?>
                        <div class="alert alert-success py-2 mb-2"><?php echo $activity_success; ?></div>
                    <?php endif; ?>
                    <form method="POST" class="mb-3 d-flex gap-2 align-items-center">
                        <input type="password" name="activity_password" class="form-control form-control-sm w-auto" placeholder="Current Password" required>
                        <button type="submit" name="session_activity_action" value="reset" class="btn btn-outline-danger btn-sm"><i class="fas fa-power-off me-1"></i>Reset Activity</button>
                        <button type="submit" name="session_activity_action" value="rescan" class="btn btn-outline-primary btn-sm"><i class="fas fa-sync-alt me-1"></i>Re-Scan Activity</button>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Session Start</th>
                                    <th>Last Activity</th>
                                    <th>IP Address</th>
                                    <th>Device</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $sess): ?>
                                <tr<?php if ($sess['session_id'] === session_id()) echo ' class=\"table-success\"'; ?>>
                                    <td><?php echo htmlspecialchars($sess['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($sess['last_activity']); ?></td>
                                    <td><?php echo htmlspecialchars($sess['ip_address']); ?></td>
                                    <td style="max-width:200px;overflow-x:auto;"><small><?php echo htmlspecialchars($sess['user_agent']); ?></small></td>
                                    <td><?php echo ($sess['session_id'] === session_id()) ? '<span class=\"badge bg-success\">Current</span>' : ($sess['is_active'] ? '<span class=\"badge bg-primary\">Active</span>' : '<span class=\"badge bg-secondary\">Inactive</span>'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- End Session Activity Card -->
            <!-- 2FA PIN Management -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Two-Factor Authentication (2FA) PIN</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $conn->prepare('SELECT two_factor_pin FROM students WHERE id = ?');
                    $stmt->bind_param('i', $student_db_id);
                    $stmt->execute();
                    $pin_check = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    $has_pin = !empty($pin_check['two_factor_pin']);
                    ?>
                    <?php if (!$has_pin): ?>
                        <div class="alert alert-info">
                            <h4 class="alert-heading">Set Up Your 2FA PIN</h4>
                            <p>Enhance your account's security by creating a 6-digit PIN. This PIN will be required each time you log in, providing an extra layer of protection for your account.</p>
                            <hr>
                            <p class="mb-0"><strong>Important:</strong> Please choose a PIN that you can easily remember but is difficult for others to guess. If you forget your PIN, you will need to reset it via a code sent to your registered email address.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($pin_success): ?>
                        <div class="alert alert-success"><?php echo $pin_success; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <?php if ($has_pin): ?>
                            <p>Your account is protected with a 2FA PIN. You can change or delete it below.</p>
                            <div class="mb-3">
                                <label for="current_2fa_pin" class="form-label">Current PIN</label>
                                <input type="password" name="current_2fa_pin" id="current_2fa_pin" class="form-control" required>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="new_2fa_pin" class="form-label"><?php echo $has_pin ? 'New' : 'Create'; ?> 6-Digit PIN</label>
                            <input type="password" name="new_2fa_pin" id="new_2fa_pin" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_2fa_pin" class="form-label">Confirm New PIN</label>
                            <input type="password" name="confirm_2fa_pin" id="confirm_2fa_pin" class="form-control" required>
                        </div>
                        <button type="submit" name="set_2fa_pin" class="btn btn-primary">Save PIN</button>
                    </form>

                    <?php if ($has_pin): ?>
                    <hr>
                    <h5 class="mt-4">Delete 2FA PIN</h5>
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Deleting your 2FA PIN will reduce your account's security. We strongly recommend keeping it enabled.
                    </div>
                    <p>To delete your PIN, please enter your current account password for verification.</p>
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete your 2FA PIN? This will lower your account security.');">
                        <input type="hidden" name="delete_2fa_pin" value="1">
                        <div class="mb-3">
                            <label for="current_password_for_delete" class="form-label">Current Password</label>
                            <input type="password" name="current_password_for_delete" id="current_password_for_delete" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete PIN</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
