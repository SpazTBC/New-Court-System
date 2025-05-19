<?php
include("config/config.php");
session_start();


if (isset($_GET['guild_id']) && isset($_GET['user_id'])) {
    $guild_id = $_GET['guild_id'];
    $user_id = $_GET['user_id'];
    
    // Redirect to callback.php with the provided parameters
    header("Location: callback.php?guild_id=" . urlencode($guild_id) . "&user_id=" . urlencode($user_id));
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

// Check if guild_id is provided in the URL
$guild_id = isset($_GET['guild_id']) ? $_GET['guild_id'] : null;

// Store guild_id in session if provided
if ($guild_id) {
    $_SESSION['guild_id'] = $guild_id;
}

if (!isset($_SESSION['verified'])) {
    $auth_url = "https://discord.com/api/oauth2/authorize?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=identify%20guilds";
    
    if ($guild_id) {
        $auth_url .= "&state=" . urlencode($guild_id);
    }
    
    header("Location: $auth_url");
    exit;
}


$discord_id = isset($_SESSION['discord_id']) ? $conn->real_escape_string($_SESSION['discord_id']) : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$avatar = isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'https://cdn.discordapp.com/embed/avatars/0.png';
$access_token = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : '';

// Fetch user's guilds from Discord API
$guilds_url = "https://discord.com/api/users/@me/guilds";
$header = array(
    "Authorization: Bearer " . $access_token,
    "Cache-Control: no-cache"
);

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $guilds_url,
    CURLOPT_HTTPHEADER => $header,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FRESH_CONNECT => true
));

$guilds_response = curl_exec($curl);
curl_close($curl);

$guilds_data = json_decode($guilds_response, true);

$admin_guilds = array();
if ($guilds_data && is_array($guilds_data)) {
    foreach ($guilds_data as $guild) {
        if (is_array($guild) && isset($guild['permissions']) && is_numeric($guild['permissions'])) {
            if (($guild['permissions'] & 0x8) == 0x8) {  // Check for administrator permission
                $admin_guilds[] = $guild;
            }
        }
    }
} else {
    // Log an error or handle the case where $guilds_data is not an array
    error_log("Error: guilds_data is not an array. Raw response: " . $guilds_response);
}

// Fetch only verified servers and reputation data
$sql = "SELECT v.guild_id, ur.reputation, ur.warnings, ur.strikes, ur.banned,
        (SELECT COUNT(*) FROM admin_actions WHERE discord_id = v.discord_id AND guild_id = v.guild_id AND action_type = 'warning') as warning_count,
        (SELECT COUNT(*) FROM admin_actions WHERE discord_id = v.discord_id AND guild_id = v.guild_id AND action_type = 'strike') as strike_count,
        (SELECT COUNT(*) FROM admin_actions WHERE discord_id = v.discord_id AND guild_id = v.guild_id AND action_type = 'ban') as ban_count
        FROM verifications v 
        LEFT JOIN user_reputation ur ON v.guild_id = ur.guild_id AND v.discord_id = ur.discord_id 
        WHERE v.discord_id = '$discord_id' AND v.verified = 1";
$result = $conn->query($sql);

$verified_servers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $guild_id = $row['guild_id'];
        $guild_name = "Unknown Server";
        $guild_icon = null;

        // Fetch guild details from Discord API
        $guild_url = "https://discord.com/api/guilds/$guild_id";
        $header = array("Authorization: Bot " . $bot_token);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $guild_url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_RETURNTRANSFER => true
        ));

        $guild_response = curl_exec($curl);
        curl_close($curl);

        $guild_data = json_decode($guild_response, true);

        if ($guild_data && isset($guild_data['name'])) {
            $guild_name = $guild_data['name'];
            $guild_icon = $guild_data['icon'];
        }

        $verified_servers[] = [
            'id' => $guild_id,
            'name' => $guild_name,
            'icon' => $guild_icon,
            'reputation' => $row['reputation'] ?? 100,
            'warnings' => $row['warning_count'] ?? 0,
            'strikes' => $row['strike_count'] ?? 0,
            'banned' => $row['ban_count'] > 0
        ];
    }
}

$is_guild_owner = false;
$managed_guild_id = null;
if (!empty($admin_guilds)) {
    foreach ($admin_guilds as $guild) {
        if (($guild['permissions'] & 0x8) == 0x8) {  // Check for administrator permission
            $managed_guild_id = $guild['id'];
            $is_guild_owner = true;
            break;
        }
    }
}

// Fetch bot settings for the managed guild
$bot_settings = [];
if ($is_guild_owner && $managed_guild_id) {
    $sql = "SELECT * FROM bot_settings WHERE guild_id = '$managed_guild_id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $bot_settings = $result->fetch_assoc();
    }
}

$conn->close();

function getReputationColor($reputation) {
    if ($reputation >= 90) return 'success';
    if ($reputation >= 70) return 'info';
    if ($reputation >= 50) return 'warning';
    return 'danger';
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Server Verification Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%);
            color: #ffffff;
            min-height: 100vh;
        }
        .card {
            background: rgba(44, 44, 44, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }
        .server-icon, .user-avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #7289da;
            box-shadow: 0 0 20px rgba(114, 137, 218, 0.3);
            transition: transform 0.3s ease;
        }
        .server-icon:hover, .user-avatar:hover {
            transform: scale(1.05);
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
        .reputation-badge {
            font-size: 1.2rem;
            padding: 0.5em 1em;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .status-icons {
            font-size: 1.2rem;
            margin: 15px 0;
        }
        .status-icons i {
            margin: 0 8px;
            transition: transform 0.2s ease;
        }
        .status-icons i:hover {
            transform: scale(1.2);
        }
        .server-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: #7289da;
            margin: 15px 0;
        }
        .server-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 20px;
        }
        .welcome-section {
            background: rgba(44, 44, 44, 0.7);
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Welcome Section -->
        <div class="welcome-section text-center mb-5">
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="User Avatar" class="user-avatar mb-4">
            <h1 class="display-4 mb-3">Welcome, <span class="text-discord"><?php echo htmlspecialchars($username); ?></span></h1>
            <p class="lead text-muted">Manage your Discord server verifications</p>
        </div>

        <!-- Servers Grid -->
        <div class="server-grid">
            <?php if (!empty($verified_servers)): ?>
                <?php foreach ($verified_servers as $server): ?>
                    <div class="card h-100">
                        <div class="card-body text-center p-4">
                            <?php if ($server['icon']): ?>
                                <img src="https://cdn.discordapp.com/icons/<?php echo $server['id']; ?>/<?php echo $server['icon']; ?>.png" 
                                     alt="<?php echo htmlspecialchars($server['name']); ?>" 
                                     class="server-icon">
                            <?php else: ?>
                                <div class="server-icon d-flex align-items-center justify-content-center bg-secondary">
                                    <i class="fas fa-server fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="server-name"><?php echo htmlspecialchars($server['name']); ?></h3>
                            
                            <div class="reputation-badge bg-<?php echo getReputationColor($server['reputation']); ?>">
                                <?php echo $server['reputation']; ?>% Trust Score
                            </div>
                            
                            <div class="status-icons">
                                <?php if ($server['warnings'] > 0): ?>
                                    <i class="fas fa-exclamation-triangle text-warning" 
                                       data-bs-toggle="tooltip" 
                                       title="Warnings: <?php echo $server['warnings']; ?>"></i>
                                <?php endif; ?>
                                <?php if ($server['strikes'] > 0): ?>
                                    <i class="fas fa-bolt text-warning"
                                       data-bs-toggle="tooltip" 
                                       title="Strikes: <?php echo $server['strikes']; ?>"></i>
                                <?php endif; ?>
                                <?php if ($server['banned']): ?>
                                    <i class="fas fa-ban text-danger"
                                       data-bs-toggle="tooltip" 
                                       title="Banned"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="manage-bot-container mt-3" data-server-id="<?php echo $server['id']; ?>">
                                <!-- Dynamic button insertion -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center p-4" role="alert">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <p class="lead">You haven't verified with any servers yet.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mt-5">
            <div class="btn-group">
                <button class="btn btn-discord dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-plus me-2"></i>Add Bot to Server
                </button>
                <ul class="dropdown-menu">
                    <?php foreach ($admin_guilds as $guild): ?>
                        <li><a class="dropdown-item" href="https://discord.com/api/oauth2/authorize?client_id=<?php echo $client_id; ?>&permissions=8&scope=bot&guild_id=<?php echo $guild['id']; ?>" target="_blank">
                            <?php echo htmlspecialchars($guild['name']); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <a href="logout.php" class="btn btn-discord ms-2">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="admin.php" class="btn btn-discord ms-2">
                    <i class="fas fa-user-shield me-2"></i>Admin Panel
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Server ownership check function remains the same
        function checkServerOwnership() {
            fetch('check_ownership.php')
                .then(response => response.json())
                .then(ownedGuilds => {
                    document.querySelectorAll('.manage-bot-container').forEach(container => {
                        const serverId = container.dataset.serverId;
                        if (ownedGuilds.includes(serverId)) {
                            container.innerHTML = `
                                <a href="manage_bot.php?guild_id=${serverId}" class="btn btn-discord btn-sm">
                                    <i class="fas fa-cog me-2"></i>Manage Bot
                                </a>
                            `;
                        }
                    });
                });
        }

        checkServerOwnership();
        setInterval(checkServerOwnership, 5000);
    </script>
</body>
</html>
</html>