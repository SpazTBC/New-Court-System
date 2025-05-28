<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Check if parameters are provided
if(!isset($_GET['client_id']) || !isset($_GET['file']) || !is_numeric($_GET['client_id'])) {
    header("Location: ../view_intakes.php");
    exit();
}

$client_id = $_GET['client_id'];
$filename = $_GET['file'];

// Sanitize filename to prevent directory traversal
$filename = basename($filename);

$client_folder = "client_" . $client_id;
$file_path = $client_folder . "/signed_documents/" . $filename;

// Check if file exists and delete it
if(file_exists($file_path)) {
    if(unlink($file_path)) {
        header("Location: profile.php?id=" . $client_id . "&delete_success=1");
    } else {
        header("Location: profile.php?id=" . $client_id . "&delete_error=1");
    }
} else {
    header("Location: profile.php?id=" . $client_id . "&delete_error=2");
}
exit();
?>