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
    header("Location: ../?error=no_access&charactername=" . urlencode($_GET['charactername']));
    exit();
}

$characterName = $currentCharacter['charactername'];
$characterJob = $currentCharacter['job'];

// Get all client intakes
$stmt = $conn->prepare("SELECT * FROM client_intake ORDER BY intake_date DESC");
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Intake - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        .navbar {
            background-color: #0d1117 !important;
            border-bottom: 1px solid #30363d;
        }
        .card {
            background-color: #21262d;
            border: 1px solid #30363d;
            color: #e0e0e0;
        }
        .card-header {
            background-color: #161b22 !important;
            border-bottom: 1px solid #30363d;
            color: #e0e0e0 !important;
        }
        .table-dark {
            --bs-table-bg: #21262d;
            --bs-table-striped-bg: #2d333b;
            --bs-table-hover-bg: #30363d;
            --bs-table-border-color: #30363d;
        }
        .btn-primary {
            background-color: #238636;
            border-color: #238636;
        }
        .btn-primary:hover {
            background-color: #2ea043;
            border-color: #2ea043;
        }
        .btn-outline-secondary {
            color: #e0e0e0;
            border-color: #30363d;
        }
        .btn-outline-secondary:hover {
            background-color: #30363d;
            border-color: #484f58;
            color: #e0e0e0;
        }
        .btn-outline-primary {
            color: #58a6ff;
            border-color: #58a6ff;
        }
        .btn-outline-primary:hover {
            background-color: #58a6ff;
            border-color: #58a6ff;
            color: #0d1117;
        }
        .btn-outline-warning {
            color: #f0883e;
            border-color: #f0883e;
        }
        .btn-outline-warning:hover {
            background-color: #f0883e;
            border-color: #f0883e;
            color: #0d1117;
        }
        .alert-success {
            background-color: #0f5132;
            border-color: #238636;
            color: #3fb950;
        }
        .shadow {
            box-shadow: 0 16px 32px rgba(1, 4, 9, 0.85) !important;
        }
        .text-muted {
            color: #8b949e !important;
        }
        .display-1 {
            color: #484f58;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../">
                <i class='bx bx-building'></i> Court System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class='bx bx-user'></i>
                    <span class="ms-2"><?php echo htmlspecialchars($characterName); ?> (<?php echo htmlspecialchars($characterJob); ?>)</span>
                </span>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class='bx bx-user-plus'></i> Client Intake</h3>
                        <div>
                            <a href="../login/home.php?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-outline-secondary">
                                <i class='bx bx-arrow-back'></i> Back to Dashboard
                            </a>
                            <a href="new_intake.php?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-primary">
                                <i class='bx bx-plus'></i> New Client Intake
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['delete_success'])) {
                            $client_name = $_GET['client_name'] ?? 'Unknown';
                            echo '<div class="alert alert-success alert-dismissible fade show">
                                <i class="bx bx-check-circle"></i> Client <strong>' . htmlspecialchars($client_name) . '</strong> has been permanently deleted along with all associated documents.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>';
                        } ?>
                        <?php if (empty($clients)): ?>
                            <div class="text-center py-5">
                                <i class='bx bx-user-plus display-1 text-muted'></i>
                                <h4 class="text-muted">No client intakes found</h4>
                                <p class="text-muted">There are currently no client intakes in the system.</p>
                                <a href="new_intake.php?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-primary">
                                    <i class='bx bx-plus'></i> Create First Client Intake
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Client Name</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Case Type</th>
                                            <th>City, State</th>
                                            <th>Intake Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clients as $client): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $fullName = trim($client['first_name'] . ' ' . ($client['middle_name'] ?? '') . ' ' . $client['last_name']);
                                                    echo htmlspecialchars($fullName); 
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                                <td><?php echo htmlspecialchars($client['case_type'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars(($client['city'] ?? '') . ', ' . ($client['state'] ?? '')); ?></td>
                                                <td><?php echo $client['intake_date'] ? date('M j, Y', strtotime($client['intake_date'])) : 'N/A'; ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="view_details.php?id=<?php echo $client['id']; ?>&charactername=<?php echo urlencode($characterName); ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class='bx bx-eye'></i> View
                                                        </a>
                                                        <a href="edit_intake.php?id=<?php echo $client['id']; ?>&charactername=<?php echo urlencode($characterName); ?>" class="btn btn-sm btn-outline-warning">
                                                            <i class='bx bx-edit'></i> Edit
                                                        </a>
                                                    </div>
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