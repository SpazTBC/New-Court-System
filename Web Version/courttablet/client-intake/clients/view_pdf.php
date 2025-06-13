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
    http_response_code(404);
    exit('File not found');
}

$client_folder = 'client_' . $client_id;
$documents_folder = $client_folder . '/signed_documents';
$file_path = $documents_folder . '/' . $filename;

// Security check
$real_documents_path = realpath($documents_folder);
$real_file_path = realpath($file_path);

if (!$real_file_path || !$real_documents_path || strpos($real_file_path, $real_documents_path) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

if (!file_exists($file_path)) {
    http_response_code(404);
    exit('File not found');
}

// Check if it's a PDF
$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if ($file_extension !== 'pdf') {
    http_response_code(400);
    exit('Not a PDF file');
}

// Set headers for PDF viewing
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($file_path));
header('Accept-Ranges: bytes');
header('Cache-Control: private, no-transform, no-store, must-revalidate');
header('Pragma: no-cache');

// Output the PDF
readfile($file_path);
exit();
?>