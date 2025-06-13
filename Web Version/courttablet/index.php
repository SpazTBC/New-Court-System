<?php
require_once 'include/database.php';
require_once 'auth/character_auth.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Check if user is already logged in
if (isset($_SESSION['character_data'])) {
    $characterName = $_SESSION['character_data']['charactername'] ?? '';
    if (!empty($characterName)) {
        header("Location: login/home.php?charactername=" . urlencode($characterName));
        exit();
    }
}

// Handle character selection
$error_message = '';
$success_message = '';

if ($_POST && isset($_POST['character_name'])) {
    $characterName = trim($_POST['character_name']);
    
    if (empty($characterName)) {
        $error_message = "Please enter a character name.";
    } else {
        // Validate character access
        $auth = validateCharacterAccess($characterName);
        
        if ($auth['valid']) {
            // Get character data
            $characterData = getCharacterData($characterName);
            
            if ($characterData) {
                // Check if character has court access
                if (hasCourtAccess($characterData)) {
                    // Store character data in session
                    $_SESSION['character_data'] = $characterData;
                    
                    // Redirect to dashboard
                    header("Location: login/home.php?charactername=" . urlencode($characterName));
                    exit();
                } else {
                    $error_message = "Your character does not have access to the court system.";
                }
            } else {
                $error_message = "Character not found in the system.";
            }
        } else {
            switch($auth['message']) {
                case 'Character not found':
                    $error_message = "Character '" . htmlspecialchars($characterName) . "' was not found.";
                    break;
                case 'Character is banned':
                    $error_message = "This character is banned from the court system.";
                    break;
                default:
                    $error_message = "Access denied: " . $auth['message'];
            }
        }
    }
}

// Handle URL error parameters
if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'not_found':
            $error_message = "Character not found or session expired.";
            break;
        case 'banned':
            $error_message = "This character is banned from the court system.";
            break;
        case 'no_access':
            $error_message = "You don't have permission to access the court system.";
            break;
        case 'session_expired':
            $error_message = "Your session has expired. Please log in again.";
            break;
        default:
            $error_message = "An error occurred. Please try again.";
    }
}

if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case 'logout':
            $success_message = "You have been successfully logged out.";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Court System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d1117 0%, #161b22 100%);
            min-height: 100vh;
            color: #e0e0e0;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background-color: #21262d;
            border: 1px solid #30363d;
            border-radius: 12px;
            box-shadow: 0 16px 32px rgba(1, 4, 9, 0.85);
            backdrop-filter: blur(10px);
        }
        .login-header {
            background: linear-gradient(135deg, #238636 0%, #2ea043 100%);
            border-radius: 12px 12px 0 0;
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid #30363d;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            background-color: #0d1117;
            border: 2px solid #30363d;
            color: #e0e0e0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1.1rem;
        }
        .form-control:focus {
            background-color: #0d1117;
            border-color: #58a6ff;
            color: #e0e0e0;
            box-shadow: 0 0 0 0.25rem rgba(88, 166, 255, 0.25);
        }
        .form-control::placeholder {
            color: #8b949e;
        }
        .btn-primary {
            background: linear-gradient(135deg, #238636 0%, #2ea043 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2ea043 0%, #238636 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(46, 160, 67, 0.3);
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .alert-danger {
            background-color: #490202;
            color: #f85149;
            border-left: 4px solid #f85149;
        }
        .alert-success {
            background-color: #0f5132;
            color: #3fb950;
            border-left: 4px solid #3fb950;
        }
        .court-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ffffff;
        }
        .system-info {
            background-color: #0d1117;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #30363d;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .feature-list i {
            color: #3fb950;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="login-card">
                        <div class="login-header">
                            <i class='bx bx-building court-icon'></i>
                            <h2 class="text-white mb-0">Court System</h2>
                            <p class="text-white-50 mb-0">Secure Legal Management Platform</p>
                        </div>
                        <div class="login-body">
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success_message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label for="character_name" class="form-label">
                                        <i class='bx bx-user'></i> Character Name
                                    </label>
                                    <input type="text" class="form-control" id="character_name" name="character_name" 
                                           placeholder="Enter your character name" required 
                                           value="<?php echo htmlspecialchars($_POST['character_name'] ?? ''); ?>">
                                    <div class="form-text text-muted">
                                        Enter your in-game character name to access the court system
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class='bx bx-log-in'></i> Access Court System
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="system-info">
                        <h5 class="text-info mb-3">
                            <i class='bx bx-info-circle'></i> System Features
                        </h5>
                        <ul class="feature-list">
                            <li><i class='bx bx-check'></i> Case Management & Tracking</li>
                            <li><i class='bx bx-check'></i> Client Intake System</li>
                            <li><i class='bx bx-check'></i> Evidence Upload & Storage</li>
                            <li><i class='bx bx-check'></i> Document Management</li>
                            <li><i class='bx bx-check'></i> User Role Management</li>
                            <li><i class='bx bx-check'></i> Secure Authentication</li>
                        </ul>
                        
                        <div class="mt-3 pt-3 border-top border-secondary">
                            <small class="text-muted">
                                <i class='bx bx-shield-check'></i> 
                                Secure access restricted to authorized court personnel only
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus on character name input
        document.getElementById('character_name').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const characterName = document.getElementById('character_name').value.trim();
            
            if (!characterName) {
                e.preventDefault();
                
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger alert-dismissible fade show';
                errorAlert.innerHTML = `
                    <i class='bx bx-error-circle'></i> Please enter your character name.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                const form = document.querySelector('form');
                form.insertBefore(errorAlert, form.firstChild);
                
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Accessing System...';
        });

        // Character name input formatting
        document.getElementById('character_name').addEventListener('input', function(e) {
            // Remove any characters that aren't letters, numbers, spaces, or common name characters
            let value = e.target.value.replace(/[^a-zA-Z0-9\s\-_\.]/g, '');
            
            // Limit length
            if (value.length > 50) {
                value = value.substring(0, 50);
            }
            
            e.target.value = value;
        });

        // Enter key handling
        document.getElementById('character_name').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('form').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>
