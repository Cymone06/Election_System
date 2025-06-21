<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'election_system');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

/**
 * Utility function to sanitize input data
 * @param string $data The data to sanitize
 * @return string Sanitized data
 */
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Utility function to execute a prepared statement
 * @param string $sql The SQL query
 * @param string $types The types of parameters
 * @param array $params The parameters to bind
 * @return mysqli_stmt|false The prepared statement or false on failure
 */
function execute_query($sql, $types = "", $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("Query preparation failed: " . $conn->error);
        return false;
    }

    // Only bind if both types and params are provided and count matches
    if (!empty($params) && !empty($types)) {
        if (strlen($types) !== count($params)) {
            error_log("Parameter count does not match types string in execute_query.");
            $stmt->close();
            return false;
        }
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    return $stmt;
}

/**
 * Utility function to fetch a single row
 * @param string $sql The SQL query
 * @param string $types The types of parameters
 * @param array $params The parameters to bind
 * @return array|null The fetched row or null if not found
 */
function fetch_row($sql, $types = "", $params = []) {
    $stmt = execute_query($sql, $types, $params);
    if ($stmt === false) {
        return null;
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Utility function to fetch multiple rows
 * @param string $sql The SQL query
 * @param string $types The types of parameters
 * @param array $params The parameters to bind
 * @return array The fetched rows
 */
function fetch_all($sql, $types = "", $params = []) {
    $stmt = execute_query($sql, $types, $params);
    if ($stmt === false) {
        return [];
    }

    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Utility function to insert data and return the last insert ID
 * @param string $sql The SQL query
 * @param string $types The types of parameters
 * @param array $params The parameters to bind
 * @return int|false The last insert ID or false on failure
 */
function insert_data($sql, $types = "", $params = []) {
    $stmt = execute_query($sql, $types, $params);
    if ($stmt === false) {
        return false;
    }

    $insert_id = $stmt->insert_id;
    $stmt->close();
    return $insert_id;
}

/**
 * Utility function to update data
 * @param string $sql The SQL query
 * @param string $types The types of parameters
 * @param array $params The parameters to bind
 * @return bool True on success, false on failure
 */
function update_data($sql, $types = "", $params = []) {
    $stmt = execute_query($sql, $types, $params);
    if ($stmt === false) {
        return false;
    }

    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    return $affected_rows > 0;
}

/**
 * Utility function to delete data
 * @param string $sql The SQL query
 * @param string $types The types of parameters
 * @param array $params The parameters to bind
 * @return bool True on success, false on failure
 */
function delete_data($sql, $types = "", $params = []) {
    $stmt = execute_query($sql, $types, $params);
    if ($stmt === false) {
        return false;
    }

    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    return $affected_rows > 0;
}

/**
 * Utility function to begin a transaction
 * @return bool True on success, false on failure
 */
function begin_transaction() {
    global $conn;
    return $conn->begin_transaction();
}

/**
 * Utility function to commit a transaction
 * @return bool True on success, false on failure
 */
function commit_transaction() {
    global $conn;
    return $conn->commit();
}

/**
 * Utility function to rollback a transaction
 * @return bool True on success, false on failure
 */
function rollback_transaction() {
    global $conn;
    return $conn->rollback();
}

/**
 * Utility function to check if a record exists
 * @param string $table The table name
 * @param string $column The column to check
 * @param mixed $value The value to check for
 * @return bool True if record exists, false otherwise
 */
function record_exists($table, $column, $value) {
    $sql = "SELECT 1 FROM $table WHERE $column = ? LIMIT 1";
    $stmt = execute_query($sql, "s", [$value]);
    if ($stmt === false) {
        return false;
    }

    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Utility function to get the count of records
 * @param string $table The table name
 * @param string $where The WHERE clause (optional)
 * @param string $types The types of parameters (optional)
 * @param array $params The parameters to bind (optional)
 * @return int The count of records
 */
function get_count($table, $where = "", $types = "", $params = []) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    if (!empty($where)) {
        $sql .= " WHERE $where";
    }

    $stmt = execute_query($sql, $types, $params);
    if ($stmt === false) {
        return 0;
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)$row['count'];
}

// Error handling
function handle_error($message, $error = null) {
    error_log($message . ($error ? ": " . $error : ""));
    return false;
}

// Close the database connection when the script ends
register_shutdown_function(function() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
});

function getCurrentSessionId() {
    return session_id();
}

function logUserSession($conn, $user_id, $user_type) {
    $session_id = session_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    // Deactivate all previous sessions for this user
    $stmt = $conn->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ? AND user_type = ?");
    $stmt->bind_param('is', $user_id, $user_type);
    $stmt->execute();
    $stmt->close();
    // Insert new session
    $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, user_type, ip_address, user_agent, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param('issss', $user_id, $session_id, $user_type, $ip, $ua);
    $stmt->execute();
    $stmt->close();
}

function checkActiveSession($conn, $user_id, $user_type) {
    $session_id = session_id();
    $stmt = $conn->prepare("SELECT session_id, ip_address, user_agent FROM user_sessions WHERE user_id = ? AND user_type = ? AND is_active = 1 ORDER BY last_activity DESC LIMIT 1");
    $stmt->bind_param('is', $user_id, $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    if ($row && $row['session_id'] !== $session_id) {
        return $row;
    }
    return false;
}
?> 