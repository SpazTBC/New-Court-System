<?php
require_once '../../include/database.php';
require_once '../../auth/character_auth.php';

// Get character name
$characterName = $_GET['charactername'] ?? '';

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

$client_id = filter_var($_GET['client_id'], FILTER_SANITIZE_NUMBER_INT);
$filename = $_GET['file'] ?? '';

if (empty($filename) || empty($client_id)) {
    header("Location: profile.php?id=" . $client_id . "&charactername=" . urlencode($characterName) . "&error=invalid_request");
    exit();
}

$client_folder = 'client_' . $client_id;
$documents_folder = $client_folder . '/signed_documents';
$file_path = $documents_folder . '/' . $filename;

// Security check - ensure file is within the expected directory
$real_documents_path = realpath($documents_folder);
$real_file_path = realpath($file_path);

if (!$real_file_path || !$real_documents_path || strpos($real_file_path, $real_documents_path) !== 0) {
    header("Location: profile.php?id=" . $client_id . "&charactername=" . urlencode($characterName) . "&error=invalid_file");
    exit();
}

if (!file_exists($file_path)) {
    header("Location: profile.php?id=" . $client_id . "&charactername=" . urlencode($characterName) . "&error=file_not_found");
    exit();
}

// Extract original filename from the stored filename
$parts = explode('_', pathinfo($filename, PATHINFO_FILENAME));
$extension = pathinfo($filename, PATHINFO_EXTENSION);

// Get original filename (last part before extension)
if (count($parts) >= 4) {
    $original_filename = end($parts) . '.' . $extension;
} else {
    $original_filename = $filename;
}

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $original_filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file
readfile($file_path);
exit();
?>