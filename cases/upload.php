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
    $file_size = $_FILES["file"]["size"];
    $path = $uploadDir . "/" . $file;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    // Updated file size limit and allowed extensions
    $max_file_size = 20 * 1024 * 1024; // 20MB    $allowed = array("jpg", "jpeg", "png", "gif", "pdf", "doc", "docx", "txt", "rtf", "wmv", "mp4", "avi");

    // Check file size
    if($file_size > $max_file_size) {
        header("Location: view.php?id=" . $_POST['id'] . "&error=size");
        exit();
    }

    // Check file extension
    if(in_array($ext, $allowed)) {
        // Generate unique filename if file already exists
        $file_info = pathinfo($file);
        $filename = $file_info['filename'];
        $extension = $file_info['extension'];
        $counter = 1;
        $final_filename = $file;
        
        while(file_exists($uploadDir . "/" . $final_filename)) {
            $final_filename = $filename . "_" . $counter . "." . $extension;
            $counter++;
        }
        
        $final_path = $uploadDir . "/" . $final_filename;
        
        if(move_uploaded_file($tmp_name, $final_path)) {
            header("Location: view.php?id=" . $_POST['id'] . "&success=upload");
        } else {
            header("Location: view.php?id=" . $_POST['id'] . "&error=upload");
        }
        exit();
    } else {
        header("Location: view.php?id=" . $_POST['id'] . "&error=type");
        exit();
    }
}

header("Location: view.php?id=" . $_POST['id']);
exit();
?>