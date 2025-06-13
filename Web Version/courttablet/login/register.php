<?php
// Include database connection
require_once '../include/database.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $charactername = trim($_POST['charactername'] ?? '');
    $job = trim($_POST['job'] ?? '');
    
    // Validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($charactername) || empty($job)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if username already exists
            $checkStmt = $conn->prepare("SELECT userid FROM users WHERE username = ?");
            $checkStmt->execute([$username]);
            
            if ($checkStmt->fetch()) {
                $error_message = "Username already exists. Please choose a different username.";
            } else {
                // Check if character name already exists
                $checkCharStmt = $conn->prepare("SELECT userid FROM users WHERE charactername = ?");
                $checkCharStmt->execute([$charactername]);
                
                if ($checkCharStmt->fetch()) {
                    $error_message = "Character name already exists. Please choose a different character name.";
                } else {
                    // Hash the password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Set job_approved based on job role
                    $jobApproved = ($job === 'AG') ? 0 : 1;
                    
                    // Insert new user with all required columns
                    $insertStmt = $conn->prepare("INSERT INTO users (charactername, username, password, job, supervisorjob, shared1, shared2, shared3, shared4, ip, banned, staff, job_approved) VALUES (?, ?, ?, ?, 0, '', '', '', '', '', 0, 0, ?)");
                    $insertStmt->execute([$charactername, $username, $hashedPassword, $job, $jobApproved]);
                    
                    // Different success messages based on job
                    if ($job === 'AG') {
                        $success_message = "Registration successful! Your Attorney General account has been created but requires admin approval before you can access AG features. You can still <a href='../index.php'>login</a> with your character name, but some features will be limited until approved.";
                    } else {
                        $success_message = "Registration successful! You can now <a href='../index.php'>login</a> with your character name.";
                    }
                    
                    // Clear form data on success
                    $username = $password = $confirm_password = $charactername = $job = '';
                }
            }
        } catch (Exception $e) {
            $error_message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card register-card">
                    <div class="card-header bg-transparent border-0 text-center py-4">
                        <h2 class="mb-0">
                            <i class='bx bx-user-plus text-primary'></i>
                            <br>Create Account
                        </h2>
                        <p class="text-muted mt-2">Join the Court System</p>
                    </div>
                    <div class="card-body px-5 pb-5">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class='bx bx-check-circle'></i> <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class='bx bx-user'></i></span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="charactername" class="form-label">Character Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class='bx bx-id-card'></i></span>
                                        <input type="text" class="form-control" id="charactername" name="charactername" 
                                               value="<?php echo htmlspecialchars($charactername ?? ''); ?>" required>
                                    </div>
                                    <div class="form-text">Enter your full character name (e.g., "John Smith")</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class='bx bx-lock'></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class='bx bx-lock-alt'></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="job" class="form-label">Job/Role <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bx-briefcase'></i></span>
                                    <select class="form-select" id="job" name="job" required>
                                        <option value="">Select your role...</option>
                                        <option value="Judge" <?php echo (isset($job) && $job === 'Judge') ? 'selected' : ''; ?>>Judge</option>
                                        <option value="Attorney" <?php echo (isset($job) && $job === 'Attorney') ? 'selected' : ''; ?>>Attorney</option>
                                        <option value="Police" <?php echo (isset($job) && $job === 'Police') ? 'selected' : ''; ?>>Police</option>
                                        <option value="AG" <?php echo (isset($job) && $job === 'AG') ? 'selected' : ''; ?>>Attorney General (AG)</option>
                                    </select>
                                </div>
                                <div class="form-text">Select your role in the court system</div>
                            </div>

                            <!-- Dynamic alert based on selected job -->
                            <div class="alert alert-info" id="jobAlert">
                                <i class='bx bx-info-circle'></i>
                                <span id="jobAlertText">Your account will be automatically approved and ready to use immediately after registration.</span>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class='bx bx-user-plus'></i> Create Account
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? 
                                <a href="../index.php" class="text-decoration-none">Login here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Username validation (no spaces, special characters)
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const regex = /^[a-zA-Z0-9_]+$/;
            
            if (username && !regex.test(username)) {
                this.setCustomValidity('Username can only contain letters, numbers, and underscores');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Update alert message based on job selection
        document.getElementById('job').addEventListener('change', function() {
            const jobAlert = document.getElementById('jobAlert');
            const jobAlertText = document.getElementById('jobAlertText');
            
            if (this.value === 'AG') {
                jobAlert.className = 'alert alert-warning';
                jobAlertText.innerHTML = '<strong>Note:</strong> Attorney General accounts require admin approval. You can login after registration, but AG features will be limited until approved by an administrator.';
            } else {
                jobAlert.className = 'alert alert-info';
                jobAlertText.innerHTML = 'Your account will be automatically approved and ready to use immediately after registration.';
            }
        });
    </script>
</body>
</html>
