<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Check if client ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../view_intakes.php");
    exit();
}

$client_id = $_GET['id'];

// Database connection
require_once "../../include/database.php";

// Get client details
try {
    $stmt = $conn->prepare("SELECT * FROM client_intake WHERE id = :id");
    $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: ../view_intakes.php");
        exit();
    }
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Get uploaded documents
$documents = [];
$client_folder = "client_" . $client_id;
$documents_path = $client_folder . "/signed_documents";

if (is_dir($documents_path)) {
    $files = scandir($documents_path);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $documents[] = [
                'name' => $file,
                'path' => $documents_path . "/" . $file,
                'size' => filesize($documents_path . "/" . $file),
                'modified' => filemtime($documents_path . "/" . $file)
            ];
        }
    }
    
    // Sort documents by modification date (newest first)
    usort($documents, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

$menu = "CLIENTS";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Profile - <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="../../css/dark-mode.css" rel="stylesheet">
    <script src="../../js/dark-mode.js"></script>
    <style>
        .upload-progress {
            display: none;
        }
        .file-size-info {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .large-file-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand">
                <span class="fw-bold text-white">Blackwood & Associates</span>
            </div>
            <?php include("../../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Client Information Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Client Profile: <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h4>
                        <div>
                            <a href="../edit_intake.php?id=<?php echo $client_id; ?>" class="btn btn-warning me-2"><i class='bx bx-edit'></i> Edit Profile</a>
                            <a href="../view_intakes.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Back to List</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Contact Information</h6>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($client['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($client['phone']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($client['address'] . ', ' . $client['city'] . ', ' . $client['state'] . ' ' . $client['zip']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Case Information</h6>
                                <p><strong>Case Type:</strong> <?php echo htmlspecialchars(ucfirst($client['case_type'])); ?></p>
                                <p><strong>Intake Date:</strong> <?php echo date('M d, Y', strtotime($client['intake_date'])); ?></p>
                                <p><strong>Intake By:</strong> <?php echo htmlspecialchars($client['intake_by']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Management Section -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class='bx bx-file'></i> Document Management</h5>
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class='bx bx-upload'></i> Upload Document
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['upload_success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class='bx bx-check-circle'></i> Document uploaded successfully!
                                <?php if (isset($_GET['file'])): ?>
                                    <br><strong>File:</strong> <?php echo htmlspecialchars(urldecode($_GET['file'])); ?>
                                <?php endif; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['upload_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class='bx bx-error-circle'></i> 
                                <?php
                                $error_msg = "Error uploading document. Please try again.";
                                $error_code = $_GET['upload_error'];
                                
                                switch($error_code) {
                                    case '2':
                                        $size = isset($_GET['size']) ? $_GET['size'] : 'unknown';
                                        $error_msg = "File size too large ({$size}MB). Maximum size is 200MB.";
                                        break;
                                    case '3':
                                        $ext = isset($_GET['ext']) ? $_GET['ext'] : 'unknown';
                                        $error_msg = "File type '{$ext}' not allowed. Please upload PDF, DOC, DOCX, JPG, PNG, GIF, TXT, RTF, or video/audio files.";
                                        break;
                                    case '4':
                                        $error_msg = "Upload was interrupted. Please try again.";
                                        break;
                                    case '5':
                                        $error_msg = "No file was selected for upload.";
                                        break;
                                    default:
                                        $error_msg = "Failed to upload file. Please check file size (max 200MB) and type, then try again.";
                                }
                                echo $error_msg;
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['delete_success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class='bx bx-check-circle'></i> Document deleted successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['folder_created'])): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class='bx bx-info-circle'></i> Client folder created successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($documents)): ?>
                            <div class="text-center py-4">
                                <i class='bx bx-file-blank' style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">No documents uploaded yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Document Name</th>
                                            <th>File Size</th>
                                            <th>Upload Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($documents as $doc): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $ext = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));
                                                    $icon = 'bx-file';
                                                    if($ext == 'pdf') $icon = 'bx-file-blank';
                                                    if(in_array($ext, ['doc', 'docx'])) $icon = 'bx-file-doc';
                                                    if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp'])) $icon = 'bx-image';
                                                    if(in_array($ext, ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'flv', 'webm', 'm4v', '3gp'])) $icon = 'bx-video';
                                                    if(in_array($ext, ['mp3', 'wav', 'flac', 'aac', 'ogg', 'wma'])) $icon = 'bx-music';
                                                    if(in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) $icon = 'bx-archive';
                                                    ?>
                                                    <i class='bx <?php echo $icon; ?> me-2'></i>
                                                    <?php echo htmlspecialchars($doc['name']); ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if($doc['size'] > 1024 * 1024) {
                                                        echo number_format($doc['size'] / (1024 * 1024), 2) . ' MB';
                                                    } else {
                                                        echo number_format($doc['size'] / 1024, 2) . ' KB';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('M d, Y h:i A', $doc['modified']); ?></td>
                                                <td>
                                                    <a href="view_document.php?client_id=<?php echo $client_id; ?>&file=<?php echo urlencode($doc['name']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class='bx bx-show'></i> View
                                                    </a>
                                                    <a href="view_document.php?client_id=<?php echo $client_id; ?>&file=<?php echo urlencode($doc['name']); ?>" 
                                                       class="btn btn-sm btn-outline-success" download>
                                                        <i class='bx bx-download'></i> Download
                                                    </a>
                                                    <a href="delete_document.php?client_id=<?php echo $client_id; ?>&file=<?php echo urlencode($doc['name']); ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this document?')">
                                                        <i class='bx bx-trash'></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="upload_document.php" method="post" enctype="multipart/form-data" id="uploadForm">
                    <!-- Important: MAX_FILE_SIZE must come before the file input field -->
                    <input type="hidden" name="MAX_FILE_SIZE" value="209715200"> <!-- 200MB in bytes -->
                    <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client_id); ?>">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="document" class="form-label">Select Document</label>
                            <input type="file" class="form-control" id="document" name="document" required
                                   accept=".pdf,.doc,.docx,.txt,.rtf,.odt,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.bmp,.tiff,.webp,.mp4,.avi,.mov,.wmv,.mkv,.flv,.webm,.m4v,.3gp,.mp3,.wav,.flac,.aac,.ogg,.wma,.zip,.rar,.7z,.tar,.gz">
                            <div class="form-text file-size-info">
                                <strong>Maximum file size: 200MB</strong><br>
                                <strong>Allowed file types:</strong> 
                                <ul class="mb-0 mt-1">
                                    <li><strong>Documents:</strong> PDF, DOC, DOCX, TXT, RTF, ODT, XLS, XLSX, PPT, PPTX</li>
                                    <li><strong>Images:</strong> JPG, PNG, GIF, BMP, TIFF, WEBP</li>
                                    <li><strong>Videos:</strong> MP4, AVI, MOV, WMV, MKV, FLV, WEBM, M4V, 3GP</li>
                                    <li><strong>Audio:</strong> MP3, WAV, FLAC, AAC, OGG, WMA</li>
                                    <li><strong>Archives:</strong> ZIP, RAR, 7Z, TAR, GZ</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Debug info (remove this after testing) -->
                        <div class="alert alert-info">
                            <small>Debug: Client ID = <?php echo htmlspecialchars($client_id); ?></small>
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
                            <i class='bx bx-time'></i>
                            <strong>Large File Detected:</strong> This file is quite large and may take a while to upload. 
                            Please ensure you have a stable internet connection.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <span class="upload-text">
                                <i class='bx bx-upload'></i> Upload Document
                            </span>
                            <span class="upload-spinner d-none">
                                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                Uploading...
                            </span>
                        </button>
                    </div>
                </form>            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File input change event
        document.getElementById('document').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('fileInfo');
            const largeFileWarning = document.getElementById('largeFileWarning');
            const uploadBtn = document.getElementById('uploadBtn');
            
            if (file) {
                const maxSize = 200 * 1024 * 1024; // 200MB in bytes
                const largeFileThreshold = 50 * 1024 * 1024; // 50MB threshold for warning
                const sizeMB = Math.round(file.size / 1024 / 1024 * 100) / 100;
                const sizeDisplay = sizeMB > 1 ? sizeMB + ' MB' : Math.round(file.size / 1024 * 100) / 100 + ' KB';
                
                let statusClass = 'text-success';
                let statusIcon = 'bx-check-circle';
                let statusText = 'Ready to upload';
                let canUpload = true;
                
                if (file.size > maxSize) {
                    statusClass = 'text-danger';
                    statusIcon = 'bx-error-circle';
                    statusText = 'File too large (max 200MB)';
                    canUpload = false;
                } else if (file.size > largeFileThreshold) {
                    statusClass = 'text-warning';
                    statusIcon = 'bx-time';
                    statusText = 'Large file - upload may take several minutes';
                    largeFileWarning.style.display = 'block';
                } else {
                    largeFileWarning.style.display = 'none';
                }
                
                // Get file type icon
                const ext = file.name.split('.').pop().toLowerCase();
                let fileIcon = 'bx-file';
                if (['pdf'].includes(ext)) fileIcon = 'bx-file-blank';
                if (['doc', 'docx'].includes(ext)) fileIcon = 'bx-file-doc';
                if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp'].includes(ext)) fileIcon = 'bx-image';
                if (['mp4', 'avi', 'mov', 'wmv', 'mkv', 'flv', 'webm', 'm4v', '3gp'].includes(ext)) fileIcon = 'bx-video';
                if (['mp3', 'wav', 'flac', 'aac', 'ogg', 'wma'].includes(ext)) fileIcon = 'bx-music';
                if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) fileIcon = 'bx-archive';
                
                fileInfo.innerHTML = `
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <i class="bx ${fileIcon} fs-2 me-3 text-primary"></i>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${file.name}</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Size: ${sizeDisplay} | Type: ${file.type || 'Unknown'}</small>
                                        <span class="${statusClass}">
                                            <i class="bx ${statusIcon}"></i> ${statusText}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                fileInfo.style.display = 'block';
                uploadBtn.disabled = !canUpload;
                
                if (!canUpload) {
                    uploadBtn.innerHTML = '<i class="bx bx-error-circle"></i> File Too Large';
                    uploadBtn.classList.add('btn-danger');
                    uploadBtn.classList.remove('btn-primary');
                } else {
                    uploadBtn.innerHTML = '<i class="bx bx-upload"></i> Upload Document';
                    uploadBtn.classList.add('btn-primary');
                    uploadBtn.classList.remove('btn-danger');
                }
            } else {
                fileInfo.style.display = 'none';
                largeFileWarning.style.display = 'none';
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="bx bx-upload"></i> Upload Document';
                uploadBtn.classList.add('btn-primary');
                uploadBtn.classList.remove('btn-danger');
            }
        });

        // Form submission with progress
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('document');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadText = uploadBtn.querySelector('.upload-text');
            const uploadSpinner = uploadBtn.querySelector('.upload-spinner');
            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            
            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('Please select a file to upload.');
                return;
            }
            
            const file = fileInput.files[0];
            const maxSize = 200 * 1024 * 1024; // 200MB
            
            if (file.size > maxSize) {
                e.preventDefault();
                const sizeMB = Math.round(file.size / 1024 / 1024 * 100) / 100;
                alert(`File is too large (${sizeMB}MB). Maximum allowed size is 200MB.`);
                return;
            }
            
            // Show progress and disable button
            uploadText.classList.add('d-none');
            uploadSpinner.classList.remove('d-none');
            uploadBtn.disabled = true;
            progressDiv.style.display = 'block';
            
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
        });

        // Reset form when modal is closed
        document.getElementById('uploadModal').addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('uploadForm');
            const fileInfo = document.getElementById('fileInfo');
            const largeFileWarning = document.getElementById('largeFileWarning');
            const progressDiv = document.getElementById('uploadProgress');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadText = uploadBtn.querySelector('.upload-text');
            const uploadSpinner = uploadBtn.querySelector('.upload-spinner');
            
            form.reset();
            fileInfo.style.display = 'none';
            largeFileWarning.style.display = 'none';
            progressDiv.style.display = 'none';
            uploadBtn.disabled = false;
            uploadText.classList.remove('d-none');
            uploadSpinner.classList.add('d-none');
            uploadBtn.innerHTML = '<i class="bx bx-upload"></i> Upload Document';
            uploadBtn.classList.add('btn-primary');
            uploadBtn.classList.remove('btn-danger');
        });
    </script>
</body>
</html>