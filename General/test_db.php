<?php
require_once 'config/connect.php';

echo "<h2>Database Connection Test</h2>";

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
} else {
    echo "✅ Database connection successful!<br><br>";
    
    // Test if students table exists
    $result = $conn->query("SHOW TABLES LIKE 'students'");
    if ($result->num_rows > 0) {
        echo "✅ Students table exists<br>";
        
        // Show table structure
        $result = $conn->query("DESCRIBE students");
        echo "<h3>Students table structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "❌ Students table does not exist<br>";
    }
    
    // Test if password_resets table exists
    $result = $conn->query("SHOW TABLES LIKE 'password_resets'");
    if ($result->num_rows > 0) {
        echo "✅ Password resets table exists<br>";
        
        // Show table structure
        $result = $conn->query("DESCRIBE password_resets");
        echo "<h3>Password resets table structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "❌ Password resets table does not exist<br>";
    }
    
    // Test if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "✅ Users table exists<br>";
    } else {
        echo "❌ Users table does not exist<br>";
    }
}

$conn->close();
?> 