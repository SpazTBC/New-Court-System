<?php
include("config/config.php");
session_start();

// Check if the user is logged in and verified
if (!isset($_SESSION['verified']) || !$_SESSION['verified']) {
    header("Location: index.php");
    exit;
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sd-verification';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$discord_id = isset($_SESSION['discord_id']) ? $conn->real_escape_string($_SESSION['discord_id']) : '';
$guild_id = isset($_GET['guild_id']) ? $conn->real_escape_string($_GET['guild_id']) : '';

// Fetch user's guilds from Discord API
$guilds_url = "https://discord.com/api/users/@me/guilds";
$header = array("Authorization: Bearer " . $_SESSION['access_token']);

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $guilds_url,
    CURLOPT_HTTPHEADER => $header,
    CURLOPT_RETURNTRANSFER => true
));

$guilds_response = curl_exec($curl);
curl_close($curl);

$guilds_data = json_decode($guilds_response, true);

$is_guild_owner = false;
if ($guilds_data) {
    foreach ($guilds_data as $guild) {
        if ($guild['id'] == $guild_id && $guild['owner']) {
            $is_guild_owner = true;
            break;
        }
    }
}

if (!$is_guild_owner) {
    header("Location: index.php");
    exit;
}

// Fetch current bot settings
$sql = "SELECT * FROM bot_settings WHERE guild_id = '$guild_id'";
$result = $conn->query($sql);
$bot_settings = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $welcome_channel = $conn->real_escape_string($_POST['welcome_channel']);
    $welcome_message = $conn->real_escape_string($_POST['welcome_message']);
    $verification_channel = $conn->real_escape_string($_POST['verification_channel']);
    $verified_roles = $conn->real_escape_string($_POST['verified_roles']);
    $remove_roles = $conn->real_escape_string($_POST['remove_roles']);

    if ($bot_settings) {
        $sql = "UPDATE bot_settings SET 
                welcome_channel = '$welcome_channel',
                welcome_message = '$welcome_message',
                verification_channel = '$verification_channel',
                verified_roles = '$verified_roles',
                remove_roles = '$remove_roles'
                WHERE guild_id = '$guild_id'";
    } else {
        $sql = "INSERT INTO bot_settings (guild_id, welcome_channel, welcome_message, verification_channel, verified_roles, remove_roles) 
                VALUES ('$guild_id', '$welcome_channel', '$welcome_message', '$verification_channel', '$verified_roles', '$remove_roles')";
    }

    if ($conn->query($sql) === TRUE) {
        $success_message = "Bot settings updated successfully!";
    } else {
        $error_message = "Error updating bot settings: " . $conn->error;
    }

    // Refresh bot settings after update
    $result = $conn->query("SELECT * FROM bot_settings WHERE guild_id = '$guild_id'");
    $bot_settings = $result->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bot Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%);
            color: #ffffff;
            min-height: 100vh;
        }
        .dashboard-header {
            background: rgba(44, 44, 44, 0.9);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .settings-card {
            background: rgba(44, 44, 44, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
        }
        .form-control, .form-select {
            background: rgba(30, 30, 30, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(40, 40, 40, 0.9);
            border-color: #7289da;
            box-shadow: 0 0 0 0.25rem rgba(114, 137, 218, 0.25);
        }
        .btn-discord {
            background-color: #7289da;
            border-color: #7289da;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-discord:hover {
            background-color: #5e77d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(114, 137, 218, 0.4);
        }
        .form-label {
            color: #7289da;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-text {
            color: rgba(255, 255, 255, 0.6);
        }
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .settings-section {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Dashboard Header -->
        <div class="dashboard-header text-center">
            <h1 class="display-4 mb-4">Bot Settings</h1>
            <p class="lead text-muted">Configure your Discord bot settings</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="settings-card">
            <div class="settings-section">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="welcome_channel" class="form-label">
                            <i class="fas fa-hashtag me-2"></i>Welcome Channel ID
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="welcome_channel" 
                               name="welcome_channel" 
                               value="<?php echo $bot_settings['welcome_channel'] ?? ''; ?>"
                               placeholder="Enter channel ID">
                    </div>

                    <div class="mb-4">
                        <label for="welcome_message" class="form-label">
                            <i class="fas fa-comment-dots me-2"></i>Welcome Message
                        </label>
                        <textarea class="form-control" 
                                  id="welcome_message" 
                                  name="welcome_message" 
                                  rows="4"
                                  placeholder="Enter welcome message"><?php echo $bot_settings['welcome_message'] ?? ''; ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="verification_channel" class="form-label">
                            <i class="fas fa-shield-alt me-2"></i>Verification Channel ID
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="verification_channel" 
                               name="verification_channel" 
                               value="<?php echo $bot_settings['verification_channel'] ?? ''; ?>"
                               placeholder="Enter verification channel ID">
                    </div>

                    <div class="mb-4">
                        <label for="verified_roles" class="form-label">
                            <i class="fas fa-user-tag me-2"></i>Verified Role IDs
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="verified_roles" 
                               name="verified_roles" 
                               value="<?php echo $bot_settings['verified_roles'] ?? ''; ?>"
                               placeholder="Enter role IDs separated by commas">
                        <div class="form-text">Enter multiple role IDs separated by commas (e.g., 123456789,987654321)</div>
                    </div>

                    <div class="mb-4">
                        <label for="remove_roles" class="form-label">
                            <i class="fas fa-user-minus me-2"></i>Remove Role IDs
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="remove_roles" 
                               name="remove_roles" 
                               value="<?php echo $bot_settings['remove_roles'] ?? ''; ?>"
                               placeholder="Enter role IDs to remove">
                        <div class="form-text">Enter role IDs to be removed upon verification, separated by commas</div>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-discord">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>