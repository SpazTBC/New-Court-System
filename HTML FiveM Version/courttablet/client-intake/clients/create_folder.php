<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Check if client ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$client_id = $_GET['id'];

// Database connection
require_once "../../include/database.php";

// Verify client exists
try {
    $stmt = $conn->prepare("SELECT id FROM client_intake WHERE id = :id");
    $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: index.php");
        exit();
    }
} catch(PDOException $e) {
    header("Location: index.php");
    exit();
}

// Create client directory structure
$client_folder = "client_" . $client_id;
$documents_folder = $client_folder . "/signed_documents";

try {
    // Create main client folder
    if (!is_dir($client_folder)) {
        mkdir($client_folder, 0755, true);
    }
    
    // Create signed documents subfolder
    if (!is_dir($documents_folder)) {
        mkdir($documents_folder, 0755, true);
    }
    
    // Create a readme file
    $readme_content = "Client Folder for Client ID: " . $client_id . "\n";
    $readme_content .= "Created: " . date('Y-m-d H:i:s') . "\n";
    $readme_content .= "Created by: " . $_SESSION['username'] . "\n\n";
    $readme_content .= "This folder contains:\n";
    $readme_content .= "- signed_documents/ : Contains all signed documents and files for this client\n";
    
    file_put_contents($client_folder . "/README.txt", $readme_content);
    
    header("Location: profile.php?id=" . $client_id . "&folder_created=1");
} catch(Exception $e) {
    header("Location: index.php?error=folder_creation_failed");
}
exit();
?>