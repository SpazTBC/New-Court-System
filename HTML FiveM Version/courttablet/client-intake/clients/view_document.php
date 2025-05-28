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

// Security check
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

// Get client details
$stmt = $conn->prepare("SELECT * FROM client_intake WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header("Location: ../index.php?charactername=" . urlencode($characterName) . "&error=client_not_found");
    exit();
}

$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$file_info = pathinfo($filename);

// Extract document info from filename
$parts = explode('_', $file_info['filename']);
$document_type = 'Other';
$description = '';
$original_filename = $filename;

if (count($parts) >= 3) {
    $document_type = ucfirst($parts[0]);
    if (count($parts) >= 4) {
        $description = str_replace('-', ' ', $parts[1]);
        $original_filename = implode('_', array_slice($parts, 3)) . '.' . $file_extension;
    } else {
        $original_filename = implode('_', array_slice($parts, 2)) . '.' . $file_extension;
    }
}

// Determine file type for viewing
$is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp']);
$is_pdf = $file_extension === 'pdf';
$is_text = in_array($file_extension, ['txt', 'log']);

$characterDisplayName = $currentCharacter['charactername'];
$characterJob = $currentCharacter['job'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        .document-viewer {
            max-width: 100%;
            max-height: 80vh;
            overflow: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
        }
        .document-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .document-text {
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            background: white;
            padding: 20px;
            border-radius: 4px;
            max-height: 60vh;
            overflow-y: auto;
        }
        .pdf-viewer {
            width: 100%;
            height: 80vh;
            border: none;
            border-radius: 8px;
            background: #fff;
        }
        .pdf-fallback {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }
        .pdf-error {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../../">
                <i class='bx bx-building'></i> Court System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class='bx bx-user'></i>
                    <span class="ms-2"><?php echo htmlspecialchars($characterDisplayName); ?> (<?php echo htmlspecialchars($characterJob); ?>)</span>
                </span>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class='bx bx-file'></i> Document Viewer: <?php echo htmlspecialchars($original_filename); ?>
                        </h5>
                        <div>
                            <a href="download.php?file=<?php echo urlencode($filename); ?>&client_id=<?php echo $client_id; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                               class="btn btn-light btn-sm me-2">
                                <i class='bx bx-download'></i> Download
                            </a>
                            <a href="profile.php?id=<?php echo $client_id; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                               class="btn btn-secondary btn-sm">
                                <i class='bx bx-arrow-back'></i> Back to Profile
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="document-viewer">
                            <?php if ($is_image): ?>
                                <!-- Image Viewer -->
                                <div class="text-center">
                                    <img src="<?php echo htmlspecialchars($file_path); ?>" 
                                         alt="Document Image" 
                                         class="document-image">
                                </div>
                                
                            <?php elseif ($is_pdf): ?>
                                <!-- PDF Viewer with fallback -->
                                <div id="pdfContainer">
                                    <iframe src="view_pdf.php?file=<?php echo urlencode($filename); ?>&client_id=<?php echo $client_id; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                                            class="pdf-viewer" id="pdfViewer">
                                    </iframe>
                                    
                                    <div class="pdf-fallback" id="pdfFallback" style="display: none;">
                                        <i class='bx bx-file-pdf display-1 text-danger'></i>
                                        <h4 class="mt-3">PDF Viewer Not Available</h4>
                                        <p class="text-muted">
                                            Your browser cannot display this PDF file inline.
                                        </p>
                                        <div class="d-grid gap-2 d-md-block">
                                            <a href="download.php?file=<?php echo urlencode($filename); ?>&client_id=<?php echo $client_id; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                                               class="btn btn-primary">
                                                <i class='bx bx-download'></i> Download PDF
                                            </a>
                                            <a href="view_pdf.php?file=<?php echo urlencode($filename); ?>&client_id=<?php echo $client_id; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                                               class="btn btn-outline-primary" target="_blank">
                                                <i class='bx bx-external-link'></i> Open in New Tab
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php elseif ($is_text): ?>
                                <!-- Text File Viewer -->
                                <div class="document-text">
                                    <?php echo htmlspecialchars(file_get_contents($file_path)); ?>
                                </div>
                                
                            <?php else: ?>
                                <!-- Unsupported File Type -->
                                <div class="text-center py-5">
                                    <i class='bx bx-file display-1 text-muted'></i>
                                    <h4 class="mt-3">File Preview Not Available</h4>
                                    <p class="text-muted">
                                        This file type (<?php echo strtoupper($file_extension); ?>) cannot be previewed in the browser.
                                    </p>
                                    <a href="download.php?file=<?php echo urlencode($filename); ?>&client_id=<?php echo $client_id; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                                       class="btn btn-primary">
                                        <i class='bx bx-download'></i> Download File
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- File Information -->
                        <div class="mt-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">File Information</h6>
                                            <p class="card-text mb-1">
                                                <strong>File Name:</strong> <?php echo htmlspecialchars($original_filename); ?>
                                            </p>
                                            <p class="card-text mb-1">
                                                <strong>Document Type:</strong> <?php echo htmlspecialchars($document_type); ?>
                                            </p>
                                            <?php if (!empty($description)): ?>
                                            <p class="card-text mb-1">
                                                <strong>Description:</strong> <?php echo htmlspecialchars($description); ?>
                                            </p>
                                            <?php endif; ?>
                                            <p class="card-text mb-1">
                                                <strong>File Type:</strong> <?php echo strtoupper($file_extension); ?>
                                            </p>
                                            <p class="card-text mb-0">
                                                <strong>File Size:</strong> <?php echo number_format(filesize($file_path) / 1024, 2); ?> KB
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Client Information</h6>
                                            <p class="card-text mb-1">
                                                <strong>Client:</strong> <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                            </p>
                                            <p class="card-text mb-1">
                                                <strong>Client ID:</strong> <?php echo htmlspecialchars($client_id); ?>
                                            </p>
                                            <p class="card-text mb-0">
                                                <strong>Storage Path:</strong> <code><?php echo htmlspecialchars($file_path); ?></code>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // PDF viewer fallback handling
        <?php if ($is_pdf): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const pdfViewer = document.getElementById('pdfViewer');
            const pdfFallback = document.getElementById('pdfFallback');
            
            // Check if PDF loaded successfully after a delay
            setTimeout(function() {
                try {
                    // Try to access the iframe content
                    if (pdfViewer.contentDocument === null) {
                        // PDF didn't load, show fallback
                        pdfViewer.style.display = 'none';
                        pdfFallback.style.display = 'block';
                    }
                } catch (e) {
                    // Cross-origin or other error, PDF might still be working
                    console.log('PDF viewer check failed, but PDF might still be working');
                }
            }, 3000);
            
            // Handle iframe load errors
            pdfViewer.addEventListener('error', function() {
                pdfViewer.style.display = 'none';
                pdfFallback.style.display = 'block';
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>