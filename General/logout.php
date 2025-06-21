<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

// If remember me cookie exists, remove it from database
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
    
    // Remove the cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?> 