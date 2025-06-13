<?php
session_start();
// Check if user is logged in and has proper permissions
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Database connection
require_once "../../include/database.php";

// Check if user is an attorney or admin
$stmt = $conn->prepare("SELECT job FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user['job'] !== "Attorney" && $user['job'] !== "Admin") {
    header("Location: /login/home.php");
    exit();
}

if(!isset($_POST['action']) || $_POST['action'] !== 'delete_orphaned') {
    header("Location: cleanup.php");
    exit();
}

// Function to recursively delete directory
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

try {
    // Get all valid client IDs from database
    $stmt = $conn->prepare("SELECT id FROM client_intake");
    $stmt->execute();
    $valid_clients = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Find orphaned folders
    $deleted_count = 0;
    $error_count = 0;
    $deleted_folders = [];

    if ($handle = opendir('.')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && is_dir($entry) && strpos($entry, 'client_') === 0) {
                $client_id = str_replace('client_', '', $entry);
                if (is_numeric($client_id) && !in_array($client_id, $valid_clients)) {
                    // This is an orphaned folder
                    if (deleteDirectory($entry)) {
                        $deleted_count++;
                        $deleted_folders[] = $entry;
                    } else {
                        $error_count++;
                    }
                }
            }
        }
        closedir($handle);
    }

    if ($error_count > 0) {
        $message = "Deleted {$deleted_count} folders, but {$error_count} folders could not be deleted due to permission issues.";
        header("Location: cleanup.php?error=" . urlencode($message));
    } else {
        $message = "Successfully deleted {$deleted_count} orphaned folders.";
        header("Location: cleanup.php?success=" . urlencode($message));
    }

} catch(Exception $e) {
    $message = "Error during cleanup: " . $e->getMessage();
    header("Location: cleanup.php?error=" . urlencode($message));
}

exit();
?>