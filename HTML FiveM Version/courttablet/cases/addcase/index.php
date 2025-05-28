<?php
require_once '../../include/database.php';
require_once '../../auth/character_auth.php';

// Get character name from URL parameters
$characterName = $_GET['charactername'] ?? $_GET['character_name'] ?? '';

if (empty($characterName)) {
    header("Location: ../../?error=character_not_found");
    exit();
}

// Get current character data and username
$currentCharacter = getCharacterData($characterName);
if (!$currentCharacter) {
    header("Location: ../../?error=not_found&charactername=" . urlencode($characterName));
    exit();
}

// Get the username for this character
$usernameStmt = $conn->prepare("SELECT username FROM users WHERE charactername = ?");
$usernameStmt->execute([$characterName]);
$userResult = $usernameStmt->fetch(PDO::FETCH_ASSOC);

if (!$userResult) {
    header("Location: ../../?error=user_not_found&charactername=" . urlencode($characterName));
    exit();
}

$username = $userResult['username'];
$characterDisplayName = $currentCharacter['charactername'] ?? ($currentCharacter['first_name'] . ' ' . $currentCharacter['last_name']);
$characterJob = $currentCharacter['job'];

// Get all users for sharing dropdown - get both charactername and username
$usersStmt = $conn->prepare("SELECT DISTINCT charactername, username, job FROM users WHERE charactername IS NOT NULL AND charactername != '' AND charactername != ? ORDER BY charactername");
$usersStmt->execute([$characterName]); // Exclude current user from sharing options
$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

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
$allowedJobs = ['police', 'lawyer', 'judge', 'admin', 'Police', 'Lawyer', 'Judge', 'Admin', 'POLICE', 'LAWYER', 'JUDGE', 'ADMIN', 'ag'];
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
        strpos($jobLower, 'officer') !== false ||
        strpos($jobLower, 'ag') !== false
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
        strpos($jobLower, 'ag') !== false || 
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

// Function to generate case number based on type
function generateCaseNumber($type) {
    $typeLower = strtolower($type);
    $randomNumber = mt_rand(100000, 999999);
    
    if ($typeLower === 'criminal') {
        return 'CF-' . $randomNumber;
    } elseif ($typeLower === 'civil') {
        return 'CV-' . $randomNumber;
    } elseif ($typeLower === 'family') {
        return 'F-' . $randomNumber;
    } elseif ($typeLower === 'traffic') {
        return 'TF-' . $randomNumber;
    } elseif ($typeLower === 'property') {
        return 'PR-' . $randomNumber;
    } else {
        return 'OT-' . $randomNumber; // Other cases
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $defendant = trim($_POST['defendant'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $evidence = trim($_POST['evidence'] ?? '');
    $shared01 = trim($_POST['shared01'] ?? '');
    $shared02 = trim($_POST['shared02'] ?? '');
    $shared03 = trim($_POST['shared03'] ?? '');
    $shared04 = trim($_POST['shared04'] ?? '');
    
    if (empty($defendant) || empty($details) || empty($type)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // Generate case ID
            $caseId = generateCaseNumber($type);
            
            // Check if case ID already exists (very unlikely but good practice)
            $checkStmt = $conn->prepare("SELECT id FROM cases WHERE caseid = ?");
            $checkStmt->execute([$caseId]);
            
            while ($checkStmt->fetch()) {
                $caseId = generateCaseNumber($type);
                $checkStmt->execute([$caseId]);
            }
            
            // Determine status and type based on job role
            $caseStatus = getCaseStatus($characterJob);
            $caseType = getCaseType($characterJob, $type);
            
            // Check if the evidence column exists
            $checkColumn = $conn->query("SHOW COLUMNS FROM cases LIKE 'evidence'");
            $evidenceColumnExists = $checkColumn->rowCount() > 0;
            
            // Check if shared columns exist
            $checkSharedColumns = $conn->query("SHOW COLUMNS FROM cases WHERE Field IN ('shared01', 'shared02', 'shared03', 'shared04')");
            $sharedColumnsExist = $checkSharedColumns->rowCount() > 0;
            
            if ($evidenceColumnExists && $sharedColumnsExist) {
                // If both evidence and shared columns exist
                $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, evidence, status, type, shared01, shared02, shared03, shared04) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $data = [
                    $caseId,
                    $username,  // Use username instead of characterDisplayName
                    date('Y-m-d H:i:s'),
                    $details,
                    $defendant,
                    $evidence,
                    $caseStatus,
                    $caseType,
                    $shared01 ?: '',
                    $shared02 ?: '',
                    $shared03 ?: '',
                    $shared04 ?: ''
                ];
            } elseif ($evidenceColumnExists) {
                // If only evidence column exists
                $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, evidence, status, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $data = [
                    $caseId,
                    $username,  // Use username instead of characterDisplayName
                    date('Y-m-d H:i:s'),
                    $details,
                    $defendant,
                    $evidence,
                    $caseStatus,
                    $caseType
                ];
            } elseif ($sharedColumnsExist) {
                // If only shared columns exist
                $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, status, type, shared01, shared02, shared03, shared04) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $data = [
                    $caseId,
                    $username,  // Use username instead of characterDisplayName
                    date('Y-m-d H:i:s'),
                    $details,
                    $defendant,
                    $caseStatus,
                    $caseType,
                    $shared01 ?: '',
                    $shared02 ?: '',
                    $shared03 ?: '',
                    $shared04 ?: ''
                ];
            } else {
                // If neither evidence nor shared columns exist
                $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, status, type) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $data = [
                    $caseId,
                    $username,  // Use username instead of characterDisplayName
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
            $defendant = $details = $type = $evidence = $shared01 = $shared02 = $shared03 = $shared04 = '';
            
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
            <div class="col-md-10">
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
                                        <option value="Criminal" <?php echo (isset($type) && $type === 'Criminal') ? 'selected' : ''; ?>>Criminal </option>
                                        <option value="Civil" <?php echo (isset($type) && $type === 'Civil') ? 'selected' : ''; ?>>Civil </option>
                                        <option value="Traffic" <?php echo (isset($type) && $type === 'Traffic') ? 'selected' : ''; ?>>Traffic </option>
                                        <option value="Family" <?php echo (isset($type) && $type === 'Family') ? 'selected' : ''; ?>>Family </option>
                                    </select>
                                    <div class="form-text">Case number will be automatically generated based on type</div>
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

                            <!-- Case Sharing Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class='bx bx-share-alt'></i> Share Case Access
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">
                                        <small>Select up to 4 users to share this case with. Shared users will be able to view and work on this case.</small>
                                    </p>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="shared01" class="form-label">Share with User 1</label>
                                            <select class="form-select user-select" id="shared01" name="shared01">
                                                <option value="">Select a user...</option>
                                                <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                            <?php echo (isset($shared01) && $shared01 === $user['username']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['charactername'] . ' (' . $user['job'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="shared02" class="form-label">Share with User 2</label>
                                            <select class="form-select user-select" id="shared02" name="shared02">
                                                <option value="">Select a user...</option>
                                                <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                            <?php echo (isset($shared02) && $shared02 === $user['username']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['charactername'] . ' (' . $user['job'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="shared03" class="form-label">Share with User 3</label>
                                            <select class="form-select user-select" id="shared03" name="shared03">
                                                <option value="">Select a user...</option>
                                                <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                            <?php echo (isset($shared03) && $shared03 === $user['username']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['charactername'] . ' (' . $user['job'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="shared04" class="form-label">Share with User 4</label>
                                            <select class="form-select user-select" id="shared04" name="shared04">
                                                <option value="">Select a user...</option>
                                                <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                            <?php echo (isset($shared04) && $shared04 === $user['username']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['charactername'] . ' (' . $user['job'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2 for user selection dropdowns
            $('.user-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select a user...',
                allowClear: true,
                width: '100%'
            });
            
            // Prevent selecting the same user multiple times
            $('.user-select').on('change', function() {
                var selectedValues = [];
                var currentSelect = $(this);
                var currentValue = currentSelect.val();
                
                // Collect all selected values
                $('.user-select').each(function() {
                    if ($(this).val() && $(this).val() !== '') {
                        selectedValues.push($(this).val());
                    }
                });
                
                // Disable already selected options in other dropdowns
                $('.user-select').each(function() {
                    var dropdown = $(this);
                    
                    dropdown.find('option').each(function() {
                        var option = $(this);
                        var optionValue = option.val();
                        
                        if (optionValue !== '' && selectedValues.includes(optionValue) && dropdown.val() !== optionValue) {
                            option.prop('disabled', true);
                        } else {
                            option.prop('disabled', false);
                        }
                    });
                    
                    // Refresh Select2 to show disabled options
                    dropdown.trigger('change.select2');
                });
            });
            
            // Trigger change event on page load to handle pre-selected values
            $('.user-select').trigger('change');
        });
    </script>
</body>
</html>