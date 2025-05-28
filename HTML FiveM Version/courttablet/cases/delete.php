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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Delete evidence files first
        $evidence_stmt = $conn->prepare("SELECT file FROM evidence WHERE id = ?");
        $evidence_stmt->execute([$caseId]);
        $evidence_files = $evidence_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($evidence_files as $evidence) {
            if (file_exists($evidence['file'])) {
                unlink($evidence['file']);
            }
        }
        
        // Delete evidence records
        $delete_evidence_stmt = $conn->prepare("DELETE FROM evidence WHERE id = ?");
        $delete_evidence_stmt->execute([$caseId]);
        
        // Delete the case
        $delete_case_stmt = $conn->prepare("DELETE FROM cases WHERE id = ?");
        $delete_case_stmt->execute([$caseId]);
        
        header("Location: index.php?character_name=" . urlencode($characterName) . "&success=deleted");
        exit();
        
    } catch (Exception $e) {
        $error_message = "Error deleting case: " . $e->getMessage();
    }
}

// Get case details
$stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
$stmt->execute([$caseId]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    header("Location: index.php?character_name=" . urlencode($characterName) . "&error=case_not_found");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Case - Court System</title>
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
            <div class="col-md-6">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error'></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0"><i class='bx bx-trash'></i> Delete Case</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class='bx bx-error-circle'></i> 
                            <strong>Warning:</strong> This action cannot be undone. All case data and evidence files will be permanently deleted.
                        </div>

                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Case #<?php echo htmlspecialchars($case['caseid']); ?></h5>
                                <p class="card-text">
                                    <strong>Assigned User:</strong> <?php echo htmlspecialchars($case['assigneduser']); ?><br>
                                    <strong>Defendant:</strong> <?php echo htmlspecialchars($case['defendent']); ?><br>
                                    <strong>Type:</strong> <?php echo htmlspecialchars($case['type']); ?><br>
                                    <strong>Status:</strong> <?php echo htmlspecialchars($case['status']); ?><br>
                                    <strong>Assigned Date:</strong> <?php echo $case['assigned'] ? date('M j, Y', strtotime($case['assigned'])) : 'N/A'; ?>
                                </p>
                            </div>
                        </div>

                        <form method="POST">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" value="1" required>
                                    <label class="form-check-label" for="confirm_delete">
                                        I understand that this action cannot be undone and I want to permanently delete this case.
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="view.php?id=<?php echo $caseId; ?>&character_name=<?php echo urlencode($characterName); ?>" 
                                   class="btn btn-secondary me-md-2">
                                    <i class='bx bx-x'></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-danger">
                                    <i class='bx bx-trash'></i> Delete Case
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
