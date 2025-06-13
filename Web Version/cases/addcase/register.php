<?php
session_start();
include("../../include/database.php");

if(isset($_POST['submit'])) {
    $casenum = $_POST['casenum'];
    $defendent = $_POST['defendent'];
    $plaintiff = $_POST['plaintiff'] ?? ''; // Handle optional plaintiff
    $case_details = $_POST['case_details'];
    $case_type = $_POST['case_type'];
    $case_status = $_POST['case_status'] ?? 'Open';
    $hearing_date = $_POST['hearing_date'] ?? null;
    $courtroom = $_POST['courtroom'] ?? '';
    $hearing_notes = $_POST['hearing_notes'] ?? '';
    $shared1 = $_POST['shared1'] ?? '';
    $shared2 = $_POST['shared2'] ?? '';
    $shared3 = $_POST['shared3'] ?? '';
    $shared4 = $_POST['shared4'] ?? '';
    
    $assigneduser = $_SESSION['username'];
    $assigned = date('Y-m-d H:i:s');
    
    try {
        // Check if plaintiff column exists, if not add it
        $checkColumn = $conn->query("SHOW COLUMNS FROM cases LIKE 'plaintiff'");
        if ($checkColumn->rowCount() == 0) {
            $conn->exec("ALTER TABLE cases ADD COLUMN plaintiff VARCHAR(255) AFTER defendent");
        }
        
        $stmt = $conn->prepare("INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, plaintiff, type, status, hearing_date, courtroom, hearing_notes, shared01, shared02, shared03, shared04) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $casenum,
            $assigneduser,
            $assigned,
            $case_details,
            $defendent,
            $plaintiff,
            $case_type,
            $case_status,
            $hearing_date,
            $courtroom,
            $hearing_notes,
            $shared1,
            $shared2,
            $shared3,
            $shared4
        ]);
        
        header("Location: ../index.php?success=case_created");
        exit();
        
    } catch(PDOException $e) {
        header("Location: index.php?error=database_error");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../../css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Add A Case</title>
</head>
<body>
    <div id="menu">
        <div class='logo'>
            <!-- <img src="../../images/logo.png"/> -->
            <span class="fw-bold text-white">Blackwood & Associates</span>
        </div> <!-- logo end -->
        <?php include("../../include/menu.php"); ?>
    </div> <!-- MENU -->

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Case Created Successfully</h4>
                    </div>
                    <div class="card-body text-center">
                        <i class="bx bx-check-circle text-success" style="font-size: 4rem;"></i>
                        <h5 class="mt-3">Case has been created successfully!</h5>
                        <p class="text-muted">Case Number: <?php echo htmlspecialchars($casenum ?? 'N/A'); ?></p>
                        <a href="../index.php" class="btn btn-primary">
                            <i class="bx bx-arrow-back"></i> Back to Cases
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>