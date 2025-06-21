<?php
require_once 'config/connect.php';

echo "<h2>Database Setup Script</h2>";

// Read and execute the SQL file
$sql_file = 'config/database.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if ($conn->query($statement)) {
                $success_count++;
                echo "✅ Executed: " . substr($statement, 0, 50) . "...<br>";
            } else {
                $error_count++;
                echo "❌ Error: " . $conn->error . " in statement: " . substr($statement, 0, 50) . "...<br>";
            }
        }
    }
    
    echo "<br><strong>Setup Complete:</strong><br>";
    echo "✅ Successful statements: $success_count<br>";
    echo "❌ Failed statements: $error_count<br>";
    
} else {
    echo "❌ SQL file not found: $sql_file";
}

// Test the tables
echo "<h3>Testing Tables:</h3>";

$tables = ['users', 'students', 'password_resets', 'positions', 'applications', 'candidates', 'votes', 'election_periods', 'updates'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' missing<br>";
    }
}

$conn->close();
?> 