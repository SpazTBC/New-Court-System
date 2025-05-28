<?php
require_once '../../include/database.php';
require_once '../../auth/character_auth.php';

$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    header("Location: ../../?error=not_found");
    exit();
}

// Validate character access - only certain roles can submit cases
$auth = validateCharacterAccess($_GET['charactername'], ['police', 'attorney', 'judge', 'admin']);
if (!$auth['valid']) {
    header("Location: ../../?error=no_access&charactername=" . urlencode($_GET['charactername']));
    exit();
}

$characterName = $currentCharacter['charactername'];
$characterJob = $currentCharacter['job'];

$success_message = '';
$error_message = '';

if ($_POST && isset($_POST['submit'])) {
    $defendent = trim($_POST['defendent']);
    $details = trim($_POST['details']);
    $evidence = trim($_POST['evidence']);
    $type = trim($_POST['type']);
    
    if (empty($defendent) || empty($details) || empty($type)) {
        $error_message = "All fields except evidence are required!";
    } else {
        try {
            // Generate case ID
            $tempCaseNum = 'CASE-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $currentDate = date('Y-m-d H:i:s');
            
            // Check if evidence column exists
            $checkColumn = $conn->query("SHOW COLUMNS FROM cases LIKE 'evidence'");
            $evidenceColumnExists = $checkColumn->rowCount() > 0;
            
            if ($evidenceColumnExists) {
                $query = "INSERT INTO cases (caseid, assigneduser, assigned, details, defendent, evidence, status, type) 
                         VALUES (:caseid, :assigneduser, :assigned, :details, :defendant, :evidence, 'pending', :type)";
                $data = [
                    ':caseid' => $tempCaseNum,
                    ':assigneduser' => $characterName,
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
                    ':assigneduser' => $characterName,
                    ':assigned' => $currentDate,
                    ':details' => $details,
                    ':defendant' => $defendent,
                    ':type' => $type
                ];
            }
            
            $stmt = $conn->prepare($query);
            $stmt->execute($data);
            
            $success_message = "Case submitted successfully! Case ID: " . $tempCaseNum;
            
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
                    <span class="ms-2"><?php echo htmlspecialchars($characterName); ?> (<?php echo htmlspecialchars($characterJob); ?>)</span>
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

                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0"><i class='bx bx-plus'></i> Submit New Case</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="charactername" value="<?php echo htmlspecialchars($_GET['charactername']); ?>">
                            
                            <div class="mb-3">
                                <label for="defendent" class="form-label">Defendant Name</label>
                                <input type="text" class="form-control" id="defendent" name="defendent" required 
                                       value="<?php echo htmlspecialchars($_POST['defendent'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Case Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select case type...</option>
                                    <option value="Criminal" <?php echo ($_POST['type'] ?? '') == 'Criminal' ? 'selected' : ''; ?>>Criminal</option>
                                    <option value="Civil" <?php echo ($_POST['type'] ?? '') == 'Civil' ? 'selected' : ''; ?>>Civil</option>
                                    <option value="Traffic" <?php echo ($_POST['type'] ?? '') == 'Traffic' ? 'selected' : ''; ?>>Traffic</option>
                                    <option value="Family" <?php echo ($_POST['type'] ?? '') == 'Family' ? 'selected' : ''; ?>>Family</option>
                                    <option value="Other" <?php echo ($_POST['type'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="details" class="form-label">Case Details</label>
                                <textarea class="form-control" id="details" name="details" rows="5" required 
                                          placeholder="Provide detailed information about the case..."><?php echo htmlspecialchars($_POST['details'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="evidence" class="form-label">Evidence (Optional)</label>
                                <textarea class="form-control" id="evidence" name="evidence" rows="3" 
                                          placeholder="List any evidence related to this case..."><?php echo htmlspecialchars($_POST['evidence'] ?? ''); ?></textarea>
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