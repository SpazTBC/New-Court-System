<?php
require_once '../include/database.php';
require_once '../auth/character_auth.php';

// Get character name
$characterName = $_GET['character_name'] ?? $_GET['charactername'] ?? '';
if (empty($characterName) && isset($_GET['first_name']) && isset($_GET['last_name'])) {
    $characterName = trim($_GET['first_name'] . ' ' . $_GET['last_name']);
}

$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    header("Location: ../?error=not_found");
    exit();
}

// Validate character access
$auth = validateCharacterAccess($characterName);
if (!$auth['valid']) {
    header("Location: ../?error=no_access");
    exit();
}

$characterDisplayName = $currentCharacter['charactername'];
$characterJob = $currentCharacter['job'];
$username = $currentCharacter['username'];

// Get user information including job and character name
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR charactername = ?");
$stmt->execute([$username, $characterDisplayName]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set variables for compatibility
$userJob = strtolower($user['job']);
$jobApproved = $user['job_approved'] ?? 1;
$isAG = ($userJob === 'ag');
$isCivilian = ($userJob === 'civilian');
$isAttorney = ($user['job'] === "Attorney");

// Handle success/error messages
$message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'deleted':
            $message = '<div class="alert alert-success alert-dismissible fade show">
                <i class="bx bx-check-circle"></i> Case deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            break;
        case 'updated':
            $message = '<div class="alert alert-success alert-dismissible fade show">
                <i class="bx bx-check-circle"></i> Case updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'case_not_found':
            $message = '<div class="alert alert-danger alert-dismissible fade show">
                <i class="bx bx-error-circle"></i> Case not found!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            break;
    }
}

// Function to check if user is supervisor
function isSupervisor($username) {
    global $conn;
    $stmt = $conn->prepare("SELECT supervisorjob FROM users WHERE username = ? AND supervisorjob = '1'");
    $stmt->execute([$username]);
    return $stmt->rowCount() > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <span class="fw-bold text-white">Blackwood & Associates</span>
                <span class="ms-2">Welcome <?php echo htmlspecialchars($characterDisplayName); ?></span>
            </div>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class='bx bx-user'></i>
                    <span class="ms-2"><?php echo htmlspecialchars($characterDisplayName); ?> (<?php echo htmlspecialchars($characterJob); ?>)</span>
                </span>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php echo $message; ?>
        
        <!-- Main Cases Section -->
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h2>Case Management</h2>
                <div class="d-flex gap-2">
                    <?php
                    // Show approval page link for Attorney General
                    if ($userJob == 'ag' && isset($jobApproved) && $jobApproved == 1) {
                        echo '<a href="approve/?character_name=' . urlencode($characterDisplayName) . '" class="btn btn-warning me-2">
                            <i class="bx bx-check-shield"></i> Pending Approvals
                        </a>';
                    }
                    
                    // Different button text based on job - Only show for non-civilians
                    if ($userJob !== 'civilian') {
                        if ($userJob === 'police') {
                            $buttonText = "Submit A Case";
                            $buttonLink = "submitcase/?character_name=" . urlencode($characterName);
                        } else {
                            $buttonText = "New Case";
                            $buttonLink = "addcase/?character_name=" . urlencode($characterName);
                        }
                        echo '<a href="' . $buttonLink . '" class="btn btn-primary">
                            <i class="bx bx-plus"></i> ' . $buttonText . '
                        </a>';
                    }
                    ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Number</th>
                                <th>Supervisor</th>
                                <th>Date Assigned</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($isCivilian) {
                                // For civilians, look for cases where they are the defendant (by character name)
                                $query = "SELECT * FROM cases WHERE defendent = :charactername ORDER BY id DESC";
                                $stmt = $conn->prepare($query);
                                $stmt->bindParam(':charactername', $characterDisplayName);
                            } else {
                                // FIXED: Use same logic as login/home.php - search by both username and character name
                                $query = "SELECT * FROM cases WHERE 
                                    assigneduser = :username OR 
                                    assigneduser = :charactername OR 
                                    defendent = :username2 OR 
                                    defendent = :charactername2";
                                
                                // For non-AG users, filter out pending cases
                                if (!$isAG) {
                                    $query .= " AND (status != 'pending' OR status IS NULL)";
                                }
                                $query .= " ORDER BY assigned DESC";
                                
                                $stmt = $conn->prepare($query);
                                $stmt->bindParam(':username', $username);
                                $stmt->bindParam(':charactername', $characterDisplayName);
                                $stmt->bindParam(':username2', $username);
                                $stmt->bindParam(':charactername2', $characterDisplayName);
                            }
                            
                            $stmt->execute();
                            $cases = $stmt->fetchAll();
                            
                            if(!empty($cases)):
                                foreach($cases as $case):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                <td><?php echo htmlspecialchars($case['type']); ?></td>
                                <td>
                                    <?php if($case['supervisor']): ?>
                                        <span class="badge bg-success">
                                            <i class='bx bx-check'></i> <?php echo htmlspecialchars($case['supervisor']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No Supervisor</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                <td><?php echo htmlspecialchars($case['type']); ?></td>
                                <td>
                                    <?php if(isset($case['status']) && $case['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending Approval</span>
                                    <?php elseif(isset($case['status']) && $case['status'] == 'denied'): ?>
                                        <span class="badge bg-danger">Denied</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-sm btn-info">View</a>
                                    <?php if (!$isCivilian): ?>
                                        <a href="modify.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="delete.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-sm btn-danger">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <?php if ($isCivilian): ?>
                                        No cases found where you are listed as a defendant.
                                    <?php else: ?>
                                        No cases found. Cases will appear here when you are assigned as the user or listed as defendant.
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Supervisor Cases Section -->
        <?php if(isSupervisor($username)): ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0">Supervised Cases </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Number</th>
                                <th>Assigned To</th>
                                <th>Date Assigned</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM cases WHERE supervisor = :username");
                            $stmt->execute(['username' => $username]);
                            
                            if ($stmt->rowCount() > 0):
                                while($case = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['id']); ?></td>
                                    <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigneduser']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                    <td><?php echo htmlspecialchars($case['type']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-sm btn-info">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="alert alert-info">
                                            No supervised cases found.
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Shared Cases Section -->
        <?php
        $sharedTypes = ['shared01', 'shared02', 'shared03', 'shared04'];
        foreach($sharedTypes as $index => $type):
            $stmt = $conn->prepare("SELECT * FROM cases WHERE $type = :username");
            $stmt->execute(['username' => $username]);
            if($stmt->rowCount() > 0):
        ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0">Shared Cases - <?php echo $index + 1; ?></h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Number</th>
                                <th>Assigned To</th>
                                <th>Date Assigned</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($case = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['id']); ?></td>
                                    <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigneduser']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                    <td><?php echo htmlspecialchars($case['type']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $case['id']; ?>&character_name=<?php echo urlencode($characterName); ?>" class="btn btn-sm btn-info">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
            endif;
        endforeach;
        ?>
        
        <!-- If user is AG, get count of pending cases -->
        <?php
            if ($userJob === 'ag' && $jobApproved == 1) {
                $pendingStmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM cases WHERE status = 'pending'");
                $pendingStmt->execute();
                $pendingCount = $pendingStmt->fetch()['pending_count'];
                
                if ($pendingCount > 0) {
                    echo '<div class="alert alert-warning">
                        <i class="bx bx-bell"></i> You have ' . $pendingCount . ' pending case' . ($pendingCount > 1 ? 's' : '') . ' to review.
                        <a href="approve/?character_name=' . urlencode($characterDisplayName) . '" class="btn btn-sm btn-warning ms-2">Review Now</a>
                    </div>';
                }
            }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
