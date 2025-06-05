<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Database connection
require_once "../include/database.php";

// Get all client intakes
try {
    $stmt = $conn->prepare("SELECT * FROM client_intake ORDER BY intake_date DESC");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Client Intakes - Court System</title>
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
                <!-- <img src="../images/logo.png" alt="Logo" class="img-fluid" style="max-height: 40px;"> -->
                <span class="fw-bold text-white">Blackwood & Associates</span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Client Intake Records</h4>
                        <div>
                            <a href="index.php" class="btn btn-primary me-2">New Client Intake</a>
                            <a href="/login/home.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Back to Dashboard</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'success'): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class='bx bx-check-circle'></i> Client and all associated files have been permanently deleted.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['delete_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class='bx bx-error-circle'></i> Error deleting client. Please try again or contact administrator.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table id="intakeTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Case Type</th>
                                        <th>Intake Date</th>
                                        <th>Intake By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($result) > 0) {
                                        foreach($result as $row) {
                                            echo "<tr>";
                                            echo "<td>" . $row['id'] . "</td>";
                                            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['case_type']) . "</td>";
                                            echo "<td>" . date('M d, Y h:i A', strtotime($row['intake_date'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['intake_by']) . "</td>";
                                            echo "<td>
                                                <a href='clients/profile.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary' title='Client Profile'><i class='bx bx-user'></i> Profile</a>
                                                <a href='view_details.php?id=" . $row['id'] . "' class='btn btn-sm btn-info' title='View Details'><i class='bx bx-show'></i> View</a>
                                                <a href='edit_intake.php?id=" . $row['id'] . "' class='btn btn-sm btn-warning' title='Edit'><i class='bx bx-edit'></i> Edit</a>
                                                <button type='button' class='btn btn-sm btn-danger' title='Delete Client' onclick='confirmDelete(" . $row['id'] . ", \"" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) . "\")'><i class='bx bx-trash'></i> Delete</button>
                                            </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No client intake records found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class='bx bx-warning'></i> Permanent Deletion Warning
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">
                        <i class='bx bx-error-circle'></i> 
                        <strong>This action cannot be undone!</strong>
                    </div>
                    <p>You are about to permanently delete:</p>
                    <ul>
                        <li><strong>Client:</strong> <span id="clientName"></span></li>
                        <li><strong>All client information</strong> from the database</li>
                        <li><strong>All uploaded documents</strong> and files</li>
                        <li><strong>Client folder</strong> and all contents</li>
                    </ul>
                    <p class="text-danger fw-bold">This deletion is permanent and cannot be recovered.</p>
                    <p>Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class='bx bx-x'></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class='bx bx-trash'></i> Yes, Delete Permanently
                    </button>
                </div>
                </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#intakeTable').DataTable({
                "order": [[ 3, "desc" ]],
                "pageLength": 25
            });
        });

        let clientIdToDelete = null;

        function confirmDelete(clientId, clientName) {
            clientIdToDelete = clientId;
            document.getElementById('clientName').textContent = clientName;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (clientIdToDelete) {
                // Show loading state
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Deleting...';
                this.disabled = true;
                
                // Redirect to delete handler
                window.location.href = 'delete_client.php?id=' + clientIdToDelete;
            }
        });
    </script>
</body>
</html>