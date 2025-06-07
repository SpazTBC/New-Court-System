<?php
session_start();
$menu = "CASES";
include("../include/database.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Case Details</title>
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
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <!-- <img src="../images/logo.png" alt="Logo" class="img-fluid me-2" style="max-height: 40px;"> -->
                <span class="fw-bold text-white">Blackwood & Associates</span>
                <span class="ms-2">Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <?php
        // Display upload messages
        if (isset($_GET['success'])) {
            $success = $_GET['success'];
            $success_message = '';
            
            switch($success) {
                case 'upload':
                    $success_message = 'File uploaded successfully!';
                    if (isset($_GET['file'])) {
                        $success_message .= ' File: ' . htmlspecialchars(urldecode($_GET['file']));
                    }
                    break;
                case 'case_closed':
                    $success_message = 'Case has been successfully closed.';
                    break;
                case 'case_reopened':
                    $success_message = 'Case has been successfully reopened.';
                    break;
            }
            
            if ($success_message) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle"></i> ' . $success_message . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        }

        // Display error messages
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
            $error_message = '';
            
            switch($error) {
                case 'size':
                    $size = isset($_GET['size']) ? $_GET['size'] : 'unknown';
                    $max = isset($_GET['max']) ? $_GET['max'] : '200';
                    $error_message = "File is too large ({$size}MB). Maximum allowed size is {$max}MB.";
                    break;
                case 'type':
                    $ext = isset($_GET['ext']) ? $_GET['ext'] : 'unknown';
                    $error_message = "File type '{$ext}' is not allowed. Please upload images, documents, videos, audio files, or archives.";
                    break;
                case 'upload_failed':
                case 'move_failed':
                    $error_message = "Upload failed. Please try again.";
                    break;
                case 'empty_file':
                    $error_message = "Please select a file to upload.";
                    break;
                case 'directory':
                    $error_message = "Could not create upload directory. Please contact administrator.";
                    break;
                case 'file_too_large_ini':
                    $error_message = "File exceeds the maximum upload size allowed by the server (200MB).";
                    break;
                case 'file_too_large_form':
                    $error_message = "File exceeds the maximum upload size allowed by the form (200MB).";
                    break;
                case 'partial_upload':
                    $error_message = "File was only partially uploaded. Please try again.";
                    break;
                case 'no_file':
                    $error_message = "No file was selected for upload.";
                    break;
                case 'no_tmp_dir':
                    $error_message = "Missing temporary upload directory. Please contact administrator.";
                    break;
                case 'cant_write':
                    $error_message = "Failed to write file to disk. Please contact administrator.";
                    break;
                case 'close_failed':
                    $error_message = "Failed to close case. Please try again.";
                    break;
                case 'reopen_failed':
                    $error_message = "Failed to reopen case. Please try again.";
                    break;
                case 'case_not_found':
                    $error_message = "Case not found or you don't have permission to access it.";
                    break;
                case 'access_denied':
                    $error_message = "You don't have permission to perform this action.";
                    break;
                default:
                    $error_message = "An unknown error occurred during upload.";
            }
            
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle"></i> ' . $error_message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }

        $caseId = $_GET['id'];
        $stmt = $conn->prepare("
            SELECT c.*, u.job 
            FROM cases c 
            JOIN users u ON u.username = :username 
            WHERE c.id = :caseId
        ");
        $stmt->execute([
            'username' => $_SESSION['username'],
            'caseId' => $caseId
        ]);
        while($case = $stmt->fetch()):
        ?>
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
                                <th>Date Assigned:</th>
                                <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                            </tr>
                            <tr>
                                <th>Defendant:</th>
                                <td><?php echo htmlspecialchars($case['defendent']); ?></td>
                            </tr>
                            <tr>
                                <th>Details:</th>
                                <td><?php echo nl2br(htmlspecialchars($case['details'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">File Upload</h5>
                            </div>
                            <div class="card-body">
                                <?php if($case['job'] !== "Civilian" && $_SESSION['username'] !== $case['defendent']): ?>
                                   <form enctype="multipart/form-data" action="upload.php" method="post" class="mb-3" id="uploadForm">
                                        <div class="mb-3">
                                             <label class="form-label">Select File</label>
                                             <input type="file" class="form-control" name="file" id="file" accept=".jpg,.jpeg,.png,.gif,.bmp,.tiff,.webp,.pdf,.doc,.docx,.txt,.rtf,.odt,.xls,.xlsx,.ppt,.pptx,.wmv,.mp4,.avi,.mov,.mkv,.flv,.webm,.m4v,.3gp,.mp3,.wav,.flac,.aac,.ogg,.wma,.zip,.rar,.7z,.tar,.gz">
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
                                        
                                        <input type="hidden" value="<?php echo htmlspecialchars($case['caseid']); ?>" name="caseids">
                                        <input type="hidden" value="<?php echo htmlspecialchars($case['id']); ?>" name="id">
                                        <button type="submit" name="submit" class="btn btn-primary" id="uploadBtn">
                                             <i class='bx bx-upload'></i> Upload File
                                        </button>
                                   </form>
                                <?php endif; ?>

                                <div class="mt-4">
                                    <h6>Case Files</h6>
                                    <?php
                                    $dir = 'uploads/' . $case['caseid'] . '/';
                                    if (file_exists($dir)):
                                        $files = glob($dir . "*");
                                        if (!empty($files)):
                                            // Only show files if user is not a civilian and not the defendant
                                            if($case['job'] !== "Civilian" && $_SESSION['username'] !== $case['defendent']):
                                    ?>
                                        <div class="list-group">
                                            <?php foreach($files as $file): 
                                                $fileSize = filesize($file);
                                                $fileSizeMB = round($fileSize / 1024 / 1024, 2);
                                                $fileSizeDisplay = $fileSizeMB > 1 ? $fileSizeMB . ' MB' : round($fileSize / 1024, 2) . ' KB';
                                            ?>
                                                <a href="<?php echo $file; ?>" 
                                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                                   target="_blank">
                                                    <div>
                                                        <i class='bx bx-file'></i> 
                                                        <strong><?php echo basename($file); ?></strong>
                                                        <br>
                                                        <small class="text-muted">Size: <?php echo $fileSizeDisplay; ?></small>
                                                    </div>
                                                    <span class="badge bg-primary rounded-pill" data-timestamp="<?php echo filemtime($file); ?>">
                                                        <?php echo date("m/d/Y h:i A", filemtime($file)); ?>
                                                    </span>
                                                </a>
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
                                        <div class="alert alert-info">No files uploaded yet</div>
                                    <?php 
                                        endif;
                                    endif; 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if($case['job'] !== "Civilian" && $_SESSION['username'] !== $case['defendent']): ?>
                    <div class="mt-4">
                        <div class="d-flex gap-2">
                            <?php if($case['status'] !== 'closed'): ?>
                                <a href="modify.php?id=<?php echo $case['id']; ?>" class="btn btn-warning">
                                    <i class='bx bx-edit'></i> Modify
                                </a>
                                <a href="close_case.php?id=<?php echo $case['id']; ?>" class="btn btn-success" 
                                    onclick="return confirm('Are you sure you want to close this case? This will mark it as resolved but keep all data for future reference.')">
                                    <i class='bx bx-check-circle'></i> Close Case
                                </a>
                                <a href="delete.php?id=<?php echo $case['id']; ?>" class="btn btn-danger" 
                                    onclick="return confirm('Are you sure you want to delete this case? This action cannot be undone!')">
                                    <i class='bx bx-trash'></i> Delete
                                </a>
                            <?php else: ?>
                                <a href="reopen_case.php?id=<?php echo $case['id']; ?>" class="btn btn-primary" 
                                    onclick="return confirm('Are you sure you want to reopen this case?')">
                                    <i class='bx bx-refresh'></i> Reopen Case
                                </a>
                                <div class="alert alert-success d-inline-flex align-items-center ms-2 mb-0 py-2">
                                    <i class='bx bx-check-circle me-2'></i> This case is closed
                                </div>
                            <?php endif; ?>
                            <a href="index.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back'></i> Back to Cases
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Back to Cases
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('file');
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
        document.getElementById('file').addEventListener('change', function(e) {
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
    </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Convert all timestamps to user's local time
    const timestampElements = document.querySelectorAll('[data-timestamp]');
    
    timestampElements.forEach(function(element) {
        const timestamp = parseInt(element.getAttribute('data-timestamp')) * 1000; // Convert to milliseconds
        const date = new Date(timestamp);
        
        // Format: MM/DD/YYYY HH:MM (24-hour format)
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