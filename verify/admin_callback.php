<?php

include("config/config.php");


session_start();
// Include your database connection code here

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Exchange code for token (similar to your existing callback.php)
    // Fetch user information
    
    if ($user && isset($user['id'])) {
        $discord_id = $conn->real_escape_string($user['id']);
        
        // Check if the user is an admin
        $admin_check_sql = "SELECT * FROM admins WHERE discord_id = '$discord_id'";
        $admin_result = $conn->query($admin_check_sql);
        
        if ($admin_result->num_rows > 0) {
            $_SESSION['is_admin'] = true;
            $_SESSION['discord_id'] = $discord_id;
            $_SESSION['username'] = $user['username'];
            header("Location: admin.php");
            exit;
        } else {
            $_SESSION['is_admin'] = false;
            header("Location: admin_login.php?error=not_admin");
            exit;
        }
    }
}

header("Location: admin_login.php");
exit;
?>