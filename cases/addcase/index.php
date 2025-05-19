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
    <title>Add New Case</title>
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
                        <h3 class="mb-0">Create New Case</h3>
                    </div>
                    <div class="card-body">
                        <!-- Case Number Generation Buttons -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <form method="POST" action="criminal.php">
                                    <button type="submit" name="generate" class="btn btn-danger w-100">
                                        <i class='bx bx-file'></i> Generate Criminal Case
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="POST" action="civil.php">
                                    <button type="submit" name="civil" class="btn btn-info w-100">
                                        <i class='bx bx-file'></i> Generate Civil Case
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="POST" action="family.php">
                                    <button type="submit" name="family" class="btn btn-success w-100">
                                        <i class='bx bx-file'></i> Generate Family Case
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Case Details Form -->
                        <form method="POST" action="register.php" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="casenum" name="casenum" placeholder="Case ID Number" required>
                                        <label for="casenum">Case ID Number</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="user" name="user" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                                        <label for="user">Assigned User</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="date" name="date" value="<?php echo date('m/d/Y h:i:sA', time()); ?>" readonly>
                                        <label for="date">Date</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="details" name="details" style="height: 100px" required></textarea>
                                        <label for="details">Case Details</label>
                                    </div>
                                </div>

                                <!-- Shared Users Section -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="shared1" name="shared1" placeholder="Share with user 1">
                                        <label for="shared1">Share with User 1 (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="shared2" name="shared2" placeholder="Share with user 2">
                                        <label for="shared2">Share with User 2 (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="shared3" name="shared3" placeholder="Share with user 3">
                                        <label for="shared3">Share with User 3 (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="shared4" name="shared4" placeholder="Share with user 4">
                                        <label for="shared4">Share with User 4 (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="defendent" name="defendent" required>
                                        <label for="defendent">Name of Defendant</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" name="submit" class="btn btn-primary btn-lg w-100">
                                        <i class='bx bx-save'></i> Create Case File
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
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

        document.addEventListener('DOMContentLoaded', function() {
    // Criminal Case
    document.querySelector('button[name="generate"]').addEventListener('click', function(e) {
        e.preventDefault();
        const caseNumber = 'CF-' + Math.floor(Math.random() * 900000) + 100000;
        document.getElementById('casenum').value = caseNumber;
    });

    // Civil Case
    document.querySelector('button[name="civil"]').addEventListener('click', function(e) {
        e.preventDefault();
        const caseNumber = 'CV-' + Math.floor(Math.random() * 900000) + 100000;
        document.getElementById('casenum').value = caseNumber;
    });

    // Family Case
    document.querySelector('button[name="family"]').addEventListener('click', function(e) {
        e.preventDefault();
        const caseNumber = 'F-' + Math.floor(Math.random() * 900000) + 100000;
        document.getElementById('casenum').value = caseNumber;
    });
});
    </script>
</body>
</html>
