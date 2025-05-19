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
    <title>Delete Case</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <!-- <img src="../images/logo.png" alt="Logo" class="img-fluid me-2" style="max-height: 40px;"> -->
                <span class="fw-bold text-white">Blackwood & Associates</span>
                <span class="ms-2">Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php
                $caseId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
                $stmt = $conn->prepare("SELECT * FROM cases WHERE id = :id");
                $stmt->execute(['id' => $caseId]);
                
                if($case = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if($case['defendent'] === $_SESSION['username']) {
                        ?>
                        <div class="alert alert-warning">
                            <i class='bx bx-error'></i> You don't have permission to delete this case.
                        </div>
                        <a href="index.php" class="btn btn-primary">
                            <i class='bx bx-arrow-back'></i> Return to Cases
                        </a>
                        <?php
                    } else {
                        ?>
                        <div class="card shadow-lg">
                            <div class="card-header bg-danger text-white">
                                <h3 class="mb-0"><i class='bx bx-trash'></i> Delete Confirmation</h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger">
                                    <i class='bx bx-error-circle'></i> This action cannot be undone!
                                </div>
                                
                                <h5>Are you sure you want to delete case:</h5>
                                <p class="lead"><?php echo htmlspecialchars($case['caseid']); ?></p>
                                
                                <form method="POST" action="deleteconfirm.php" class="mt-4">
                                    <input type="hidden" value="<?php echo $case['id']; ?>" name="caseid">
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="submit" class="btn btn-danger btn-lg">
                                            <i class='bx bx-trash'></i> Yes, Delete Case
                                        </button>
                                        <a href="index.php" class="btn btn-secondary btn-lg">
                                            <i class='bx bx-x'></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
