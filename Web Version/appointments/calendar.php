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

// Check permissions - Allow Assistants, Attorneys, AG, and Staff
$allowed_roles = ['Assistant', 'Attorney', 'AG'];
if (!in_array($user['job'], $allowed_roles) && $user['staff'] != 1) {
    header("Location: ../login/home.php?error=access_denied");
    exit();
}

$is_assistant = ($user['job'] === 'Assistant' || $user['staff'] == 1);
$is_attorney = in_array($user['job'], ['Attorney', 'AG']);

// Get current month/year or from URL parameters
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get appointments for the current month based on user role
$start_date = "$current_year-" . str_pad($current_month, 2, '0', STR_PAD_LEFT) . "-01";
$end_date = date('Y-m-t', strtotime($start_date));

if ($is_assistant) {
    // Assistants see all appointments
    $appointments_stmt = $conn->prepare("
        SELECT a.*, u.charactername as attorney_name 
        FROM appointments a 
        LEFT JOIN users u ON a.assigned_attorney = u.username 
        WHERE DATE(a.appointment_date) BETWEEN ? AND ?
        ORDER BY a.appointment_date ASC
    ");
    $appointments_stmt->execute([$start_date, $end_date]);
} else {
    // Attorneys see only their appointments
    $appointments_stmt = $conn->prepare("
        SELECT a.*, u.charactername as attorney_name 
        FROM appointments a 
        LEFT JOIN users u ON a.assigned_attorney = u.username 
        WHERE a.assigned_attorney = ? AND DATE(a.appointment_date) BETWEEN ? AND ?
        ORDER BY a.appointment_date ASC
    ");
    $appointments_stmt->execute([$_SESSION['username'], $start_date, $end_date]);
}

$appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group appointments by date
$appointments_by_date = [];
foreach ($appointments as $appointment) {
    $date = date('Y-m-d', strtotime($appointment['appointment_date']));
    $appointments_by_date[$date][] = $appointment;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_attorney ? 'My Appointment Calendar' : 'Appointment Calendar'; ?> - Blackwood & Associates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        .calendar-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: #dee2e6;
            border: 1px solid #dee2e6;
        }
        .calendar-day {
            background: white;
            min-height: 120px;
            padding: 0.5rem;
            position: relative;
        }
        .calendar-day.other-month {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        .calendar-day.today {
            background-color: #e3f2fd;
        }
        .day-number {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .appointment-item {
            background: #007bff;
            color: white;
            padding: 0.25rem;
            margin-bottom: 0.25rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .appointment-item:hover {
            background: #0056b3;
        }
        .appointment-item.confirmed {
            background: #28a745;
        }
        .appointment-item.cancelled {
            background: #dc3545;
        }
        .appointment-item.completed {
            background: #6c757d;
        }
        .weekday-header {
            background: #495057;
            color: white;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
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

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class='bx bx-calendar'></i> 
                        <?php echo $is_attorney ? 'My Appointment Calendar' : 'Appointment Calendar'; ?>
                    </h2>
                    <div>
                        <?php if ($is_assistant): ?>
                            <a href="schedule.php" class="btn btn-primary">
                                <i class='bx bx-plus'></i> New Appointment
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class='bx bx-list-ul'></i> List View
                        </a>
                    </div>
                </div>

                <div class="calendar-container">
                    <div class="calendar-header">
                        <div>
                            <?php
                            $prev_month = $current_month - 1;
                            $prev_year = $current_year;
                            if ($prev_month < 1) {
                                $prev_month = 12;
                                $prev_year--;
                            }
                            
                            $next_month = $current_month + 1;
                            $next_year = $current_year;
                            if ($next_month > 12) {
                                $next_month = 1;
                                $next_year++;
                            }
                            ?>
                            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-outline-primary">
                                <i class='bx bx-chevron-left'></i>
                            </a>
                        </div>
                        <h4 class="mb-0">
                            <?php 
                            $month_names = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                                          'July', 'August', 'September', 'October', 'November', 'December'];
                            echo $month_names[$current_month] . ' ' . $current_year;
                            ?>
                        </h4>
                        <div>
                            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-outline-primary">
                                <i class='bx bx-chevron-right'></i>
                            </a>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        <!-- Weekday headers -->
                        <div class="weekday-header">Sunday</div>
                        <div class="weekday-header">Monday</div>
                        <div class="weekday-header">Tuesday</div>
                        <div class="weekday-header">Wednesday</div>
                        <div class="weekday-header">Thursday</div>
                        <div class="weekday-header">Friday</div>
                        <div class="weekday-header">Saturday</div>

                        <?php
                        // Calculate calendar days
                        $first_day = date('w', strtotime($start_date));
                        $days_in_month = date('t', strtotime($start_date));
                        $today = date('Y-m-d');
                        
                        // Previous month days
                        $prev_month_days = date('t', strtotime("-1 month", strtotime($start_date)));
                        for ($i = $first_day - 1; $i >= 0; $i--) {
                            $day = $prev_month_days - $i;
                            echo '<div class="calendar-day other-month">';
                            echo '<div class="day-number">' . $day . '</div>';
                            echo '</div>';
                        }
                        
                        // Current month days
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $date = $current_year . '-' . str_pad($current_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                            $is_today = $date === $today;
                            
                            echo '<div class="calendar-day' . ($is_today ? ' today' : '') . '">';
                            echo '<div class="day-number">' . $day . '</div>';
                            
                            // Show appointments for this day
                            if (isset($appointments_by_date[$date])) {
                                foreach ($appointments_by_date[$date] as $appointment) {
                                    $time = date('g:i A', strtotime($appointment['appointment_date']));
                                    $status_class = $appointment['status'];
                                    echo '<div class="appointment-item ' . $status_class . '" onclick="viewAppointment(' . $appointment['id'] . ')" title="' . htmlspecialchars($appointment['client_name']) . ' - ' . $time . '">';
                                    echo '<div>' . $time . '</div>';
                                    echo '<div>' . htmlspecialchars(substr($appointment['client_name'], 0, 15)) . '</div>';
                                    echo '</div>';
                                }
                            }
                            
                            echo '</div>';
                        }
                        
                        // Next month days to fill the grid
                        $total_cells = ceil(($first_day + $days_in_month) / 7) * 7;
                        $remaining_cells = $total_cells - ($first_day + $days_in_month);
                        for ($day = 1; $day <= $remaining_cells; $day++) {
                            echo '<div class="calendar-day other-month">';
                            echo '<div class="day-number">' . $day . '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Legend -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6>Legend:</h6>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="d-flex align-items-center">
                                        <div class="appointment-item me-2" style="width: 20px; height: 20px;"></div>
                                        <span>Scheduled</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="appointment-item confirmed me-2" style="width: 20px; height: 20px;"></div>
                                        <span>Confirmed</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="appointment-item completed me-2" style="width: 20px; height: 20px;"></div>
                                        <span>Completed</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="appointment-item cancelled me-2" style="width: 20px; height: 20px;"></div>
                                        <span>Cancelled</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewAppointment(appointmentId) {
            window.location.href = 'view.php?id=' + appointmentId;
        }
    </script>
</body>
</html>