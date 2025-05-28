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

// Check if user has permission to submit cases - Police should be able to submit
$allowedJobs = ['police', 'attorney', 'judge', 'admin', 'Police', 'Attorney', 'Judge', 'Admin', 'POLICE', 'ATTORNEY', 'JUDGE', 'ADMIN'];
$hasPermission = in_array($characterJob, $allowedJobs);

// If still no permission, let's be more flexible and check for partial matches
if (!$hasPermission) {
    $jobLower = strtolower($characterJob);
    $hasPermission = (
        strpos($jobLower, 'police') !== false ||
        strpos($jobLower, 'attorney') !== false ||
        strpos($jobLower, 'judge') !== false ||
        strpos($jobLower, 'admin') !== false ||
        strpos($jobLower, 'prosecutor') !== false ||
        strpos($jobLower, 'detective') !== false ||
        strpos($jobLower, 'officer') !== false
    );
}

if (!$hasPermission) {
    header("Location: ../index.php?charactername=" . urlencode($characterName) . "&error=no_permission&job=" . urlencode($characterJob));
    exit();
}

$success_message = '';
$error_message = '';

// Function to generate case number based on type (matches JavaScript logic exactly)
function generateCaseNumber($type) {
    $typeLower = strtolower($type);
    // Exact match to JavaScript: Math.floor(Math.random() * 900000) + 100000
    $randomNumber = mt_rand(100000, 999999);
    
    if ($typeLower === 'criminal') {
        return 'CF-' . $randomNumber;
    } elseif ($typeLower === 'civil') {
        return 'CV-' . $randomNumber;
    } elseif ($typeLower === 'family') {
        return 'F-' . $randomNumber;
    } elseif ($typeLower === 'traffic') {
        return 'TF-' . $randomNumber;
    } else {
        return 'OT-' . $randomNumber; // Other cases
    }
}

if ($_POST && isset($_POST['submit'])) {
    $defendent = trim($_POST['defendent']);
    $details = trim($_POST['details']);
    $evidence = trim($_POST['evidence']);
    $type = trim($_POST['type']);
    
    if (empty($defendent) || empty($details) || empty($type)) {
        $error_message = "All fields except evidence are required!";
    } else {
        try {
            // Generate case ID based on type
            $tempCaseNum = generateCaseNumber($type);
            $currentDate = date('Y-m-d H:i:s');
            
            // Check if case number already exists (very unlikely but good practice)
            $checkStmt = $conn->prepare("SELECT id FROM cases WHERE caseid = ?");
            $checkStmt->execute([$tempCaseNum]);
            
            while ($checkStmt->fetch()) {
                $tempCaseNum = generateCaseNumber($type);
                $checkStmt->execute([$tempCaseNum]);
            }
            
            // Check if evidence column exists
            $checkColumn = $conn->query("SHOW COLUMNS FROM cases LIKE 'evidence'");
            $evidenceColumnExists = $checkColumn->rowCount() > 0;
            
            if ($evidenceColumnExists) {
                $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, evidence, status, type) 
                         VALUES (:caseid, :assigneduser, :assigned, :details, :defendant, :evidence, 'pending', :type)";
                $data = [
                    ':caseid' => $tempCaseNum,
                    ':assigneduser' => $characterDisplayName,
                    ':assigned' => $currentDate,
                    ':details' => $details,
                    ':defendant' => $defendent,
                    ':evidence' => $evidence,
                    ':type' => $type
                ];
            } else {
                $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, status, type) 
                         VALUES (:caseid, :assigneduser, :assigned, :details, :defendant, 'pending', :type)";
                $data = [
                    ':caseid' => $tempCaseNum,
                    ':assigneduser' => $characterDisplayName,
                    ':assigned' => $currentDate,
                    ':details' => $details,
                    ':defendant' => $defendent,
                    ':type' => $type
                ];
            }
            
            $stmt = $conn->prepare($query);
            $stmt->execute($data);
            
            $success_message = "Case submitted successfully! Case ID: " . $tempCaseNum . " (Status: Pending Approval)";
            
            // Clear form data
            $defendent = $details = $evidence = $type = '';
            
        } catch (Exception $e) {
            $error_message = "Error submitting case: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Case - Court System</title>
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
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error'></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class='bx bx-check'></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Status Information -->
                <div class="alert alert-warning">
                    <i class='bx bx-info-circle'></i> 
                    <strong>Note:</strong> Cases submitted by Police officers require approval from the Attorney General before they become active.
                </div>

                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0"><i class='bx bx-plus'></i> Submit New Case</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="charactername" value="<?php echo htmlspecialchars($characterName); ?>">
                            
                            <div class="mb-3">
                                <label for="defendent" class="form-label">Defendant Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="defendent" name="defendent" required 
                                       value="<?php echo htmlspecialchars($defendent ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Case Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select case type...</option>
                                    <option value="Criminal" <?php echo (isset($type) && $type == 'Criminal') ? 'selected' : ''; ?>>Criminal </option>
                                    <option value="Civil" <?php echo (isset($type) && $type == 'Civil') ? 'selected' : ''; ?>>Civil </option>
                                    <option value="Traffic" <?php echo (isset($type) && $type == 'Traffic') ? 'selected' : ''; ?>>Traffic </option>
                                    <option value="Family" <?php echo (isset($type) && $type == 'Family') ? 'selected' : ''; ?>>Family </option>
                                    <option value="Other" <?php echo (isset($type) && $type == 'Other') ? 'selected' : ''; ?>>Other </option>
                                </select>
                                <div class="form-text">Case number will be automatically generated based on type</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="details" class="form-label">Case Details <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="details" name="details" rows="5" required 
                                          placeholder="Provide detailed information about the case..."><?php echo htmlspecialchars($details ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="evidence" class="form-label">Evidence (Optional)</label>
                                <textarea class="form-control" id="evidence" name="evidence" rows="3" 
                                          placeholder="List any evidence related to this case..."><?php echo htmlspecialchars($evidence ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="../?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-secondary me-md-2">
                                    <i class='bx bx-arrow-back'></i> Back to Cases
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class='bx bx-check'></i> Submit Case
                                </button>
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