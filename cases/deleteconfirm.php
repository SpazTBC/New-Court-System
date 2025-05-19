<?php
session_start();
$menu = "CASES";
include("../include/database.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Deleted</title>
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

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php
                $case = $_POST['caseid'];
                $stmt = $conn->prepare("DELETE FROM cases WHERE id = ?");
                
                try {
                    $stmt->execute([$case]);
                    ?>
                    <div class="card shadow-lg border-success">
                        <div class="card-body text-center p-5">
                            <i class='bx bx-check-circle text-success' style='font-size: 4rem;'></i>
                            <h2 class="mt-3">Case Deleted Successfully</h2>
                            <p class="text-muted mb-4">Case ID: <?php echo htmlspecialchars($case); ?></p>
                            
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-primary btn-lg">
                                    <i class='bx bx-home'></i> Return to Cases
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                } catch(PDOException $e) {
                    ?>
                    <div class="card shadow-lg border-danger">
                        <div class="card-body text-center p-5">
                            <i class='bx bx-error-circle text-danger' style='font-size: 4rem;'></i>
                            <h2 class="mt-3">Deletion Failed</h2>
                            <p class="text-muted mb-4">There was an error deleting the case.</p>
                            
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-primary btn-lg">
                                    <i class='bx bx-home'></i> Return to Cases
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
