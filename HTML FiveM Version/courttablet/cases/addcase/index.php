<?php
require_once '../../include/database.php';
require_once '../../auth/character_auth.php';

// Get character name from URL parameters
$characterName = $_GET['charactername'] ?? $_GET['character_name'] ?? '';

if (empty($characterName)) {
    header("Location: ../../?error=character_not_found");
    exit();
}

// Get current character data
$currentCharacter = getCharacterData($characterName);
if (!$currentCharacter) {
    header("Location: ../../?error=not_found&charactername=" . urlencode($characterName));
    exit();
}

// Validate character access
$auth = validateCharacterAccess($characterName);
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
    header("Location: ../../?error=" . $error . "&charactername=" . urlencode($characterName));
    exit();
}

// Check if character has court access
if (!hasCourtAccess($currentCharacter)) {
    header("Location: ../../?error=no_access&charactername=" . urlencode($characterName));
    exit();
}

$characterDisplayName = $currentCharacter['charactername'] ?? ($currentCharacter['first_name'] . ' ' . $currentCharacter['last_name']);
$characterJob = $currentCharacter['job'];

// Debug: Let's see what job we're getting
$debugMode = isset($_GET['debug']);
if ($debugMode) {
    echo "<pre>Debug Info:\n";
    echo "Character Name: " . htmlspecialchars($characterName) . "\n";
    echo "Character Job: " . htmlspecialchars($characterJob) . "\n";
    echo "Character Data: " . print_r($currentCharacter, true) . "\n";
    echo "</pre>";
}

// Check if user has permission to add cases - EXPANDED PERMISSIONS
$allowedJobs = ['police', 'lawyer', 'judge', 'admin', 'Police', 'Lawyer', 'Judge', 'Admin', 'POLICE', 'LAWYER', 'JUDGE', 'ADMIN'];
$hasPermission = in_array($characterJob, $allowedJobs);

// If still no permission, let's be more flexible and check for partial matches
if (!$hasPermission) {
    $jobLower = strtolower($characterJob);
    $hasPermission = (
        strpos($jobLower, 'police') !== false ||
        strpos($jobLower, 'lawyer') !== false ||
        strpos($jobLower, 'judge') !== false ||
        strpos($jobLower, 'admin') !== false ||
        strpos($jobLower, 'attorney') !== false ||
        strpos($jobLower, 'prosecutor') !== false ||
        strpos($jobLower, 'detective') !== false ||
        strpos($jobLower, 'officer') !== false
    );
}

if (!$hasPermission && !$debugMode) {
    // Add debug parameter to see what's happening
    header("Location: ../index.php?charactername=" . urlencode($characterName) . "&error=no_permission&job=" . urlencode($characterJob) . "&debug_link=1");
    exit();
}

// Function to determine case status based on job role
function getCaseStatus($job) {
    $jobLower = strtolower($job);
    
    // Police cases need approval, so they start as pending
    if (strpos($jobLower, 'police') !== false || 
        strpos($jobLower, 'officer') !== false || 
        strpos($jobLower, 'detective') !== false) {
        return 'pending';
    }
    
    // Judges, lawyers, attorneys, prosecutors can create approved cases
    if (strpos($jobLower, 'judge') !== false || 
        strpos($jobLower, 'lawyer') !== false || 
        strpos($jobLower, 'attorney') !== false || 
        strpos($jobLower, 'prosecutor') !== false || 
        strpos($jobLower, 'admin') !== false) {
        return 'approved';
    }
    
    // Default to pending for any other roles
    return 'pending';
}

// Function to determine case type based on job role
function getCaseType($job, $selectedType) {
    $jobLower = strtolower($job);
    
    // Police cases are typically criminal or traffic
    if (strpos($jobLower, 'police') !== false || 
        strpos($jobLower, 'officer') !== false || 
        strpos($jobLower, 'detective') !== false) {
        return $selectedType; // Use whatever they selected
    }
    
    // For other roles, use their selection or default to the selected type
    return $selectedType;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $defendant = trim($_POST['defendant'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $evidence = trim($_POST['evidence'] ?? '');
    
    if (empty($defendant) || empty($details) || empty($type)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // Generate case ID
            $caseId = 'CASE-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Check if case ID already exists
            $checkStmt = $conn->prepare("SELECT id FROM cases WHERE caseid = ?");
            $checkStmt->execute([$caseId]);
            
            while ($checkStmt->fetch()) {
                $caseId = 'CASE-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $checkStmt->execute([$caseId]);
            }
            
            // Determine status and type based on job role
            $caseStatus = getCaseStatus($characterJob);
            $caseType = getCaseType($characterJob, $type);
            
            // Check if the evidence column exists
            $checkColumn = $conn->query("SHOW COLUMNS FROM cases LIKE 'evidence'");
            $evidenceColumnExists = $checkColumn->rowCount() > 0;
            
            if ($evidenceColumnExists) {
                // If evidence column exists, include it in the query
                $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, evidence, status, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $data = [
                    $caseId,
                    $characterDisplayName,
                    date('Y-m-d H:i:s'),
                    $details,
                    $defendant,
                    $evidence,
                    $caseStatus,
                    $caseType
                ];
            } else {
                // If evidence column doesn't exist, exclude it from the query
                $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, status, type) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $data = [
                    $caseId,
                    $characterDisplayName,
                    date('Y-m-d H:i:s'),
                    $details,
                    $defendant,
                    $caseStatus,
                    $caseType
                ];
            }
            
            // Execute the query
            $stmt = $conn->prepare($query);
            $stmt->execute($data);
            
            $statusMessage = ($caseStatus === 'approved') ? 'approved and ready for processing' : 'pending approval';
            $success_message = "Case " . $caseId . " has been successfully created and is " . $statusMessage . "!";
            
            // Clear form data
            $defendant = $details = $type = $evidence = '';
            
        } catch (Exception $e) {
            $error_message = "Error creating case: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Case - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../../">
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
                <?php if ($debugMode): ?>
                    <div class="alert alert-info mb-4">
                        <h5>Debug Information</h5>
                        <p><strong>Character Name:</strong> <?php echo htmlspecialchars($characterName); ?></p>
                        <p><strong>Character Job:</strong> <?php echo htmlspecialchars($characterJob); ?></p>
                        <p><strong>Has Permission:</strong> <?php echo $hasPermission ? 'Yes' : 'No'; ?></p>
                        <p><strong>Case Status Will Be:</strong> <?php echo getCaseStatus($characterJob); ?></p>
                        <p><strong>Allowed Jobs:</strong> <?php echo implode(', ', array_slice($allowedJobs, 0, 4)); ?></p>
                        <details>
                            <summary>Full Character Data</summary>
                            <pre><?php print_r($currentCharacter); ?></pre>
                        </details>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class='bx bx-plus-circle'></i> Add New Case
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!$hasPermission): ?>
                            <div class="alert alert-warning">
                                <i class='bx bx-error-circle'></i> 
                                <strong>Permission Issue:</strong> Your job role "<?php echo htmlspecialchars($characterJob); ?>" may not have permission to create cases.
                                <br><small>If this is incorrect, please contact an administrator.</small>
                                <br><a href="?<?php echo $_SERVER['QUERY_STRING']; ?>&debug=1" class="btn btn-sm btn-outline-primary mt-2">View Debug Info</a>
                            </div>
                        <?php endif; ?>

                        <!-- Status Information -->
                        <div class="alert alert-info">
                            <i class='bx bx-info-circle'></i> 
                            <strong>Case Status:</strong> 
                            <?php 
                            $futureStatus = getCaseStatus($characterJob);
                            if ($futureStatus === 'approved') {
                                echo 'Cases created by your role (' . htmlspecialchars($characterJob) . ') will be automatically <strong>approved</strong>.';
                            } else {
                                echo 'Cases created by your role (' . htmlspecialchars($characterJob) . ') will be <strong>pending approval</strong>.';
                            }
                            ?>
                        </div>

                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="defendant" class="form-label">Defendant Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="defendant" name="defendant" 
                                           value="<?php echo htmlspecialchars($defendant ?? ''); ?>" required>
                                    <div class="form-text">Enter the full name of the defendant</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="type" class="form-label">Case Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Select case type...</option>
                                        <option value="Criminal" <?php echo (isset($type) && $type === 'Criminal') ? 'selected' : ''; ?>>Criminal</option>
                                        <option value="Civil" <?php echo (isset($type) && $type === 'Civil') ? 'selected' : ''; ?>>Civil</option>
                                        <option value="Traffic" <?php echo (isset($type) && $type === 'Traffic') ? 'selected' : ''; ?>>Traffic</option>
                                        <option value="Family" <?php echo (isset($type) && $type === 'Family') ? 'selected' : ''; ?>>Family</option>
                                        <option value="Property" <?php echo (isset($type) && $type === 'Property') ? 'selected' : ''; ?>>Property</option>
                                        <option value="Other" <?php echo (isset($type) && $type === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="details" class="form-label">Case Details <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="details" name="details" rows="6" required><?php echo htmlspecialchars($details ?? ''); ?></textarea>
                                <div class="form-text">Provide detailed information about the case, charges, and circumstances</div>
                            </div>

                            <div class="mb-3">
                                <label for="evidence" class="form-label">Evidence Description</label>
                                <textarea class="form-control" id="evidence" name="evidence" rows="4"><?php echo htmlspecialchars($evidence ?? ''); ?></textarea>
                                <div class="form-text">Describe any evidence related to this case (optional)</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Case Information</h6>
                                            <p class="card-text mb-1"><strong>Filed by:</strong> <?php echo htmlspecialchars($characterDisplayName); ?></p>
                                            <p class="card-text mb-1"><strong>Role:</strong> <?php echo htmlspecialchars($characterJob); ?></p>
                                            <p class="card-text mb-1"><strong>Status:</strong> 
                                                <span class="badge bg-<?php echo getCaseStatus($characterJob) === 'approved' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst(getCaseStatus($characterJob)); ?>
                                                </span>
                                            </p>
                                            <p class="card-text mb-0"><strong>Date:</strong> <?php echo date('M j, Y g:i A'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg" <?php echo !$hasPermission ? 'disabled' : ''; ?>>
                                            <i class='bx bx-plus-circle'></i> Create Case
                                        </button>
                                        <a href="../?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-secondary">
                                            <i class='bx bx-arrow-back'></i> Back to Cases
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
