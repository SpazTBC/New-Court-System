<?php
require_once '../include/database.php';
require_once '../auth/character_auth.php';

$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    header("Location: ../?error=not_found");
    exit();
}

// Validate character access
$auth = validateCharacterAccess($_GET['charactername']);
if (!$auth['valid']) {
    header("Location: ../?error=no_access&charactername=" . urlencode($_GET['charactername']));
    exit();
}

$characterName = $currentCharacter['charactername'];
$characterJob = $currentCharacter['job'];

// Get client ID from URL
$clientId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get client details
$stmt = $conn->prepare("SELECT * FROM client_intake WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header("Location: index.php?charactername=" . urlencode($characterName) . "&error=client_not_found");
    exit();
}

$success_message = '';
$error_message = '';

if ($_POST && isset($_POST['submit'])) {
    $firstName = trim($_POST['firstName']);
    $middleName = trim($_POST['middleName']);
    $lastName = trim($_POST['lastName']);
    $dob = trim($_POST['dob']);
    $ssnLastFour = trim($_POST['ssnLastFour']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip = trim($_POST['zip']);
    $caseType = trim($_POST['caseType']);
    $caseDescription = trim($_POST['caseDescription']);
    $referralSource = trim($_POST['referralSource']);
    
    if (empty($firstName) || empty($lastName) || empty($phone) || empty($caseDescription)) {
        $error_message = "First name, last name, phone, and case description are required!";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE client_intake SET first_name = ?, middle_name = ?, last_name = ?, dob = ?, ssn_last_four = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip = ?, case_type = ?, case_description = ?, referral_source = ? WHERE id = ?");
            $stmt->execute([$firstName, $middleName, $lastName, $dob, $ssnLastFour, $email, $phone, $address, $city, $state, $zip, $caseType, $caseDescription, $referralSource, $clientId]);
            
            $success_message = "Client intake updated successfully!";
            
            // Refresh client data
            $stmt = $conn->prepare("SELECT * FROM client_intake WHERE id = ?");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error_message = "Error updating client intake: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Client Intake - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
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
        .form-control, .form-select {
            background-color: #0d1117;
            border: 2px solid #30363d;
            color: #e0e0e0;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0d1117;
            border-color: #58a6ff;
            color: #e0e0e0;
            box-shadow: 0 0 0 0.25rem rgba(88, 166, 255, 0.25);
        }
        .form-control::placeholder {
            color: #8b949e;
        }
        .form-label {
            color: #f0f6fc;
            font-weight: 500;
        }
        .btn-primary {
            background-color: #238636;
            border-color: #238636;
        }
        .btn-primary:hover {
            background-color: #2ea043;
            border-color: #2ea043;
        }
        .btn-secondary {
            background-color: #21262d;
            border-color: #30363d;
            color: #e0e0e0;
        }
        .btn-secondary:hover {
            background-color: #30363d;
            border-color: #484f58;
            color: #e0e0e0;
        }
        .alert-danger {
            background-color: #490202;
            border-color: #f85149;
            color: #f85149;
        }
        .alert-success {
            background-color: #0f5132;
            border-color: #238636;
            color: #3fb950;
        }
        .text-danger {
            color: #f85149 !important;
        }
        .text-muted {
            color: #8b949e !important;
        }
        h5 {
            color: #58a6ff;
            border-bottom: 1px solid #30363d;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .shadow {
            box-shadow: 0 16px 32px rgba(1, 4, 9, 0.85) !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../">
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
            <div class="col-md-10">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class='bx bx-error'></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class='bx bx-check'></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="mb-0"><i class='bx bx-edit'></i> Edit Client Intake</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="charactername" value="<?php echo htmlspecialchars($_GET['charactername']); ?>">
                            
                            <h5 class="mb-3">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required 
                                           value="<?php echo htmlspecialchars($client['first_name']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middleName" name="middleName" 
                                           value="<?php echo htmlspecialchars($client['middle_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required 
                                           value="<?php echo htmlspecialchars($client['last_name']); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" 
                                           value="<?php echo htmlspecialchars($client['dob'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ssnLastFour" class="form-label">Last 4 digits of SSN</label>
                                    <input type="text" class="form-control" id="ssnLastFour" name="ssnLastFour" maxlength="4" pattern="[0-9]{4}"
                                           value="<?php echo htmlspecialchars($client['ssn_last_four'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <h5 class="mb-3 mt-4">Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required 
                                           value="<?php echo htmlspecialchars($client['phone']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($client['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="<?php echo htmlspecialchars($client['city'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state" name="state" maxlength="2"
                                           value="<?php echo htmlspecialchars($client['state'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" id="zip" name="zip" maxlength="10"
                                           value="<?php echo htmlspecialchars($client['zip'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <h5 class="mb-3 mt-4">Case Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="caseType" class="form-label">Case Type</label>
                                    <select class="form-select" id="caseType" name="caseType">
                                        <option value="">Select case type...</option>
                                        <option value="Criminal" <?php echo ($client['case_type'] ?? '') == 'Criminal' ? 'selected' : ''; ?>>Criminal</option>
                                        <option value="Civil" <?php echo ($client['case_type'] ?? '') == 'Civil' ? 'selected' : ''; ?>>Civil</option>
                                        <option value="Family" <?php echo ($client['case_type'] ?? '') == 'Family' ? 'selected' : ''; ?>>Family</option>
                                        <option value="Traffic" <?php echo ($client['case_type'] ?? '') == 'Traffic' ? 'selected' : ''; ?>>Traffic</option>
                                        <option value="Personal Injury" <?php echo ($client['case_type'] ?? '') == 'Personal Injury' ? 'selected' : ''; ?>>Personal Injury</option>
                                        <option value="Other" <?php echo ($client['case_type'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="referralSource" class="form-label">Referral Source</label>
                                    <select class="form-select" id="referralSource" name="referralSource">
                                        <option value="">How did you hear about us?</option>
                                        <option value="internet" <?php echo ($client['referral_source'] ?? '') == 'internet' ? 'selected' : ''; ?>>Internet Search</option>
                                        <option value="friend" <?php echo ($client['referral_source'] ?? '') == 'friend' ? 'selected' : ''; ?>>Friend/Family</option>
                                        <option value="attorney" <?php echo ($client['referral_source'] ?? '') == 'attorney' ? 'selected' : ''; ?>>Attorney Referral</option>
                                        <option value="advertisement" <?php echo ($client['referral_source'] ?? '') == 'advertisement' ? 'selected' : ''; ?>>Advertisement</option>
                                        <option value="other" <?php echo ($client['referral_source'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="caseDescription" class="form-label">Brief Description of Case <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="caseDescription" name="caseDescription" rows="4" required 
                                          placeholder="Please provide a brief description of your legal matter..."><?php echo htmlspecialchars($client['case_description']); ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="view_details.php?id=<?php echo $clientId; ?>&charactername=<?php echo urlencode($characterName); ?>" class="btn btn-secondary me-md-2">
                                    <i class='bx bx-arrow-back'></i> Cancel
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class='bx bx-check'></i> Update Client Intake
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