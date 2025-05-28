<?php
session_start();
$menu = "APPROVALS";
include('../../include/database.php');

// Check if user is AG
$stmt = $conn->prepare("SELECT job FROM users WHERE username = :username");
$stmt->execute(['username' => $_SESSION['username']]);
$user = $stmt->fetch();
$userJob = strtolower($user['job']);

if ($userJob !== 'ag') {
    // Redirect non-AG users
    header('Location: ../index.php');
    exit;
}

// Handle approval/denial
if(isset($_POST['action']) && isset($_POST['case_id'])) {
    $caseId = $_POST['case_id'];
    $action = $_POST['action'];
    
    if($action === 'approve') {
        $casenum = $_POST['casenum'];
        $date = date('m/d/Y h:i:sA', strtotime($_POST['date']));
        
        // Determine case type based on case number
        if(strpos($casenum, 'CF') !== false) {
            $type = 'CRIMINAL';
        } elseif(strpos($casenum, 'CV') !== false) {
            $type = 'CIVIL';
        } elseif(strpos($casenum, 'F') !== false) {
            $type = 'FAMILY';
        } else {
            $type = 'UNKNOWN';
        }
        
        $query = "UPDATE cases SET status = 'approved', caseid = :caseid, assigned = :assigned, type = :type WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            'caseid' => $casenum,
            'assigned' => $date,
            'type' => $type,
            'id' => $caseId
        ]);
    } else {        $query = "UPDATE cases SET status = 'denied' WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['id' => $caseId]);
    }
    
    header('Location: index.php');
    exit;
}

// Get case details for modal
if(isset($_GET['view_case'])) {
    $caseId = $_GET['view_case'];
    $stmt = $conn->prepare("SELECT * FROM cases WHERE id = :id");
    $stmt->execute(['id' => $caseId]);
    $caseDetails = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../../css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <title>Case Approval</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <span class="fw-bold text-white">Blackwood & Associates</span>
                <span class="ms-2">Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <?php include("../../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Pending Case Approvals</h4>
            </div>
            <div class="card-body">
                <?php
                // Get all pending cases
                $stmt = $conn->prepare("SELECT c.*, u.job FROM cases c JOIN users u ON u.username = c.assigneduser WHERE c.status = 'pending' ORDER BY c.id DESC");
                $stmt->execute();
                $pendingCases = $stmt->fetchAll();
                
                if(count($pendingCases) > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Submitted By</th>
                                <th>Defendant</th>
                                <th>Details</th>
                                <th>Submission Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pendingCases as $case): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($case['assigneduser']); ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($case['job']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($case['defendent']); ?></td>
                                <td><?php echo htmlspecialchars(substr($case['details'], 0, 50)) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $case['id']; ?>">
                                            <i class='bx bx-check'></i> Approve
                                        </button>
                                        <form method="post">
                                            <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                            <input type="hidden" name="action" value="deny">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to deny this case?')">
                                                <i class='bx bx-x'></i> Deny
                                            </button>
                                        </form>
                                        <a href="../view.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-info">
                                            <i class='bx bx-detail'></i> View
                                        </a>
                                    </div>
                                    
                                    <!-- Approval Modal -->
                                    <div class="modal fade" id="approveModal<?php echo $case['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="approveModalLabel">Approve Case</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        
                                                        <!-- Case Number Generation Buttons -->
                                                        <div class="row mb-3">
                                                            <div class="col-12 mb-2">
                                                                <label class="form-label">Generate Case Number</label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <button type="button" class="btn btn-danger w-100 generate-case" data-type="criminal">
                                                                    <i class='bx bx-file'></i> Criminal
                                                                </button>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <button type="button" class="btn btn-info w-100 generate-case" data-type="civil">
                                                                    <i class='bx bx-file'></i> Civil
                                                                </button>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <button type="button" class="btn btn-success w-100 generate-case" data-type="family">
                                                                    <i class='bx bx-file'></i> Family
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="casenum<?php echo $case['id']; ?>" class="form-label">Case Number</label>
                                                            <input type="text" class="form-control" id="casenum<?php echo $case['id']; ?>" name="casenum" required 
                                                                   placeholder="Format: CF for Criminal, CV for Civil, F for Family">
                                                            <small class="text-muted">Example: CF12345, CV54321, F98765</small>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="date<?php echo $case['id']; ?>" class="form-label">Date Assigned</label>
                                                            <input type="datetime-local" class="form-control" id="date<?php echo $case['id']; ?>" name="date" 
                                                                   value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                                        </div>                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Case Details</label>
                                                            <p class="form-control-plaintext border p-2 bg-light">
                                                                <?php echo nl2br(htmlspecialchars($case['details'])); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Approve Case</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class='bx bx-info-circle'></i> No pending cases to approve
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="../index.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Back to Cases
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all case generation buttons
    document.querySelectorAll('.generate-case').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const type = this.getAttribute('data-type');
            const modalId = this.closest('.modal').id;
            const caseId = modalId.replace('approveModal', '');
            let caseNumber;
            
            // Generate case number based on type
            if (type === 'criminal') {
                caseNumber = 'CF-' + Math.floor(Math.random() * 900000) + 100000;
            } else if (type === 'civil') {
                caseNumber = 'CV-' + Math.floor(Math.random() * 900000) + 100000;
            } else if (type === 'family') {
                caseNumber = 'F-' + Math.floor(Math.random() * 900000) + 100000;
            }
            
            // Set the case number in the input field
            document.getElementById('casenum' + caseId).value = caseNumber;
        });
    });
});
</script>

</body>
</html>