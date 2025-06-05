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

// Get upcoming hearings for current user
$hearings_stmt = $conn->prepare("
    SELECT c.*, u.charactername as creator_name,
           COALESCE(j1.charactername, j2.charactername, j3.charactername, j4.charactername, 'TBD') as judge_name
    FROM cases c 
    LEFT JOIN users u ON c.assigneduser = u.username 
    LEFT JOIN users j1 ON c.shared01 = j1.username AND j1.job = 'Judge'
    LEFT JOIN users j2 ON c.shared02 = j2.username AND j2.job = 'Judge'  
    LEFT JOIN users j3 ON c.shared03 = j3.username AND j3.job = 'Judge'
    LEFT JOIN users j4 ON c.shared04 = j4.username AND j4.job = 'Judge'
    WHERE (c.shared01 = ? OR c.shared02 = ? OR c.shared03 = ? OR c.shared04 = ? OR c.assigneduser = ?) 
    AND c.hearing_date IS NOT NULL 
    AND c.hearing_date >= NOW() 
    ORDER BY c.hearing_date ASC
");
$username = $_SESSION['username'];
$hearings_stmt->execute([$username, $username, $username, $username, $username]);
$hearings = $hearings_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get past hearings
$past_hearings_stmt = $conn->prepare("
    SELECT c.*, u.charactername as creator_name,
           COALESCE(j1.charactername, j2.charactername, j3.charactername, j4.charactername, 'TBD') as judge_name
    FROM cases c 
    LEFT JOIN users u ON c.assigneduser = u.username 
    LEFT JOIN users j1 ON c.shared01 = j1.username AND j1.job = 'Judge'
    LEFT JOIN users j2 ON c.shared02 = j2.username AND j2.job = 'Judge'  
    LEFT JOIN users j3 ON c.shared03 = j3.username AND j3.job = 'Judge'
    LEFT JOIN users j4 ON c.shared04 = j4.username AND j4.job = 'Judge'
    WHERE (c.shared01 = ? OR c.shared02 = ? OR c.shared03 = ? OR c.shared04 = ? OR c.assigneduser = ?) 
    AND c.hearing_date IS NOT NULL 
    AND c.hearing_date < NOW() 
    ORDER BY c.hearing_date DESC
");
$past_hearings_stmt->execute([$username, $username, $username, $username, $username]);
$past_hearings = $past_hearings_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Court Hearing Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        .hearing-card {
            transition: transform 0.2s ease-in-out;
            border-left: 4px solid #007bff;
        }
        .hearing-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .hearing-card.urgent {
            border-left-color: #dc3545;
        }
        .hearing-card.today {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .time-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .status-badge {
            font-size: 0.8rem;
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class='bx bx-check-circle'></i>
                <?php 
                switch($_GET['success']) {
                    case 'hearing_scheduled':
                        echo 'Hearing has been successfully scheduled!';
                        break;
                    case 'hearing_updated':
                        echo 'Hearing has been successfully updated!';
                        break;
                    default:
                        echo 'Operation completed successfully!';
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class='bx bx-error-circle'></i>
                <?php 
                switch($_GET['error']) {
                    case 'access_denied':
                        echo 'Access denied. You do not have permission to view hearings.';
                        break;
                    case 'case_not_found':
                        echo 'Case not found or you do not have access to it.';
                        break;
                    default:
                        echo 'An error occurred. Please try again.';
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-calendar-event'></i> Court Hearing Schedule</h2>
                    <a href="schedule.php" class="btn btn-primary">
                        <i class='bx bx-calendar-plus'></i> Schedule Hearing
                    </a>
                </div>

                <!-- Upcoming Hearings -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class='bx bx-time'></i> Upcoming Hearings (<?php echo count($hearings); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($hearings)): ?>
                            <div class="text-center py-4">
                                <i class='bx bx-calendar-x' style="font-size: 3rem; color: #6c757d;"></i>
                                <p class="text-muted mt-2">No upcoming hearings scheduled</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($hearings as $hearing): ?>
                                    <?php
                                    $hearing_date = new DateTime($hearing['hearing_date']);
                                    $now = new DateTime();
                                    $today = new DateTime('today');
                                    $tomorrow = new DateTime('tomorrow');
                                    
                                    $is_today = $hearing_date->format('Y-m-d') === $today->format('Y-m-d');
                                    $is_urgent = $hearing_date <= $now->add(new DateInterval('P1D'));
                                    
                                    $card_class = 'hearing-card';
                                    if ($is_today) $card_class .= ' today';
                                    elseif ($is_urgent) $card_class .= ' urgent';
                                    ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card <?php echo $card_class; ?> h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">Case #<?php echo htmlspecialchars($hearing['casenum']); ?></h6>
                                                    <span class="badge bg-<?php echo $is_today ? 'warning' : ($is_urgent ? 'danger' : 'primary'); ?> status-badge">
                                                        <?php echo ucfirst($hearing['hearing_status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <p class="card-text">
                                                    <strong>Defendant:</strong> <?php echo htmlspecialchars($hearing['defendent']); ?>
                                                </p>
                                                
                                                <div class="mb-2">
                                                    <span class="badge bg-info time-badge">
                                                        <i class='bx bx-calendar'></i> 
                                                        <?php echo $hearing_date->format('M j, Y'); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <span class="badge bg-secondary time-badge">
                                                        <i class='bx bx-time'></i> 
                                                        <?php echo $hearing_date->format('g:i A'); ?>
                                                    </span>
                                                </div>
                                                
                                                <p class="card-text">
                                                    <i class='bx bx-building'></i> 
                                                    <strong><?php echo htmlspecialchars($hearing['courtroom']); ?></strong>
                                                </p>
                                                
                                                <p class="card-text">
                                                    <i class='bx bx-user-circle'></i> 
                                                    <strong>Judge:</strong> <?php echo htmlspecialchars($hearing['judge_name']); ?>
                                                </p>
                                                
                                                <?php if (!empty($hearing['hearing_notes'])): ?>
                                                    <p class="card-text">
                                                        <small class="text-muted">
                                                            <i class='bx bx-note'></i> 
                                                            <?php echo htmlspecialchars($hearing['hearing_notes']); ?>
                                                        </small>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="mt-3">
                                                    <a href="../view_case.php?id=<?php echo $hearing['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class='bx bx-eye'></i> View Case
                                                    </a>
                                                    <a href="edit_hearing.php?id=<?php echo $hearing['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class='bx bx-edit'></i> Edit
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Past Hearings -->
                <div class="card shadow">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class='bx bx-history'></i> Past Hearings (<?php echo count($past_hearings); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($past_hearings)): ?>
                            <div class="text-center py-4">
                                <i class='bx bx-calendar-check' style="font-size: 3rem; color: #6c757d;"></i>
                                <p class="text-muted mt-2">No past hearings found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Case #</th>
                                            <th>Defendant</th>
                                            <th>Date & Time</th>
                                            <th>Courtroom</th>
                                            <th>Judge</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($past_hearings as $hearing): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($hearing['casenum']); ?></td>
                                                <td><?php echo htmlspecialchars($hearing['defendent']); ?></td>
                                                <td>
                                                    <?php 
                                                    $past_date = new DateTime($hearing['hearing_date']);
                                                    echo $past_date->format('M j, Y g:i A'); 
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($hearing['courtroom']); ?></td>
                                                <td><?php echo htmlspecialchars($hearing['judge_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $hearing['hearing_status'] === 'completed' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($hearing['hearing_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="../view_case.php?id=<?php echo $hearing['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class='bx bx-eye'></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>