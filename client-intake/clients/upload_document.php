<?php
session_start();

// Set up file logging in the same directory
$log_file = __DIR__ . '/upload_debug.log';

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    writeLog("ERROR: User not logged in");
    header("Location: /login/index.php");
    exit();
}

// Add comprehensive debugging
writeLog("=== UPLOAD ATTEMPT START ===");
writeLog("REQUEST_METHOD: " . $_SERVER["REQUEST_METHOD"]);
writeLog("Content-Length: " . ($_SERVER['CONTENT_LENGTH'] ?? 'not set'));
writeLog("POST data size: " . strlen(serialize($_POST)));
writeLog("POST data: " . print_r($_POST, true));
writeLog("FILES data: " . print_r($_FILES, true));
writeLog("Current working directory: " . getcwd());
writeLog("Script directory: " . __DIR__);

// Check if this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    writeLog("ERROR: Not a POST request");
    header("Location: ../view_intakes.php?error=not_post");
    exit();
}

// Check if POST data was received (this fails when upload limits are exceeded)
if (empty($_POST) && empty($_FILES)) {
    writeLog("ERROR: No POST or FILES data - likely exceeded upload limits");
    writeLog("Server upload_max_filesize: " . ini_get('upload_max_filesize'));
    writeLog("Server post_max_size: " . ini_get('post_max_size'));
    writeLog("Server memory_limit: " . ini_get('memory_limit'));
    writeLog("Server max_execution_time: " . ini_get('max_execution_time'));
    
    // Try to get client_id from URL if available
    $client_id = isset($_GET['client_id']) ? $_GET['client_id'] : 'unknown';
    header("Location: profile.php?id=" . $client_id . "&upload_error=2&reason=limits_exceeded");
    exit();
}

// Check if we have the client_id
if (!isset($_POST['client_id']) || empty($_POST['client_id'])) {
    writeLog("ERROR: No client_id in POST data");
    writeLog("Available POST keys: " . implode(', ', array_keys($_POST)));
    
    // Try to extract from referrer
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    writeLog("Referrer: " . $referrer);
    if (preg_match('/id=(\d+)/', $referrer, $matches)) {
        $client_id = $matches[1];
        writeLog("Extracted client_id from referrer: " . $client_id);
    } else {
        writeLog("Could not extract client_id from referrer, redirecting to view_intakes");
        header("Location: ../view_intakes.php?error=no_client_id");
        exit();
    }
} else {
    $client_id = $_POST['client_id'];
}

// Validate client ID
if(!is_numeric($client_id)) {
    writeLog("ERROR: Invalid client_id: " . $client_id);
    header("Location: profile.php?id=" . $client_id . "&upload_error=1&reason=invalid_id");
    exit();
}

writeLog("Processing upload for client_id: " . $client_id);

// Check if FILES array exists and has our document
if (!isset($_FILES["document"])) {
    writeLog("ERROR: No document in FILES array");
    writeLog("Available FILES keys: " . implode(', ', array_keys($_FILES)));
    header("Location: profile.php?id=" . $client_id . "&upload_error=5&reason=no_files");
    exit();
}

$file_error = $_FILES["document"]["error"];
writeLog("File upload error code: " . $file_error);

// Check for upload errors first
if ($file_error !== UPLOAD_ERR_OK) {
    writeLog("ERROR: File upload error - code " . $file_error);
    
    switch($file_error) {
        case UPLOAD_ERR_INI_SIZE:
            writeLog("ERROR: File exceeds upload_max_filesize (" . ini_get('upload_max_filesize') . ")");
            header("Location: profile.php?id=" . $client_id . "&upload_error=2&reason=ini_size&limit=" . ini_get('upload_max_filesize'));
            break;
        case UPLOAD_ERR_FORM_SIZE:
            writeLog("ERROR: File exceeds MAX_FILE_SIZE");
            header("Location: profile.php?id=" . $client_id . "&upload_error=2&reason=form_size");
            break;
        case UPLOAD_ERR_PARTIAL:
            writeLog("ERROR: File was only partially uploaded");
            header("Location: profile.php?id=" . $client_id . "&upload_error=4&reason=partial");
            break;
        case UPLOAD_ERR_NO_FILE:
            writeLog("ERROR: No file was uploaded");
            header("Location: profile.php?id=" . $client_id . "&upload_error=5&reason=no_file");
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            writeLog("ERROR: Missing temporary folder");
            header("Location: profile.php?id=" . $client_id . "&upload_error=1&reason=no_tmp");
            break;
        case UPLOAD_ERR_CANT_WRITE:
            writeLog("ERROR: Failed to write file to disk");
            header("Location: profile.php?id=" . $client_id . "&upload_error=1&reason=cant_write");
            break;
        case UPLOAD_ERR_EXTENSION:
            writeLog("ERROR: File upload stopped by extension");
            header("Location: profile.php?id=" . $client_id . "&upload_error=3&reason=extension");
            break;
        default:
            writeLog("ERROR: Unknown upload error");
            header("Location: profile.php?id=" . $client_id . "&upload_error=1&reason=unknown");
    }
    exit();
}

// Check if file name is empty
if(empty($_FILES["document"]["name"])) {
    writeLog("ERROR: Empty filename");
    header("Location: profile.php?id=" . $client_id . "&upload_error=5&reason=empty_name");
    exit();
}

// File upload settings - 200MB
$max_file_size = 200 * 1024 * 1024; // 200MB in bytes
$allowed_extensions = array(
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

$file = basename($_FILES["document"]["name"]);
$tmp_name = $_FILES["document"]["tmp_name"];
$file_size = $_FILES["document"]["size"];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

writeLog("File details:");
writeLog("- Name: " . $file);
writeLog("- Size: " . $file_size . " bytes (" . round($file_size/1024/1024, 2) . " MB)");
writeLog("- Extension: " . $ext);
writeLog("- Temp file: " . $tmp_name);
writeLog("- Temp file exists: " . (file_exists($tmp_name) ? 'YES' : 'NO'));
if (file_exists($tmp_name)) {
    writeLog("- Temp file size: " . filesize($tmp_name) . " bytes");
}

// Validate file size
if($file_size > $max_file_size) {
    writeLog("ERROR: File too large - " . $file_size . " > " . $max_file_size);
    header("Location: profile.php?id=" . $client_id . "&upload_error=2&size=" . round($file_size/1024/1024, 2));
    exit();
}

// Validate file extension
if(!in_array($ext, $allowed_extensions)) {
    writeLog("ERROR: File type not allowed - " . $ext);
    header("Location: profile.php?id=" . $client_id . "&upload_error=3&ext=" . $ext);
    exit();
}

// Create client directory structure
$client_folder = "client_" . $client_id;
$upload_dir = $client_folder . "/signed_documents";

writeLog("Creating directories:");
writeLog("- Client folder: " . $client_folder);
writeLog("- Upload dir: " . $upload_dir);
writeLog("- Full path will be: " . realpath('.') . '/' . $upload_dir);

// Create directories if needed
if (!is_dir($client_folder)) {
    if (!mkdir($client_folder, 0755, true)) {
        writeLog("ERROR: Failed to create client folder");
        writeLog("- Current directory: " . getcwd());
        writeLog("- Directory permissions: " . (is_writable('.') ? 'writable' : 'not writable'));
        header("Location: profile.php?id=" . $client_id . "&upload_error=1&reason=mkdir_client");
        exit();
    }
    writeLog("Created client folder successfully");
}

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        writeLog("ERROR: Failed to create upload directory");
        writeLog("- Parent directory exists: " . (is_dir($client_folder) ? 'YES' : 'NO'));
        writeLog("- Parent directory writable: " . (is_writable($client_folder) ? 'YES' : 'NO'));
        header("Location: profile.php?id=" . $client_id . "&upload_error=1&reason=mkdir_upload");
        exit();
    }
    writeLog("Created upload directory successfully");
}

$path = $upload_dir . "/" . $file;

// Check if file already exists and rename if necessary
$counter = 1;
$original_path = $path;
while(file_exists($path)) {
    $file_info = pathinfo($original_path);
    $path = $file_info['dirname'] . '/' . $file_info['filename'] . '_' . $counter . '.' . $file_info['extension'];
    $counter++;
    writeLog("File exists, trying new name: " . $path);
}

writeLog("Final upload path: " . $path);
writeLog("Destination directory writable: " . (is_writable(dirname($path)) ? 'YES' : 'NO'));

// Attempt to move the uploaded file
if(move_uploaded_file($tmp_name, $path)) {
    writeLog("SUCCESS: File uploaded successfully to " . $path);
    writeLog("Final file size: " . filesize($path) . " bytes");
    $final_filename = basename($path);
    writeLog("Redirecting to success page with file: " . $final_filename);
    header("Location: profile.php?id=" . $client_id . "&upload_success=1&file=" . urlencode($final_filename));
} else {
    writeLog("ERROR: Failed to move uploaded file from " . $tmp_name . " to " . $path);
    writeLog("- Temp file exists: " . (file_exists($tmp_name) ? 'YES' : 'NO'));
    writeLog("- Destination dir exists: " . (is_dir(dirname($path)) ? 'YES' : 'NO'));
    writeLog("- Destination dir writable: " . (is_writable(dirname($path)) ? 'YES' : 'NO'));
    writeLog("- Full destination path: " . realpath(dirname($path)));
    
    // Try to get more specific error information
    $error = error_get_last();
    if ($error) {
        writeLog("Last PHP error: " . print_r($error, true));
    }
    
    header("Location: profile.php?id=" . $client_id . "&upload_error=1&reason=move_failed");
}

writeLog("=== UPLOAD ATTEMPT END ===");
exit();
?>
