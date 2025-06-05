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
                    <div class="card-header bg-primary text-white">
                        <h4>Case Registration Result</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        session_start();
                        include("../../include/connection.php");

                        if (!isset($_SESSION['username'])) {
                            header("Location: ../../login.php");
                            exit();
                        }

                        if (isset($_POST['submit'])) {
                            try {
                                // Get form data
                                $casenum = $_POST['casenum'];
                                $defendent = $_POST['defendent'];
                                $hearing_date = $_POST['hearing_date'];
                                $courtroom = $_POST['courtroom'];
                                $hearing_notes = $_POST['hearing_notes'] ?? '';
                                $shared1 = $_POST['shared1'] ?? '';
                                $shared2 = $_POST['shared2'] ?? '';
                                $shared3 = $_POST['shared3'] ?? '';
                                $shared4 = $_POST['shared4'] ?? '';
                                $creator = $_SESSION['username'];

                                // Insert case with hearing information
                                $stmt = $conn->prepare("INSERT INTO cases (casenum, defendent, hearing_date, courtroom, hearing_notes, hearing_status, shared1, shared2, shared3, shared4, creator, created_at) VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?, ?, ?, ?, NOW())");
                                
                                $stmt->execute([
                                    $casenum,
                                    $defendent,
                                    $hearing_date,
                                    $courtroom,
                                    $hearing_notes,
                                    $shared1,
                                    $shared2,
                                    $shared3,
                                    $shared4,
                                    $creator
                                ]);

                                // Get the case ID for notifications
                                $case_id = $conn->lastInsertId();

                                // Send notifications to shared users (excluding police)
                                $shared_users = array_filter([$shared1, $shared2, $shared3, $shared4]);
                                
                                foreach ($shared_users as $username) {
                                    if (!empty($username)) {
                                        // Check if user is not police
                                        $user_check = $conn->prepare("SELECT job FROM users WHERE username = ?");
                                        $user_check->execute([$username]);
                                        $user_data = $user_check->fetch(PDO::FETCH_ASSOC);
                                        
                                        if ($user_data && strtolower($user_data['job']) !== 'police') {
                                            // Create notification for hearing
                                            $notification_stmt = $conn->prepare("INSERT INTO notifications (username, message, type, case_id, created_at) VALUES (?, ?, 'hearing', ?, NOW())");
                                            $message = "You have been assigned to case #{$casenum} with a hearing scheduled for " . date('M j, Y g:i A', strtotime($hearing_date)) . " in {$courtroom}";
                                            $notification_stmt->execute([$username, $message, $case_id]);
                                        }
                                    }
                                }

                                header("Location: ../index.php?success=case_created");
                                exit();

                            } catch (PDOException $e) {
                                $error = "Error creating case: " . $e->getMessage();
                            }
                        }
                        ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class='bx bx-error'></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                            <a href="index.php" class="btn btn-secondary">Go Back</a>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class='bx bx-check'></i> Case created successfully with hearing scheduled!
                            </div>
                            <a href="../index.php" class="btn btn-primary">View Cases</a>
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