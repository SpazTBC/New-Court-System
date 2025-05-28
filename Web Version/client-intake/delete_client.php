<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_intakes.php?delete_error=1");
    exit();
}

$client_id = $_GET['id'];

// Database connection
require_once "../include/database.php";

// Function to recursively delete directory and all contents
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // First, verify the client exists and get their info for logging
    $stmt = $conn->prepare("SELECT first_name, last_name FROM client_intake WHERE id = :id");
    $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $conn->rollback();
        header("Location: view_intakes.php?delete_error=2");
        exit();
    }
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    $client_name = $client['first_name'] . ' ' . $client['last_name'];
    
    // Delete client from database
    $stmt = $conn->prepare("DELETE FROM client_intake WHERE id = :id");
    $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Commit database transaction
    $conn->commit();
    
    // Now delete the client folder and all files
    $client_folder = "clients/client_" . $client_id;
    
    if (is_dir($client_folder)) {
        if (deleteDirectory($client_folder)) {
            // Log the deletion
            $log_entry = date('Y-m-d H:i:s') . " - Client deleted: " . $client_name . " (ID: " . $client_id . ") by " . $_SESSION['username'] . " - Database and files removed\n";
            file_put_contents("clients/deletion_log.txt", $log_entry, FILE_APPEND | LOCK_EX);
            
            // Success - redirect with success message
            header("Location: view_intakes.php?deleted=success");
        } else {
            // Database deleted but folder deletion failed
            $log_entry = date('Y-m-d H:i:s') . " - Client deleted: " . $client_name . " (ID: " . $client_id . ") by " . $_SESSION['username'] . " - Database removed, folder deletion failed\n";
            file_put_contents("clients/deletion_log.txt", $log_entry, FILE_APPEND | LOCK_EX);
            
            header("Location: view_intakes.php?deleted=success&folder_warning=1");
        }
    } else {
        // No folder existed, just log database deletion
        $log_entry = date('Y-m-d H:i:s') . " - Client deleted: " . $client_name . " (ID: " . $client_id . ") by " . $_SESSION['username'] . " - Database removed, no folder found\n";
        file_put_contents("clients/deletion_log.txt", $log_entry, FILE_APPEND | LOCK_EX);
        
        header("Location: view_intakes.php?deleted=success");
    }
    
} catch(PDOException $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log the error
    $log_entry = date('Y-m-d H:i:s') . " - Failed to delete client ID: " . $client_id . " by " . $_SESSION['username'] . " - Error: " . $e->getMessage() . "\n";
    file_put_contents("clients/deletion_log.txt", $log_entry, FILE_APPEND | LOCK_EX);
    
    header("Location: view_intakes.php?delete_error=3");
}

exit();
?>