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

// Get cases without hearings that the user has access to
try {
    $cases_without_hearings_stmt = $conn->prepare("
        SELECT c.*, u.charactername as creator_name 
        FROM cases c 
        LEFT JOIN users u ON c.assigneduser = u.username 
        WHERE (c.shared01 = ? OR c.shared02 = ? OR c.shared03 = ? OR c.shared04 = ? OR c.assigneduser = ?) 
        AND (c.hearing_date IS NULL OR c.hearing_date = '') 
        ORDER BY c.assigned DESC
    ");
    $username = $_SESSION['username'];
    $cases_without_hearings_stmt->execute([$username, $username, $username, $username, $username]);
    $cases_without_hearings = $cases_without_hearings_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cases_without_hearings = [];
    $error_message = "Error fetching cases: " . $e->getMessage();
}

// Handle quick hearing scheduling
if (isset($_POST['schedule_hearing'])) {
    try {
        $case_id = $_POST['case_id'];
        $hearing_date = $_POST['hearing_date'];
        $courtroom = $_POST['courtroom'];
        $hearing_notes = $_POST['hearing_notes'] ?? '';

        $update_stmt = $conn->prepare("UPDATE cases SET hearing_date = ?, courtroom = ?, hearing_notes = ?, hearing_status = 'scheduled' WHERE id = ?");
        $update_stmt->execute([$hearing_date, $courtroom, $hearing_notes, $case_id]);

        header("Location: index.php?success=hearing_scheduled");
        exit();
    } catch (PDOException $e) {
        $schedule_error = "Error scheduling hearing: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Court Hearing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="../../css/dark-mode.css" rel="stylesheet">
    <script src="../../js/dark-mode.js"></script>
    <style>
        .case-card {
            transition: transform 0.2s ease-in-out;
            border-left: 4px solid #28a745;
        }
        .case-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .schedule-form {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }
        .schedule-form.show {
            display: block;
        }
        .no-cases-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem;
            padding: 3rem 2rem;
            text-align: center;
        }
    </style>
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
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-calendar-plus'></i> Schedule Court Hearing</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Back to Schedule
                    </a>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error'></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($schedule_error)): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error'></i> <?php echo htmlspecialchars($schedule_error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($cases_without_hearings)): ?>
                    <!-- Cases Without Hearings -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class='bx bx-calendar-x'></i> Cases Without Scheduled Hearings (<?php echo count($cases_without_hearings); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($cases_without_hearings as $case): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card case-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">Case #<?php echo htmlspecialchars($case['caseid']); ?></h6>
                                                    <span class="badge bg-warning">No Hearing</span>
                                                </div>
                                                
                                                <p class="card-text">
                                                    <strong>Defendant:</strong> <?php echo htmlspecialchars($case['defendent']); ?>
                                                </p>
                                                
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class='bx bx-user'></i> Created by: <?php echo htmlspecialchars($case['creator_name'] ?? $case['creator']); ?>
                                                    </small>
                                                </p>
                                                
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class='bx bx-calendar'></i> Created: <?php echo date('M j, Y', strtotime($case['assigned'])); ?>
                                                    </small>
                                                </p>
                                                
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="toggleScheduleForm(<?php echo $case['id']; ?>)">
                                                        <i class='bx bx-calendar-plus'></i> Schedule Hearing
                                                    </button>
                                                    <a href="../view.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class='bx bx-eye'></i> View
                                                    </a>
                                                </div>

                                                <!-- Quick Schedule Form -->
                                                <div id="schedule-form-<?php echo $case['id']; ?>" class="schedule-form">
                                                    <form method="POST" class="needs-validation" novalidate>
                                                        <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                                        
                                                        <div class="row g-2">
                                                            <div class="col-12">
                                                                <h6 class="text-primary mb-2">Schedule Hearing for Case #<?php echo htmlspecialchars($case['caseid']); ?></h6>
                                                            </div>
                                                            
                                                            <div class="col-md-6">
                                                                <div class="form-floating">
                                                                    <input type="datetime-local" class="form-control" name="hearing_date" required>
                                                                    <label><i class='bx bx-calendar'></i> Date & Time</label>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-md-6">
                                                                <div class="form-floating">
                                                                    <select class="form-select" name="courtroom" required>
                                                                        <option value="">Select Courtroom</option>
                                                                        <option value="Courtroom A">Courtroom A</option>
                                                                        <option value="Family Court">Family Court</option>
                                                                        <option value="Civil Court">Civil Court</option>
                                                                    </select>
                                                                    <label><i class='bx bx-building'></i> Courtroom</label>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-12">
                                                                <div class="form-floating">
                                                                    <textarea class="form-control" name="hearing_notes" style="height: 80px" placeholder="Notes"></textarea>
                                                                    <label><i class='bx bx-note'></i> Notes (Optional)</label>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-12">
                                                                <div class="d-flex gap-2">
                                                                    <button type="submit" name="schedule_hearing" class="btn btn-success btn-sm">
                                                                        <i class='bx bx-check'></i> Schedule
                                                                    </button>
                                                                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleScheduleForm(<?php echo $case['id']; ?>)">
                                                                        <i class='bx bx-x'></i> Cancel
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- No Cases or Create New Case Section -->
                <div class="no-cases-section">
                    <?php if (empty($cases_without_hearings)): ?>
                        <div class="mb-4">
                            <i class='bx bx-calendar-check' style="font-size: 4rem; opacity: 0.8;"></i>
                            <h3 class="mt-3">All Cases Have Scheduled Hearings</h3>
                            <p class="lead">Great! All your cases already have hearings scheduled.</p>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <i class='bx bx-plus-circle' style="font-size: 4rem; opacity: 0.8;"></i>
                            <h3 class="mt-3">Need to Create a New Case?</h3>
                            <p class="lead">If you don't see the case you're looking for above, create a new one with a hearing.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="../addcase/index.php" class="btn btn-light btn-lg">
                            <i class='bx bx-file-plus'></i> Create New Case with Hearing
                        </a>
                        <?php if (!empty($cases_without_hearings)): ?>
                            <a href="../index.php" class="btn btn-outline-light btn-lg">
                                <i class='bx bx-folder-open'></i> View All Cases
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleScheduleForm(caseId) {
            const form = document.getElementById('schedule-form-' + caseId);
            form.classList.toggle('show');
            
            if (form.classList.contains('show')) {
                // Set minimum date to current date/time
                const dateInput = form.querySelector('input[name="hearing_date"]');
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                dateInput.min = now.toISOString().slice(0, 16);
                dateInput.focus();
            }
        }

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

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>