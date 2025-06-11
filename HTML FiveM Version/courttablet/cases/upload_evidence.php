<?php
require_once '../include/database.php';
require_once '../auth/character_auth.php';

// Get character name from POST data
$characterName = $_POST['character_name'] ?? '';
$caseId = filter_var($_POST['case_id'], FILTER_SANITIZE_NUMBER_INT);

// Validate character access
$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=auth");
    exit();
}

$auth = validateCharacterAccess($characterName);
if (!$auth['valid']) {
    header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=auth");
    exit();
}

// Check if case exists
$stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
$stmt->execute([$caseId]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    header("Location: index.php?character_name=" . urlencode($characterName) . "&error=case_not_found");
    exit();
}

// Handle file upload - using correct field name from your form
if (!isset($_FILES['evidence_file']) || $_FILES['evidence_file']['error'] === UPLOAD_ERR_NO_FILE) {
    header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=no_file");
    exit();
}

$file = $_FILES['evidence_file'];
$uploadError = $file['error'];

// Check for upload errors
switch ($uploadError) {
    case UPLOAD_ERR_OK:
        break;
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
        header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=size");
        exit();
    case UPLOAD_ERR_PARTIAL:
        header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=partial");
        exit();
    case UPLOAD_ERR_NO_TMP_DIR:
        header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=no_tmp");
        exit();
    case UPLOAD_ERR_CANT_WRITE:
        header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=cant_write");
        exit();
    default:
        header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=unknown");
        exit();
}

// Validate file size (200MB max)
$maxFileSize = 200 * 1024 * 1024; // 200MB in bytes
if ($file['size'] > $maxFileSize) {
    header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=size");
    exit();
}

// Validate file type
$allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp', 'txt', 'rtf', 'odt', 'xls', 'xlsx', 'ppt', 'pptx', 'wmv', 'mp4', 'avi', 'mov', 'mkv', 'flv', 'webm', 'm4v', '3gp', 'mp3', 'wav', 'flac', 'aac', 'ogg', 'wma', 'zip', 'rar', '7z', 'tar', 'gz'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=type");
    exit();
}

// Create evidence directory using the case number from your view.php structure
$evidenceDir = 'uploads/' . $case['caseid'];
if (!is_dir($evidenceDir)) {
    if (!mkdir($evidenceDir, 0755, true)) {
        header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=cant_write");
        exit();
    }
}

// Generate unique filename to prevent conflicts
$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$timestamp = date('Y-m-d_H-i-s');
$uniqueFilename = $originalName . '_' . $timestamp . '.' . $extension;
$uploadPath = $evidenceDir . '/' . $uniqueFilename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_error=1&reason=cant_write");
    exit();
}

// Remove the database logging part since it's causing the error
// The file system storage is sufficient for now

// Success - redirect back to case view
header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&upload_success=1&file=" . urlencode($uniqueFilename));
exit();
?>
