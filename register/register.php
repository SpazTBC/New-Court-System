<?php
session_start();
include("../include/database.php");

if (isset($_POST['submit'])) {
    // Clean and validate inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $job = trim($_POST['jobs']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $character = preg_replace('/[^A-Za-z0-9\-\ ]/', '', trim($_POST['character']));

    // Validation checks
    $errors = [];
    
    // Username blacklist
    $blacklist = ['Admin', 'Administrator', 'Staff', 'Moderator', 'Mod', 'Owner'];
    if (in_array($username, $blacklist)) {
        header('Location: index.php?error=username_restricted');
        exit();
    }

    // Required fields validation
    if (empty($username)) $errors[] = 'username_required';
    if (empty($password)) $errors[] = 'password_required';
    if (empty($character)) $errors[] = 'character_required';
    
    // Job validation
    $valid_jobs = ['Civilian', 'Judge', 'Attorney'];
    if (!in_array($job, $valid_jobs)) $errors[] = 'invalid_job';

    if (empty($errors)) {
        try {
            // Check for existing username
            $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                header('Location: index.php?error=username_exists');
                exit();
            }

            // Insert new user
            $encrypted_password = base64_encode(hash('SHA256', $password));
            
            $stmt = $conn->prepare("
                INSERT INTO users (charactername, username, password, job, supervisorjob, 
                                 shared1, shared2, shared3, shared4, ip)
                VALUES (?, ?, ?, ?, '0', '1', '1', '1', '1', ?)
            ");
            
            $stmt->execute([$character, $username, $encrypted_password, $job, $ip]);

            // Set session and redirect
            $_SESSION['username'] = $username;
            $_SESSION['success'] = "Registration successful";
            header('Location: ../login/home.php');
            exit();

        } catch(PDOException $e) {
            header('Location: index.php?error=system');
            exit();
        }
    } else {
        header('Location: index.php?error=' . implode(',', $errors));
        exit();
    }
}

include("../include/footer.php");
?>
