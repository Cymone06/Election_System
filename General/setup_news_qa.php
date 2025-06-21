<?php
require_once 'config/database.php';

echo "<h2>Setting up News & Q&A System</h2>";

try {
    // Read and execute the SQL file
    $sqlFile = 'config/news_qa_tables_fixed.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if ($conn->query($statement)) {
                    echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
                } else {
                    echo "<p style='color: red;'>✗ Error executing: " . $conn->error . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color: red;'>✗ SQL file not found: $sqlFile</p>";
    }
    
    // Create uploads directory for news images
    $uploadsDir = 'uploads/news/';
    if (!file_exists($uploadsDir)) {
        if (mkdir($uploadsDir, 0777, true)) {
            echo "<p style='color: green;'>✓ Created directory: $uploadsDir</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create directory: $uploadsDir</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Directory already exists: $uploadsDir</p>";
    }
    
    echo "<h3 style='color: green;'>✓ News & Q&A system setup completed!</h3>";
    echo "<p><a href='news.php'>Go to News & Q&A page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Setup failed: " . $e->getMessage() . "</p>";
}
?> 