<?php
require_once 'auth/character_auth.php';

// Handle different input methods
$autoLogin = false;
$characterName = '';
$firstName = '';
$lastName = '';
$debugMode = isset($_GET['debug']);

// Check if we're getting charactername directly
if (isset($_GET['charactername']) && !empty(trim($_GET['charactername']))) {
    $characterName = trim($_GET['charactername']);
    // Split charactername into first and last name
    $nameParts = explode(' ', $characterName, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';
    $autoLogin = true;
}
// Check for separate first_name and last_name parameters
elseif (isset($_GET['first_name']) && isset($_GET['last_name'])) {
    $firstName = trim($_GET['first_name']);
    $lastName = trim($_GET['last_name']);
    $characterName = $firstName . ' ' . $lastName;
    $autoLogin = true;
}
// Check for firstName and lastName parameters
elseif (isset($_GET['firstName']) && isset($_GET['lastName'])) {
    $firstName = trim($_GET['firstName']);
    $lastName = trim($_GET['lastName']);
    $characterName = $firstName . ' ' . $lastName;
    $autoLogin = true;
}
// Check POST data
elseif (isset($_POST['charactername']) && !empty(trim($_POST['charactername']))) {
    $characterName = trim($_POST['charactername']);
    $nameParts = explode(' ', $characterName, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';
    $autoLogin = true;
}

// If we have character data, try to auto-login
if ($autoLogin && !empty($firstName) && !empty($lastName)) {
    // Redirect to home page with character data
    header("Location: /courttablet/login/home.php?charactername=" . urlencode($characterName));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class='bx bx-building'></i> FiveM Court System
            </a>
            <?php if ($debugMode): ?>
                <span class="navbar-text text-warning">DEBUG MODE</span>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white text-center">
                        <h3 class="mb-0">
                            <i class='bx bx-user'></i> FiveM Court System
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <i class='bx bx-error'></i> 
                                <?php 
                                switch($_GET['error']) {
                                    case 'not_found':
                                        echo 'Character "' . htmlspecialchars($_GET['charactername'] ?? 'Unknown') . '" not found in the system.';
                                        break;
                                    case 'banned':
                                        echo 'This character has been banned from the system.';
                                        break;
                                    case 'no_access':
                                        echo 'This character does not have access to the court system.';
                                        break;
                                    case 'character_not_found':
                                        echo 'Character data not found. Please ensure you are logged into FiveM.';
                                        break;
                                    case 'auth_failed':
                                        echo 'Authentication failed: ' . htmlspecialchars($_GET['reason'] ?? 'Unknown error');
                                        break;
                                    default:
                                        echo 'Access denied. Please check your character name.';
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- Auto-login form (hidden, used by FiveM) -->
                        <form id="autoLoginForm" action="/courttablet/login/home.php" method="GET" style="display: none;">
                            <input type="hidden" id="characterNameHidden" name="charactername" value="">
                        </form>

                        <!-- Manual login form (fallback) -->
                        <form action="/courttablet/login/home.php" method="GET" id="manualLoginForm">
                            <div class="mb-3">
                                <label for="charactername" class="form-label">Character Name</label>
                                <input type="text" class="form-control" id="charactername" name="charactername" 
                                       placeholder="Enter your FiveM character name (First Last)"
                                       value="<?php echo htmlspecialchars($characterName); ?>"
                                       <?php echo $autoLogin ? 'readonly' : 'required'; ?>>
                                <div class="form-text">
                                    Enter your character name as "First Last" (e.g., "John Smith")
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="loginButton">
                                    <i class='bx bx-log-in'></i> Enter Court System
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted" id="loginHelpText">
                                <?php if ($autoLogin): ?>
                                    Logged in as: <?php echo htmlspecialchars($characterName); ?>
                                <?php else: ?>
                                    Use F7 in FiveM or enter your character name manually
                                <?php endif; ?>
                            </small>
                        </div>

                        <!-- Registration Link -->
                        <div class="text-center mt-3">
                            <p class="mb-0">Don't have an account? 
                                <a href="login/register.php" class="text-decoration-none">Register here</a>
                            </p>
                        </div>

                        <!-- FiveM Integration Status -->
                        <div class="mt-3">
                            <div class="alert alert-info" id="fivemStatus">
                                <i class='bx bx-info-circle'></i> 
                                <span id="statusText">Waiting for FiveM connection...</span>
                            </div>
                        </div>

                        <?php if ($debugMode): ?>
                            <div class="mt-3">
                                <div class="alert alert-warning">
                                    <strong>Debug Info:</strong><br>
                                    Character Name: <?php echo htmlspecialchars($characterName ?: 'Not set'); ?><br>
                                    First Name: <?php echo htmlspecialchars($firstName ?: 'Not set'); ?><br>
                                    Last Name: <?php echo htmlspecialchars($lastName ?: 'Not set'); ?><br>
                                    Auto Login: <?php echo $autoLogin ? 'Yes' : 'No'; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // FiveM Integration
        let fivemConnected = false;
        let connectionAttempts = 0;
        const maxConnectionAttempts = 10;
        
        // Function to receive character data from FiveM
        function setCharacterData(firstName, lastName) {
            console.log('Received character data:', firstName, lastName);
            
            if (!firstName || !lastName) {
                console.error('Invalid character data received');
                return;
            }
            
            const characterName = firstName + ' ' + lastName;
            document.getElementById('characterNameHidden').value = characterName;
            document.getElementById('charactername').value = characterName;
            document.getElementById('charactername').readOnly = true;
            
            document.getElementById('loginHelpText').innerHTML = 'Logged in as: ' + characterName;
            document.getElementById('statusText').innerHTML = 'Connected to FiveM - Character: ' + characterName;
            document.getElementById('fivemStatus').className = 'alert alert-success';
            
            fivemConnected = true;
            
            // Auto-submit after a short delay
            setTimeout(() => {
                document.getElementById('autoLoginForm').submit();
            }, 1000);
        }
        
        // Function to handle FiveM disconnect
        function onFiveMDisconnect() {
            document.getElementById('charactername').readOnly = false;
            document.getElementById('charactername').value = '';
            document.getElementById('loginHelpText').innerHTML = 'Use F7 in FiveM or enter your character name manually';
            document.getElementById('statusText').innerHTML = 'FiveM connection lost. Please use manual login.';
            document.getElementById('fivemStatus').className = 'alert alert-warning';
            fivemConnected = false;
        }
        
        // Check for FiveM every few seconds
        let connectionCheckInterval = setInterval(() => {
            if (!fivemConnected && connectionAttempts < maxConnectionAttempts) {
                connectionAttempts++;
                
                // Try to get data from FiveM
                if (typeof GetParentResourceName !== 'undefined') {
                    // We're in FiveM CEF
                    fetch(`https://${GetParentResourceName()}/getCharacterData`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({})
                    }).then(response => response.json())
                    .then(data => {
                        if (data.firstName && data.lastName && !data.error) {
                            setCharacterData(data.firstName, data.lastName);
                            clearInterval(connectionCheckInterval);
                        } else if (data.error) {
                            console.error('FiveM character data error:', data.error);
                            document.getElementById('statusText').innerHTML = 'Error: ' + data.error;
                            document.getElementById('fivemStatus').className = 'alert alert-danger';
                        }
                    }).catch(error => {
                        console.log('FiveM not connected:', error);
                        if (connectionAttempts >= maxConnectionAttempts) {
                            document.getElementById('statusText').innerHTML = 'FiveM not detected. Use manual login below.';
                            document.getElementById('fivemStatus').className = 'alert alert-secondary';
                            clearInterval(connectionCheckInterval);
                        }
                    });
                } else {
                    // Not in FiveM environment
                    if (connectionAttempts >= maxConnectionAttempts) {
                        document.getElementById('statusText').innerHTML = 'Not running in FiveM. Use manual login below.';
                        document.getElementById('fivemStatus').className = 'alert alert-secondary';
                        clearInterval(connectionCheckInterval);
                    }
                }
            }
        }, 2000);
        
        // Handle window messages from FiveM
        window.addEventListener('message', function(event) {
            const data = event.data;
            
            switch(data.type) {
                case 'openTablet':
                    if (data.characterData && data.characterData.firstName && data.characterData.lastName) {
                        setCharacterData(data.characterData.firstName, data.characterData.lastName);
                    }
                    break;
                    
                case 'setCharacterData':
                    if (data.characterData && data.characterData.firstName && data.characterData.lastName) {
                        setCharacterData(data.characterData.firstName, data.characterData.lastName);
                    }
                    break;
                    
                case 'closeTablet':
                    onFiveMDisconnect();
                    break;
            }
        });
        
        // Expose functions globally for FiveM
        window.setCharacterData = setCharacterData;
        window.onFiveMDisconnect = onFiveMDisconnect;
        
        // Close tablet function for FiveM
        function closeTablet() {
            if (typeof GetParentResourceName !== 'undefined') {
                fetch(`https://${GetParentResourceName()}/closeTablet`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({})
                });
            }
        }
        
        // ESC key to close tablet in FiveM
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && typeof GetParentResourceName !== 'undefined') {
                closeTablet();
            }
        });
        
        // Debug mode functions
        <?php if ($debugMode): ?>
        console.log('Debug mode enabled');
        
        // Test function for manual character data setting
        window.testCharacterData = function(firstName, lastName) {
            setCharacterData(firstName || 'John', lastName || 'Doe');
        };
        
        // Add debug button
        setTimeout(() => {
            const debugButton = document.createElement('button');
            debugButton.className = 'btn btn-warning btn-sm mt-2';
            debugButton.innerHTML = '<i class="bx bx-bug"></i> Test Login (Debug)';
            debugButton.onclick = () => testCharacterData('Shawn', 'Blackwood');
            
            const cardBody = document.querySelector('.card-body');
            cardBody.appendChild(debugButton);
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
