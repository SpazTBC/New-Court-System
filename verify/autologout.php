<?php
session_start();

// Set the timeout period in seconds (10 seconds for this example)
$timeout_period = 10;

// Check if the last activity time is set
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_period)) {
    // Last activity was more than 10 seconds ago
    session_unset();     // Unset all session variables
    session_destroy();   // Destroy the session
    
    // Redirect to the index page (which will handle Discord authentication if needed)
    header("Location: index.php");
    exit();
}

// Update last activity time stamp
$_SESSION['last_activity'] = time();

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['verified']) && $_SESSION['verified'];
}

// Function to log out the user
function logout() {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>