<?php
require_once '../../include/database.php';
require_once '../../auth/character_auth.php';

// Get character name
$characterName = $_POST['charactername'] ?? '';

$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    header("Location: ../../?error=not_found");
    exit();
}

// Validate character access
$auth = validateCharacterAccess($characterName);
if (!$auth['valid']) {
    header("Location: ../../?error=no_access");
    exit();
}

$client_id = filter_var($_POST['client_id'], FILTER_SANITIZE_NUMBER_INT);
$document_type = $_POST['document_type'] ?? 'Other';
$description = $_POST['description'] ?? '';

// Create client directory structure
$client_folder = 'client_' . $client_id;
$documents_folder = $client_folder . '/signed_documents';

if (!is_dir($client_folder)) {
    mkdir($client_folder, 0755, true);
}
if (!is_dir($documents_folder)) {
    mkdir($documents_folder, 0755, true);
}

// Check if file was uploaded
if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $error_code = $_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE;
    
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $reason = "size";
            break;
        case UPLOAD_ERR_PARTIAL:
            $reason = "partial";
            break;
        case UPLOAD_ERR_NO_FILE:
            $reason = "no_file";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $reason = "no_tmp";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $reason = "cant_write";
            break;
        default:
            $reason = "unknown";
    }
    
    header("Location: profile.php?id=" . $client_id . "&charactername=" . urlencode($characterName) . "&upload_error=1&reason=" . $reason);
    exit();
}

$file = $_FILES['document'];
$original_filename = $file['name'];
$tmp_name = $file['tmp_name'];
$file_size = $file['size'];

// Validate file size (100MB max)
$max_size = 100 * 1024 * 1024; // 100MB
if ($file_size > $max_size) {
    header("Location: profile.php?id=" . $client_id . "&charactername=" . urlencode($characterName) . "&upload_error=1&reason=size");
    exit();
}

// Validate file type
$allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
$file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    header("Location: profile.php?id=" . $client_id . "&charactername=" . urlencode($characterName) . "&upload_error=1&reason=type");
    exit();
}

// Generate filename with metadata
// Format: type_description_timestamp_originalname.ext
$clean_description = !empty($description) ? preg_replace('/[^a-zA-Z0-9\s]/', '', $description) : '';
$clean_description = str_replace(' ', '-', $clean_description);
$timestamp = time();

$filename_parts = [];
$filename_parts[] = strtolower($document_type);
if (!empty($clean_description)) {
    $filename_parts[] = strtolower($clean_description);
}
$filename_parts[] = $timestamp;
$filename_parts[] = pathinfo($original_filename, PATHINFO_FILENAME);

$new_filename = implode('_', $filename_parts) . '.' . $file_extension;
$file_path = $documents_folder . '/' . $new_filename;

// Ensure unique filename
$counter = 1;
while (file_exists($file_path)) {
    $filename_parts_unique = $filename_parts;
    $filename_parts_unique[] = $counter;
    $new_filename = implode('_', $filename_parts_unique) . '.' . $file_extension;
    $file_path = $documents_folder . '/' . $new_filename;
    $counter++;
}

// Move uploaded file
if (move_uploaded_file($tmp_name, $file_path)) {
    header("Location: profile.php?id=" . $client_id . "&charactername=" . urlencode($characterName) . "&upload_success=1");
} else {
    header("Location: profile.php?id=" . $client_id . "&charactername=" . urlencode($characterName) . "&upload_error=1&reason=cant_write");
}
exit();
?>
