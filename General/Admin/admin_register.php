<?php
require_once '../config/session_config.php';

// Generate CSRF token only on GET (form display)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}

// Get any error messages from the session
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['errors'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - STVC Election System</title>
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
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            position: relative;
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .register-header h2 {
            margin: 0;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .register-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .register-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-register::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-register:hover::after {
            width: 300px;
            height: 300px;
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-link a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--primary-color);
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: var(--accent-color);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
        }

        .requirement i {
            margin-right: 0.5rem;
            font-size: 0.75rem;
        }

        .requirement.valid {
            color: var(--success-color);
        }

        .requirement.invalid {
            color: var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2><i class="fas fa-user-shield me-2"></i>Admin Registration</h2>
            <p>Create a new administrator account</p>
        </div>
        
        <div class="register-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Registration Successful!</strong><br>
                    <?php echo htmlspecialchars($success); ?>
                    <hr>
                    <div class="mt-2">
                        <h6><i class="fas fa-info-circle me-1"></i>What happens next?</h6>
                        <ul class="mb-0">
                            <li>Your account is now pending approval by the Super Administrator</li>
                            <li>You will receive an email notification once your account is approved</li>
                            <li>You can try logging in, but access will be restricted until approval</li>
                            <li>If you have urgent access needs, please contact the Super Administrator</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form action="process_admin_register.php" method="POST" id="adminRegisterForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="firstName" class="form-label">First Name</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" id="firstName" name="firstName" 
                                       placeholder="Enter first name" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="lastName" class="form-label">Last Name</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" id="lastName" name="lastName" 
                                       placeholder="Enter last name" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="adminId" class="form-label">Admin ID</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-id-card"></i>
                        </span>
                        <input type="text" class="form-control" id="adminId" name="adminId" 
                               placeholder="Enter admin ID (e.g., ADMIN001)" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-phone"></i>
                        </span>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               placeholder="Enter phone number" pattern="[0-9\-\+\s]{7,20}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter password" required>
                    </div>
                    <div class="password-requirements">
                        <div class="requirement" id="length">
                            <i class="fas fa-circle"></i>At least 8 characters
                        </div>
                        <div class="requirement" id="uppercase">
                            <i class="fas fa-circle"></i>One uppercase letter
                        </div>
                        <div class="requirement" id="lowercase">
                            <i class="fas fa-circle"></i>One lowercase letter
                        </div>
                        <div class="requirement" id="number">
                            <i class="fas fa-circle"></i>One number
                        </div>
                        <div class="requirement" id="special">
                            <i class="fas fa-circle"></i>One special character
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" 
                               placeholder="Confirm password" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" name="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and 
                            <a href="#" class="text-decoration-none">Privacy Policy</a>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus me-2"></i>Register Admin Account
                </button>
            </form>

            <div class="login-link">
                <a href="admin_login.php">
                    <i class="fas fa-sign-in-alt me-1"></i>Already have an account? Login here
                </a>
            </div>

            <div class="back-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        
        function validatePassword() {
            const value = password.value;
            
            // Check length
            const lengthValid = value.length >= 8;
            document.getElementById('length').className = `requirement ${lengthValid ? 'valid' : 'invalid'}`;
            document.getElementById('length').innerHTML = `<i class="fas fa-${lengthValid ? 'check' : 'times'}"></i>At least 8 characters`;
            
            // Check uppercase
            const uppercaseValid = /[A-Z]/.test(value);
            document.getElementById('uppercase').className = `requirement ${uppercaseValid ? 'valid' : 'invalid'}`;
            document.getElementById('uppercase').innerHTML = `<i class="fas fa-${uppercaseValid ? 'check' : 'times'}"></i>One uppercase letter`;
            
            // Check lowercase
            const lowercaseValid = /[a-z]/.test(value);
            document.getElementById('lowercase').className = `requirement ${lowercaseValid ? 'valid' : 'invalid'}`;
            document.getElementById('lowercase').innerHTML = `<i class="fas fa-${lowercaseValid ? 'check' : 'times'}"></i>One lowercase letter`;
            
            // Check number
            const numberValid = /\d/.test(value);
            document.getElementById('number').className = `requirement ${numberValid ? 'valid' : 'invalid'}`;
            document.getElementById('number').innerHTML = `<i class="fas fa-${numberValid ? 'check' : 'times'}"></i>One number`;
            
            // Check special character
            const specialValid = /[!@#$%^&*(),.?":{}|<>]/.test(value);
            document.getElementById('special').className = `requirement ${specialValid ? 'valid' : 'invalid'}`;
            document.getElementById('special').innerHTML = `<i class="fas fa-${specialValid ? 'check' : 'times'}"></i>One special character`;
        }
        
        function validateConfirmPassword() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validateConfirmPassword);
    </script>
 
</body>
</html> 