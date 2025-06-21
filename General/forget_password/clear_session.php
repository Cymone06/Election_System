<?php
session_start();

// Clear reset-related session data
unset($_SESSION['reset_link']);
unset($_SESSION['user_email']);

// Return success response
http_response_code(200);
echo json_encode(['status' => 'success']);
?> 