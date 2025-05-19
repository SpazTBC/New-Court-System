<?php
session_start();
$menu = "CASE";
include("../../include/database.php");

// Check user permissions
$stmt = $conn->prepare("SELECT job FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch();

if ($user['job'] === "Civilian") {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Case Files</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand">
                <img src="../../images/logo.png" alt="Logo" class="img-fluid" style="max-height: 40px;">
            </div>
            <?php include("../../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0"><i class='bx bx-share-alt'></i> Share Case File</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="register.php" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-floating">
                                    <select class="form-select form-control" id="casenum" name="casenum" required>
                                        <option value="">Select a Case</option>
                                        <?php
                                        // First get cases where user is assigned
                                        $stmt = $conn->prepare("SELECT caseid FROM cases WHERE assigneduser = :username");
                                        $stmt->execute(['username' => $_SESSION['username']]);
                                        while($case = $stmt->fetch()): 
                                        ?>
                                            <option value="<?php echo htmlspecialchars($case['caseid']); ?>">
                                                Case: <?php echo htmlspecialchars($case['caseid']); ?>
                                            </option>
                                        <?php 
                                        endwhile;
                                        ?>
                                    </select>
                                        <label for="casenum">Select Case</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="shared1" name="shared1">
                                        <label for="shared1">Share with User 1 (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="shared2" name="shared2">
                                        <label for="shared2">Share with User 2 (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="shared3" name="shared3">
                                        <label for="shared3">Share with User 3 (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="shared4" name="shared4">
                                        <label for="shared4">Share with User 4 (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" name="submit" class="btn btn-primary btn-lg w-100">
                                        <i class='bx bx-share'></i> Update Case File
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-lg mt-4">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0"><i class='bx bx-list-ul'></i> Active Shares</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Case ID</th>
                                        <th>Shared With</th>
                                        <th>Date Shared</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("SELECT * FROM cases WHERE assigneduser = ?");
                                    $stmt->execute([$_SESSION['username']]);
                                    while($case = $stmt->fetch()):
                                        $shared_users = array_filter([
                                            $case['shared01'],
                                            $case['shared02'],
                                            $case['shared03'],
                                            $case['shared04']
                                        ]);
                                        foreach($shared_users as $shared_user):
                                            if(!empty($shared_user)):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                        <td><?php echo htmlspecialchars($shared_user); ?></td>
                                        <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                        <td>
                                            <form method="POST" action="remove_share.php" style="display: inline;">
                                                <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                                <input type="hidden" name="shared_user" value="<?php echo $shared_user; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class='bx bx-x'></i> Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php
                                            endif;
                                        endforeach;
                                    endwhile;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>