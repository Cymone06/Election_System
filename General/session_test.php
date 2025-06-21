<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

echo "<h2>Session Test Page</h2>";

// Check if user is logged in
$is_logged_in = isset($_SESSION['student_db_id']);

if ($is_logged_in) {
    echo "<p>✅ User is logged in!</p>";
    echo "<p>Student DB ID: " . $_SESSION['student_db_id'] . "</p>";
    
    // Get user information
    $student_db_id = $_SESSION['student_db_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, email, department, student_id FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($user_info) {
        echo "<p>Welcome, " . htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']) . "!</p>";
        echo "<p>Student ID: " . htmlspecialchars($user_info['student_id']) . "</p>";
        echo "<p>Email: " . htmlspecialchars($user_info['email']) . "</p>";
        echo "<p>Department: " . htmlspecialchars($user_info['department']) . "</p>";
    }
    
    echo "<p><a href='logout.php'>Logout</a></p>";
} else {
    echo "<p>❌ User is not logged in</p>";
    echo "<p><a href='login.php'>Login</a></p>";
}

echo "<h3>Session Information:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session Name: " . session_name() . "</p>";

echo "<h3>All Session Variables:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><a href='index.php'>Go to Home Page</a></p>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
?> 