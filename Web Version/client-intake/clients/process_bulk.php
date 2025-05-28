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

if($user['job'] !== "Attorney") {
    header("Location: /login/home.php");
    exit();
}

if(!isset($_GET['action'])) {
    header("Location: bulk_operations.php");
    exit();
}

$action = $_GET['action'];
$results = [];

switch($action) {
    case 'create_folders':
        try {
            $stmt = $conn->prepare("SELECT id FROM client_intake");
            $stmt->execute();
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $created_count = 0;
            $existing_count = 0;
            
            foreach($clients as $client) {
                $client_folder = "client_" . $client['id'];
                $documents_folder = $client_folder . "/signed_documents";
                
                if(!is_dir($client_folder)) {
                    mkdir($client_folder, 0755, true);
                    mkdir($documents_folder, 0755, true);
                    
                    // Create README file
                    $readme_content = "Client Folder for Client ID: " . $client['id'] . "\n";
                    $readme_content .= "Created: " . date('Y-m-d H:i:s') . "\n";
                    $readme_content .= "Created by: " . $_SESSION['username'] . " (Bulk Operation)\n\n";
                    $readme_content .= "This folder contains:\n";
                    $readme_content .= "- signed_documents/ : Contains all signed documents and files for this client\n";
                    
                    file_put_contents($client_folder . "/README.txt", $readme_content);
                    $created_count++;
                } else {
                    $existing_count++;
                }
            }
            
            $results['success'] = true;
            $results['message'] = "Created {$created_count} new folders. {$existing_count} folders already existed.";
            
        } catch(Exception $e) {
            $results['success'] = false;
            $results['message'] = "Error creating folders: " . $e->getMessage();
        }
        break;
        
    default:
        $results['success'] = false;
        $results['message'] = "Unknown action.";
        break;
}

// Redirect back with results
$status = $results['success'] ? 'success' : 'error';
$message = urlencode($results['message']);
header("Location: bulk_operations.php?{$status}={$message}");
exit();
?>