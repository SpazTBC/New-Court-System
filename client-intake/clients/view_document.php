<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("HTTP/1.0 403 Forbidden");
    exit("Access denied");
}

// Check if parameters are provided
if(!isset($_GET['client_id']) || !isset($_GET['file']) || !is_numeric($_GET['client_id'])) {
    header("HTTP/1.0 404 Not Found");
    exit("File not found");
}

$client_id = $_GET['client_id'];
$filename = basename($_GET['file']); // Sanitize filename

// Database connection to verify client exists
require_once "../../include/database.php";

try {
    $stmt = $conn->prepare("SELECT id FROM client_intake WHERE id = :id");
    $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("HTTP/1.0 404 Not Found");
        exit("Client not found");
    }
} catch(PDOException $e) {
    header("HTTP/1.0 500 Internal Server Error");
    exit("Database error");
}

$client_folder = "client_" . $client_id;
$file_path = $client_folder . "/signed_documents/" . $filename;

// Check if file exists
if(!file_exists($file_path)) {
    header("HTTP/1.0 404 Not Found");
    exit("File not found");
}

// Get file info
$file_info = pathinfo($file_path);
$file_extension = strtolower($file_info['extension']);

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
];

$content_type = isset($content_types[$file_extension]) ? $content_types[$file_extension] : 'application/octet-stream';

// Set headers
header('Content-Type: ' . $content_type);
header('Content-Length: ' . filesize($file_path));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: private, max-age=3600');

// Output file
readfile($file_path);
exit();
?>