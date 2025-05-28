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
            $error_message = "File is too large. Maximum file size is 100MB.";
            break;
        case 'type':
            $error_message = "Invalid file type. Please upload PDF, DOC, DOCX, JPG, PNG, TXT, MP4, AVI, or MOV files only.";
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

// Get evidence files for this case from existing evidence table
$evidence_stmt = $conn->prepare("SELECT * FROM evidence WHERE id = ?");
$evidence_stmt->execute([$caseId]);
$evidence_files = $evidence_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
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
        
        <div class="row">
            <div class="col-md-4">
                <!-- Case Info Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class='bx bx-file'></i> Case Information</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title">Case #<?php echo htmlspecialchars($case['caseid']); ?></h6>
                        <p class="card-text">
                            <strong>Assigned User:</strong> <?php echo htmlspecialchars($case['assigneduser']); ?><br>
                            <strong>Defendant:</strong> <?php echo htmlspecialchars($case['defendent']); ?><br>
                            <strong>Type:</strong> <?php echo htmlspecialchars($case['type']); ?><br>
                            <strong>Status:</strong> 
                            <span class="badge bg-<?php echo $case['status'] == 'pending' ? 'warning' : ($case['status'] == 'closed' ? 'success' : 'primary'); ?>">
                                <?php echo htmlspecialchars(ucfirst($case['status'])); ?>
                            </span><br>
                            <strong>Assigned Date:</strong> <?php echo $case['assigned'] ? date('M j, Y', strtotime($case['assigned'])) : 'N/A'; ?>
                            <?php if (!empty($case['supervisor'])): ?>
                                <br><strong>Supervisor:</strong> <?php echo htmlspecialchars($case['supervisor']); ?>
                            <?php endif; ?>
                        </p>
                        <div class="d-grid gap-2">
                            <a href="modify.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-outline-warning btn-sm">
                                <i class='bx bx-edit'></i> Modify Case
                            </a>
                            <a href="delete.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-outline-danger btn-sm">
                                <i class='bx bx-trash'></i> Delete Case
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Case Details Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class='bx bx-file-blank'></i> Case Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="border p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($case['details'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Evidence Upload Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class='bx bx-upload'></i> Upload Evidence</h5>
                    </div>
                    <div class="card-body">
                        <form action="upload_evidence.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="case_id" value="<?php echo $caseId; ?>">
                            <input type="hidden" name="character_name" value="<?php echo htmlspecialchars($characterName); ?>">
                            
                            <div class="mb-3">
                                <label for="evidence_file" class="form-label">Select Evidence File</label>
                                <input type="file" class="form-control" id="evidence_file" name="evidence_file" required>
                                <div class="form-text">
                                    Supported formats: PDF, DOC, DOCX, JPG, PNG, TXT, MP4, AVI, MOV (Max: 100MB)
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class='bx bx-upload'></i> Upload Evidence
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Evidence List Card -->
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class='bx bx-file'></i> Case Evidence</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Get evidence files for this case - handle comma-separated files
                        $evidence_stmt = $conn->prepare("SELECT file FROM evidence WHERE id = ?");
                        $evidence_stmt->execute([$caseId]);
                        $evidence_data = $evidence_stmt->fetchColumn();

                        if ($evidence_data) {
                            // Parse the comma-separated evidence files
                            $evidence_files_list = array_filter(array_map('trim', explode(',', $evidence_data)));
                            
                            if (!empty($evidence_files_list)) {
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <th>File Type</th>
                                                <th>File Size</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($evidence_files_list as $index => $file_path): ?>
                                                <?php 
                                                $file_path = trim($file_path);
                                                if (empty($file_path)) continue;
                                                
                                                $filename = basename($file_path);
                                                $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                                $file_exists = file_exists($file_path);
                                                $file_size = $file_exists ? filesize($file_path) : 0;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <i class='bx bx-file'></i>
                                                        <?php echo htmlspecialchars($filename); ?>
                                                        <?php if (!$file_exists): ?>
                                                            <span class="badge bg-danger ms-2">File Missing</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo strtoupper($file_extension); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($file_exists): ?>
                                                            <?php echo number_format($file_size / 1024, 2); ?> KB
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($file_exists): ?>
                                                            <div class="btn-group" role="group">
                                                                <a href="view_evidence.php?case_id=<?php echo $caseId; ?>&file_index=<?php echo $index; ?>&character_name=<?php echo urlencode($characterName); ?>" 
                                                                   class="btn btn-sm btn-outline-info" title="View">
                                                                    <i class='bx bx-show'></i>
                                                                </a>
                                                                <a href="download_evidence.php?case_id=<?php echo $caseId; ?>&file_index=<?php echo $index; ?>&character_name=<?php echo urlencode($characterName); ?>" 
                                                                   class="btn btn-sm btn-outline-primary" title="Download">
                                                                    <i class='bx bx-download'></i>
                                                                </a>
                                                                <?php if (in_array($characterJob, ['admin', 'judge'])): ?>
                                                                    <a href="delete_evidence.php?case_id=<?php echo $caseId; ?>&file_index=<?php echo $index; ?>&character_name=<?php echo urlencode($characterName); ?>" 
                                                                       class="btn btn-sm btn-outline-danger" title="Delete"
                                                                       onclick="return confirm('Are you sure you want to delete this evidence file?')">
                                                                        <i class='bx bx-trash'></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">File not found</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Quick Preview for Images -->
                                <?php 
                                $image_files = [];
                                foreach ($evidence_files_list as $index => $file_path) {
                                    $file_path = trim($file_path);
                                    if (empty($file_path) || !file_exists($file_path)) continue;
                                    
                                    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                    $is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp']);
                                    
                                    if ($is_image) {
                                        $image_files[] = ['path' => $file_path, 'index' => $index, 'name' => basename($file_path)];
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
                                                        <a href="view_evidence.php?case_id=<?php echo $caseId; ?>&file_index=<?php echo $image['index']; ?>&character_name=<?php echo urlencode($characterName); ?>" 
                                                           class="btn btn-sm btn-primary w-100">
                                                            <i class='bx bx-eye'></i> View
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php
                            } else {
                                ?>
                                <div class="text-center py-4">
                                    <i class='bx bx-file display-4 text-muted'></i>
                                    <p class="text-muted mt-2">No evidence files uploaded yet.</p>
                                </div>
                                <?php
                            }
                        } else {
                            ?>
                            <div class="text-center py-4">
                                <i class='bx bx-file display-4 text-muted'></i>
                                <p class="text-muted mt-2">No evidence files uploaded yet.</p>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <a href="index.php?character_name=<?php echo urlencode($characterName); ?>" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to Cases
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- In your evidence upload form, add this JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('evidence_file');
        const uploadButton = document.querySelector('button[type="submit"]');
        const maxSize = 100 * 1024 * 1024; // 100MB in bytes
        
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    // Check file size
                    if (file.size > maxSize) {
                        // Show error message
                        showErrorMessage(`File "${file.name}" is too large. Maximum file size is 100MB. Selected file is ${(file.size / 1024 / 1024).toFixed(2)}MB.`);
                        
                        // Clear the input
                        fileInput.value = '';
                        
                        // Disable upload button
                        uploadButton.disabled = true;
                        uploadButton.innerHTML = '<i class="bx bx-error-circle"></i> File Too Large';
                        uploadButton.classList.remove('btn-success');
                        uploadButton.classList.add('btn-danger');
                        
                        return;
                    }
                    
                    // Check file type
                    const allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt', 'mp4', 'avi', 'mov', 'mp3', 'wav'];
                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    
                    if (!allowedExtensions.includes(fileExtension)) {
                        showErrorMessage(`File type "${fileExtension}" is not allowed. Please select: PDF, DOC, DOCX, JPG, PNG, TXT, MP4, AVI, MOV, MP3, or WAV files.`);
                        
                        // Clear the input
                        fileInput.value = '';
                        
                        // Disable upload button
                        uploadButton.disabled = true;
                        uploadButton.innerHTML = '<i class="bx bx-error-circle"></i> Invalid File Type';
                        uploadButton.classList.remove('btn-success');
                        uploadButton.classList.add('btn-danger');
                        
                        return;
                    }
                    
                    // File is valid
                    uploadButton.disabled = false;
                    uploadButton.innerHTML = '<i class="bx bx-upload"></i> Upload Evidence';
                    uploadButton.classList.remove('btn-danger');
                    uploadButton.classList.add('btn-success');
                    
                    // Show file info
                    showSuccessMessage(`File "${file.name}" selected (${(file.size / 1024 / 1024).toFixed(2)}MB). Ready to upload.`);
                }
            });
        }
        
        function showErrorMessage(message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.file-validation-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create error alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show file-validation-alert mt-2';
            alertDiv.innerHTML = `
                <i class="bx bx-error-circle"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert after file input
            fileInput.parentNode.insertBefore(alertDiv, fileInput.nextSibling);
        }
        
        function showSuccessMessage(message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.file-validation-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create success alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show file-validation-alert mt-2';
            alertDiv.innerHTML = `
                <i class="bx bx-check-circle"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert after file input
            fileInput.parentNode.insertBefore(alertDiv, fileInput.nextSibling);
        }
    });
    </script>
</body>
</html>