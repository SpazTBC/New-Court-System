<?php
session_start();
// Check if user is logged in
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

if($user['job'] !== "Attorney") {
    header("Location: /login/home.php");
    exit();
}

$menu = "CLIENTS";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Client Operations - Court System</title>
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
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class='bx bx-cog'></i> Bulk Client Operations</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning" role="alert">
                            <i class='bx bx-error-circle'></i> <strong>Warning:</strong> These operations affect multiple client records. Use with caution.
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Create Missing Folders</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Create folders for all clients who don't have one yet.</p>
                                        <a href="process_bulk.php?action=create_folders" class="btn btn-primary" 
                                           onclick="return confirm('Create folders for all clients without folders?')">
                                            <i class='bx bx-folder-plus'></i> Create All Folders
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">Generate Report</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Generate a comprehensive report of all client folders and documents.</p>
                                        <a href="generate_report.php" class="btn btn-info">
                                            <i class='bx bx-file-blank'></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0">Cleanup Operations</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-danger">
                                            <strong>Danger Zone:</strong> These operations can permanently delete data.
                                        </p>
                                        <a href="cleanup.php" class="btn btn-outline-danger">
                                            <i class='bx bx-trash'></i> Cleanup Orphaned Folders
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="index.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back'></i> Back to Client Management
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>