<?php
session_start();
include("../../include/database.php");

// Set menu variable for active state
$menu = "HEARINGS";

if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit();
}

// Get current user's job
$user_stmt = $conn->prepare("SELECT job FROM users WHERE username = ?");
$user_stmt->execute([$_SESSION['username']]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
$current_user_job = $user_data['job'] ?? '';

// Only allow non-police users
if (strtolower($current_user_job) === 'police') {
    header("Location: ../index.php?error=access_denied");
    exit();
}

$case_id = $_GET['id'] ?? 0;

// Get case details
$case_stmt = $conn->prepare("SELECT * FROM cases WHERE id = ? AND (shared01 = ? OR shared02 = ? OR shared03 = ? OR shared04 = ? OR assigneduser = ?)");
$username = $_SESSION['username'];
$case_stmt->execute([$case_id, $username, $username, $username, $username, $username]);
$case = $case_stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    header("Location: index.php?error=case_not_found");
    exit();
}

// Handle form submission
if (isset($_POST['update_hearing'])) {
    try {
        $hearing_date = $_POST['hearing_date'];
        $courtroom = $_POST['courtroom'];
        $hearing_notes = $_POST['hearing_notes'] ?? '';
        $hearing_status = $_POST['hearing_status'];

        $update_stmt = $conn->prepare("UPDATE cases SET hearing_date = ?, courtroom = ?, hearing_notes = ?, hearing_status = ? WHERE id = ?");
        $update_stmt->execute([$hearing_date, $courtroom, $hearing_notes, $hearing_status, $case_id]);

        header("Location: index.php?success=hearing_updated");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating hearing: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hearing - Case #<?php echo htmlspecialchars($case['casenum']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="../../css/dark-mode.css" rel="stylesheet">
    <script src="../../js/dark-mode.js"></script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand">
                <span class="fw-bold text-white">Blackwood & Associates</span>
            </div>
            <?php include("../../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class='bx bx-edit'></i> Edit Hearing - Case #<?php echo htmlspecialchars($case['casenum']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class='bx bx-error'></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <strong>Defendant:</strong> <?php echo htmlspecialchars($case['defendent']); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="datetime-local" class="form-control" id="hearing_date" name="hearing_date" 
                                               value="<?php echo date('Y-m-d\TH:i', strtotime($case['hearing_date'])); ?>" required>
                                        <label for="hearing_date"><i class='bx bx-calendar'></i> Hearing Date & Time</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="courtroom" name="courtroom" required>
                                            <option value="">Select Courtroom</option>
                                            <option value="Courtroom A" <?php echo $case['courtroom'] === 'Courtroom A' ? 'selected' : ''; ?>>Courtroom A</option>
                                            <option value="Family Court" <?php echo $case['courtroom'] === 'Family Court' ? 'selected' : ''; ?>>Family Court</option>
                                            <option value="Civil Court" <?php echo $case['courtroom'] === 'Civil Court' ? 'selected' : ''; ?>>Civil Court</option>
                                        </select>
                                        <label for="courtroom"><i class='bx bx-building'></i> Courtroom</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="hearing_status" name="hearing_status" required>
                                            <option value="scheduled" <?php echo $case['hearing_status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                            <option value="completed" <?php echo $case['hearing_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="postponed" <?php echo $case['hearing_status'] === 'postponed' ? 'selected' : ''; ?>>Postponed</option>
                                            <option value="cancelled" <?php echo $case['hearing_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <label for="hearing_status"><i class='bx bx-flag'></i> Hearing Status</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="hearing_notes" name="hearing_notes" style="height: 120px" placeholder="Additional notes about the hearing"><?php echo htmlspecialchars($case['hearing_notes']); ?></textarea>
                                        <label for="hearing_notes"><i class='bx bx-note'></i> Hearing Notes</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="update_hearing" class="btn btn-primary">
                                            <i class='bx bx-save'></i> Update Hearing
                                        </button>
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class='bx bx-arrow-back'></i> Back to Schedule
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to current date/time
        document.addEventListener('DOMContentLoaded', function() {
            const hearingDateInput = document.getElementById('hearing_date');
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            hearingDateInput.min = now.toISOString().slice(0, 16);
        });

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
    </script>
</body>
</html>