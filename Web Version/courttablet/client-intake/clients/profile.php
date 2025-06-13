<?php
require_once '../../include/database.php';
require_once '../../auth/character_auth.php';

$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    header("Location: ../../?error=not_found");
    exit();
}

// Validate character access
$auth = validateCharacterAccess($_GET['charactername']);
if (!$auth['valid']) {
    header("Location: ../../?error=no_access&charactername=" . urlencode($_GET['charactername']));
    exit();
}

$characterName = $currentCharacter['charactername'];
$characterJob = $currentCharacter['job'];

// Get client ID from URL
$clientId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get client details
$stmt = $conn->prepare("SELECT * FROM client_intake WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header("Location: ../index.php?charactername=" . urlencode($characterName) . "&error=client_not_found");
    exit();
}

// Handle file upload success/error messages
$upload_message = '';
if (isset($_GET['upload_success'])) {
    $upload_message = '<div class="alert alert-success alert-dismissible fade show">
        <i class="bx bx-check-circle"></i> File uploaded successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
} elseif (isset($_GET['upload_error'])) {
    $error_code = $_GET['upload_error'];
    $reason = $_GET['reason'] ?? '';
    
    switch ($reason) {
        case 'size':
            $error_message = "File is too large. Maximum file size is 100MB.";
            break;
        case 'type':
            $error_message = "Invalid file type. Please upload PDF, DOC, DOCX, JPG, PNG, or TXT files only.";
            break;
        case 'partial':
            $error_message = "File was only partially uploaded. Please try again.";
            break;
        case 'no_file':
            $error_message = "No file was selected for upload.";
            break;
        case 'no_tmp':
            $error_message = "Missing temporary upload directory. Please contact administrator.";
            break;
        case 'cant_write':
            $error_message = "Failed to write file to disk. Please contact administrator.";
            break;
        case 'extension':
            $error_message = "File upload stopped by extension. Please contact administrator.";
            break;
        default:
            $error_message = "An unknown error occurred during upload.";
    }
    
    $upload_message = '<div class="alert alert-danger alert-dismissible fade show">
        <i class="bx bx-error-circle"></i> ' . $error_message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
} elseif (isset($_GET['delete_success'])) {
    $upload_message = '<div class="alert alert-success alert-dismissible fade show">
        <i class="bx bx-check-circle"></i> Document deleted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
} elseif (isset($_GET['delete_error'])) {
    $upload_message = '<div class="alert alert-danger alert-dismissible fade show">
        <i class="bx bx-error-circle"></i> Failed to delete document. Please try again.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// Get uploaded documents from folder structure
$documents = [];
$client_folder = "client_" . $clientId;
$documents_folder = $client_folder . "/signed_documents";

// Create client folder structure if it doesn't exist
if (!is_dir($client_folder)) {
    mkdir($client_folder, 0755, true);
}
if (!is_dir($documents_folder)) {
    mkdir($documents_folder, 0755, true);
}

// Scan for documents in the folder
if (is_dir($documents_folder)) {
    $files = scandir($documents_folder);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && is_file($documents_folder . '/' . $file)) {
            $file_path = $documents_folder . '/' . $file;
            $file_info = pathinfo($file);
            
            // Extract document type and description from filename if available
            // Format: type_description_originalname.ext or just originalname.ext
            $parts = explode('_', $file_info['filename']);
            $document_type = 'Other';
            $description = '';
            $original_filename = $file;
            
            if (count($parts) >= 3) {
                $document_type = ucfirst($parts[0]);
                $description = str_replace('-', ' ', $parts[1]);
                $original_filename = implode('_', array_slice($parts, 2)) . '.' . $file_info['extension'];
            } elseif (count($parts) == 2) {
                $document_type = ucfirst($parts[0]);
                $original_filename = $parts[1] . '.' . $file_info['extension'];
            }
            
            $documents[] = [
                'filename' => $file,
                'original_filename' => $original_filename,
                'document_type' => $document_type,
                'description' => $description,
                'file_size' => filesize($file_path),
                'upload_date' => date('Y-m-d H:i:s', filemtime($file_path)),
                'file_path' => $file_path
            ];
        }
    }
    
    // Sort by upload date (newest first)
    usort($documents, function($a, $b) {
        return strtotime($b['upload_date']) - strtotime($a['upload_date']);
    });
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Profile - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        .navbar {
            background-color: #0d1117 !important;
            border-bottom: 1px solid #30363d;
        }
        .card {
            background-color: #21262d;
            border: 1px solid #30363d;
            color: #e0e0e0;
        }
        .card-header {
            background-color: #161b22 !important;
            border-bottom: 1px solid #30363d;
            color: #e0e0e0 !important;
        }
        .bg-dark {
            background-color: #161b22 !important;
        }
        .bg-primary {
            background-color: #238636 !important;
        }
        .bg-success {
            background-color: #238636 !important;
        }
        .bg-info {
            background-color: #0969da !important;
        }
        .bg-light {
            background-color: #0d1117 !important;
            border: 1px solid #30363d;
        }
        .form-control, .form-select {
            background-color: #0d1117;
            border: 2px solid #30363d;
            color: #e0e0e0;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0d1117;
            border-color: #58a6ff;
            color: #e0e0e0;
            box-shadow: 0 0 0 0.25rem rgba(88, 166, 255, 0.25);
        }
        .form-control::placeholder {
            color: #8b949e;
        }
        .btn-primary {
            background-color: #238636;
            border-color: #238636;
        }
        .btn-primary:hover {
            background-color: #2ea043;
            border-color: #2ea043;
        }
        .btn-secondary {
            background-color: #21262d;
            border-color: #30363d;
            color: #e0e0e0;
        }
        .btn-secondary:hover {
            background-color: #30363d;
            border-color: #484f58;
            color: #e0e0e0;
        }
        .btn-outline-primary {
            color: #58a6ff;
            border-color: #58a6ff;
        }
        .btn-outline-primary:hover {
            background-color: #58a6ff;
            border-color: #58a6ff;
            color: #0d1117;
        }
        .btn-outline-warning {
            color: #f0883e;
            border-color: #f0883e;
        }
        .btn-outline-warning:hover {
            background-color: #f0883e;
            border-color: #f0883e;
            color: #0d1117;
        }
        .btn-outline-info {
            color: #58a6ff;
            border-color: #58a6ff;
        }
        .btn-outline-info:hover {
            background-color: #58a6ff;
            border-color: #58a6ff;
            color: #0d1117;
        }
        .btn-outline-danger {
            color: #f85149;
            border-color: #f85149;
        }
        .btn-outline-danger:hover {
            background-color: #f85149;
            border-color: #f85149;
            color: #0d1117;
        }
        .btn-danger {
            background-color: #da3633;
            border-color: #da3633;
        }
        .btn-danger:hover {
            background-color: #f85149;
            border-color: #f85149;
        }
        .table-striped > tbody > tr:nth-of-type(odd) > td {
            background-color: #2d333b;
        }
        .table td {
            color: #e0e0e0;
            border-color: #30363d;
        }
        .alert-success {
            background-color: #0f5132;
            border-color: #238636;
            color: #3fb950;
        }
        .alert-danger {
            background-color: #490202;
            border-color: #f85149;
            color: #f85149;
        }
        .alert-info {
            background-color: #0c2d48;
            border-color: #0969da;
            color: #58a6ff;
        }
        .text-muted {
            color: #8b949e !important;
        }
        .display-4 {
            color: #484f58;
        }
        .shadow {
            box-shadow: 0 16px 32px rgba(1, 4, 9, 0.85) !important;
        }
        .upload-progress {
            display: none;
        }
        .large-file-warning {
            background: #2d1b00;
            border: 1px solid #f0883e;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #f0883e;
        }
        .file-info {
            background: #0c2d48;
            border: 1px solid #0969da;
            border-radius: 0.375rem;
            padding: 0.75rem;
            color: #58a6ff;
        }
        .document-icon {
            font-size: 1.2em;
            margin-right: 0.5rem;
        }
        .document-row {
            transition: background-color 0.2s ease;
        }
        .document-row:hover {
            background-color: #30363d;
        }
        .danger-zone {
            border: 2px solid #da3633;
            border-radius: 0.5rem;
            background: #2d0a0a;
        }
        .danger-zone .card-header {
            background: #da3633 !important;
            border-bottom: 1px solid #da3633;
        }
        .modal-content {
            background-color: #21262d;
            border: 1px solid #30363d;
        }
        .modal-header {
            border-bottom: 1px solid #30363d;
        }
        .modal-footer {
            border-top: 1px solid #30363d;
        }
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .badge {
            background-color: #30363d !important;
            color: #e0e0e0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../../">
                <i class='bx bx-building'></i> Court System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class='bx bx-user'></i>
                    <span class="ms-2"><?php echo htmlspecialchars($characterName); ?> (<?php echo htmlspecialchars($characterJob); ?>)</span>
                </span>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <?php echo $upload_message; ?>
        
        <div class="row">
            <div class="col-md-4">
                <!-- Client Info Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class='bx bx-user'></i> Client Information</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title">
                            <?php 
                            $fullName = trim($client['first_name'] . ' ' . ($client['middle_name'] ? $client['middle_name'] . ' ' : '') . $client['last_name']);
                            echo htmlspecialchars($fullName); 
                            ?>
                        </h6>
                        <p class="card-text">
                            <strong>Phone:</strong> <?php echo htmlspecialchars($client['phone']); ?><br>
                            <?php if (!empty($client['email'])): ?>
                                <strong>Email:</strong> <?php echo htmlspecialchars($client['email']); ?><br>
                            <?php endif; ?>
                            <strong>Case Type:</strong> <?php echo htmlspecialchars($client['case_type'] ?? 'N/A'); ?><br>
                            <strong>Intake Date:</strong> <?php echo $client['intake_date'] ? date('M j, Y', strtotime($client['intake_date'])) : 'N/A'; ?>
                        </p>
                        <div class="d-grid gap-2">
                            <a href="../view_details.php?id=<?php echo $client['id']; ?>&charactername=<?php echo urlencode($characterName); ?>" class="btn btn-outline-primary btn-sm">
                                <i class='bx bx-eye'></i> View Full Details
                            </a>
                            <a href="../edit_intake.php?id=<?php echo $client['id']; ?>&charactername=<?php echo urlencode($characterName); ?>" class="btn btn-outline-warning btn-sm">
                                <i class='bx bx-edit'></i> Edit Client
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Folder Info Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class='bx bx-folder'></i> Document Storage</h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text small">
                            <strong>Client Folder:</strong> <?php echo htmlspecialchars($client_folder); ?><br>
                            <strong>Documents:</strong> <?php echo count($documents); ?> file(s)<br>
                            <strong>Storage Path:</strong> <?php echo htmlspecialchars($documents_folder); ?>
                        </p>
                    </div>
                </div>

                <!-- Danger Zone Card -->
                <div class="card shadow mb-4 danger-zone">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class='bx bx-error-circle'></i> Danger Zone</h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text small text-danger">
                            <strong>Warning:</strong> Deleting this client will permanently remove all client information and associated documents. This action cannot be undone.
                        </p>
                        <div class="d-grid">
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteClientModal">
                                <i class='bx bx-trash'></i> Delete Client
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Document Upload Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class='bx bx-upload'></i> Upload Document</h5>
                    </div>
                    <div class="card-body">
                        <form action="upload_document.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                            <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">
                            <input type="hidden" name="charactername" value="<?php echo htmlspecialchars($_GET['charactername']); ?>">
                            
                            <div class="mb-3">
                                <label for="document" class="form-label">Select Document</label>
                                <input type="file" class="form-control" id="document" name="document" 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt" required>
                                <div class="form-text">
                                    Supported formats: PDF, DOC, DOCX, JPG, PNG, TXT (Max: 100MB)
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="document_type" class="form-label">Document Type</label>
                                <select class="form-select" id="document_type" name="document_type" required>
                                    <option value="">Select document type...</option>
                                    <option value="ID">Identification</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Evidence">Evidence</option>
                                    <option value="Medical">Medical Records</option>
                                    <option value="Financial">Financial Documents</option>
                                    <option value="Legal">Legal Documents</option>
                                    <option value="Insurance">Insurance Documents</option>
                                    <option value="Employment">Employment Records</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="2" 
                                          placeholder="Brief description of the document..."></textarea>
                            </div>
                            
                            <!-- File info display -->
                            <div id="fileInfo" style="display: none;" class="mb-3"></div>
                            
                            <!-- Progress bar -->
                            <div class="progress mb-3 upload-progress" id="uploadProgress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%" id="progressBar">
                                    <span id="progressText">0%</span>
                                </div>
                            </div>
                            
                            <div class="alert alert-info" role="alert">
                                <i class='bx bx-info-circle'></i> 
                                <strong>Large File Upload:</strong> For files larger than 50MB, the upload may take several minutes. 
                                Please be patient and do not refresh the page during upload.
                            </div>
                            
                            <div id="largeFileWarning" class="large-file-warning" style="display: none;">
                                <i class='bx bx-error-circle'></i>
                                <strong>Large File Detected:</strong> This file is quite large and may take several minutes to upload. 
                                Please ensure you have a stable internet connection and do not close this page.
                            </div>
                            
                            <button type="submit" class="btn btn-primary" id="uploadBtn">
                                <i class='bx bx-upload'></i> Upload Document
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Documents List Card -->
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class='bx bx-file'></i> Client Documents (<?php echo count($documents); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($documents)): ?>
                            <div class="text-center py-4">
                                <i class='bx bx-file display-4 text-muted'></i>
                                <p class="text-muted mt-2">No documents uploaded yet.</p>
                                <p class="text-muted small">Documents will be stored in: <code><?php echo htmlspecialchars($documents_folder); ?></code></p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Document Name</th>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Upload Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($documents as $doc): ?>
                                            <tr class="document-row">
                                                <td>
                                                    <?php
                                                    $extension = strtolower(pathinfo($doc['filename'], PATHINFO_EXTENSION));
                                                    $icon = 'bx-file';
                                                    switch ($extension) {
                                                        case 'pdf':
                                                            $icon = 'bx-file-pdf';
                                                            break;
                                                        case 'doc':
                                                        case 'docx':
                                                            $icon = 'bx-file-doc';
                                                            break;
                                                        case 'jpg':
                                                        case 'jpeg':
                                                        case 'png':
                                                            $icon = 'bx-image';
                                                            break;
                                                        case 'txt':
                                                            $icon = 'bx-file-txt';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class='bx <?php echo $icon; ?> document-icon'></i>
                                                    <?php echo htmlspecialchars($doc['original_filename']); ?>
                                                    <?php if (!empty($doc['description'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($doc['description']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge"><?php echo htmlspecialchars($doc['document_type']); ?></span>
                                                </td>
                                                <td><?php echo number_format($doc['file_size'] / 1024, 1); ?> KB</td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($doc['upload_date'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="download.php?file=<?php echo urlencode($doc['filename']); ?>&client_id=<?php echo $clientId; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                                                           class="btn btn-outline-primary" title="Download">
                                                            <i class='bx bx-download'></i>
                                                        </a>
                                                        <a href="view_document.php?file=<?php echo urlencode($doc['filename']); ?>&client_id=<?php echo $clientId; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                                                           class="btn btn-outline-info" title="View">
                                                            <i class='bx bx-show'></i>
                                                        </a>
                                                        <a href="delete_document.php?file=<?php echo urlencode($doc['filename']); ?>&client_id=<?php echo $clientId; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                                                           class="btn btn-outline-danger" title="Delete"
                                                           onclick="return confirm('Are you sure you want to delete this document?')">
                                                            <i class='bx bx-trash'></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Quick Preview for Images -->
                            <?php 
                            $image_files = [];
                            foreach ($documents as $index => $doc) {
                                $extension = strtolower(pathinfo($doc['filename'], PATHINFO_EXTENSION));
                                $is_image = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp']);
                                
                                if ($is_image) {
                                    $image_files[] = ['path' => $doc['file_path'], 'index' => $index, 'name' => $doc['original_filename']];
                                }
                            }
                            
                            if (!empty($image_files)): ?>
                                <hr>
                                <h6><i class='bx bx-image'></i> Image Previews</h6>
                                <div class="row">
                                    <?php foreach ($image_files as $image): ?>
                                        <div class="col-md-3 mb-3">
                                            <div class="card">
                                                <img src="<?php echo htmlspecialchars($image['path']); ?>" 
                                                     class="card-img-top" 
                                                     style="height: 150px; object-fit: cover;"
                                                     alt="<?php echo htmlspecialchars($image['name']); ?>">
                                                <div class="card-body p-2">
                                                    <p class="card-text small text-truncate" title="<?php echo htmlspecialchars($image['name']); ?>">
                                                        <?php echo htmlspecialchars($image['name']); ?>
                                                    </p>
                                                    <a href="view_document.php?file=<?php echo urlencode($documents[$image['index']]['filename']); ?>&client_id=<?php echo $clientId; ?>&charactername=<?php echo urlencode($characterName); ?>" 
                                                       class="btn btn-sm btn-primary w-100">
                                                        <i class='bx bx-eye'></i> View
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <a href="../index.php?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to Client Intake
                </a>
            </div>
        </div>
    </div>

    <!-- Delete Client Modal -->
    <div class="modal fade" id="deleteClientModal" tabindex="-1" aria-labelledby="deleteClientModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteClientModalLabel">
                        <i class='bx bx-error-circle'></i> Delete Client
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class='bx bx-error-circle'></i>
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    <p>You are about to permanently delete:</p>
                    <ul>
                        <li><strong>Client:</strong> <?php echo htmlspecialchars($fullName); ?></li>
                        <li><strong>All client information</strong> from the database</li>
                        <li><strong><?php echo count($documents); ?> document(s)</strong> and the entire client folder</li>
                        <li><strong>Storage folder:</strong> <?php echo htmlspecialchars($client_folder); ?></li>
                    </ul>
                    <p>Please type <strong>DELETE</strong> to confirm:</p>
                    <form action="delete_client.php" method="POST" id="deleteClientForm">
                        <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">
                        <input type="hidden" name="charactername" value="<?php echo htmlspecialchars($_GET['charactername']); ?>">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="deleteConfirmation" name="confirmation" 
                                   placeholder="Type DELETE to confirm" required>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger" id="deleteClientBtn" disabled>
                                <i class='bx bx-trash'></i> Delete Client Permanently
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload handling
        document.getElementById('document').addEventListener('change', function() {
            const file = this.files[0];
            const fileInfo = document.getElementById('fileInfo');
            const largeFileWarning = document.getElementById('largeFileWarning');
            
            if (file) {
                const fileSize = file.size;
                const fileSizeMB = fileSize / (1024 * 1024);
                
                // Show file info
                fileInfo.innerHTML = `
                    <div class="file-info">
                        <strong>Selected File:</strong> ${file.name}<br>
                        <strong>Size:</strong> ${fileSizeMB.toFixed(2)} MB<br>
                        <strong>Type:</strong> ${file.type || 'Unknown'}
                    </div>
                `;
                fileInfo.style.display = 'block';
                
                // Show warning for large files
                if (fileSizeMB > 50) {
                    largeFileWarning.style.display = 'block';
                } else {
                    largeFileWarning.style.display = 'none';
                }
            } else {
                fileInfo.style.display = 'none';
                largeFileWarning.style.display = 'none';
            }
        });

        document.getElementById('uploadForm').addEventListener('submit', function() {
            const file = document.getElementById('document').files[0];
            const uploadProgress = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const uploadBtn = document.getElementById('uploadBtn');
            
            if (file) {
                // Show progress bar
                uploadProgress.style.display = 'block';
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Uploading...';
                
                // Simulate progress (since we can't get real progress with standard form submission)
                let progress = 0;
                const fileSize = file.size;
                const isLargeFile = fileSize > 50 * 1024 * 1024; // 50MB
                const progressIncrement = isLargeFile ? 2 : 10; // Slower progress for large files
                const progressInterval = isLargeFile ? 1000 : 300; // Longer intervals for large files
                
                const interval = setInterval(function() {
                    progress += Math.random() * progressIncrement;
                    if (progress > 90) progress = 90; // Don't go to 100% until we know it's done
                    
                    progressBar.style.width = progress + '%';
                    progressText.textContent = Math.round(progress) + '%';
                }, progressInterval);
                
                // Clean up interval after reasonable time (based on file size)
                const timeoutDuration = Math.min(Math.max(fileSize / 1024 / 1024 * 2000, 30000), 300000); // 2 seconds per MB, min 30s, max 5 minutes
                setTimeout(function() {
                    clearInterval(interval);
                }, timeoutDuration);
            }
        });

        // Delete client confirmation handling
        document.getElementById('deleteConfirmation').addEventListener('input', function() {
            const deleteBtn = document.getElementById('deleteClientBtn');
            const confirmation = this.value.trim();
            
            if (confirmation === 'DELETE') {
                deleteBtn.disabled = false;
                deleteBtn.classList.remove('btn-secondary');
                deleteBtn.classList.add('btn-danger');
            } else {
                deleteBtn.disabled = true;
                deleteBtn.classList.remove('btn-danger');
                deleteBtn.classList.add('btn-secondary');
            }
        });

        // Reset form when modal is closed
        document.getElementById('deleteClientModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('deleteConfirmation').value = '';
            document.getElementById('deleteClientBtn').disabled = true;
        });
    </script>
</body>
</html>