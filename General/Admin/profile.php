<?php
require_once '../config/session_config.php';
require_once '../config/database.php';

// Handle POST actions before any output
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email'])
) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (password_verify($current_password, $user['password'])) {
        // Update profile
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $email, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        
        // Update password if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        header('Location: profile.php?success=1');
        exit();
    } else {
        header('Location: profile.php?error=invalid_password');
        exit();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_2fa'])) {
        $admin_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $code = random_int(100000, 999999);
        $_SESSION['2fa_code'] = $code;
        $_SESSION['2fa_email'] = $admin['email'];
        require_once __DIR__ . '/../includes/email_helper.php';
        sendSystemEmail($admin['email'], 'Your 2FA Verification Code', "Your 2FA code is: <b>$code</b>");
        header('Location: verify_2fa.php');
        exit();
    } elseif (isset($_POST['disable_2fa'])) {
        $admin_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE users SET two_factor_enabled = 0 WHERE id = ?");
        $stmt->bind_param('i', $admin_id);
        $stmt->execute();
        $stmt->close();
        header('Location: profile.php');
        exit();
    }
}

// Check if user is logged in and is admin or super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Set user_type for session tracking (always 'admin' for both admin and super_admin)
$user_type = 'admin';
// Fetch active sessions and recent login activity for this admin
$sessions = [];
$stmt = $conn->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND user_type = ? ORDER BY last_activity DESC LIMIT 10");
$stmt->bind_param('is', $admin_id, $user_type);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}
$stmt->close();

// --- SESSION ACTIVITY SECTION LOGIC ---
$activity_error = '';
$activity_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['session_activity_action'])) {
    $entered_password = $_POST['activity_password'] ?? '';
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $activity_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!password_verify($entered_password, $activity_user['password'])) {
        $activity_error = 'Incorrect current password for this action.';
    } else {
        if ($_POST['session_activity_action'] === 'reset') {
            $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND user_type = ?");
            $stmt->bind_param('is', $admin_id, $user_type);
            $stmt->execute();
            $stmt->close();
            session_destroy();
            header('Location: admin_login.php?reset=1');
            exit();
        } elseif ($_POST['session_activity_action'] === 'rescan') {
            $activity_success = 'Session activity re-scanned.';
        }
    }
}
// Fetch sessions after possible rescan
$sessions = [];
$stmt = $conn->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND user_type = ? ORDER BY last_activity DESC LIMIT 10");
$stmt->bind_param('is', $admin_id, $user_type);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}
$stmt->close();
// Always fetch admin info for profile display after all POST logic
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- 2FA PIN LOGIC ---
$pin_success = '';
$pin_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_2fa_pin'])) {
    $new_pin = trim($_POST['new_2fa_pin'] ?? '');
    $confirm_pin = trim($_POST['confirm_2fa_pin'] ?? '');
    $current_pin = trim($_POST['current_2fa_pin'] ?? '');
    $has_pin = !empty($admin['two_factor_pin']);
    if (!preg_match('/^\d{6}$/', $new_pin)) {
        $pin_error = 'PIN must be exactly 6 digits.';
    } elseif ($new_pin !== $confirm_pin) {
        $pin_error = 'PINs do not match.';
    } elseif ($has_pin && (empty($current_pin) || !password_verify($current_pin, $admin['two_factor_pin']))) {
        $pin_error = 'Current PIN is incorrect.';
    } else {
        $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET two_factor_pin = ? WHERE id = ?');
        $stmt->bind_param('si', $hashed_pin, $admin_id);
        $stmt->execute();
        $stmt->close();
        $pin_success = $has_pin ? '2FA PIN changed successfully.' : '2FA PIN set successfully.';
        // Refresh admin info
        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $admin_id);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_2fa_pin'])) {
    $current_password = trim($_POST['current_password_for_delete'] ?? '');
    // Verify password
    $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $current_admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (empty($current_password) || !password_verify($current_password, $current_admin['password'])) {
        $pin_error = 'Incorrect password. PIN was not deleted.';
    } else {
        $stmt = $conn->prepare('UPDATE users SET two_factor_pin = NULL WHERE id = ?');
        $stmt->bind_param('i', $admin_id);
        $stmt->execute();
        $stmt->close();
        $pin_success = '2FA PIN has been deleted successfully.';
        // Refresh admin info
        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $admin_id);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Admin Dashboard</title>
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

        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
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
        }

        .info-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .info-value {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-vote-yea me-2"></i>
                STVC Election System - Admin
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
                        <a class="nav-link" href="manage_accounts.php"><i class="fas fa-users me-1"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php"><i class="fas fa-cog me-1"></i> Settings</a>
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
                    <h1 class="mb-2"><i class="fas fa-user me-2"></i>My Profile</h1>
                    <p class="mb-0">Manage your account information and preferences</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <i class="fas fa-user-shield fa-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Profile updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_password'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Current password is incorrect!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <!-- Profile Overview -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h4 class="text-center mb-3"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h4>
                    <p class="text-center text-muted mb-4">Administrator</p>
                    
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin['email']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Admin ID</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin['admin_id']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Member Since</div>
                        <div class="info-value"><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Account Status</div>
                        <div class="info-value">
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Edit Profile Form -->
                <div class="profile-card">
                    <h5 class="mb-3"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_id" class="form-label">Admin ID</label>
                            <input type="text" class="form-control" id="admin_id" value="<?php echo htmlspecialchars($admin['admin_id']); ?>" readonly>
                            <small class="text-muted">Admin ID cannot be changed</small>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Change Password</h6>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <button type="submit" class="btn btn-admin">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
                
                <!-- Account Security -->
                <div class="profile-card">
                    <h5 class="mb-3"><i class="fas fa-shield-alt me-2"></i>Account Security</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Last Login</div>
                                <div class="info-value"><?php echo date('M d, Y H:i', strtotime($admin['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Password Strength</div>
                                <div class="info-value">
                                    <span class="badge bg-success">Strong</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fw-bold">Two-Factor Authentication (PIN):</span>
                            <?php if (!empty($admin['two_factor_pin'])): ?>
                                <span class="badge bg-success">PIN Set</span>
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#set2FAPinModal">
                                    <i class="fas fa-key me-1"></i>Change PIN
                                </button>
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete2FAPinModal">
                                    <i class="fas fa-trash-alt me-1"></i>Delete PIN
                                </button>
                            <?php else: ?>
                                <span class="badge bg-secondary">Not Set</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($pin_error): ?><div class="alert alert-danger mt-2"><?php echo $pin_error; ?></div><?php endif; ?>
                        <?php if ($pin_success): ?><div class="alert alert-success mt-2"><?php echo $pin_success; ?></div><?php endif; ?>
                    </div>
                </div>

                <!-- Set/Change 2FA PIN Modal -->
                <div class="modal fade" id="set2FAPinModal" tabindex="-1" aria-labelledby="set2FAPinLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="POST">
                        <div class="modal-header">
                          <h5 class="modal-title" id="set2FAPinLabel"><?php echo !empty($admin['two_factor_pin']) ? 'Change 2FA PIN' : 'Set 2FA PIN'; ?></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <?php if (!empty($admin['two_factor_pin'])): ?>
                            <div class="mb-3">
                              <label for="current_2fa_pin" class="form-label">Current PIN</label>
                              <input type="password" class="form-control" id="current_2fa_pin" name="current_2fa_pin" maxlength="6" pattern="\d{6}" placeholder="Enter current 6-digit PIN" required>
                            </div>
                          <?php endif; ?>
                          <div class="mb-3">
                            <label for="new_2fa_pin" class="form-label">New PIN</label>
                            <input type="password" class="form-control" id="new_2fa_pin" name="new_2fa_pin" maxlength="6" pattern="\d{6}" placeholder="Enter new 6-digit PIN" required>
                          </div>
                          <div class="mb-3">
                            <label for="confirm_2fa_pin" class="form-label">Confirm New PIN</label>
                            <input type="password" class="form-control" id="confirm_2fa_pin" name="confirm_2fa_pin" maxlength="6" pattern="\d{6}" placeholder="Confirm new 6-digit PIN" required>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" name="set_2fa_pin" class="btn btn-primary">Save PIN</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Delete 2FA PIN Modal -->
                <div class="modal fade" id="delete2FAPinModal" tabindex="-1" aria-labelledby="delete2FAPinLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="POST">
                        <div class="modal-header">
                          <h5 class="modal-title" id="delete2FAPinLabel">Delete 2FA PIN</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete your 2FA PIN? Your account will be less secure.</p>
                            <div class="mb-3">
                              <label for="current_password_for_delete" class="form-label">Enter your password to confirm</label>
                              <input type="password" class="form-control" id="current_password_for_delete" name="current_password_for_delete" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" name="delete_2fa_pin" class="btn btn-danger">Delete PIN</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

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
                        <form method="POST" class="mb-3 d-flex gap-2 align-items-center flex-wrap">
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

                <!-- 2FA PIN Management -->
                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Two-Factor Authentication (2FA) PIN</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $has_pin = !empty($admin['two_factor_pin']);
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
                            <div class="alert alert-success"><?php echo htmlspecialchars($pin_success); ?></div>
                        <?php endif; ?>
                        <?php if ($pin_error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($pin_error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?php if ($has_pin): ?>
                                <p>Your account is protected with a 2FA PIN. You can change or delete it below.</p>
                                <div class="mb-3">
                                    <label for="current_2fa_pin" class="form-label">Current PIN</label>
                                    <input type="password" name="current_2fa_pin" id="current_2fa_pin" class="form-control" >
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="new_2fa_pin" class="form-label"><?php echo $has_pin ? 'New' : 'Create'; ?> 6-Digit PIN</label>
                                <input type="password" name="new_2fa_pin" id="new_2fa_pin" class="form-control" required pattern="\d{6}" title="PIN must be exactly 6 digits.">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_2fa_pin" class="form-label">Confirm New PIN</label>
                                <input type="password" name="confirm_2fa_pin" id="confirm_2fa_pin" class="form-control" required>
                            </div>
                            <button type="submit" name="set_2fa_pin" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $has_pin ? 'Change' : 'Set'; ?> PIN</button>
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
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="footer-overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="footer-brand d-flex align-items-center justify-content-center justify-content-md-start">
                        <img src="../uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
                        <span class="h5 mb-0">STVC Election System - Admin</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="admin_dashboard.php" class="text-white-50 text-decoration-none">Dashboard</a></li>
                        <li><a href="manage_applications.php" class="text-white-50 text-decoration-none">Applications</a></li>
                        <li><a href="manage_positions.php" class="text-white-50 text-decoration-none">Positions</a></li>
                        <li><a href="manage_users.php" class="text-white-50 text-decoration-none">Users</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white mb-3">Contact</h6>
                    <p class="text-white-50 mb-1">
                        <i class="fas fa-envelope me-2"></i>
                        admin@stvc.edu
                    </p>
                    <p class="text-white-50 mb-1">
                        <i class="fas fa-phone me-2"></i>
                        +1 (555) 123-4567
                    </p>
                    <p class="text-white-50">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        STVC Campus
                    </p>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-white-50 mb-0">
                        &copy; 2024 STVC Election System. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white-50"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

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