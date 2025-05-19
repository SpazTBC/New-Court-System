<?php
include("config/config.php");
session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: admin_login.php");
    exit;
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sd-verification';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$incident_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch specific incident details
$stmt = $conn->prepare("SELECT * FROM admin_actions WHERE id = ?");
$stmt->bind_param("i", $incident_id);
$stmt->execute();
$result = $stmt->get_result();
$incident = $result->fetch_assoc();

if (!$incident) {
    header("Location: admin.php");
    exit;
}

// Function to fetch username from Discord API (reuse your existing function)
function getDiscordUsername($discord_id) {
    global $bot_token;
    $url = "https://discordapp.com/api/users/{$discord_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bot {$bot_token}",
        "Content-Type: application/json"
    ));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $user_data = json_decode($response, true);
        return $user_data['username'] ?? 'Unknown';
    }
    return 'Unknown';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Details</title>
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
        .incident-card {
            background: rgba(44, 44, 44, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
        }
        .detail-label {
            color: #7289da;
            font-weight: 600;
        }
        .btn-discord {
            background-color: #7289da;
            border-color: #7289da;
            transition: all 0.3s ease;
        }
        .btn-discord:hover {
            background-color: #5e77d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(114, 137, 218, 0.4);
        }
        .incident-type {
            font-size: 1.2rem;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .type-warning { background-color: #ffc107; color: #000; }
        .type-strike { background-color: #fd7e14; color: #fff; }
        .type-ban { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="dashboard-header text-center">
            <h1 class="display-4 mb-4">Incident Details</h1>
            <p class="lead text-muted">Viewing detailed information about the incident</p>
        </div>

        <div class="incident-card">
            <div class="card-body p-4">
                <div class="incident-type type-<?php echo strtolower($incident['action_type']); ?>">
                    <i class="fas fa-<?php echo strtolower($incident['action_type']) === 'ban' ? 'ban' : 
                        (strtolower($incident['action_type']) === 'strike' ? 'bolt' : 'exclamation-triangle'); ?> me-2"></i>
                    <?php echo ucfirst($incident['action_type']); ?>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><span class="detail-label">User:</span><br>
                        <?php echo htmlspecialchars(getDiscordUsername($incident['discord_id'])); ?> 
                        (<?php echo htmlspecialchars($incident['discord_id']); ?>)</p>
                    </div>
                    <div class="col-md-6">
                        <p><span class="detail-label">Guild ID:</span><br>
                        <?php echo htmlspecialchars($incident['guild_id']); ?></p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><span class="detail-label">Admin:</span><br>
                        <?php echo htmlspecialchars(getDiscordUsername($incident['admin_discord_id'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><span class="detail-label">Timestamp:</span><br>
                        <?php echo htmlspecialchars($incident['timestamp']); ?></p>
                    </div>
                </div>

                <div class="mb-4">
                    <p><span class="detail-label">Reason:</span><br>
                    <?php echo htmlspecialchars($incident['reason']); ?></p>
                </div>

                <div class="text-center mt-5">
                    <a href="javascript:history.back()" class="btn btn-discord">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>