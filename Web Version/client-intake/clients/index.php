<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Database connection
require_once "../../include/database.php";

// Get all clients with their folder information
try {
    $stmt = $conn->prepare("SELECT * FROM client_intake ORDER BY last_name, first_name");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Check which clients have folders and count documents
$client_data = [];
foreach($clients as $client) {
    $client_folder = "client_" . $client['id'];
    $documents_path = $client_folder . "/signed_documents";
    
    $folder_exists = is_dir($client_folder);
    $document_count = 0;
    
    if($folder_exists && is_dir($documents_path)) {
        $files = scandir($documents_path);
        $document_count = count($files) - 2; // Subtract . and ..
    }
    
    $client_data[] = [
        'client' => $client,
        'folder_exists' => $folder_exists,
        'document_count' => $document_count
    ];
}

$menu = "CLIENTS";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="../../css/dark-mode.css" rel="stylesheet">
    <script src="../../js/dark-mode.js"></script>
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
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Client Management</h4>
                        <div>
                            <a href="../index.php" class="btn btn-primary me-2">New Client Intake</a>
                            <a href="../view_intakes.php" class="btn btn-info me-2">View All Intakes</a>
                            <a href="/login/home.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Back to Dashboard</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="clientTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Client ID</th>
                                        <th>Name</th>
                                        <th>Case Type</th>
                                        <th>Email</th>
                                        <th>Documents</th>
                                        <th>Folder Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($client_data as $data): ?>
                                        <tr>
                                            <td><?php echo $data['client']['id']; ?></td>
                                            <td><?php echo htmlspecialchars($data['client']['first_name'] . ' ' . $data['client']['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($data['client']['case_type'])); ?></td>
                                            <td><?php echo htmlspecialchars($data['client']['email']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $data['document_count']; ?> files</span>
                                            </td>
                                            <td>
                                                <?php if($data['folder_exists']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Folder</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="profile.php?id=<?php echo $data['client']['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class='bx bx-user'></i> Profile
                                                </a>
                                                <?php if(!$data['folder_exists']): ?>
                                                    <a href="create_folder.php?id=<?php echo $data['client']['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class='bx bx-folder-plus'></i> Create Folder
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#clientTable').DataTable({
                "order": [[ 1, "asc" ]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>