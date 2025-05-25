<?php
session_start();
$menu = "CASES";
include("../include/database.php");

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST and GET data
    error_log("POST data: " . print_r($_POST, true));
    error_log("GET data: " . print_r($_GET, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Try multiple ways to get the case ID
    $case_id = null;
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $case_id = $_POST['id'];
    } elseif (isset($_POST['case_id']) && !empty($_POST['case_id'])) {
        $case_id = $_POST['case_id'];
    } elseif (isset($_GET['id']) && !empty($_GET['id'])) {
        $case_id = $_GET['id'];
    } elseif (isset($_POST['caseids']) && !empty($_POST['caseids'])) {
        // Try to extract ID from case identifier
        $case_id = $_POST['caseids'];
    }
    
    if (!$case_id) {
        error_log("No case ID found in request");
        // Redirect back to cases list with error
        header("Location: index.php?error=no_case_id");
        exit();
    }
    
    // Validate that we have a file
    if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
        $upload_error = $_FILES["file"]["error"] ?? "No file uploaded";
        error_log("File upload error: " . $upload_error);
        
        // Handle specific upload errors
        $error_message = "";
        switch($_FILES["file"]["error"]) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "file_too_large_ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "file_too_large_form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "partial_upload";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "no_file";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "no_tmp_dir";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "cant_write";
                break;
            default:
                $error_message = "upload_failed";
        }
        
        // Try to redirect back to the case view if we have an ID
        if (is_numeric($case_id)) {
            header("Location: view.php?id=" . $case_id . "&error=" . $error_message);
        } else {
            header("Location: index.php?error=" . $error_message);
        }
        exit();
    }
    
    // Check if file is empty
    if (empty($_FILES["file"]["name"])) {
        if (is_numeric($case_id)) {
            header("Location: view.php?id=" . $case_id . "&error=empty_file");
        } else {
            header("Location: index.php?error=empty_file");
        }
        exit();
    }
    
    // If case_id is not numeric, we might need to look it up in the database
    if (!is_numeric($case_id)) {
        // Try to find the case by caseid field
        $stmt = $conn->prepare("SELECT id FROM cases WHERE caseid = ? OR type = ?");
        $stmt->execute([$case_id, $case_id]);
        $case_row = $stmt->fetch();
        
        if ($case_row) {
            $numeric_case_id = $case_row['id'];
        } else {
            error_log("Could not find case with identifier: " . $case_id);
            header("Location: index.php?error=case_not_found");
            exit();
        }
    } else {
        $numeric_case_id = $case_id;
    }
    
    // Get case identifier for folder name
    $case_identifier = isset($_POST['caseids']) ? $_POST['caseids'] : 'case_' . $case_id;
    $case = preg_replace('/[^a-zA-Z0-9-_]/', '', $case_identifier);
    $uploadDir = "uploads/" . $case;
    
    // Create directories if needed
    if (!is_dir("uploads")) {
        if (!mkdir("uploads", 0755, true)) {
            error_log("Failed to create uploads directory");
            header("Location: view.php?id=" . $numeric_case_id . "&error=directory");
            exit();
        }
    }
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create case directory: " . $uploadDir);
            header("Location: view.php?id=" . $numeric_case_id . "&error=directory");
            exit();
        }
    }
    
    $file = basename($_FILES["file"]["name"]);
    $tmp_name = $_FILES["file"]["tmp_name"];
    $file_size = $_FILES["file"]["size"];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    // Increased file size limit to 200MB and expanded allowed extensions
    $max_file_size = 200 * 1024 * 1024; // 200MB
    $allowed = array(
        // Images
        "jpg", "jpeg", "png", "gif", "bmp", "tiff", "webp",
        // Documents
        "pdf", "doc", "docx", "txt", "rtf", "odt", "xls", "xlsx", "ppt", "pptx",
        // Videos
        "wmv", "mp4", "avi", "mov", "mkv", "flv", "webm", "m4v", "3gp",
        // Audio
        "mp3", "wav", "flac", "aac", "ogg", "wma",
        // Archives
        "zip", "rar", "7z", "tar", "gz"
    );

    // Check file size
    if ($file_size > $max_file_size) {
        error_log("File too large: " . $file_size . " bytes (max: " . $max_file_size . ")");
        $size_mb = round($file_size / 1024 / 1024, 2);
        header("Location: view.php?id=" . $numeric_case_id . "&error=size&size=" . $size_mb . "&max=200");
        exit();
    }

    // Check file extension
    if (!in_array($ext, $allowed)) {
        error_log("File type not allowed: " . $ext);
        header("Location: view.php?id=" . $numeric_case_id . "&error=type&ext=" . $ext);
        exit();
    }

    // Generate unique filename if file already exists
    $file_info = pathinfo($file);
    $filename = $file_info['filename'];
    $extension = $file_info['extension'];
    $counter = 1;
    $final_filename = $file;
    
    while (file_exists($uploadDir . "/" . $final_filename)) {
        $final_filename = $filename . "_" . $counter . "." . $extension;
        $counter++;
    }
    
    $final_path = $uploadDir . "/" . $final_filename;
    
    // Attempt to move the uploaded file
    if (move_uploaded_file($tmp_name, $final_path)) {
        error_log("File uploaded successfully: " . $final_path . " (Size: " . round($file_size/1024/1024, 2) . "MB)");
        header("Location: view.php?id=" . $numeric_case_id . "&success=upload&file=" . urlencode($final_filename));
    } else {
        error_log("Failed to move uploaded file from " . $tmp_name . " to " . $final_path);
        header("Location: view.php?id=" . $numeric_case_id . "&error=move_failed");
    }
    exit();
}

// If not a POST request, redirect to cases index
header("Location: index.php");
exit();
?>