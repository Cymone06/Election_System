<?php
// Database connection parameters
$host = 'localhost';
$username = 'root'; // Change to your database username
$password = ''; // Change to your database password
$database = 'election_system'; // Change to your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Set character set to utf8mb4
$conn->set_charset('utf8mb4');
?> 