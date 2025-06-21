<?php
require_once 'config/session_config.php';
require_once 'config/database.php';
global $conn;

// Robust database connection check
if (!isset($conn) || !$conn instanceof mysqli || $conn->connect_error) {
    die('Database connection failed. Please contact the administrator.');
}

// Robust session check
if (!isset($_SESSION['student_db_id']) || !is_numeric($_SESSION['student_db_id'])) {
    $_SESSION['error'] = 'Please log in to apply for positions.';
    header('Location: login.php');
    exit();
}

// Check application portal status
$portal_status_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'application_portal_status'");
$application_portal_status = $portal_status_result->fetch_assoc()['setting_value'] ?? 'closed';

// Fetch user info for autofill
$user_info = null;
$student_db_id = (int)$_SESSION['student_db_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email, student_id, phone_number, profile_picture FROM students WHERE id = ?");
if (!$stmt) {
    die('Failed to prepare statement for user info.');
}
$stmt->bind_param("i", $student_db_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user_info) {
    $_SESSION['error'] = 'User information not found.';
    header('Location: login.php');
    exit();
}

// Fetch available positions from the database
$stmt = $conn->prepare("SELECT id, position_name, description FROM positions WHERE status = 'active'");
if (!$stmt) {
    die('Failed to prepare statement for positions.');
}
$stmt->execute();
$positions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get any error messages from the session
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);

// Fetch profile picture
$profile_pic_path = (!empty($user_info['profile_picture']) && file_exists($user_info['profile_picture'])) ? $user_info['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_info['first_name'] . ' ' . $user_info['last_name']) . '&background=3498db&color=fff&size=128';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Position - STVC Election System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1 0 auto;
        }

        .footer-section {
            flex-shrink: 0;
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

        .application-section {
            padding: 80px 0;
        }

        .application-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
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
            padding: 10px 30px;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .position-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .position-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .position-card.selected {
            border-color: var(--secondary-color);
            background-color: rgba(52, 152, 219, 0.1);
        }

        .alert {
            border-radius: 8px;
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-vote-yea me-2"></i>
                STVC Election System
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
                        <a class="nav-link active" href="application.php"><i class="fas fa-edit me-1"></i> Apply</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width:36px;height:36px;border-radius:50%;object-fit:cover;margin-right:8px;vertical-align:middle;">
                            <?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="application.php"><i class="fas fa-edit me-2"></i>My Applications</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Application Section -->
    <main>
        <section class="application-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
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

                        <?php if ($application_portal_status === 'closed'): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-3x mb-3"></i>
                                <h4 class="alert-heading">Application Portal Closed</h4>
                                <p>The application portal is not currently open. Please check back later or contact an administrator for more information.</p>
                                <hr>
                                <a href="index.php" class="btn btn-primary">Return to Homepage</a>
                            </div>
                        <?php else: ?>
                            <div class="application-form">
                                <h2 class="text-center mb-4">Apply for Position</h2>
                                <form action="process_application.php" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                                    <!-- Personal Information -->
                                    <div class="mb-4">
                                        <h4 class="mb-3">Personal Information</h4>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="firstName" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user_info['first_name'] ?? ''); ?>" required>
                                                <div class="invalid-feedback">Please enter your first name.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="lastName" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user_info['last_name'] ?? ''); ?>" required>
                                                <div class="invalid-feedback">Please enter your last name.</div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="admissionNumber" class="form-label">Admission Number</label>
                                                <input type="text" class="form-control" id="admissionNumber" name="admissionNumber" required>
                                                <div class="invalid-feedback">Please enter your admission number.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="hometown" class="form-label">Hometown</label>
                                                <input type="text" class="form-control" id="hometown" name="hometown" required>
                                                <div class="invalid-feedback">Please enter your hometown.</div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="yearOfAdmission" class="form-label">Year of Admission</label>
                                                <input type="number" class="form-control" id="yearOfAdmission" name="yearOfAdmission" min="2000" max="2100" required>
                                                <div class="invalid-feedback">Please enter your year of admission.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="yearOfGraduation" class="form-label">Year of Graduation</label>
                                                <input type="number" class="form-control" id="yearOfGraduation" name="yearOfGraduation" min="2000" max="2100" required>
                                                <div class="invalid-feedback">Please enter your year of graduation.</div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" required readonly>
                                            <div class="invalid-feedback">Please enter a valid email address.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="studentId" class="form-label">Student ID</label>
                                            <input type="text" class="form-control" id="studentId" name="studentId" value="<?php echo htmlspecialchars($user_info['student_id'] ?? ''); ?>" required readonly>
                                            <div class="invalid-feedback">Please enter your student ID.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_info['phone_number'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">Please enter your phone number.</div>
                                        </div>
                                    </div>

                                    <!-- Position Selection -->
                                    <div class="mb-4">
                                        <h4 class="mb-3">Select Position</h4>
                                        <div class="position-list">
                                            <?php foreach ($positions as $position): ?>
                                                <div class="position-card">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="position" 
                                                               id="position<?php echo $position['id']; ?>" 
                                                               value="<?php echo $position['id']; ?>" required>
                                                        <label class="form-check-label" for="position<?php echo $position['id']; ?>">
                                                            <h5 class="mb-1"><?php echo htmlspecialchars($position['position_name']); ?></h5>
                                                            <p class="mb-0 text-muted"><?php echo htmlspecialchars($position['description']); ?></p>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Biography and Goals -->
                                    <div class="mb-4">
                                        <h4 class="mb-3">Biography and Goals</h4>
                                        <div class="mb-3">
                                            <label for="biography" class="form-label">Biography</label>
                                            <textarea class="form-control" id="biography" name="biography" rows="4" required
                                                      placeholder="Tell us about yourself, your experience, and why you're interested in this position."></textarea>
                                            <div class="invalid-feedback">Please provide your biography.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="goals" class="form-label">Goals and Vision</label>
                                            <textarea class="form-control" id="goals" name="goals" rows="4" required
                                                      placeholder="What are your goals if elected? What changes would you like to implement?"></textarea>
                                            <div class="invalid-feedback">Please describe your goals and vision.</div>
                                        </div>
                                    </div>

                                    <!-- Additional Information -->
                                    <div class="mb-4">
                                        <h4 class="mb-3">Additional Information</h4>
                                        <div class="mb-3">
                                            <label for="experience" class="form-label">Relevant Experience</label>
                                            <textarea class="form-control" id="experience" name="experience" rows="3"
                                                      placeholder="List any relevant experience or achievements."></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="skills" class="form-label">Skills and Qualifications</label>
                                            <textarea class="form-control" id="skills" name="skills" rows="3"
                                                      placeholder="List your relevant skills and qualifications."></textarea>
                                        </div>
                                    </div>

                                    <!-- Image Upload Section -->
                                    <div class="mb-4">
                                        <h4 class="mb-3">Upload Profile Image</h4>
                                        <div class="mb-3">
                                                <label for="image1" class="form-label">Profile Image</label>
                                                <input type="file" class="form-control" id="image1" name="image1" accept="image/*" required>
                                                <div class="invalid-feedback">Please upload a profile image.</div>
                                                <small class="text-muted">Upload a clear profile photo (Max size: 2MB)</small>
                                                <div id="image1Preview" class="mt-2" style="display: none;">
                                                    <img src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary w-100">Submit Application</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Position card selection effect
        document.querySelectorAll('.position-card').forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            radio.addEventListener('change', function() {
                document.querySelectorAll('.position-card').forEach(c => c.classList.remove('selected'));
                if (this.checked) {
                    card.classList.add('selected');
                }
            });
        });

        // Image preview functionality
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                // Check file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }

                // Check file type
                if (!file.type.startsWith('image/')) {
                    alert('Please upload an image file');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.querySelector('img').src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Add event listeners for image preview
        document.getElementById('image1').addEventListener('change', function() {
            previewImage(this, 'image1Preview');
        });
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
</html> 