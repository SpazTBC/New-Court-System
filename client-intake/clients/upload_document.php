<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["document"]) && isset($_POST['client_id'])) {
    $client_id = $_POST['client_id'];
    
    // Validate client ID
    if(!is_numeric($client_id)) {
        header("Location: profile.php?id=" . $client_id . "&upload_error=1");
        exit();
    }
    
    // Database connection to verify client exists
    require_once "../../include/database.php";
    
    try {
        $stmt = $conn->prepare("SELECT id FROM client_intake WHERE id = :id");
        $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            header("Location: ../view_intakes.php");
            exit();
        }
    } catch(PDOException $e) {
        header("Location: profile.php?id=" . $client_id . "&upload_error=1");
        exit();
    }
    
    // Check if file is empty
    if(empty($_FILES["document"]["name"])) {
        header("Location: profile.php?id=" . $client_id . "&upload_error=1");
        exit();
    }
    
    // File upload settings
    $max_file_size = 20 * 1024 * 1024; // 20MB in bytes
    $allowed_extensions = array("pdf", "doc", "docx", "jpg", "jpeg", "png", "gif", "txt", "rtf");
    
    $file = basename($_FILES["document"]["name"]);
    $tmp_name = $_FILES["document"]["tmp_name"];
    $file_size = $_FILES["document"]["size"];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    // Create client directory structure if it doesn't exist
    $client_folder = "client_" . $client_id;
    $upload_dir = $client_folder . "/signed_documents";
    
    if (!is_dir($client_folder)) {
        mkdir($client_folder, 0755, true);
    }
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validate file size
    if($file_size > $max_file_size) {
        header("Location: profile.php?id=" . $client_id . "&upload_error=2");
        exit();
    }
    
    // Validate file extension
    if(!in_array($ext, $allowed_extensions)) {
        header("Location: profile.php?id=" . $client_id . "&upload_error=3");
        exit();
    }
    
    // Generate unique filename to prevent conflicts
    $file_info = pathinfo($file);
    $filename = $file_info['filename'];
    $extension = $file_info['extension'];
    $counter = 1;
    $final_filename = $file;
    
    while(file_exists($upload_dir . "/" . $final_filename)) {
        $final_filename = $filename . "_" . $counter . "." . $extension;
        $counter++;
    }
    
    $upload_path = $upload_dir . "/" . $final_filename;
    
    // Move uploaded file
    if(move_uploaded_file($tmp_name, $upload_path)) {
        // Log the upload to client folder
        $log_entry = date('Y-m-d H:i:s') . " - Document uploaded: " . $final_filename . " (" . round($file_size/1024/1024, 2) . " MB) by " . $_SESSION['username'] . "\n";
        file_put_contents($client_folder . "/upload_log.txt", $log_entry, FILE_APPEND | LOCK_EX);
        
        header("Location: profile.php?id=" . $client_id . "&upload_success=1");
    } else {
        header("Location: profile.php?id=" . $client_id . "&upload_error=4");
    }
} else {
    // Invalid request
    header("Location: ../view_intakes.php");
}
exit();
?>