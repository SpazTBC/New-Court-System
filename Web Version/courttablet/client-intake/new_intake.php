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
            $stmt = $conn->prepare("INSERT INTO client_intake (first_name, middle_name, last_name, dob, ssn_last_four, email, phone, address, city, state, zip, case_type, case_description, referral_source, intake_date, intake_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$firstName, $middleName, $lastName, $dob, $ssnLastFour, $email, $phone, $address, $city, $state, $zip, $caseType, $caseDescription, $referralSource, date('Y-m-d H:i:s'), $characterName]);
            
            $success_message = "Client intake created successfully!";
            
            // Clear form data
            $firstName = $middleName = $lastName = $dob = $ssnLastFour = $phone = $email = $address = $city = $state = $zip = $caseType = $caseDescription = $referralSource = '';
            
        } catch (Exception $e) {
            $error_message = "Error creating client intake: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Client Intake - Court System</title>
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
        .bg-dark {
            background-color: #161b22 !important;
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
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0"><i class='bx bx-user-plus'></i> New Client Intake</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="charactername" value="<?php echo htmlspecialchars($_GET['charactername']); ?>">
                            
                            <h5 class="mb-3"><i class='bx bx-user'></i> Personal Information</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required 
                                           value="<?php echo htmlspecialchars($firstName ?? ''); ?>"
                                           placeholder="Enter first name">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middleName" name="middleName" 
                                           value="<?php echo htmlspecialchars($middleName ?? ''); ?>"
                                           placeholder="Enter middle name (optional)">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required 
                                           value="<?php echo htmlspecialchars($lastName ?? ''); ?>"
                                           placeholder="Enter last name">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" 
                                           value="<?php echo htmlspecialchars($dob ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ssnLastFour" class="form-label">Last 4 digits of SSN</label>
                                    <input type="text" class="form-control" id="ssnLastFour" name="ssnLastFour" 
                                           maxlength="4" pattern="[0-9]{4}" placeholder="1234"
                                           value="<?php echo htmlspecialchars($ssnLastFour ?? ''); ?>">
                                    <div class="form-text text-muted">For identification purposes only</div>
                                </div>
                            </div>
                            
                            <h5 class="mb-3 mt-4"><i class='bx bx-phone'></i> Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required 
                                           value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                           placeholder="(555) 123-4567">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                           placeholder="client@example.com">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($address ?? ''); ?>"
                                       placeholder="123 Main Street">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="<?php echo htmlspecialchars($city ?? ''); ?>"
                                           placeholder="Los Santos">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state" name="state" maxlength="2"
                                           value="<?php echo htmlspecialchars($state ?? ''); ?>"
                                           placeholder="CA" style="text-transform: uppercase;">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" id="zip" name="zip" maxlength="10"
                                           value="<?php echo htmlspecialchars($zip ?? ''); ?>"
                                           placeholder="90210">
                                </div>
                            </div>
                            
                            <h5 class="mb-3 mt-4"><i class='bx bx-file'></i> Case Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="caseType" class="form-label">Case Type</label>
                                    <select class="form-select" id="caseType" name="caseType">
                                        <option value="">Select case type...</option>
                                        <option value="Criminal" <?php echo (isset($caseType) && $caseType == 'Criminal') ? 'selected' : ''; ?>>Criminal Law</option>
                                        <option value="Civil" <?php echo (isset($caseType) && $caseType == 'Civil') ? 'selected' : ''; ?>>Civil Law</option>
                                        <option value="Family" <?php echo (isset($caseType) && $caseType == 'Family') ? 'selected' : ''; ?>>Family Law</option>
                                        <option value="Traffic" <?php echo (isset($caseType) && $caseType == 'Traffic') ? 'selected' : ''; ?>>Traffic Violations</option>
                                        <option value="Personal Injury" <?php echo (isset($caseType) && $caseType == 'Personal Injury') ? 'selected' : ''; ?>>Personal Injury</option>
                                        <option value="Business" <?php echo (isset($caseType) && $caseType == 'Business') ? 'selected' : ''; ?>>Business Law</option>
                                        <option value="Real Estate" <?php echo (isset($caseType) && $caseType == 'Real Estate') ? 'selected' : ''; ?>>Real Estate</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="referralSource" class="form-label">How did you hear about us?</label>
                                    <select class="form-select" id="referralSource" name="referralSource">
                                        <option value="">Select referral source...</option>
                                        <option value="internet" <?php echo (isset($referralSource) && $referralSource == 'internet') ? 'selected' : ''; ?>>Internet Search</option>
                                        <option value="friend" <?php echo (isset($referralSource) && $referralSource == 'friend') ? 'selected' : ''; ?>>Friend/Family Referral</option>
                                        <option value="attorney" <?php echo (isset($referralSource) && $referralSource == 'attorney') ? 'selected' : ''; ?>>Attorney Referral</option>
                                        <option value="advertisement" <?php echo (isset($referralSource) && $referralSource == 'advertisement') ? 'selected' : ''; ?>>Advertisement</option>
                                        <option value="social_media" <?php echo (isset($referralSource) && $referralSource == 'social_media') ? 'selected' : ''; ?>>Social Media</option>
                                        <option value="yellow_pages" <?php echo (isset($referralSource) && $referralSource == 'yellow_pages') ? 'selected' : ''; ?>>Yellow Pages</option>
                                        <option value="other" <?php echo (isset($referralSource) && $referralSource == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="caseDescription" class="form-label">Brief Description of Legal Matter <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="caseDescription" name="caseDescription" rows="5" required 
                                          placeholder="Please provide a detailed description of your legal matter, including relevant dates, parties involved, and specific issues you need assistance with..."><?php echo htmlspecialchars($caseDescription ?? ''); ?></textarea>
                                <div class="form-text text-muted">The more details you provide, the better we can assist you.</div>
                            </div>
                            
                            <!-- Intake Summary Card -->
                            <div class="card mb-4" style="background-color: #0d1117; border-color: #30363d;">
                                <div class="card-header" style="background-color: #161b22; border-color: #30363d;">
                                    <h6 class="mb-0"><i class='bx bx-info-circle'></i> Intake Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Intake Officer:</strong> <?php echo htmlspecialchars($characterName); ?></p>
                                            <p class="mb-1"><strong>Position:</strong> <?php echo htmlspecialchars($characterJob); ?></p>
                                            <p class="mb-0"><strong>Date:</strong> <?php echo date('M j, Y g:i A'); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Status:</strong> <span class="badge bg-warning text-dark">New Intake</span></p>
                                            <p class="mb-1"><strong>Priority:</strong> <span class="badge bg-secondary">Standard</span></p>
                                            <p class="mb-0"><strong>Follow-up:</strong> <span class="badge bg-info">Required</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php?charactername=<?php echo urlencode($_GET['charactername']); ?>" class="btn btn-secondary me-md-2">
                                    <i class='bx bx-arrow-back'></i> Cancel
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                    <i class='bx bx-check'></i> Create Client Intake
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Information -->
                <div class="card shadow mt-4" style="background-color: #0d1117; border-color: #30363d;">
                    <div class="card-header" style="background-color: #161b22; border-color: #30363d;">
                        <h6 class="mb-0"><i class='bx bx-help-circle'></i> Client Intake Guidelines</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-info">Required Information</h6>
                                <ul class="list-unstyled">
                                    <li><i class='bx bx-check text-success'></i> Client's full name</li>
                                    <li><i class='bx bx-check text-success'></i> Valid phone number</li>
                                    <li><i class='bx bx-check text-success'></i> Case description</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">Next Steps</h6>
                                <ul class="list-unstyled">
                                    <li><i class='bx bx-right-arrow-alt'></i> Initial consultation scheduling</li>
                                    <li><i class='bx bx-right-arrow-alt'></i> Document collection</li>
                                    <li><i class='bx bx-right-arrow-alt'></i> Case evaluation</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-format phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})(\d{3})/, '($1) $2');
            }
            e.target.value = value;
        });

        // Auto-uppercase state field
        document.getElementById('state').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });

        // Auto-resize textarea
        document.getElementById('caseDescription').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const caseDescription = document.getElementById('caseDescription').value.trim();

            if (!firstName || !lastName || !phone || !caseDescription) {
                e.preventDefault();
                
                // Show error message
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger alert-dismissible fade show';
                errorAlert.innerHTML = `
                    <i class='bx bx-error'></i> Please fill in all required fields marked with <span class="text-danger">*</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const container = document.querySelector('.col-md-10');
                container.insertBefore(errorAlert, container.firstChild);
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                return false;
            }

            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Creating Intake...';
        });

        // Character counter for case description
        const caseDescTextarea = document.getElementById('caseDescription');
        const charCounter = document.createElement('div');
        charCounter.className = 'form-text text-muted text-end';
        charCounter.style.fontSize = '0.8em';
        caseDescTextarea.parentNode.appendChild(charCounter);

        function updateCharCounter() {
            const length = caseDescTextarea.value.length;
            charCounter.textContent = `${length} characters`;
            
            if (length < 50) {
                charCounter.className = 'form-text text-warning text-end';
                charCounter.textContent = `${length} characters (minimum 50 recommended)`;
            } else {
                charCounter.className = 'form-text text-muted text-end';
                charCounter.textContent = `${length} characters`;
            }
        }

        caseDescTextarea.addEventListener('input', updateCharCounter);
        updateCharCounter(); // Initial call
    </script>
</body>
</html>