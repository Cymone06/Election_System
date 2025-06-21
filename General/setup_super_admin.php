<?php
require_once 'config/database.php';

echo "<h2>Setting up Super Admin System</h2>";

try {
    // Update the users table to include new roles and statuses
    echo "<p>Updating users table schema...</p>";
    
    // Add super_admin to role ENUM
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'super_admin', 'election_officer') DEFAULT 'student'";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Role ENUM updated successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Role ENUM already updated or error: " . $conn->error . "</p>";
    }
    
    // Add pending and rejected to status ENUM
    $sql = "ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'pending', 'rejected') DEFAULT 'active'";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Status ENUM updated successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Status ENUM already updated or error: " . $conn->error . "</p>";
    }
    
    // Create super admin user
    echo "<p>Creating super admin user...</p>";
    
    // Check if super admin already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = 'superadmin@stvc.edu'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create super admin user (password: SuperAdmin@123)
        $hashedPassword = password_hash('SuperAdmin@123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO users (student_id, email, password, first_name, last_name, role, status, created_at) 
            VALUES ('SUPERADMIN001', 'superadmin@stvc.edu', ?, 'Super', 'Admin', 'super_admin', 'active', NOW())
        ");
        $stmt->bind_param('s', $hashedPassword);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Super admin user created successfully</p>";
            echo "<p><strong>Super Admin Credentials:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Email:</strong> superadmin@stvc.edu</li>";
            echo "<li><strong>Password:</strong> SuperAdmin@123</li>";
            echo "<li><strong>Student ID:</strong> SUPERADMIN001</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create super admin user: " . $stmt->error . "</p>";
        }
    } else {
        // Update existing super admin
        $hashedPassword = password_hash('SuperAdmin@123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            UPDATE users 
            SET role = 'super_admin', status = 'active', password = ? 
            WHERE email = 'superadmin@stvc.edu'
        ");
        $stmt->bind_param('s', $hashedPassword);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Super admin user updated successfully</p>";
            echo "<p><strong>Super Admin Credentials:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Email:</strong> superadmin@stvc.edu</li>";
            echo "<li><strong>Password:</strong> SuperAdmin@123</li>";
            echo "<li><strong>Student ID:</strong> SUPERADMIN001</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>✗ Failed to update super admin user: " . $stmt->error . "</p>";
        }
    }
    
    // Update existing admin users to have 'admin' role if not set
    echo "<p>Updating existing admin users...</p>";
    $sql = "UPDATE users SET role = 'admin' WHERE role = 'student' AND email LIKE '%@stvc.edu' AND email != 'superadmin@stvc.edu'";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Existing admin users updated</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Error updating existing admin users: " . $conn->error . "</p>";
    }
    
    echo "<h3 style='color: green;'>✓ Super Admin System Setup Complete!</h3>";
    echo "<p><a href='Admin/admin_login.php'>Go to Admin Login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?> 