<?php
require_once '../include/database.php';
require_once '../auth/character_auth.php';

$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    header("Location: ../?error=not_found");
    exit();
}

// Validate character access
$auth = validateCharacterAccess($_GET['charactername']);
if (!$auth['valid']) {
    $error = '';
    switch($auth['message']) {
        case 'Character not found':
            $error = 'not_found';
            break;
        case 'Character is banned':
            $error = 'banned';
            break;
        default:
            $error = 'no_access';
    }
    header("Location: ../?error=" . $error . "&charactername=" . urlencode($_GET['charactername']));
    exit();
}

// Check if character has court access
if (!hasCourtAccess($currentCharacter)) {
    header("Location: ../?error=no_access&charactername=" . urlencode($_GET['charactername']));
    exit();
}

$characterName = $currentCharacter['charactername'];
$characterJob = $currentCharacter['job'];
$username = $currentCharacter['username']; // Get the username too

// Get cases assigned to this character - using both username and character name
$stmt = $conn->prepare("SELECT * FROM cases WHERE 
    assigneduser = ? OR 
    assigneduser = ? OR 
    defendent = ? OR 
    defendent = ? 
    ORDER BY assigned DESC LIMIT 10");
$stmt->execute([$username, $characterName, $username, $characterName]);
$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Let's see what we're looking for
// Uncomment these lines temporarily to debug:
// echo "Looking for cases with:<br>";
// echo "Username: " . htmlspecialchars($username) . "<br>";
// echo "Character Name: " . htmlspecialchars($characterName) . "<br>";
// echo "Found " . count($cases) . " cases<br><br>";

// Get case statistics - also using both username and character name
$statsStmt = $conn->prepare("SELECT 
    COUNT(*) as total_cases,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cases,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_cases
    FROM cases WHERE assigneduser = ? OR assigneduser = ?");
$statsStmt->execute([$username, $characterName]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// If no stats found, set defaults
if (!$stats || $stats['total_cases'] === null) {
    $stats = [
        'total_cases' => 0,
        'pending_cases' => 0,
        'closed_cases' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../">
                <i class='bx bx-building'></i> Court System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class='bx bx-user'></i>
                    <span class="ms-2">Welcome <?php echo htmlspecialchars($characterName); ?> (<?php echo htmlspecialchars($characterJob); ?>)</span>
                </span>
                <a href="../" class="btn btn-outline-light btn-sm ms-3">
                    <i class='bx bx-log-out'></i> Exit
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h2><i class='bx bx-home'></i> Welcome to the Court System, <?php echo htmlspecialchars($characterName); ?>!</h2>
                        <p class="mb-0">Your role: <strong><?php echo htmlspecialchars($characterJob); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class='bx bx-file display-4 text-primary'></i>
                        <h3><?php echo $stats['total_cases']; ?></h3>
                        <p class="text-muted">Total Cases</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class='bx bx-time display-4 text-warning'></i>
                        <h3><?php echo $stats['pending_cases']; ?></h3>
                        <p class="text-muted">Pending Cases</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class='bx bx-check-circle display-4 text-success'></i>
                        <h3><?php echo $stats['closed_cases']; ?></h3>
                        <p class="text-muted">Closed Cases</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class='bx bx-cog'></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (in_array($characterJob, ['police', 'lawyer', 'judge', 'admin'])): ?>
                                <a href="../cases/submitcase/?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-primary">
                                    <i class='bx bx-plus'></i> Submit New Case
                                </a>
                            <?php endif; ?>
                            
                            <a href="../cases/?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-outline-primary">
                                <i class='bx bx-list-ul'></i> View All Cases
                            </a>
                            
                            <?php if ($characterJob === 'admin'): ?>
                                <a href="../ban/?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-outline-warning">
                                    <i class='bx bx-user-check'></i> User Management
                                </a>
                            <?php endif; ?>
                            
                            <a href="../client-intake/?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-outline-info">
                                <i class='bx bx-user-plus'></i> Client Intake
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Cases -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class='bx bx-file'></i> Recent Cases</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($cases)): ?>
                            <div class="text-center py-4">
                                <i class='bx bx-folder-open display-4 text-muted'></i>
                                <p class="text-muted mt-2">No cases found.</p>
                                <p class="text-muted small">
                                    Cases will appear here when you are assigned as:<br>
                                    • Assigned User: <strong><?php echo htmlspecialchars($username); ?></strong> or <strong><?php echo htmlspecialchars($characterName); ?></strong><br>
                                    • Defendant: <strong><?php echo htmlspecialchars($username); ?></strong> or <strong><?php echo htmlspecialchars($characterName); ?></strong>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Case ID</th>
                                            <th>Defendant</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cases as $case): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                                <td><?php echo htmlspecialchars($case['defendent']); ?></td>
                                                <td><?php echo htmlspecialchars($case['type']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $case['status'] == 'pending' ? 'warning' : ($case['status'] == 'closed' ? 'success' : 'primary'); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($case['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($case['assigned'])); ?></td>
                                                <td>
                                                    <a href="../cases/view.php?id=<?php echo $case['id']; ?>&charactername=<?php echo urlencode($characterName); ?>" class="btn btn-sm btn-outline-primary">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
