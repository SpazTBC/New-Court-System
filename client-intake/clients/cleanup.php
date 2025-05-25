<?php
session_start();
// Check if user is logged in and has proper permissions
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Database connection
require_once "../../include/database.php";

// Check if user is an attorney or admin
$stmt = $conn->prepare("SELECT job FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user['job'] !== "Attorney" && $user['job'] !== "Admin") {
    header("Location: /login/home.php");
    exit();
}

// Get all client IDs from database
$stmt = $conn->prepare("SELECT id FROM client_intake");
$stmt->execute();
$valid_clients = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Scan for client folders
$orphaned_folders = [];
$valid_folders = [];

if ($handle = opendir('.')) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && is_dir($entry) && strpos($entry, 'client_') === 0) {
            $client_id = str_replace('client_', '', $entry);
            if (is_numeric($client_id)) {
                if (in_array($client_id, $valid_clients)) {
                    $valid_folders[] = $entry;
                } else {
                    $orphaned_folders[] = $entry;
                }
            }
        }
    }
    closedir($handle);
}

$menu = "CLIENTS";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Orphaned Folders - Court System</title>
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
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow border-danger">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class='bx bx-error-circle'></i> Cleanup Orphaned Folders</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger" role="alert">
                            <strong>Warning:</strong> This operation will permanently delete folders and all their contents. 
                            Make sure you have backups before proceeding.
                        </div>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-success">Valid Folders (<?php echo count($valid_folders); ?>)</h5>
                                <?php if (empty($valid_folders)): ?>
                                    <p class="text-muted">No valid folders found.</p>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <?php foreach($valid_folders as $folder): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($folder); ?>
                                                <span class="badge bg-success rounded-pill">Valid</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <h5 class="text-danger">Orphaned Folders (<?php echo count($orphaned_folders); ?>)</h5>
                                <?php if (empty($orphaned_folders)): ?>
                                    <p class="text-success">No orphaned folders found. System is clean!</p>
                                <?php else: ?>
                                    <ul class="list-group mb-3">
                                        <?php foreach($orphaned_folders as $folder): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($folder); ?>
                                                <span class="badge bg-danger rounded-pill">Orphaned</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <form action="process_cleanup.php" method="post" onsubmit="return confirmCleanup()">
                                        <input type="hidden" name="action" value="delete_orphaned">
                                        <button type="submit" class="btn btn-danger">
                                            <i class='bx bx-trash'></i> Delete All Orphaned Folders
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="bulk_operations.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back'></i> Back to Bulk Operations
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmCleanup() {
            return confirm('Are you absolutely sure you want to delete all orphaned folders? This action cannot be undone!\n\nFolders to be deleted: <?php echo count($orphaned_folders); ?>');
        }
    </script>
</body>
</html>