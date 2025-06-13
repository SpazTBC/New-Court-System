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
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Case - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
        .bg-primary {
            background-color: #238636 !important;
        }
        .bg-light {
            background-color: #30363d !important;
            color: #e0e0e0 !important;
        }
        .form-control, .form-select {
            background-color: #21262d;
            border-color: #30363d;
            color: #e0e0e0;
        }
        .form-control:focus, .form-select:focus {
            background-color: #21262d;
            border-color: #58a6ff;
            color: #e0e0e0;
            box-shadow: 0 0 0 0.25rem rgba(88, 166, 255, 0.25);
        }
        .btn-primary {
            background-color: #238636;
            border-color: #238636;
        }
        .btn-primary:hover {
            background-color: #2ea043;
            border-color: #2ea043;
        }
        .btn-outline-primary {
            color: #238636;
            border-color: #238636;
        }
        .btn-outline-primary:hover {
            background-color: #238636;
            border-color: #238636;
        }
        .alert-info {
            background-color: #0c2d48;
            border-color: #1f6feb;
            color: #58a6ff;
        }
        .alert-warning {
            background-color: #332b00;
            border-color: #d29922;
            color: #d29922;
        }
        .badge.bg-success {
            background-color: #238636 !important;
        }
        .badge.bg-warning {
            background-color: #d29922 !important;
            color: #000;
        }
        .select2-container--bootstrap-5 .select2-dropdown {
            background-color: #21262d;
            border-color: #30363d;
        }
        .select2-container--bootstrap-5 .select2-results__option {
            color: #e0e0e0;
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: #238636;
        }
        .select2-container--bootstrap-5 .select2-selection {
            background-color: #21262d;
            border-color: #30363d;
            color: #e0e0e0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
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
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error'></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class='bx bx-check'></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Status Information -->
                <?php if (getCaseStatus($characterJob) === 'pending'): ?>
                    <div class="alert alert-warning">
                        <i class='bx bx-info-circle'></i> 
                        <strong>Note:</strong> Cases created by <?php echo htmlspecialchars($characterJob); ?> require approval from the Attorney General before they become active.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class='bx bx-info-circle'></i> 
                        <strong>Note:</strong> Cases created by <?php echo htmlspecialchars($characterJob); ?> are automatically approved and active.
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-primary">
                        <h3 class="mb-0"><i class='bx bx-plus'></i> Add New Case</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="charactername" value="<?php echo htmlspecialchars($characterName); ?>">
                            
                            <div class="mb-3">
                                <label for="defendant" class="form-label">Defendant Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="defendant" name="defendant" required 
                                       value="<?php echo htmlspecialchars($defendant ?? ''); ?>"
                                       placeholder="Enter the defendant's full name">
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Case Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select case type...</option>
                                    <option value="Criminal" <?php echo (isset($type) && $type == 'Criminal') ? 'selected' : ''; ?>>Criminal</option>
                                    <option value="Civil" <?php echo (isset($type) && $type == 'Civil') ? 'selected' : ''; ?>>Civil</option>
                                    <option value="Traffic" <?php echo (isset($type) && $type == 'Traffic') ? 'selected' : ''; ?>>Traffic</option>
                                    <option value="Family" <?php echo (isset($type) && $type == 'Family') ? 'selected' : ''; ?>>Family</option>
                                    <option value="Property" <?php echo (isset($type) && $type == 'Property') ? 'selected' : ''; ?>>Property</option>
                                    <option value="Corporate" <?php echo (isset($type) && $type == 'Corporate') ? 'selected' : ''; ?>>Corporate</option>
                                    <option value="Immigration" <?php echo (isset($type) && $type == 'Immigration') ? 'selected' : ''; ?>>Immigration</option>
                                    <option value="Employment" <?php echo (isset($type) && $type == 'Employment') ? 'selected' : ''; ?>>Employment</option>
                                    <option value="Other" <?php echo (isset($type) && $type == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div class="form-text">Case number will be automatically generated based on type</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="details" class="form-label">Case Details <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="details" name="details" rows="6" required 
                                          placeholder="Provide detailed information about the case, including charges, circumstances, and relevant facts..."><?php echo htmlspecialchars($details ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="evidence" class="form-label">Initial Evidence (Optional)</label>
                                <textarea class="form-control" id="evidence" name="evidence" rows="3" 
                                          placeholder="List any initial evidence related to this case..."><?php echo htmlspecialchars($evidence ?? ''); ?></textarea>
                                <div class="form-text">Additional evidence can be uploaded after case creation</div>
                            </div>

                            <!-- Case Sharing Section -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class='bx bx-share'></i> Case Sharing (Optional)</h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small">Share this case with other users to allow them to view and collaborate on it.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="shared01" class="form-label">Share with User 1</label>
                                            <select class="form-select select2" id="shared01" name="shared01">
                                                <option value="">Select user...</option>
                                                <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                            <?php echo (isset($shared01) && $shared01 == $user['username']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['charactername']); ?> 
                                                        (<?php echo htmlspecialchars($user['job']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="shared02" class="form-label">Share with User 2</label>
                                            <select class="form-select select2" id="shared02" name="shared02">
                                                <option value="">Select user...</option>
                                                <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                            <?php echo (isset($shared02) && $shared02 == $user['username']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['charactername']); ?> 
                                                        (<?php echo htmlspecialchars($user['job']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="shared03" class="form-label">Share with User 3</label>
                                            <select class="form-select select2" id="shared03" name="shared03">
                                                <option value="">Select user...</option>
                                                <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                            <?php echo (isset($shared03) && $shared03 == $user['username']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['charactername']); ?> 
                                                        (<?php echo htmlspecialchars($user['job']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="shared04" class="form-label">Share with User 4</label>
                                            <select class="form-select select2" id="shared04" name="shared04">
                                                <option value="">Select user...</option>
                                                <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                            <?php echo (isset($shared04) && $shared04 == $user['username']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['charactername']); ?> 
                                                        (<?php echo htmlspecialchars($user['job']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="../?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-secondary me-md-2">
                                    <i class='bx bx-arrow-back'></i> Back to Cases
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-check'></i> Create Case
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Case Creation Info -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class='bx bx-info-circle'></i> Case Creation Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Created By:</strong> <?php echo htmlspecialchars($characterDisplayName); ?></p>
                                <p><strong>Role:</strong> <?php echo htmlspecialchars($characterJob); ?></p>
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Case Status:</strong> 
                                    <span class="badge bg-<?php echo getCaseStatus($characterJob) === 'approved' ? 'success' : 'warning'; ?>">
                                        <?php echo htmlspecialchars(ucfirst(getCaseStatus($characterJob))); ?>
                                    </span>
                                </p>
                                <p><strong>Creation Date:</strong> <?php echo date('M j, Y g:i A'); ?></p>
                                <p><strong>Available Users:</strong> <?php echo count($allUsers); ?> users</p>
                            </div>
                        </div>
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
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select user...',
                allowClear: true,
                width: '100%'
            });

            // Prevent selecting the same user multiple times
            $('.select2').on('select2:select', function (e) {
                var selectedValue = e.params.data.id;
                var currentSelect = $(this);
                
                // Get all other select elements
                $('.select2').not(currentSelect).each(function() {
                    var otherSelect = $(this);
                    var otherValue = otherSelect.val();
                    
                    // If another select has the same value, clear it
                    if (otherValue === selectedValue) {
                        otherSelect.val(null).trigger('change');
                        
                        // Show a brief notification
                        var notification = $('<div class="alert alert-info alert-dismissible fade show mt-2" role="alert">' +
                            '<i class="bx bx-info-circle"></i> User was moved from another sharing slot.' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>');
                        
                        currentSelect.closest('.col-md-6').append(notification);
                        
                        // Auto-dismiss after 3 seconds
                        setTimeout(function() {
                            notification.alert('close');
                        }, 3000);
                    }
                });
            });

            // Form validation
            $('form').on('submit', function(e) {
                var defendant = $('#defendant').val().trim();
                var details = $('#details').val().trim();
                var type = $('#type').val();
                
                if (!defendant || !details || !type) {
                    e.preventDefault();
                    
                    var errorAlert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        '<i class="bx bx-error-circle"></i> Please fill in all required fields (Defendant Name, Case Type, and Case Details).' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    
                    $('.container .row .col-md-8').prepend(errorAlert);
                    
                    // Scroll to top to show error
                    $('html, body').animate({
                        scrollTop: 0
                    }, 500);
                    
                    return false;
                }
                
                // Show loading state
                var submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="bx bx-loader-alt bx-spin"></i> Creating Case...');
            });

            // Auto-resize textareas
            $('textarea').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    </script>
</body>
</html>
