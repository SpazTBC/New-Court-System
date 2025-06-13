<?php
require_once '../include/database.php';
require_once '../auth/character_auth.php';

// Get character name - handle both formats
$characterName = '';
if (isset($_GET['character_name'])) {
    $characterName = $_GET['character_name'];
} elseif (isset($_GET['charactername'])) {
    $characterName = $_GET['charactername'];
} elseif (isset($_GET['first_name']) && isset($_GET['last_name'])) {
    $characterName = trim($_GET['first_name'] . ' ' . $_GET['last_name']);
}

// Debug: Show what we're getting
$debug_info = '';
if (isset($_GET['debug'])) {
    $debug_info = "Character Name: " . ($characterName ?: 'NOT SET') . "<br>";
    $debug_info .= "First Name: " . ($_GET['first_name'] ?? 'NOT SET') . "<br>";
    $debug_info .= "Last Name: " . ($_GET['last_name'] ?? 'NOT SET') . "<br>";
}

$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    // Redirect with more specific error
    header("Location: ../index.php?error=character_not_found&character_name=" . urlencode($characterName));
    exit();
}

// Validate character access - remove role requirements for now
$auth = validateCharacterAccess($characterName);
if (!$auth['valid']) {
    // Show more detailed error
    header("Location: ../index.php?error=auth_failed&reason=" . urlencode($auth['message']) . "&character_name=" . urlencode($characterName));
    exit();
}

$characterDisplayName = $currentCharacter['charactername'];
$characterJob = $currentCharacter['job'];

// Get case ID from URL
$caseId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get case details
$stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
$stmt->execute([$caseId]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    header("Location: index.php?character_name=" . urlencode($characterName) . "&error=case_not_found");
    exit();
}

// Handle file upload success/error messages
$upload_message = '';
if (isset($_GET['upload_success'])) {
    $filename = $_GET['file'] ?? 'File';
    $upload_message = '<div class="alert alert-success alert-dismissible fade show">
        <i class="bx bx-check-circle"></i> Evidence file "' . htmlspecialchars($filename) . '" uploaded successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
} elseif (isset($_GET['upload_error'])) {
    $error_code = $_GET['upload_error'];
    $reason = $_GET['reason'] ?? '';
    
    switch ($reason) {
        case 'size':
            $error_message = "File is too large. Maximum file size is 200MB.";
            break;
        case 'type':
            $error_message = "Invalid file type. Please upload images, documents, videos, audio files, or archives only.";
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
        case 'database':
            $error_message = "Database error occurred. Please try again.";
            break;
        default:
            $error_message = "An unknown error occurred during upload.";
    }
    
    $upload_message = '<div class="alert alert-danger alert-dismissible fade show">
        <i class="bx bx-error-circle"></i> ' . $error_message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// Check if user can view/upload files (not civilian and not defendant)
$canAccessFiles = ($characterJob !== "Civilian" && $characterDisplayName !== $case['defendent']);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - Court System</title>
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
            background-color: #2ea043 !important;
        }
        .bg-info {
            background-color: #1f6feb !important;
        }
        .bg-light {
            background-color: #30363d !important;
            color: #e0e0e0 !important;
        }
        .btn-outline-warning {
            color: #d29922;
            border-color: #d29922;
        }
        .btn-outline-warning:hover {
            background-color: #d29922;
            border-color: #d29922;
            color: #000;
        }
        .btn-outline-danger {
            color: #da3633;
            border-color: #da3633;
        }
        .btn-outline-danger:hover {
            background-color: #da3633;
            border-color: #da3633;
        }
        .btn-success {
            background-color: #238636;
            border-color: #238636;
        }
        .btn-success:hover {
            background-color: #2ea043;
            border-color: #2ea043;
        }
        .table {
            color: #e0e0e0;
        }
        .table-striped > tbody > tr:nth-of-type(odd) > td {
            background-color: #161b22;
        }
        .badge.bg-warning {
            background-color: #d29922 !important;
            color: #000;
        }
        .badge.bg-success {
            background-color: #238636 !important;
        }
        .badge.bg-primary {
            background-color: #58a6ff !important;
            color: #000;
        }
        .badge.bg-secondary {
            background-color: #6e7681 !important;
        }
        .alert-info {
            background-color: #0c2d48;
            border-color: #1f6feb;
            color: #58a6ff;
        }
        .alert-warning {
            background-color: #2d1b00;
            border-color: #d29922;
            color: #d29922;
        }
        .btn-outline-info {
            color: #58a6ff;
            border-color: #58a6ff;
        }
        .btn-outline-info:hover {
            background-color: #58a6ff;
            border-color: #58a6ff;
            color: #000;
        }
        .btn-outline-primary {
            color: #238636;
            border-color: #238636;
        }
        .btn-outline-primary:hover {
            background-color: #238636;
            border-color: #238636;
        }
        .list-group-item {
            background-color: #21262d;
            border-color: #30363d;
            color: #e0e0e0;
        }
        .list-group-item:hover {
            background-color: #30363d;
        }
        .list-group-item-action:hover {
            background-color: #30363d;
        }
        .upload-progress {
            display: none;
        }
        .file-size-info {
            font-size: 0.875rem;
            color: #8b949e;
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
        .text-muted {
            color: #8b949e !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php?character_name=<?php echo urlencode($characterName); ?>">
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
        <?php if ($debug_info): ?>
            <div class="alert alert-info">
                <strong>Debug Info:</strong><br>
                <?php echo $debug_info; ?>
                Character Found: <?php echo $currentCharacter ? 'YES' : 'NO'; ?><br>
                Character Display Name: <?php echo htmlspecialchars($characterDisplayName); ?><br>
                Character Job: <?php echo htmlspecialchars($characterJob); ?>
            </div>
        <?php endif; ?>
        
        <?php echo $upload_message; ?>
        
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Case File: <?php echo htmlspecialchars($case['caseid']); ?></h3>
                <span class="badge bg-primary"><?php echo htmlspecialchars($case['type']); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <th>Case ID:</th>
                                <td><?php echo htmlspecialchars($case['id']); ?></td>
                            </tr>
                            <tr>
                                <th>Case Number:</th>
                                <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                            </tr>
                            <tr>
                                <th>Assigned User:</th>
                                <td><?php echo htmlspecialchars($case['assigneduser']); ?></td>
                            </tr>
                            <tr>
                                <th>Date Assigned:</th>
                                <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                            </tr>
                            <tr>
                                <th>Defendant:</th>
                                <td><?php echo htmlspecialchars($case['defendent']); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-<?php echo $case['status'] == 'pending' ? 'warning' : ($case['status'] == 'closed' ? 'success' : 'primary'); ?>">
                                        <?php echo htmlspecialchars(ucfirst($case['status'] ?? 'active')); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if (!empty($case['supervisor'])): ?>
                            <tr>
                                <th>Supervisor:</th>
                                <td><?php echo htmlspecialchars($case['supervisor']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">File Upload</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($canAccessFiles): ?>
                                    <form enctype="multipart/form-data" action="upload_evidence.php" method="post" class="mb-3" id="uploadForm">
                                        <div class="mb-3">
                                            <label class="form-label">Select File</label>
                                            <input type="file" class="form-control" name="evidence_file" id="evidence_file" 
                                                   accept=".jpg,.jpeg,.png,.gif,.bmp,.tiff,.webp,.pdf,.doc,.docx,.txt,.rtf,.odt,.xls,.xlsx,.ppt,.pptx,.wmv,.mp4,.avi,.mov,.mkv,.flv,.webm,.m4v,.3gp,.mp3,.wav,.flac,.aac,.ogg,.wma,.zip,.rar,.7z,.tar,.gz">
                                            <div class="form-text file-size-info">
                                                <strong>Maximum file size: 200MB</strong><br>
                                                Supported formats: Images (JPG, PNG, GIF, etc.), Documents (PDF, DOC, XLS, etc.),
                                                Videos (MP4, AVI, MOV, etc.), Audio (MP3, WAV, etc.), Archives (ZIP, RAR, etc.)
                                            </div>
                                        </div>
                                        
                                        <!-- Progress bar -->
                                        <div class="progress mb-3 upload-progress" id="uploadProgress">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                 role="progressbar" style="width: 0%" id="progressBar">
                                                <span id="progressText">0%</span>
                                            </div>
                                        </div>
                                        
                                        <input type="hidden" value="<?php echo htmlspecialchars($case['caseid']); ?>" name="case_number">
                                        <input type="hidden" value="<?php echo htmlspecialchars($case['id']); ?>" name="case_id">
                                        <input type="hidden" name="character_name" value="<?php echo htmlspecialchars($characterName); ?>">
                                        <button type="submit" name="submit" class="btn btn-primary" id="uploadBtn">
                                            <i class='bx bx-upload'></i> Upload File
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <div class="mt-4">
                                    <h6>Case Files</h6>
                                    <?php
                                    // Create uploads directory structure if it doesn't exist
                                    $upload_dir = 'uploads/' . $case['caseid'] . '/';
                                    if (!file_exists($upload_dir)) {
                                        mkdir($upload_dir, 0755, true);
                                    }
                                    
                                    if (file_exists($upload_dir)):
                                        $files = glob($upload_dir . "*");
                                        if (!empty($files)):
                                            // Only show files if user can access them
                                            if ($canAccessFiles):
                                    ?>
                                        <div class="list-group">
                                            <?php foreach($files as $file):
                                                $fileSize = filesize($file);
                                                $fileSizeMB = round($fileSize / 1024 / 1024, 2);
                                                $fileSizeDisplay = $fileSizeMB > 1 ? $fileSizeMB . ' MB' : round($fileSize / 1024, 2) . ' KB';
                                                
                                                // Get file extension for icon
                                                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
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
                                                    case 'gif':
                                                    case 'bmp':
                                                        $icon = 'bx-image';
                                                        break;
                                                    case 'mp4':
                                                    case 'avi':
                                                    case 'mov':
                                                    case 'wmv':
                                                        $icon = 'bx-video';
                                                        break;
                                                    case 'mp3':
                                                    case 'wav':
                                                    case 'flac':
                                                        $icon = 'bx-music';
                                                        break;
                                                    case 'zip':
                                                    case 'rar':
                                                    case '7z':
                                                        $icon = 'bx-archive';
                                                        break;
                                                }
                                            ?>
                                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <i class='bx <?php echo $icon; ?> me-3' style="font-size: 1.5em;"></i>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars(basename($file)); ?></strong><br>
                                                            <small class="text-muted">Size: <?php echo $fileSizeDisplay; ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-secondary" data-timestamp="<?php echo filemtime($file); ?>">
                                                            <?php echo date("m/d/Y h:i A", filemtime($file)); ?>
                                                        </span>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="<?php echo $file; ?>" class="btn btn-outline-primary" target="_blank" title="View">
                                                                <i class='bx bx-show'></i>
                                                            </a>
                                                            <a href="download_evidence.php?file=<?php echo urlencode(basename($file)); ?>&case_id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" 
                                                               class="btn btn-outline-info" title="Download">
                                                                <i class='bx bx-download'></i>
                                                            </a>
                                                            <?php if (in_array($characterJob, ['admin', 'judge'])): ?>
                                                                <a href="delete_evidence.php?file=<?php echo urlencode(basename($file)); ?>&case_id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" 
                                                                   class="btn btn-outline-danger" title="Delete"
                                                                   onclick="return confirm('Are you sure you want to delete this evidence file?')">
                                                                    <i class='bx bx-trash'></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class='bx bx-lock-alt'></i> You don't have permission to view case files
                                        </div>
                                    <?php
                                            endif;
                                        else:
                                    ?>
                                        <div class="alert alert-info">
                                            <i class='bx bx-info-circle'></i> No files uploaded yet
                                        </div>
                                    <?php
                                        endif;
                                    else:
                                    ?>
                                        <div class="alert alert-info">
                                            <i class='bx bx-info-circle'></i> No files uploaded yet
                                        </div>
                                    <?php
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Case Details Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class='bx bx-file-blank'></i> Case Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="border p-3 bg-light rounded">
                                    <?php echo nl2br(htmlspecialchars($case['details'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <?php if ($canAccessFiles): ?>
                    <div class="mt-4">
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if(($case['status'] ?? 'active') !== 'closed'): ?>
                                <a href="modify.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-outline-warning">
                                    <i class='bx bx-edit'></i> Modify
                                </a>
                                <a href="close_case.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-success"
                                   onclick="return confirm('Are you sure you want to close this case? This will mark it as resolved but keep all data for future reference.')">
                                    <i class='bx bx-check-circle'></i> Close Case
                                </a>
                                <a href="delete.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this case? This action cannot be undone!')">
                                    <i class='bx bx-trash'></i> Delete
                                </a>
                            <?php else: ?>
                                <a href="reopen_case.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-primary"
                                   onclick="return confirm('Are you sure you want to reopen this case?')">
                                    <i class='bx bx-refresh'></i> Reopen Case
                                </a>
                                <div class="alert alert-success d-inline-flex align-items-center ms-2 mb-0 py-2">
                                    <i class='bx bx-check-circle me-2'></i> This case is closed
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="index.php?character_name=<?php echo urlencode($characterName); ?>" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Back to Cases
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('evidence_file');
            const uploadBtn = document.getElementById('uploadBtn');
            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            
            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('Please select a file to upload.');
                return;
            }
            
            const file = fileInput.files[0];
            const maxSize = 200 * 1024 * 1024; // 200MB in bytes
            
            if (file.size > maxSize) {
                e.preventDefault();
                const sizeMB = Math.round(file.size / 1024 / 1024 * 100) / 100;
                alert(`File is too large (${sizeMB}MB). Maximum allowed size is 200MB.`);
                return;
            }
            
            // Show progress and disable button
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Uploading...';
            progressDiv.style.display = 'block';
            
            // Simulate progress (since we can't get real progress with standard form submission)
            let progress = 0;
            const interval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                
                progressBar.style.width = progress + '%';
                progressText.textContent = Math.round(progress) + '%';
            }, 500);
            
            // Clean up interval after 30 seconds (fallback)
            setTimeout(function() {
                clearInterval(interval);
            }, 30000);
        });
        
        // File input change event to show file info
        document.getElementById('evidence_file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const sizeMB = Math.round(file.size / 1024 / 1024 * 100) / 100;
                const sizeDisplay = sizeMB > 1 ? sizeMB + ' MB' : Math.round(file.size / 1024 * 100) / 100 + ' KB';
                
                // Create or update file info display
                let fileInfo = document.getElementById('fileInfo');
                if (!fileInfo) {
                    fileInfo = document.createElement('div');
                    fileInfo.id = 'fileInfo';
                    fileInfo.className = 'mt-2 p-2 bg-light rounded';
                    e.target.parentNode.appendChild(fileInfo);
                }
                
                let statusClass = 'text-success';
                let statusIcon = 'bx-check-circle';
                let statusText = 'Ready to upload';
                
                if (file.size > 200 * 1024 * 1024) {
                    statusClass = 'text-danger';
                    statusIcon = 'bx-error-circle';
                    statusText = 'File too large (max 200MB)';
                }
                
                fileInfo.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bx ${statusIcon} ${statusClass} me-2"></i>
                        <div>
                            <strong>${file.name}</strong><br>
                            <small>Size: ${sizeDisplay} | Type: ${file.type || 'Unknown'}</small><br>
                            <small class="${statusClass}">${statusText}</small>
                        </div>
                    </div>
                `;
            }
        });
        
        // Convert all timestamps to user's local time
        document.addEventListener('DOMContentLoaded', function() {
            const timestampElements = document.querySelectorAll('[data-timestamp]');
            
            timestampElements.forEach(function(element) {
                const timestamp = parseInt(element.getAttribute('data-timestamp')) * 1000; // Convert to milliseconds
                const date = new Date(timestamp);
                
                // Format: MM/DD/YYYY HH:MM (12-hour format)
                const formatted = date.toLocaleString('en-US', {
                    month: '2-digit',
                    day: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                
                element.textContent = formatted;
            });
        });
    </script>
</body>
</html>