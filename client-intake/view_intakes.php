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
                                                <a href='view_details.php?id=" . $row['id'] . "' class='btn btn-sm btn-info'><i class='bx bx-show'></i> View</a>
                                                <a href='edit_intake.php?id=" . $row['id'] . "' class='btn btn-sm btn-warning'><i class='bx bx-edit'></i> Edit</a>
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
    </script>
</body>
</html>