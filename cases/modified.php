<?php
session_start();
$menu = "CASES";
include("../include/database.php");

// Validate and sanitize inputs
$id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
$caseid = filter_var($_POST['caseid'], FILTER_SANITIZE_STRING);
$assigned = filter_var($_POST['assigneduser'], FILTER_SANITIZE_STRING);
$details = filter_var($_POST['details'], FILTER_SANITIZE_STRING);
$type = filter_var($_POST['type'], FILTER_SANITIZE_STRING);
$supervisor = filter_var($_POST['supervisor'], FILTER_SANITIZE_STRING);

date_default_timezone_set('America/Chicago');
$date = date("m-d-Y h:i:s A");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Update Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <img src="../images/logo.png" alt="Logo" class="img-fluid me-2" style="max-height: 40px;">
                <span>Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0"><i class='bx bx-check-circle'></i> Update Status</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stmt = $conn->prepare("UPDATE cases SET assigneduser = ?, details = ?, type = ?, supervisor = ? WHERE id = ?");
                            $stmt->execute([$assigned, $details, $type, $supervisor, $id]);

                            if (!empty($supervisor)) {
                                $stmt = $conn->prepare("UPDATE users SET supervisorjob = '1' WHERE username = ?");
                                $stmt->execute([$supervisor]);
                            }
                            ?>
                            <div class="alert alert-success mb-4">
                                <i class='bx bx-check'></i> Case updated successfully
                            </div>

                            <div class="card mb-4 bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Update Details</h5>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Case ID:</span>
                                            <strong><?php echo htmlspecialchars($caseid); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Assigned To:</span>
                                            <strong><?php echo htmlspecialchars($assigned); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Type:</span>
                                            <strong><?php echo htmlspecialchars($type); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Supervisor:</span>
                                            <strong><?php echo htmlspecialchars($supervisor); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Last Modified:</span>
                                            <strong><?php echo $date; ?></strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-primary btn-lg">
                                    <i class='bx bx-home'></i> Return to Cases
                                </a>
                                <a href="view.php?id=<?php echo (int)$id; ?>" class="btn btn-outline-secondary">
                                    <i class='bx bx-show'></i> View Case
                                </a>
                            </div>
                            <?php
                        } catch(PDOException $e) {
                            ?>
                            <div class="alert alert-danger">
                                <i class='bx bx-error'></i> Update failed: <?php error_log($e->getMessage()); ?>
                            </div>
                            <a href="index.php" class="btn btn-primary">
                                <i class='bx bx-arrow-back'></i> Return to Cases
                            </a>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
