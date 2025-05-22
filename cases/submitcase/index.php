<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$menu = "CASE";
include('../../include/database.php');

// Check if user is police
$stmt = $conn->prepare("SELECT job FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$user = $stmt->fetch();
if (strtolower($user['job']) !== 'police') {
    // Redirect non-police users
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../../css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <title>Submit A Case</title>
</head>
<body>
    <div id="menu">
        <div class='logo'>
            <span class="fw-bold text-white">Blackwood & Associates</span>
        </div>
        <?php include("../../include/menu.php"); ?>
    </div>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Submit Case for Approval</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        if(isset($_POST['submit']))
                        {
                            $user = $_SESSION['username'];
                            $details = $_POST['details'];
                            $defendent = $_POST['defendent'];
                            
                            // Check if evidence field exists in the form
                            $evidence = isset($_POST['evidence']) ? $_POST['evidence'] : '';
                            
                            // Set temporary values for required fields - will be updated by AG
                            $tempCaseNum = 'PENDING-' . time();
                            $currentDate = date('Y-m-d');

                            // Check if the evidence column exists in the cases table
                            try {
                                // First, check if the evidence column exists
                                $checkColumn = $conn->query("SHOW COLUMNS FROM cases LIKE 'evidence'");
                                $evidenceColumnExists = $checkColumn->rowCount() > 0;
                                
                                if ($evidenceColumnExists) {
                                    // If evidence column exists, include it in the query
                                    $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, evidence, status, type) 
                                    VALUES (:caseid, :assigneduser, :assigned, :details, :defendant, :evidence, 'pending', 'PENDING')";
                                    $data = [
                                        ':caseid' => $tempCaseNum,
                                        ':assigneduser' => $user,
                                        ':assigned' => $currentDate,
                                        ':details' => $details,
                                        ':defendant' => $defendent,
                                        ':evidence' => $evidence
                                    ];
                                } else {
                                    // If evidence column doesn't exist, exclude it from the query
                                    $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, status, type) 
                                    VALUES (:caseid, :assigneduser, :assigned, :details, :defendant, 'pending', 'PENDING')";
                                    $data = [
                                        ':caseid' => $tempCaseNum,
                                        ':assigneduser' => $user,
                                        ':assigned' => $currentDate,
                                        ':details' => $details,
                                        ':defendant' => $defendent
                                    ];
                                }
                                
                                $query_run = $conn->prepare($query);
                                $query_execute = $query_run->execute($data);

                                if($query_execute)
                                {
                                    echo '<div class="alert alert-success">
                                        <h5><i class="bx bx-check-circle"></i> Case Submitted Successfully</h5>
                                        <p>Your case has been submitted and is pending approval from the Attorney General.</p>
                                        <p>The Attorney General will assign an official case number and date.</p>
                                        <a href="../index.php" class="btn btn-sm btn-primary mt-2">Return to Cases</a>
                                    </div>';
                                }
                                else
                                {
                                    echo '<div class="alert alert-danger">Failed to submit case. Please try again.</div>';
                                }
                            } catch (PDOException $e) {
                                echo '<div class="alert alert-danger">Database Error: ' . $e->getMessage() . '</div>';
                            }
                        }
                        ?>

                        <!-- Case Submission Form -->
                        <?php if(!isset($_POST['submit']) || (isset($query_execute) && !$query_execute)): ?>
                        <form method="POST" action="">
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle"></i> The Attorney General will assign an official case number and date upon approval.
                            </div>
                            
                            <div class="mb-3">
                                <label for="defendent" class="form-label">Defendant Name</label>
                                <input type="text" class="form-control" id="defendent" name="defendent" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="details" class="form-label">Case Details</label>
                                <textarea class="form-control" id="details" name="details" rows="4" required 
                                    placeholder="Provide a detailed description of the case including charges, location, and relevant information"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="evidence" class="form-label">Evidence Description</label>
                                <textarea class="form-control" id="evidence" name="evidence" rows="3" 
                                    placeholder="Describe any evidence collected (you'll be able to upload files after approval)"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="submit" class="btn btn-primary">Submit for Approval</button>
                                <a href="../index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include("../../include/footer.php"); ?>
</body>
</html>