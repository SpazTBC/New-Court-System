<?php
session_start();
include("../include/database.php");

if (isset($_POST['login_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $password = base64_encode(hash('SHA256', $password));
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->execute([$username, $password]);
        
        if ($stmt->rowCount() === 1) {
            $_SESSION['username'] = $username;
            $_SESSION['success'] = "You are now logged in";
            header('location: home.php');
            exit();
        } else {
            header('location: index.php?error=invalid');
            exit();
        }
    }
}
?>