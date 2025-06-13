<?php
session_start();
$menu = "CASES";

if(!isset($_SESSION['username'])) {
    header("Location:../index.php");
    exit();
}

include("../include/database.php");

// Get user information including job and character name
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set a flag for attorney status
$isAttorney = ($user['job'] === "Attorney");
$characterName = $user['charactername'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="../../css/dark-mode.css" rel="stylesheet">
    <script src="../../js/dark-mode.js"></script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <!-- <img src="../images/logo.png" alt="Logo" class="img-fluid me-2" style="max-height: 40px;"> -->
                <span class="fw-bold text-white">Blackwood & Associates</span>
                <span class="ms-2">Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Main Cases Section -->
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h2>Case Management</h2>
                <div class="d-flex gap-2">
                    <?php
                    // Get the user's job from the database
                    $stmt = $conn->prepare("SELECT job FROM users WHERE username = :username");
                    $stmt->execute(['username' => $_SESSION['username']]);
                    $user = $stmt->fetch();
                    $userJob = strtolower($user['job']);
                    
                    // Show approval page link for Attorney General
                    if ($userJob == 'ag' && isset($jobApproved) && $jobApproved == 1) {
                        echo '<a href="approve/" class="btn btn-warning me-2">
                            <i class="bx bx-check-shield"></i> Pending Approvals
                        </a>';
                    }
                    
                    // Different button text based on job - Only show for non-civilians
                    if ($userJob !== 'civilian') {
                        if ($userJob === 'police') {
                            $buttonText = "Submit A Case";
                            $buttonLink = "submitcase/";
                        } else {
                            $buttonText = "New Case";
                            $buttonLink = "addcase/";
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
                                <th>Parties</th>
                                <th>Supervisor</th>
                                <th>Date Assigned</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get the user's job and character name
                            $stmt = $conn->prepare("SELECT job, job_approved, charactername FROM users WHERE username = :username");
                            $stmt->execute(['username' => $_SESSION['username']]);
                            $user = $stmt->fetch();
                            $userJob = strtolower($user['job']);
                            $jobApproved = $user['job_approved'] ?? 1; // Default to 1 for backward compatibility
                            $characterName = $user['charactername'];
                            $isAG = ($userJob === 'ag');
                            $isCivilian = ($userJob === 'civilian');

                            // Active Cases Query
                            if ($isCivilian) {
                                // For civilians, look for cases where they are the defendant (by character name)
                                $query = "SELECT * FROM cases WHERE defendent = :charactername AND (status != 'closed' OR status IS NULL)";
                                $stmt = $conn->prepare($query);
                                $stmt->bindParam(':charactername', $characterName);
                            } else {
                                // For non-civilians, get cases where this user is the assigned user (creator)
                                $query = "SELECT * FROM cases WHERE assigneduser = :username AND (status != 'closed' OR status IS NULL)";

                                // For non-AG users, filter out pending cases
                                if (!$isAG) {
                                    $query .= " AND (status != 'pending' OR status IS NULL)";
                                }

                                $stmt = $conn->prepare($query);
                                $stmt->bindParam(':username', $_SESSION['username']);
                            }

                            // Add order by
                            $query .= " ORDER BY id DESC";
                            $stmt = $conn->prepare($query);
                            
                            if ($isCivilian) {
                                $stmt->bindParam(':charactername', $characterName);
                            } else {
                                $stmt->bindParam(':username', $_SESSION['username']);
                            }
                            
                            $stmt->execute();
                            $cases = $stmt->fetchAll();

                            // Closed Cases Query
                            if ($isCivilian) {
                                $closed_query = "SELECT * FROM cases WHERE defendent = :charactername AND status = 'closed' ORDER BY closed_date DESC";
                                $closed_stmt = $conn->prepare($closed_query);
                                $closed_stmt->bindParam(':charactername', $characterName);
                            } else {
                                $closed_query = "SELECT * FROM cases WHERE assigneduser = :username AND status = 'closed' ORDER BY closed_date DESC";
                                $closed_stmt = $conn->prepare($closed_query);
                                $closed_stmt->bindParam(':username', $_SESSION['username']);
                            }

                            $closed_stmt->execute();
                            $closed_cases = $closed_stmt->fetchAll();
                            
                            if(!empty($cases)):
                                foreach($cases as $case):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <?php if (!empty($case['plaintiff'])): ?>
                                            <small class="text-success">
                                                <i class='bx bx-user-check'></i> <strong>Plaintiff:</strong> <?php echo htmlspecialchars($case['plaintiff']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <small class="text-danger">
                                            <i class='bx bx-user-x'></i> <strong>Defendant:</strong> <?php echo htmlspecialchars($case['defendent']); ?>
                                        </small>
                                    </div>
                                </td>
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
                                    <?php elseif(isset($case['status']) && $case['status'] == 'closed'): ?>
                                        <span class="badge bg-success">Closed</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <?php if (!$isCivilian): ?>
                                        <a href="modify.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="delete.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <?php if ($isCivilian): ?>
                                        No active cases found where you are listed as a defendant.
                                    <?php else: ?>
                                        No active cases found
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Closed Cases Section -->
        <?php if (!empty($closed_cases)): ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-success text-white">
                <h3 class="mb-0"><i class='bx bx-check-circle'></i> Closed Cases (<?php echo count($closed_cases); ?>)</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Number</th>
                                <th>Parties</th>
                                <th>Supervisor</th>
                                <th>Date Assigned</th>
                                <th>Date Closed</th>
                                <th>Closed By</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($closed_cases as $case): ?>
                            <tr class="table-light">
                                <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                <td><?php echo htmlspecialchars($case['type']); ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <?php if (!empty($case['plaintiff'])): ?>
                                            <small class="text-success">
                                                <i class='bx bx-user-check'></i> <strong>Plaintiff:</strong> <?php echo htmlspecialchars($case['plaintiff']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <small class="text-danger">
                                            <i class='bx bx-user-x'></i> <strong>Defendant:</strong> <?php echo htmlspecialchars($case['defendent']); ?>
                                        </small>
                                    </div>
                                </td>
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
                                <td>
                                    <?php if($case['closed_date']): ?>
                                        <?php echo date('M j, Y g:i A', strtotime($case['closed_date'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($case['closed_by']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($case['closed_by']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($case['type']); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <?php if (!$isCivilian): ?>
                                        <a href="reopen_case.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-success" 
                                           onclick="return confirm('Are you sure you want to reopen this case?')">Reopen</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Supervisor Cases Section -->
        <?php if(isSupervisor($_SESSION['username'])): ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0">Supervised Cases</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Number</th>
                                <th>Parties</th>
                                <th>Assigned To</th>
                                <th>Date Assigned</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM cases WHERE supervisor = :username");
                            $stmt->execute(['username' => $_SESSION['username']]);
                            
                            if ($stmt->rowCount() > 0):
                                while($case = $stmt->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['id']); ?></td>
                                    <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <?php if (!empty($case['plaintiff'])): ?>
                                                <small class="text-success">
                                                    <i class='bx bx-user-check'></i> <strong>Plaintiff:</strong> <?php echo htmlspecialchars($case['plaintiff']); ?>
                                                </small>
                                            <?php endif; ?>
                                            <small class="text-danger">
                                                <i class='bx bx-user-x'></i> <strong>Defendant:</strong> <?php echo htmlspecialchars($case['defendent']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($case['assigneduser']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                    <td><?php echo htmlspecialchars($case['type']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-info">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center">
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
            $stmt->execute(['username' => $_SESSION['username']]);
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
                                <th>Parties</th>
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
                                    <td>
                                        <div class="d-flex flex-column">
                                            <?php if (!empty($case['plaintiff'])): ?>
                                                <small class="text-success">
                                                    <i class='bx bx-user-check'></i> <strong>Plaintiff:</strong> <?php echo htmlspecialchars($case['plaintiff']); ?>
                                                </small>
                                            <?php endif; ?>
                                            <small class="text-danger">
                                                <i class='bx bx-user-x'></i> <strong>Defendant:</strong> <?php echo htmlspecialchars($case['defendent']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($case['assigneduser']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                    <td><?php echo htmlspecialchars($case['type']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-info">
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
                        <a href="approve/" class="btn btn-sm btn-warning ms-2">Review Now</a>
                    </div>';
                }
            }
        ?>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function isSupervisor($username) {
    global $conn;
    $stmt = $conn->prepare("SELECT supervisorjob FROM users WHERE username = ? AND supervisorjob = '1'");
    $stmt->execute([$username]);
    return $stmt->rowCount() > 0;
}
?>