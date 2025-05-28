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

// Get case ID from URL
$caseId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get case details
$stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
$stmt->execute([$caseId]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    header("Location: index.php?character_name=" . urlencode($characterName) . "&error=case_not_found");
    exit();
}

// Get all users for dropdowns
$users_stmt = $conn->prepare("SELECT username, charactername, job FROM users ORDER BY charactername");
$users_stmt->execute();
$all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get supervisors (users with supervisorjob = '1' or specific supervisor roles)
$supervisors_stmt = $conn->prepare("SELECT username, charactername, job FROM users WHERE supervisorjob = '1' OR job LIKE '%supervisor%' OR job LIKE '%chief%' OR job LIKE '%captain%' ORDER BY charactername");
$supervisors_stmt->execute();
$supervisors = $supervisors_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assigned = $_POST['assigned'] ?? $case['assigneduser'];
    $details = $_POST['details'] ?? $case['details'];
    $type = $_POST['type'] ?? $case['type'];
    $supervisor = $_POST['supervisor'] ?? $case['supervisor'];
    $status = $_POST['status'] ?? $case['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE cases SET assigneduser = ?, details = ?, type = ?, supervisor = ?, status = ? WHERE id = ?");
        $stmt->execute([$assigned, $details, $type, $supervisor, $status, $caseId]);

        // Update supervisor job status if supervisor is assigned
        if (!empty($supervisor)) {
            $stmt = $conn->prepare("UPDATE users SET supervisorjob = '1' WHERE username = ? OR charactername = ?");
            $stmt->execute([$supervisor, $supervisor]);
        }
        
        header("Location: view.php?id=" . $caseId . "&character_name=" . urlencode($characterName) . "&success=updated");
        exit();
        
    } catch (Exception $e) {
        $error_message = "Error updating case: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Case - Court System</title>
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
                    <span class="ms-2"><?php echo htmlspecialchars($characterDisplayName); ?> (<?php echo htmlspecialchars($characterJob); ?>)</span>
                </span>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h3 class="mb-0"><i class='bx bx-edit'></i> Modify Case #<?php echo htmlspecialchars($case['caseid']); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class='bx bx-error'></i> <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="assigned" class="form-label">Assigned User</label>
                                    <select class="form-select" id="assigned" name="assigned" required>
                                        <option value="">Select User</option>
                                        <?php foreach ($all_users as $user): ?>
                                            <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                    <?php echo ($case['assigneduser'] == $user['username']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['charactername']); ?> 
                                                (<?php echo htmlspecialchars($user['job']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="supervisor" class="form-label">Supervisor</label>
                                    <select class="form-select" id="supervisor" name="supervisor">
                                        <option value="">No Supervisor</option>
                                        <?php foreach ($supervisors as $supervisor): ?>
                                            <option value="<?php echo htmlspecialchars($supervisor['username']); ?>" 
                                                    <?php echo ($case['supervisor'] == $supervisor['username']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($supervisor['charactername']); ?> 
                                                (<?php echo htmlspecialchars($supervisor['job']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="type" class="form-label">Case Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="PENDING" <?php echo ($case['type'] == 'PENDING') ? 'selected' : ''; ?>>PENDING</option>
                                        <option value="CRIMINAL" <?php echo ($case['type'] == 'CRIMINAL') ? 'selected' : ''; ?>>CRIMINAL</option>
                                        <option value="CIVIL" <?php echo ($case['type'] == 'CIVIL') ? 'selected' : ''; ?>>CIVIL</option>
                                        <option value="TRAFFIC" <?php echo ($case['type'] == 'TRAFFIC') ? 'selected' : ''; ?>>TRAFFIC</option>
                                        <option value="FAMILY" <?php echo ($case['type'] == 'FAMILY') ? 'selected' : ''; ?>>FAMILY</option>
                                        <option value="CORPORATE" <?php echo ($case['type'] == 'CORPORATE') ? 'selected' : ''; ?>>CORPORATE</option>
                                        <option value="IMMIGRATION" <?php echo ($case['type'] == 'IMMIGRATION') ? 'selected' : ''; ?>>IMMIGRATION</option>
                                        <option value="BANKRUPTCY" <?php echo ($case['type'] == 'BANKRUPTCY') ? 'selected' : ''; ?>>BANKRUPTCY</option>
                                        <option value="INTELLECTUAL_PROPERTY" <?php echo ($case['type'] == 'INTELLECTUAL_PROPERTY') ? 'selected' : ''; ?>>INTELLECTUAL PROPERTY</option>
                                        <option value="EMPLOYMENT" <?php echo ($case['type'] == 'EMPLOYMENT') ? 'selected' : ''; ?>>EMPLOYMENT</option>
                                        <option value="REAL_ESTATE" <?php echo ($case['type'] == 'REAL_ESTATE') ? 'selected' : ''; ?>>REAL ESTATE</option>
                                        <option value="TAX" <?php echo ($case['type'] == 'TAX') ? 'selected' : ''; ?>>TAX</option>
                                        <option value="ENVIRONMENTAL" <?php echo ($case['type'] == 'ENVIRONMENTAL') ? 'selected' : ''; ?>>ENVIRONMENTAL</option>
                                        <option value="PERSONAL_INJURY" <?php echo ($case['type'] == 'PERSONAL_INJURY') ? 'selected' : ''; ?>>PERSONAL INJURY</option>
                                        <option value="CONTRACT" <?php echo ($case['type'] == 'CONTRACT') ? 'selected' : ''; ?>>CONTRACT</option>
                                        <option value="OTHER" <?php echo ($case['type'] == 'OTHER') ? 'selected' : ''; ?>>OTHER</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Case Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="pending" <?php echo ($case['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="active" <?php echo ($case['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="on_hold" <?php echo ($case['status'] == 'on_hold') ? 'selected' : ''; ?>>On Hold</option>
                                        <option value="closed" <?php echo ($case['status'] == 'closed') ? 'selected' : ''; ?>>Closed</option>
                                        <option value="dismissed" <?php echo ($case['status'] == 'dismissed') ? 'selected' : ''; ?>>Dismissed</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="details" class="form-label">Case Details</label>
                                <textarea class="form-control" id="details" name="details" rows="8" required><?php echo htmlspecialchars($case['details']); ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="view.php?id=<?php echo $caseId; ?>&character_name=<?php echo urlencode($characterName); ?>" 
                                   class="btn btn-secondary me-md-2">
                                    <i class='bx bx-x'></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class='bx bx-save'></i> Update Case
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Current Case Information -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class='bx bx-info-circle'></i> Current Case Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Case ID:</strong> <?php echo htmlspecialchars($case['caseid']); ?></p>
                                <p><strong>Current Assigned User:</strong> <?php echo htmlspecialchars($case['assigneduser']); ?></p>
                                <p><strong>Current Type:</strong> <?php echo htmlspecialchars($case['type']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Current Status:</strong> 
                                    <span class="badge bg-<?php echo $case['status'] == 'pending' ? 'warning' : ($case['status'] == 'closed' ? 'success' : 'primary'); ?>">
                                        <?php echo htmlspecialchars(ucfirst($case['status'])); ?>
                                    </span>
                                </p>
                                <p><strong>Defendant:</strong> <?php echo htmlspecialchars($case['defendent']); ?></p>
                                <p><strong>Assigned Date:</strong> <?php echo $case['assigned'] ? date('M j, Y', strtotime($case['assigned'])) : 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
