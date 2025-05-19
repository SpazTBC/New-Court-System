<?php
session_start();
$menu = "CASES";
include("../include/database.php");

// Handle file upload
if(isset($_FILES["file"]) && isset($_POST['caseids'])) {
    // Check if file is empty
    if(empty($_FILES["file"]["name"])) {
        header("Location: view.php?id=" . $_POST['id']);
        exit();
    }
    
    $case = preg_replace('/[^a-zA-Z0-9-]/', '', $_POST['caseids']);
    $uploadDir = "uploads/" . $case;
    
    // Create directories if needed
    if (!is_dir("uploads")) mkdir("uploads", 0755);
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755);
    
    $file = basename($_FILES["file"]["name"]);
    $tmp_name = $_FILES["file"]["tmp_name"];
    $path = $uploadDir . "/" . $file;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $allowed = array("jpg", "png", "gif", "pdf", "wmv");

    if(in_array($ext, $allowed)) {
        move_uploaded_file($tmp_name, $path);
        header("Location: view.php?id=" . $_POST['id']);
        exit();
    }
}

header("Location: view.php?id=" . $_POST['id']);
exit();
?>