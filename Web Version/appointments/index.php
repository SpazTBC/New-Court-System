<?php
session_start();
$menu = "APPOINTMENTS";
include("../include/database.php");

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Get current user info
$stmt = $conn->prepare("SELECT job, staff, charactername FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch();

// Check permissions - Allow Assistants, Attorneys, AG, and Staff
$allowed_roles = ['Assistant', 'Attorney', 'AG'];
if (!in_array($user['job'], $allowed_roles) && $user['staff'] != 1) {
    header("Location: ../login/home.php?error=access_denied");
    exit();
}

$is_assistant = ($user['job'] === 'Assistant' || $user['staff'] == 1);
$is_attorney = in_array($user['job'], ['Attorney', 'AG']);

// Build query based on user role
if ($is_assistant) {
    // Assistants can see all appointments
    $appointments_query = "
        SELECT a.*, u.charactername as attorney_name, creator.charactername as creator_name
        FROM appointments a 
        LEFT JOIN users u ON a.assigned_attorney = u.username 
        LEFT JOIN users creator ON a.created_by = creator.username
        WHERE a.appointment_date >= NOW() 
        ORDER BY a.appointment_date ASC
    ";
    $past_appointments_query = "
        SELECT a.*, u.charactername as attorney_name, creator.charactername as creator_name
        FROM appointments a 
        LEFT JOIN users u ON a.assigned_attorney = u.username 
        LEFT JOIN users creator ON a.created_by = creator.username
        WHERE a.appointment_date < NOW() 
        ORDER BY a.appointment_date DESC 
        LIMIT 20
    ";
    $params = [];
} else {
    // Attorneys can only see their own appointments
    $appointments_query = "
        SELECT a.*, u.charactername as attorney_name, creator.charactername as creator_name
        FROM appointments a 
        LEFT JOIN users u ON a.assigned_attorney = u.username 
        LEFT JOIN users creator ON a.created_by = creator.username
        WHERE a.assigned_attorney = ? AND a.appointment_date >= NOW() 
        ORDER BY a.appointment_date ASC
    ";
    $past_appointments_query = "
        SELECT a.*, u.charactername as attorney_name, creator.charactername as creator_name
        FROM appointments a 
        LEFT JOIN users u ON a.assigned_attorney = u.username 
        LEFT JOIN users creator ON a.created_by = creator.username
        WHERE a.assigned_attorney = ? AND a.appointment_date < NOW() 
        ORDER BY a.appointment_date DESC 
        LIMIT 20
    ";
    $params = [$_SESSION['username']];
}

// Get upcoming appointments
$stmt = $conn->prepare($appointments_query);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get past appointments
$stmt = $conn->prepare($past_appointments_query);
$stmt->execute($params);
$past_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's appointments
$today_query = str_replace('a.appointment_date >= NOW()', 'DATE(a.appointment_date) = CURDATE()', $appointments_query);
$stmt = $conn->prepare($today_query);
$stmt->execute($params);
$today_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Blackwood & Associates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        .appointment-card {
            transition: transform 0.2s ease-in-out;
            border-left: 4px solid #007bff;
        }
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .appointment-card.today {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .appointment-card.urgent {
            border-left-color: #dc3545;
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem;
        }
        .stats-card .display-4 {
            font-weight: 300;
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
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class='bx bx-calendar-check'></i> 
                        <?php echo $is_attorney ? 'My Appointments' : 'Appointment Management'; ?>
                    </h2>
                    <div>
                        <?php if ($is_assistant): ?>
                            <a href="schedule.php" class="btn btn-primary">
                                <i class='bx bx-plus'></i> New Appointment
                            </a>
                        <?php endif; ?>
                        <a href="calendar.php" class="btn btn-secondary">
                            <i class='bx bx-calendar'></i> Calendar View
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stats-card p-4 text-center">
                            <div class="display-4"><?php echo count($today_appointments); ?></div>
                            <h6>Today's Appointments</h6>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card p-4 text-center">
                            <div class="display-4"><?php echo count($appointments); ?></div>
                            <h6>Upcoming Appointments</h6>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card p-4 text-center">
                            <div class="display-4">
                                <?php 
                                $confirmed_count = count(array_filter($appointments, function($apt) {
                                    return $apt['status'] === 'confirmed';
                                }));
                                echo $confirmed_count;
                                ?>
                            </div>
                            <h6>Confirmed</h6>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <?php if (!empty($today_appointments)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class='bx bx-calendar-star'></i> Today's Appointments (<?php echo count($today_appointments); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($today_appointments as $appointment): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card appointment-card today h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0"><?php echo htmlspecialchars($appointment['client_name']); ?></h6>
                                                <span class="badge bg-<?php echo $appointment['status'] === 'confirmed' ? 'success' : 'primary'; ?> status-badge">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </div>
                                            
                                            <p class="card-text">
                                                <i class='bx bx-time'></i> 
                                                <?php echo date('g:i A', strtotime($appointment['appointment_date'])); ?>
                                            </p>
                                            
                                            <p class="card-text">
                                                <i class='bx bx-category'></i> 
                                                <?php echo ucfirst(str_replace('_', ' ', $appointment['appointment_type'])); ?>
                                            </p>
                                            
                                            <?php if ($is_assistant && !empty($appointment['attorney_name'])): ?>
                                                <p class="card-text">
                                                    <i class='bx bx-user-circle'></i> 
                                                    <?php echo htmlspecialchars($appointment['attorney_name']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="mt-3">
                                                <a href="view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class='bx bx-eye'></i> View Details
                                                </a>
                                                <?php if ($is_assistant): ?>
                                                    <a href="edit.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class='bx bx-edit'></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Upcoming Appointments -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class='bx bx-calendar-event'></i> Upcoming Appointments (<?php echo count($appointments); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($appointments)): ?>
                            <div class="text-center py-5">
                                <i class='bx bx-calendar-x' style="font-size: 3rem; color: #6c757d;"></i>
                                <p class="text-muted mt-3">No upcoming appointments</p>
                                <?php if ($is_assistant): ?>
                                    <a href="schedule.php" class="btn btn-primary">
                                        <i class='bx bx-plus'></i> Schedule First Appointment
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($appointments as $appointment): ?>
                                    <?php
                                    $appointment_date = new DateTime($appointment['appointment_date']);
                                    $now = new DateTime();
                                    $today = new DateTime('today');
                                    $is_today = $appointment_date->format('Y-m-d') === $today->format('Y-m-d');
                                    $is_urgent = $appointment_date <= $now->add(new DateInterval('P1D'));
                                    
                                    $card_class = 'appointment-card';
                                    if ($is_today) $card_class .= ' today';
                                    elseif ($is_urgent) $card_class .= ' urgent';
                                    ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card <?php echo $card_class; ?> h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0"><?php echo htmlspecialchars($appointment['client_name']); ?></h6>
                                                    <span class="badge bg-<?php echo $appointment['status'] === 'confirmed' ? 'success' : 'primary'; ?> status-badge">
                                                        <?php echo ucfirst($appointment['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <p class="card-text">
                                                    <i class='bx bx-calendar'></i> 
                                                    <?php echo $appointment_date->format('M j, Y'); ?>
                                                </p>
                                                
                                                <p class="card-text">
                                                    <i class='bx bx-time'></i> 
                                                    <?php echo $appointment_date->format('g:i A'); ?>
                                                </p>
                                                
                                                <p class="card-text">
                                                    <i class='bx bx-category'></i> 
                                                    <?php echo ucfirst(str_replace('_', ' ', $appointment['appointment_type'])); ?>
                                                </p>
                                                
                                                <?php if ($is_assistant && !empty($appointment['attorney_name'])): ?>
                                                    <p class="card-text">
                                                        <i class='bx bx-user-circle'></i> 
                                                        <?php echo htmlspecialchars($appointment['attorney_name']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($appointment['client_phone'])): ?>
                                                    <p class="card-text">
                                                        <i class='bx bx-phone'></i> 
                                                        <a href="tel:<?php echo htmlspecialchars($appointment['client_phone']); ?>">
                                                            <?php echo htmlspecialchars($appointment['client_phone']); ?>
                                                        </a>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="mt-3">
                                                    <a href="view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class='bx bx-eye'></i> View Details
                                                    </a>
                                                    <?php if ($is_assistant): ?>
                                                        <a href="edit.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class='bx bx-edit'></i> Edit
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Past Appointments -->
                <?php if (!empty($past_appointments)): ?>
                <div class="card shadow">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class='bx bx-history'></i> Recent Past Appointments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Date & Time</th>
                                        <th>Type</th>
                                        <?php if ($is_assistant): ?>
                                            <th>Attorney</th>
                                        <?php endif; ?>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($past_appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                            <td>
                                                <?php 
                                                $past_date = new DateTime($appointment['appointment_date']);
                                                echo $past_date->format('M j, Y g:i A'); 
                                                ?>
                                            </td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $appointment['appointment_type'])); ?></td>
                                            <?php if ($is_assistant): ?>
                                                <td><?php echo htmlspecialchars($appointment['attorney_name'] ?? 'Not Assigned'); ?></td>
                                            <?php endif; ?>
                                            <td>
                                                <span class="badge bg-<?php echo $appointment['status'] === 'completed' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class='bx bx-eye'></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>