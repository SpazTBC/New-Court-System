<?php
session_start();
$menu = "CASES";
include("../include/database.php");

// Get case ID from URL
$caseId = isset($_GET['id']) ? $_GET['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Case</title>
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

    <div class="container py-4">
        <?php
        $stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
        $stmt->execute([$caseId]);
        $case = $stmt->fetch();
        
        if($case):
        ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0"><i class='bx bx-edit'></i> Modify Case #<?php echo htmlspecialchars($case['caseid']); ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="modified.php" class="row g-3">
                            <input type="hidden" value="<?php echo $case['id']; ?>" name="id">
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['caseid']); ?>" name="caseid" readonly>
                                    <label>Case Number</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['assigneduser']); ?>" name="assigneduser">
                                    <label>Assigned User</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['assigned']); ?>" name="assigned">
                                    <label>Date Assigned</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['type']); ?>" name="type">
                                    <label>Case Type</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" style="height: 100px" name="details"><?php echo htmlspecialchars($case['details']); ?></textarea>
                                    <label>Case Details</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['supervisor']); ?>" name="supervisor">
                                    <label>Supervisor</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                    <i class='bx bx-save'></i> Save Changes
                                </button>
                                <a href="view.php?id=<?php echo $case['id']; ?>" class="btn btn-secondary btn-lg">
                                    <i class='bx bx-arrow-back'></i> Back to Case
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
