<?php
session_start();
$menu = "APPOINTMENTS";
include("../include/database.php");

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get appointment details
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->execute([$appointment_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: index.php?error=appointment_not_found");
    exit();
}

// Get attorneys for assignment
$attorneys_stmt = $conn->prepare("SELECT username, charactername FROM users WHERE job IN ('Attorney', 'AG') ORDER BY charactername");
$attorneys_stmt->execute();
$attorneys = $attorneys_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if (isset($_POST['submit'])) {
    try {
        $client_number = $_POST['client_number'];
        $client_name = $_POST['client_name'];
        $client_phone = $_POST['client_phone'];
        $client_email = $_POST['client_email'];
        $appointment_date = $_POST['appointment_date'];
        $reason = $_POST['reason'];
        $appointment_type = $_POST['appointment_type'];
        $assigned_attorney = $_POST['assigned_attorney'];
        $notes = $_POST['notes'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("
            UPDATE appointments SET 
                client_number = ?, client_name = ?, client_phone = ?, client_email = ?, 
                appointment_date = ?, reason = ?, appointment_type = ?, assigned_attorney = ?, 
                notes = ?, status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $client_number, $client_name, $client_phone, $client_email,
            $appointment_date, $reason, $appointment_type, $assigned_attorney,
            $notes, $status, $appointment_id
        ]);

        header("Location: view.php?id=" . $appointment_id . "&success=appointment_updated");
        exit();

    } catch (PDOException $e) {
        $error_message = "Error updating appointment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment - Blackwood & Associates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="../js/dark-mode.js"></script>
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
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-edit'></i> Edit Appointment</h2>
                    <div>
                        <a href="view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Back to Details
                        </a>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error'></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class='bx bx-calendar-edit'></i> Edit Appointment Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-12 mb-3">
                                    <h6 class="text-primary"><i class='bx bx-user'></i> Client Information</h6>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="client_number" name="client_number" 
                                               value="<?php echo htmlspecialchars($appointment['client_number']); ?>" placeholder="Client Number">
                                        <label for="client_number">Client Number (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="client_name" name="client_name" 
                                               value="<?php echo htmlspecialchars($appointment['client_name']); ?>" placeholder="Client Name" required>
                                        <label for="client_name">Client Name *</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="client_phone" name="client_phone" 
                                               value="<?php echo htmlspecialchars($appointment['client_phone']); ?>" placeholder="Phone Number">
                                        <label for="client_phone">Phone Number</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="client_email" name="client_email" 
                                               value="<?php echo htmlspecialchars($appointment['client_email']); ?>" placeholder="Email Address">
                                        <label for="client_email">Email Address</label>
                                    </div>
                                </div>

                                <div class="col-12 mb-3 mt-4">
                                    <h6 class="text-primary"><i class='bx bx-calendar-event'></i> Appointment Details</h6>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="datetime-local" class="form-control" id="appointment_date" name="appointment_date" 
                                               value="<?php echo date('Y-m-d\TH:i', strtotime($appointment['appointment_date'])); ?>" required>
                                        <label for="appointment_date">Appointment Date & Time *</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="scheduled" <?php echo $appointment['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                            <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            <option value="no_show" <?php echo $appointment['status'] === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                        </select>
                                        <label for="status">Status *</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="appointment_type" name="appointment_type" required>
                                            <option value="consultation" <?php echo $appointment['appointment_type'] === 'consultation' ? 'selected' : ''; ?>>Initial Consultation</option>
                                            <option value="follow_up" <?php echo $appointment['appointment_type'] === 'follow_up' ? 'selected' : ''; ?>>Follow-up Meeting</option>
                                            <option value="document_review" <?php echo $appointment['appointment_type'] === 'document_review' ? 'selected' : ''; ?>>Document Review</option>
                                            <option value="court_prep" <?php echo $appointment['appointment_type'] === 'court_prep' ? 'selected' : ''; ?>>Court Preparation</option>
                                            <option value="other" <?php echo $appointment['appointment_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <label for="appointment_type">Appointment Type *</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="assigned_attorney" name="assigned_attorney">
                                            <option value="">Select Attorney (Optional)</option>
                                            <?php foreach($attorneys as $attorney): ?>
                                                <option value="<?php echo htmlspecialchars($attorney['username']); ?>" 
                                                        <?php echo $appointment['assigned_attorney'] === $attorney['username'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($attorney['charactername'] ?? $attorney['username']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="assigned_attorney">Assigned Attorney</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="reason" name="reason" style="height: 120px" placeholder="Reason for appointment" required><?php echo htmlspecialchars($appointment['reason']); ?></textarea>
                                        <label for="reason">Reason for Contacting Us *</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="notes" name="notes" style="height: 80px" placeholder="Additional notes"><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
                                        <label for="notes">Additional Notes (Optional)</label>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                            <i class='bx bx-save'></i> Update Appointment
                                        </button>
                                        <a href="view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-secondary btn-lg">
                                            <i class='bx bx-x'></i> Cancel
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

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dark-mode.js"></script>
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

        // Set minimum date to current date/time
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('appointment_date');
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            dateInput.min = now.toISOString().slice(0, 16);
        });
    </script>
</body>
</html>