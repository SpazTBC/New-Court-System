<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Database connection
require_once "../../include/database.php";

// Get all clients
try {
    $stmt = $conn->prepare("SELECT * FROM client_intake ORDER BY last_name, first_name");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Generate report data
$report_data = [];
$total_clients = count($clients);
$clients_with_folders = 0;
$total_documents = 0;

foreach($clients as $client) {
    $client_folder = "client_" . $client['id'];
    $documents_path = $client_folder . "/signed_documents";
    
    $folder_exists = is_dir($client_folder);
    $document_count = 0;
    $folder_size = 0;
    
    if($folder_exists) {
        $clients_with_folders++;
        
        if(is_dir($documents_path)) {
            $files = scandir($documents_path);
            foreach($files as $file) {
                if($file != "." && $file != "..") {
                    $document_count++;
                    $folder_size += filesize($documents_path . "/" . $file);
                }
            }
        }
    }
    
    $total_documents += $document_count;
    
    $report_data[] = [
        'client' => $client,
        'folder_exists' => $folder_exists,
        'document_count' => $document_count,
        'folder_size' => $folder_size
    ];
}

// Set headers for CSV download if requested
if(isset($_GET['format']) && $_GET['format'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="client_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Client ID', 'First Name', 'Last Name', 'Email', 'Case Type', 
        'Intake Date', 'Folder Exists', 'Document Count', 'Folder Size (KB)'
    ]);
    
    // CSV data
    foreach($report_data as $data) {
        fputcsv($output, [
            $data['client']['id'],
            $data['client']['first_name'],
            $data['client']['last_name'],
            $data['client']['email'],
            $data['client']['case_type'],
            $data['client']['intake_date'],
            $data['folder_exists'] ? 'Yes' : 'No',
            $data['document_count'],
            number_format($data['folder_size'] / 1024, 2)
        ]);
    }
    
    fclose($output);
    exit();
}

$menu = "CLIENTS";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Report - Court System</title>
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
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Client Management Report</h4>
                        <div>
                            <a href="?format=csv" class="btn btn-light btn-sm me-2">
                                <i class='bx bx-download'></i> Download CSV
                            </a>
                            <a href="bulk_operations.php" class="btn btn-secondary btn-sm">
                                <i class='bx bx-arrow-back'></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Summary Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h3><?php echo $total_clients; ?></h3>
                                        <p class="mb-0">Total Clients</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3><?php echo $clients_with_folders; ?></h3>
                                        <p class="mb-0">With Folders</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3><?php echo $total_clients - $clients_with_folders; ?></h3>
                                        <p class="mb-0">Without Folders</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3><?php echo $total_documents; ?></h3>
                                        <p class="mb-0">Total Documents</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Report -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th>Client ID</th>
                                                                    <th>Name</th>
                                                                    <th>Email</th>
                                                                    <th>Case Type</th>
                                                                    <th>Intake Date</th>
                                                                    <th>Folder Status</th>
                                                                    <th>Documents</th>
                                                                    <th>Folder Size</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach($report_data as $data): ?>
                                                                    <tr>
                                                                        <td><?php echo $data['client']['id']; ?></td>
                                                                        <td><?php echo htmlspecialchars($data['client']['first_name'] . ' ' . $data['client']['last_name']); ?></td>
                                                                        <td><?php echo htmlspecialchars($data['client']['email']); ?></td>
                                                                        <td><?php echo htmlspecialchars(ucfirst($data['client']['case_type'])); ?></td>
                                                                        <td><?php echo date('M d, Y', strtotime($data['client']['intake_date'])); ?></td>
                                                                        <td>
                                                                            <?php if($data['folder_exists']): ?>
                                                                                <span class="badge bg-success">Active</span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-warning">Missing</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td><?php echo $data['document_count']; ?></td>
                                                                        <td><?php echo number_format($data['folder_size'] / 1024, 2); ?> KB</td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>                                    <tr>