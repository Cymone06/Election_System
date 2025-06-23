<?php
require_once '../config/session_config.php';
require_once '../config/connect.php';

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'super_admin') {
    header('Location: admin_login.php?error=unauthorized');
    exit();
}

$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

if (!$election_id) {
    header('Location: manage_elections.php?error=no_election_id');
    exit();
}

// Start transaction for atomicity
$conn->begin_transaction();

try {
    // 1. Create elected_officials table if it doesn't exist
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS elected_officials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        position_name VARCHAR(255) NOT NULL,
        election_title VARCHAR(255) NOT NULL,
        term_start_date DATE,
        term_end_date DATE,
        elected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    );";
    if (!$conn->query($create_table_sql)) {
        throw new Exception("Failed to create elected_officials table: " . $conn->error);
    }

    // 2. Fetch election results to find winners
    $sql = "SELECT p.id as position_id, p.position_name, c.id as student_id, c.first_name, c.last_name, COUNT(v.id) as vote_count, e.title as election_title, e.start_date, e.end_date
            FROM votes v
            JOIN applications c ON v.candidate_id = c.id
            JOIN positions p ON v.position_id = p.id
            JOIN election_periods e ON v.election_id = e.id
            WHERE v.election_id = ?
            GROUP BY p.id, c.id
            ORDER BY p.id, vote_count DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $election_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[$row['position_name']][] = $row;
    }
    $stmt->close();

    if (empty($results)) {
        throw new Exception("No results found for this election. Cannot finalize.");
    }

    // 3. Insert winners into elected_officials table
    $stmt_insert = $conn->prepare("INSERT INTO elected_officials (student_id, first_name, last_name, position_name, election_title, term_start_date, term_end_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($results as $position => $candidates) {
        if (!empty($candidates)) {
            $winner = $candidates[0]; // The first one is the winner due to ORDER BY
            $stmt_insert->bind_param("issssss", $winner['student_id'], $winner['first_name'], $winner['last_name'], $winner['position_name'], $winner['election_title'], $winner['start_date'], $winner['end_date']);
            if (!$stmt_insert->execute()) {
                throw new Exception("Failed to insert winner: " . $stmt_insert->error);
            }
        }
    }
    $stmt_insert->close();
    
    // 4. Clear election data
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $conn->query("DELETE FROM votes WHERE election_id = $election_id");
    $conn->query("TRUNCATE TABLE application_logs");
    $conn->query("TRUNCATE TABLE candidates");
    $conn->query("TRUNCATE TABLE applications");
    $conn->query("SET FOREIGN_KEY_CHECKS=1");

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = 'Election results have been finalized, winners recorded, and data cleared.';
    header('Location: manage_elections.php');
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = 'An error occurred during finalization: ' . $e->getMessage();
    error_log($e->getMessage()); // Log error for debugging
    header('Location: manage_elections.php');
    exit();
} 