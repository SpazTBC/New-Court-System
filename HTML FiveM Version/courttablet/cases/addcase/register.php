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
                    <div class="card-header bg-primary text-white">
                        <h4>Add New Case</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        session_start();
                        $menu = "CASE";
                        include('../../include/database.php');

                        if(isset($_POST['submit']))
                        {
                            $casenum = $_POST['casenum'];
                            $user = $_SESSION['username'];
                            $username2 = $_SESSION['username'];
                            $date = $_POST['date'];
                            $details = $_POST['details'];
                            $shared1 = $_POST['shared1'] ?? '';
                            $shared2 = $_POST['shared2'] ?? '';
                            $shared3 = $_POST['shared3'] ?? '';
                            $shared4 = $_POST['shared4'] ?? '';
                            $defendent = $_POST['defendent'];
                            
                            if(strpos($casenum, 'CF') !== false) {
                                $type = 'CRIMINAL';
                            } elseif(strpos($casenum, 'CV') !== false) {
                                $type = 'CIVIL';
                            } elseif(strpos($casenum, 'F') !== false) {
                                $type = 'FAMILY';
                            } else {
                                $type = 'UNKNOWN';
                            }

                            $query = "INSERT INTO cases (caseid,assigneduser,assigned,details,shared01,shared02,shared03,shared04,type,defendent) 
                            VALUES (:caseid,:assigneduser,:assigned,:details,:shared01,:shared02,:shared03,:shared04,:type,:defendant)";
                            $query_run = $conn->prepare($query);

                            $data = [
                                ':caseid' => $casenum,
                                ':assigneduser' => $user,
                                ':assigned' => $date,
                                ':details' => $details,
                                ':shared01' => $shared1,
                                ':shared02' => $shared2,
                                ':shared03' => $shared3,
                                ':shared04' => $shared4,
                                ':type' => $type,
                                ':defendant' => $defendent,
                            ];
                            $query_execute = $query_run->execute($data);

                            if($query_execute)
                            {
                                echo '<div class="alert alert-success">Case added successfully! <a href="../index.php" class="btn btn-sm btn-primary">Return to Home</a></div>';
                            }
                            else
                            {
                                echo '<div class="alert alert-danger">Failed to add case. Please try again.</div>';
                            }
                        }
                        ?>

                        <!-- Case Registration Form -->
                        <?php if(!isset($_POST['submit']) || !$query_execute): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="casenum" class="form-label">Case Number</label>
                                <input type="text" class="form-control" id="casenum" name="casenum" required 
                                       placeholder="Format: CF for Criminal, CV for Civil, F for Family">
                                <small class="text-muted">Example: CF12345, CV54321, F98765</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="date" class="form-label">Date Assigned</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="defendent" class="form-label">Defendant Name</label>
                                <input type="text" class="form-control" id="defendent" name="defendent" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="details" class="form-label">Case Details</label>
                                <textarea class="form-control" id="details" name="details" rows="4" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Shared With</label>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <input type="text" class="form-control" name="shared1" placeholder="User 1">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" class="form-control" name="shared2" placeholder="User 2">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" class="form-control" name="shared3" placeholder="User 3">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <input type="text" class="form-control" name="shared4" placeholder="User 4">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="submit" class="btn btn-primary">Register Case</button>
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
    <?php include(base64_decode('Li4vLi4vaW5jbHVkZS9mb290ZXIucGhw')); ?>
</body>
</html>