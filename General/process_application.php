<?php
require_once 'config/session_config.php';
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['student_db_id'])) {
    $_SESSION['error'] = 'Please log in to submit an application.';
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $studentId = trim($_POST['studentId'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $positionId = (int)($_POST['position'] ?? 0);
    $biography = trim($_POST['biography'] ?? '');
    $goals = trim($_POST['goals'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $admissionNumber = trim($_POST['admissionNumber'] ?? '');
    $yearOfAdmission = (int)($_POST['yearOfAdmission'] ?? 0);
    $yearOfGraduation = (int)($_POST['yearOfGraduation'] ?? 0);
    $hometown = trim($_POST['hometown'] ?? '');

    // Validate required fields
    $errors = [];
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($studentId)) $errors[] = "Student ID is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if ($positionId <= 0) $errors[] = "Please select a position";
    if (empty($biography)) $errors[] = "Biography is required";
    if (empty($goals)) $errors[] = "Goals and vision are required";
    if (empty($admissionNumber)) $errors[] = "Admission number is required";
    if (empty($yearOfAdmission) || $yearOfAdmission < 2000 || $yearOfAdmission > 2100) $errors[] = "Valid year of admission is required";
    if (empty($yearOfGraduation) || $yearOfGraduation < 2000 || $yearOfGraduation > 2100) $errors[] = "Valid year of graduation is required";
    if (empty($hometown)) $errors[] = "Hometown is required";

    // Validate image uploads
    $uploadDir = 'uploads/applications/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    // Function to validate and process image upload
    function processImage($file, $index) {
        global $errors, $allowedTypes, $maxFileSize, $uploadDir, $studentId;
        
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image $index is required and must be uploaded without errors.";
            return false;
        }

        // Check for PHP upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading image $index (error code: {$file['error']}).";
            return false;
        }

        // Validate file type by content
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = "Image $index is not a valid image file.";
            return false;
        }
        if (!in_array($imageInfo['mime'], $allowedTypes)) {
            $errors[] = "Image $index must be a JPEG or PNG file.";
            return false;
        }

        if ($file['size'] > $maxFileSize) {
            $errors[] = "Image $index must be less than 2MB.";
            return false;
        }

        // Generate a unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeStudentId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $studentId);
        $filename = $safeStudentId . '_' . $index . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $errors[] = "Failed to upload image $index. Please try again. Debug: TMP=" . $file['tmp_name'] . ", DEST=" . $filepath . ", is_writable=" . (is_writable($uploadDir) ? 'yes' : 'no');
            return false;
        }

        return $filename;
    }

    // Process image1 only
    $image1 = processImage($_FILES['image1'] ?? null, 1);

    // Check if student ID already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM applications WHERE student_id = ?");
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "An application with this Student ID already exists";
        }
        $stmt->close();
    }

    // If no errors, proceed with submission
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->begin_transaction();

            // Insert application
            $stmt = $conn->prepare("
                INSERT INTO applications (
                    first_name, last_name, email, student_id, admission_number, year_of_admission, year_of_graduation, hometown, phone,
                    position_id, biography, goals, experience, skills,
                    image1, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->bind_param(
                "sssssiissssssss",
                $firstName, $lastName, $email, $studentId, $admissionNumber, $yearOfAdmission, $yearOfGraduation, $hometown, $phone,
                $positionId, $biography, $goals, $experience, $skills,
                $image1
            );

            if ($stmt->execute()) {
                $applicationId = $conn->insert_id;
                
                // Log the application submission
                $stmt = $conn->prepare("
                    INSERT INTO application_logs (
                        application_id, action, details, created_at
                    ) VALUES (?, 'submitted', 'Application submitted successfully', NOW())
                ");
                $stmt->bind_param("i", $applicationId);
                $stmt->execute();

                // Commit transaction
                $conn->commit();

                // Set success message
                $_SESSION['success'] = "Your application has been submitted successfully! We will review it and get back to you soon.";
                header("Location: application.php");
                exit();
            } else {
                throw new Exception("Failed to submit application");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            // Clean up uploaded files if they exist
            if ($image1 && file_exists($uploadDir . $image1)) {
                unlink($uploadDir . $image1);
            }
            
            $errors[] = "An error occurred while submitting your application. Please try again.";
        }
    }

    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: application.php");
        exit();
    }
} else {
    // If not a POST request, redirect to application form
    header("Location: application.php");
    exit();
}
?> 