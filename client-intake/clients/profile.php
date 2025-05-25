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
                                Document uploaded successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['upload_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php
                                $error_msg = "Error uploading document. Please try again.";
                                if($_GET['upload_error'] == '2') $error_msg = "File size too large. Maximum size is 20MB.";
                                if($_GET['upload_error'] == '3') $error_msg = "File type not allowed. Please upload PDF, DOC, DOCX, JPG, PNG, GIF, TXT, or RTF files.";
                                if($_GET['upload_error'] == '4') $error_msg = "Failed to save file. Please check permissions and try again.";
                                echo $error_msg;
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['delete_success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                Document deleted successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['folder_created'])): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                Client folder created successfully!
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
                                                    if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = 'bx-image';
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="upload_document.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                        <div class="mb-3">
                            <label for="document" class="form-label">Select Document</label>
                            <input type="file" class="form-control" id="document" name="document" required>
                            <div class="form-text">
                                <strong>Allowed file types:</strong> PDF, DOC, DOCX, JPG, PNG, GIF, TXT, RTF<br>
                                <strong>Maximum file size:</strong> 20MB
                            </div>
                        </div>
                        <div class="alert alert-info" role="alert">
                            <i class='bx bx-info-circle'></i> 
                            <strong>Large File Upload:</strong> For files larger than 25MB, the upload may take several minutes. 
                            Please be patient and do not refresh the page during upload.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <span class="upload-text">Upload Document</span>
                            <span class="upload-spinner d-none">
                                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                Uploading...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show upload progress for large files
        document.querySelector('form[action="upload_document.php"]').addEventListener('submit', function() {
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadText = uploadBtn.querySelector('.upload-text');
            const uploadSpinner = uploadBtn.querySelector('.upload-spinner');
            
            uploadText.classList.add('d-none');
            uploadSpinner.classList.remove('d-none');
            uploadBtn.disabled = true;
        });

        // File size validation
        document.getElementById('document').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 20 * 1024 * 1024; // 20MB (which is 20,480 KB)
                if (file.size > maxSize) {
                    alert('File size exceeds 20MB limit. Please choose a smaller file.');
                    this.value = '';
                    return;
                }
                
                // Show warning for large files
                if (file.size > 25 * 1024 * 1024) { // 25MB
                    const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                    if (!confirm(`This is a large file (${sizeInMB}MB). Upload may take several minutes. Continue?`)) {
                        this.value = '';
                    }
                }
            }
        });
    </script>
</body>
</html>