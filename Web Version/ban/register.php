<?php
include("index.php");
include("../include/database.php");

if(isset($_POST['submit']) && isset($_POST['username']) && isset($_POST['ban'])) {
    $username = $_POST['username'];
    $ban = $_POST['ban'];

    if($ban === "1" || $ban === "0") {
        // Check if user exists
        $stmt = $conn->prepare("SELECT username FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        
        if($stmt->fetch()) {
            // Update user ban status
            $updateStmt = $conn->prepare("UPDATE users SET banned = :ban WHERE username = :username");
            $updateStmt->execute([
                'ban' => $ban,
                'username' => $username
            ]);
            
            echo "Successfully Updated";
        }
    } else {
        echo "Please enter either a 1 To ban them, or 0 to unban.";
    }
}

include(base64_decode('Li4vaW5jbHVkZS9mb290ZXIucGhw'));
?>