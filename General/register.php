<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - STVC Election System</title>
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

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .registration-section {
            padding: 80px 0;
        }

        .registration-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .form-label {
            font-weight: 500;
            color: var(--primary-color);
        }

        .required-field::after {
            content: " *";
            color: var(--accent-color);
        }

        .password-toggle-btn {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
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
                        <a class="nav-link btn btn-outline-light ms-2" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Registration Section -->
    <section class="registration-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="registration-card">
                        <h2 class="text-center mb-4">Student Registration</h2>
                        <?php
                        require_once 'config/session_config.php';
                        if (isset($_SESSION['registration_errors'])) {
                            echo '<div class="alert alert-danger">';
                            foreach ($_SESSION['registration_errors'] as $error) {
                                echo '<p class="mb-0">' . htmlspecialchars($error) . '</p>';
                            }
                            echo '</div>';
                            unset($_SESSION['registration_errors']);
                        }
                        ?>
                        <form action="process_registration.php" method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label required-field">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo isset($_SESSION['form_data']['firstName']) ? htmlspecialchars($_SESSION['form_data']['firstName']) : ''; ?>" required>
                                    <div class="invalid-feedback">Please enter your first name.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label required-field">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo isset($_SESSION['form_data']['lastName']) ? htmlspecialchars($_SESSION['form_data']['lastName']) : ''; ?>" required>
                                    <div class="invalid-feedback">Please enter your last name.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="studentId" class="form-label required-field">Student ID</label>
                                <input type="text" class="form-control" id="studentId" name="studentId" value="<?php echo isset($_SESSION['form_data']['studentId']) ? htmlspecialchars($_SESSION['form_data']['studentId']) : ''; ?>" required>
                                <div class="invalid-feedback">Please enter your student ID.</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label required-field">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>

                            <div class="mb-3">
                                <label for="id_number" class="form-label required-field">ID Number</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" required>
                                <div class="invalid-feedback">Please enter your ID number.</div>
                            </div>

                            <div class="mb-3">
                                <label for="phone_number" class="form-label required-field">Phone Number</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" required value="<?php echo isset($_SESSION['form_data']['phone_number']) ? htmlspecialchars($_SESSION['form_data']['phone_number']) : ''; ?>">
                                <div class="invalid-feedback">Please enter your phone number.</div>
                            </div>

                            <div class="mb-3">
                                <label for="gender" class="form-label required-field">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo (isset($_SESSION['form_data']['gender']) && $_SESSION['form_data']['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($_SESSION['form_data']['gender']) && $_SESSION['form_data']['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo (isset($_SESSION['form_data']['gender']) && $_SESSION['form_data']['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div class="invalid-feedback">Please select your gender.</div>
                            </div>

                            <div class="mb-3">
                                <label for="department" class="form-label required-field">Department</label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="computing_and_informatics" <?php echo (isset($_SESSION['form_data']['department']) && $_SESSION['form_data']['department'] === 'computing_and_informatics') ? 'selected' : ''; ?>>Computing and Informatics</option>
                                    <option value="electrical" <?php echo (isset($_SESSION['form_data']['department']) && $_SESSION['form_data']['department'] === 'electrical') ? 'selected' : ''; ?>>Electrical Engineering</option>
                                    <option value="mechanical" <?php echo (isset($_SESSION['form_data']['department']) && $_SESSION['form_data']['department'] === 'mechanical') ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                    <option value="civil" <?php echo (isset($_SESSION['form_data']['department']) && $_SESSION['form_data']['department'] === 'civil') ? 'selected' : ''; ?>>Civil Engineering</option> 
                                    <option value="business" <?php echo (isset($_SESSION['form_data']['department']) && $_SESSION['form_data']['department'] === 'business') ? 'selected' : ''; ?>>Business Administration</option>
                                    <option value="fashion_and_design" <?php echo (isset($_SESSION['form_data']['department']) && $_SESSION['form_data']['department'] === 'fashion_and_design') ? 'selected' : ''; ?>>Fashion and Design</option>
                                    <option value="agricultural_and_extensions" <?php echo (isset($_SESSION['form_data']['department']) && $_SESSION['form_data']['department'] === 'agricultural_and_extensions') ? 'selected' : ''; ?>>Agriculturaland Extensions</option>
                                    <option value="food_and_bevarage" <?php echo (isset($_SESSION['form_data']['department']) && $_SESSION['form_data']['department'] === 'food_and_bevarage') ? 'selected' : ''; ?>>Food and Beverage</option>
                                    <option value="building_technology" <?php echo (isset($_SESSION['form_data']['department']) && $_SESSION['form_data']['department'] === 'building_technology') ? 'selected' : ''; ?>>Building Technology</option>
                                </select>
                                <div class="invalid-feedback">Please select your department.</div>
                            </div>

                            <div class="mb-3">
                                <label for="course_level" class="form-label required-field">Course Level</label>
                                <select class="form-select" id="course_level" name="course_level" required>
                                    <option value="">Select Course Level</option>
                                    <option value="Diploma/Level 6" <?php echo (isset($_SESSION['form_data']['course_level']) && $_SESSION['form_data']['course_level'] === 'Diploma/Level 6') ? 'selected' : ''; ?>>Diploma/Level 6</option>
                                    <option value="Certificate/Level 5" <?php echo (isset($_SESSION['form_data']['course_level']) && $_SESSION['form_data']['course_level'] === 'Certificate/Level 5') ? 'selected' : ''; ?>>Certificate/Level 5</option>
                                    <option value="Artisan/Level 3" <?php echo (isset($_SESSION['form_data']['course_level']) && $_SESSION['form_data']['course_level'] === 'Artisan/Level 3') ? 'selected' : ''; ?>>Artisan/Level 3</option>
                                    <option value="Other" <?php echo (isset($_SESSION['form_data']['course_level']) && $_SESSION['form_data']['course_level'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div class="invalid-feedback">Please select your course level.</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label required-field">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <span class="input-group-text password-toggle-btn" id="togglePassword">
                                        <i class="fa fa-eye" aria-hidden="true"></i>
                                    </span>
                                    <div class="invalid-feedback">Please enter a password.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label required-field">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                    <span class="input-group-text password-toggle-btn" id="toggleConfirmPassword">
                                        <i class="fa fa-eye" aria-hidden="true"></i>
                                    </span>
                                    <div class="invalid-feedback">Please confirm your password.</div>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">I agree to the terms and conditions</label>
                                <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Register</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

        // Password confirmation validation
        document.getElementById('confirmPassword').addEventListener('input', function() {
            if (this.value !== document.getElementById('password').value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Show/hide password functionality
        function setupPasswordToggle(toggleBtnId, passwordInputId) {
            const togglePassword = document.querySelector(toggleBtnId);
            const password = document.querySelector(passwordInputId);

            if (togglePassword && password) {
                togglePassword.addEventListener('click', function (e) {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        }

        setupPasswordToggle('#togglePassword', '#password');
        setupPasswordToggle('#toggleConfirmPassword', '#confirmPassword');
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