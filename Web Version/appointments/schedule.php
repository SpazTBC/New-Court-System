<?php
session_start();
$menu = "APPOINTMENTS";
include("../include/database.php");

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is Assistant or Staff - only they can schedule appointments
$stmt = $conn->prepare("SELECT job, staff FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch();

if ($user['job'] !== 'Assistant' && $user['staff'] != 1) {
    header("Location: index.php?error=access_denied");
    exit();
}

// Get all attorneys for assignment dropdown
$attorneys_stmt = $conn->prepare("SELECT username, charactername FROM users WHERE job IN ('Attorney', 'AG') AND job_approved = 1 ORDER BY charactername");
$attorneys_stmt->execute();
$attorneys = $attorneys_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if (isset($_POST['schedule_appointment'])) {
    try {
        $client_number = !empty($_POST['client_number']) ? $_POST['client_number'] : null;
        $client_name = $_POST['client_name'];
        $client_phone = !empty($_POST['client_phone']) ? $_POST['client_phone'] : null;
        $client_email = !empty($_POST['client_email']) ? $_POST['client_email'] : null;
        $appointment_date = $_POST['appointment_date'];
        $reason = $_POST['reason'];
        $appointment_type = $_POST['appointment_type'];
        $assigned_attorney = !empty($_POST['assigned_attorney']) ? $_POST['assigned_attorney'] : null;
        $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
        $created_by = $_SESSION['username'];

        $stmt = $conn->prepare("
            INSERT INTO appointments (client_number, client_name, client_phone, client_email, appointment_date, reason, appointment_type, assigned_attorney, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$client_number, $client_name, $client_phone, $client_email, $appointment_date, $reason, $appointment_type, $assigned_attorney, $notes, $created_by]);
        
        header("Location: index.php?success=appointment_scheduled");
        exit();
        
    } catch (PDOException $e) {
        $error_message = "Error scheduling appointment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule New Appointment - Blackwood & Associates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        .schedule-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem 1rem 0 0;
            padding: 2rem;
        }
        .form-section {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 0.375rem;
        }
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .time-slot {
            padding: 0.5rem;
            border: 2px solid #dee2e6;
            border-radius: 0.375rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .time-slot:hover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        .time-slot.selected {
            border-color: #007bff;
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand">
                <span class="fw-bold text-white">Blackwood & Associates</span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-calendar-plus'></i> Schedule New Appointment</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Back to Appointments
                    </a>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error'></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow-lg">
                    <div class="schedule-header">
                        <h3 class="mb-2"><i class='bx bx-user-plus'></i> New Client Appointment</h3>
                        <p class="mb-0 opacity-75">Fill out the form below to schedule a new appointment</p>
                    </div>

                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Client Information Section -->
                            <div class="form-section">
                                <h5 class="text-primary mb-3"><i class='bx bx-user'></i> Client Information</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="client_number" name="client_number" placeholder="Client Number">
                                            <label for="client_number">Client Number (Optional)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="client_name" name="client_name" placeholder="Full Name" required>
                                            <label for="client_name">Full Name *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control" id="client_phone" name="client_phone" placeholder="Phone Number">
                                            <label for="client_phone">Phone Number</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="client_email" name="client_email" placeholder="Email Address">
                                            <label for="client_email">Email Address</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Appointment Details Section -->
                            <div class="form-section">
                                <h5 class="text-primary mb-3"><i class='bx bx-calendar-event'></i> Appointment Details</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="datetime-local" class="form-control" id="appointment_date" name="appointment_date" required>
                                            <label for="appointment_date">Date & Time *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" id="appointment_type" name="appointment_type" required>
                                                <option value="">Select Type</option>
                                                <option value="consultation">Initial Consultation</option>
                                                <option value="follow_up">Follow-up Meeting</option>
                                                <option value="document_review">Document Review</option>
                                                <option value="court_prep">Court Preparation</option>
                                                <option value="other">Other</option>
                                            </select>
                                            <label for="appointment_type">Appointment Type *</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select class="form-select" id="assigned_attorney" name="assigned_attorney">
                                                <option value="">Select Attorney (Optional)</option>
                                                <?php foreach ($attorneys as $attorney): ?>
                                                    <option value="<?php echo htmlspecialchars($attorney['username']); ?>">
                                                        <?php echo htmlspecialchars($attorney['charactername'] ?? $attorney['username']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="assigned_attorney">Assign to Attorney</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reason Section -->
                            <div class="form-section">
                                <h5 class="text-primary mb-3"><i class='bx bx-message-detail'></i> Reason for Contact</h5>
                                <div class="form-floating">
                                    <textarea class="form-control" id="reason" name="reason" style="height: 120px" placeholder="Please describe the reason for this appointment..." required></textarea>
                                    <label for="reason">Reason for Contacting Us *</label>
                                </div>
                            </div>

                            <!-- Additional Notes Section -->
                            <div class="form-section">
                                <h5 class="text-primary mb-3"><i class='bx bx-note'></i> Additional Notes</h5>
                                <div class="form-floating">
                                    <textarea class="form-control" id="notes" name="notes" style="height: 100px" placeholder="Any additional notes or special requirements..."></textarea>
                                    <label for="notes">Internal Notes (Optional)</label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="text-center mt-4">
                                <button type="submit" name="schedule_appointment" class="btn btn-primary btn-lg px-5">
                                    <i class='bx bx-calendar-plus'></i> Schedule Appointment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to current date/time
        document.addEventListener('DOMContentLoaded', function() {
            const appointmentDateInput = document.getElementById('appointment_date');
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            appointmentDateInput.min = now.toISOString().slice(0, 16);
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