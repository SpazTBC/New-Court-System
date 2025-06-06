<?php
session_start();
$menu = "APPOINTMENTS";
include("../include/database.php");

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Get current user info
$stmt = $conn->prepare("SELECT job, staff FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch();

// Check permissions
$allowed_roles = ['Assistant', 'Attorney', 'AG'];
if (!in_array($user['job'], $allowed_roles) && $user['staff'] != 1) {
    header("Location: ../login/home.php?error=access_denied");
    exit();
}

$is_assistant = ($user['job'] === 'Assistant' || $user['staff'] == 1);
$is_attorney = in_array($user['job'], ['Attorney', 'AG']);

$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get appointment details with permission check
if ($is_assistant) {
    // Assistants can view all appointments
    $stmt = $conn->prepare("
        SELECT a.*, u.charactername as attorney_name, creator.charactername as creator_name 
        FROM appointments a 
        LEFT JOIN users u ON a.assigned_attorney = u.username 
        LEFT JOIN users creator ON a.created_by = creator.username
        WHERE a.id = ?
    ");
    $stmt->execute([$appointment_id]);
} else {
    // Attorneys can only view their own appointments
    $stmt = $conn->prepare("
        SELECT a.*, u.charactername as attorney_name, creator.charactername as creator_name 
        FROM appointments a 
        LEFT JOIN users u ON a.assigned_attorney = u.username 
        LEFT JOIN users creator ON a.created_by = creator.username
        WHERE a.id = ? AND a.assigned_attorney = ?
    ");
    $stmt->execute([$appointment_id, $_SESSION['username']]);
}

$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: index.php?error=appointment_not_found");
    exit();
}

// Handle status updates (for assistants only)
if (isset($_POST['update_status']) && $is_assistant) {
    try {
        $new_status = $_POST['status'];
        $notes = $_POST['notes'];
        
        $stmt = $conn->prepare("UPDATE appointments SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$new_status, $notes, $appointment_id]);
        
        header("Location: view.php?id=" . $appointment_id . "&success=status_updated");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - Blackwood & Associates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="../js/dark-mode.js"></script>
    <style>
        .appointment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem 1rem 0 0;
            padding: 2rem;
        }
        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .info-card {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
        }
        .contact-info a {
            color: #007bff;
            text-decoration: none;
        }
        .contact-info a:hover {
            text-decoration: underline;
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
                    <h2><i class='bx bx-calendar-event'></i> Appointment Details</h2>
                    <div>
                        <?php if ($is_assistant): ?>
                            <a href="edit.php?id=<?php echo $appointment['id']; ?>" class="btn btn-primary">
                                <i class='bx bx-edit'></i> Edit Appointment
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Back to List
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class='bx bx-check-circle'></i>
                        <?php 
                        switch($_GET['success']) {
                            case 'status_updated':
                                echo 'Appointment status has been updated successfully!';
                                break;
                            case 'appointment_updated':
                                echo 'Appointment has been updated successfully!';
                                break;
                            default:
                                echo 'Operation completed successfully!';
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error'></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow-lg">
                    <div class="appointment-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-2"><?php echo htmlspecialchars($appointment['client_name']); ?></h3>
                                <p class="mb-0 opacity-75">
                                    <i class='bx bx-calendar'></i> 
                                    <?php 
                                    $appointment_date = new DateTime($appointment['appointment_date']);
                                    echo $appointment_date->format('l, F j, Y \a\t g:i A'); 
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="status-badge badge bg-<?php 
                                    switch($appointment['status']) {
                                        case 'confirmed': echo 'success'; break;
                                        case 'completed': echo 'dark'; break;
                                        case 'cancelled': echo 'danger'; break;
                                        case 'no_show': echo 'warning'; break;
                                        default: echo 'primary';
                                    }
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $appointment['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Client Information -->
                            <div class="col-md-6">
                                <div class="info-card p-3 rounded">
                                    <h5 class="text-primary mb-3"><i class='bx bx-user'></i> Client Information</h5>
                                    
                                    <?php if (!empty($appointment['client_number'])): ?>
                                        <div class="mb-3">
                                            <strong>Client Number:</strong><br>
                                            <?php echo htmlspecialchars($appointment['client_number']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <strong>Full Name:</strong><br>
                                        <?php echo htmlspecialchars($appointment['client_name']); ?>
                                    </div>
                                    
                                    <?php if (!empty($appointment['client_phone'])): ?>
                                        <div class="mb-3 contact-info">
                                            <strong>Phone Number:</strong><br>
                                            <a href="tel:<?php echo htmlspecialchars($appointment['client_phone']); ?>">
                                                <i class='bx bx-phone'></i> <?php echo htmlspecialchars($appointment['client_phone']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($appointment['client_email'])): ?>
                                        <div class="mb-3 contact-info">
                                            <strong>Email Address:</strong><br>
                                            <a href="mailto:<?php echo htmlspecialchars($appointment['client_email']); ?>">
                                                <i class='bx bx-envelope'></i> <?php echo htmlspecialchars($appointment['client_email']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Appointment Details -->
                            <div class="col-md-6">
                                <div class="info-card p-3 rounded">
                                    <h5 class="text-primary mb-3"><i class='bx bx-calendar-event'></i> Appointment Details</h5>
                                    
                                    <div class="mb-3">
                                        <strong>Date & Time:</strong><br>
                                        <?php echo $appointment_date->format('l, F j, Y \a\t g:i A'); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Appointment Type:</strong><br>
                                        <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $appointment['appointment_type'])); ?></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Assigned Attorney:</strong><br>
                                        <?php echo htmlspecialchars($appointment['attorney_name'] ?? 'Not Assigned'); ?>
                                    </div>
                                    
                                    <?php if ($is_assistant): ?>
                                        <div class="mb-3">
                                            <strong>Created By:</strong><br>
                                            <?php echo htmlspecialchars($appointment['creator_name'] ?? $appointment['created_by']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <strong>Created:</strong><br>
                                        <small class="text-muted">
                                            <?php 
                                            $created = new DateTime($appointment['created_at']);
                                            echo $created->format('M j, Y g:i A');
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Reason for Contact -->
                            <div class="col-12">
                                <div class="info-card p-3 rounded">
                                    <h5 class="text-primary mb-3"><i class='bx bx-message-detail'></i> Reason for Contacting Us</h5>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($appointment['reason'])); ?></p>
                                </div>
                            </div>

                            <!-- Notes -->
                            <?php if (!empty($appointment['notes']) || $is_assistant): ?>
                                <div class="col-12">
                                    <div class="info-card p-3 rounded">
                                        <h5 class="text-primary mb-3"><i class='bx bx-note'></i> Additional Notes</h5>
                                        
                                        <?php if ($is_assistant): ?>
                                            <!-- Status Update Form for Assistants -->
                                            <form method="POST" class="mb-3">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <div class="form-floating">
                                                            <select class="form-select" name="status" required>
                                                                <option value="scheduled" <?php echo $appointment['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                                <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                                <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                                <option value="no_show" <?php echo $appointment['status'] === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                                            </select>
                                                            <label>Update Status</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-floating">
                                                            <textarea class="form-control" name="notes" style="height: 100px"><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
                                                            <label>Notes</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="submit" name="update_status" class="btn btn-primary h-100">
                                                            <i class='bx bx-save'></i> Update
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <!-- Read-only notes for attorneys -->
                                            <?php if (!empty($appointment['notes'])): ?>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">No additional notes available.</p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>