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

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Check if the admin is a super admin
$admin_id = $_SESSION['discord_id'];
$super_admin_check = $conn->prepare("SELECT is_super_admin FROM admins WHERE discord_id = ?");
$super_admin_check->bind_param("i", $admin_id);
$super_admin_check->execute();
$super_admin_result = $super_admin_check->get_result();
$is_super_admin = $super_admin_result->fetch_assoc()['is_super_admin'] ?? 0;

// Get total records for pagination
$count_result = $conn->query("SELECT COUNT(*) as total FROM verifications");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Prepare and execute the main query with pagination
if ($is_super_admin) {
    $stmt = $conn->prepare("SELECT v.discord_id, v.guild_id, v.verified_at, v.ip, ur.reputation, ur.warnings, ur.strikes, ur.banned 
            FROM verifications v
            LEFT JOIN user_reputation ur ON v.guild_id = ur.guild_id AND v.discord_id = ur.discord_id
            ORDER BY v.discord_id, v.verified_at DESC
            LIMIT ? OFFSET ?");
} else {
    $stmt = $conn->prepare("SELECT v.discord_id, v.guild_id, v.verified_at, ur.reputation, ur.warnings, ur.strikes, ur.banned
            FROM verifications v
            LEFT JOIN user_reputation ur ON v.guild_id = ur.guild_id AND v.discord_id = ur.discord_id
            ORDER BY v.verified_at DESC
            LIMIT ? OFFSET ?");
}

$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$verified_users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $verified_users[] = $row;
    }
}

// Fetch all incidents with pagination
$incidents_sql = "SELECT * FROM admin_actions ORDER BY timestamp DESC LIMIT 25";
$incidents_result = $conn->query($incidents_sql);

$incidents = [];
if ($incidents_result && $incidents_result->num_rows > 0) {
    while ($row = $incidents_result->fetch_assoc()) {
        $incidents[] = $row;
    }
}

// Function to fetch username from Discord API
function getDiscordUsername($discord_id) {
    global $bot_token;
    static $cache = array();
    
    // Return cached username if available
    if (isset($cache[$discord_id])) {
        return $cache[$discord_id];
    }
    
    $url = "https://discord.com/api/v10/users/{$discord_id}";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot {$bot_token}",
            "Content-Type: application/json",
            "User-Agent: DiscordBot (https://your-domain.com, 1.0)"
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response && $http_code === 200) {
        $user_data = json_decode($response, true);
        $username = $user_data['username'] ?? $discord_id;
        // Cache the result
        $cache[$discord_id] = $username;
        return $username;
    }
    
    // Return Discord ID if API call fails
    return $discord_id;
}

// Function to fetch bot's guilds from Discord API
function getBotGuilds() {
    global $bot_token;
    $url = "https://discordapp.com/api/users/@me/guilds";
    
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
        $guilds = json_decode($response, true);
        foreach ($guilds as &$guild) {
            $guild_details = getGuildDetails($guild['id']);
            if ($guild_details) {
                $guild['owner_id'] = $guild_details['owner_id'];
                $guild['owner_name'] = getDiscordUsername($guild_details['owner_id']);
            } else {
                $guild['owner_name'] = 'Unknown';
            }
        }
        return $guilds;
    }
    
    return [];
}

$bot_guilds = getBotGuilds();

function getGuildDetails($guild_id) {
    global $bot_token;
    $url = "https://discordapp.com/api/guilds/{$guild_id}";
    
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
        return json_decode($response, true);
    }
    
    return null;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        .card {
            background: rgba(44, 44, 44, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .table {
            margin-bottom: 0;
        }
        .btn-action {
            background-color: #7289da;
            border-color: #7289da;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            background-color: #5e77d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(114, 137, 218, 0.4);
        }
        .pagination .page-link {
            background-color: #2c2c2c;
            border-color: #404040;
            color: #ffffff;
        }
        .pagination .page-link:hover {
            background-color: #404040;
        }
        .pagination .active .page-link {
            background-color: #7289da;
            border-color: #7289da;
        }
        .section-title {
            color: #7289da;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #7289da;
        }
        .incident-buttons {
            gap: 10px;
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .table th {
            background-color: rgba(114, 137, 218, 0.1);
            border-bottom: 2px solid #7289da;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Dashboard Header -->
        <div class="dashboard-header text-center">
            <h1 class="display-4 mb-4">Admin Dashboard</h1>
            <p class="lead text-muted">Manage Users, Servers, and Incidents</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="index.php" class="btn btn-action">
                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                </a>
                <a href="bot_control.php" class="btn btn-action">
                    <i class="fas fa-robot me-2"></i>Bot Control Panel
                </a>
            </div>
        </div>
        <!-- Verified Users Section -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title">Verified Users</h2>
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Discord ID</th>
                                <th>Guild ID</th>
                                <th>Verified At</th>
                                <?php if ($is_super_admin): ?>
                                <th>IP Address</th>
                                <?php endif; ?>
                                <th>Reputation</th>
                                <th>Warnings</th>
                                <th>Strikes</th>
                                <th>Banned</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($verified_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(getDiscordUsername($user['discord_id'])); ?></td>
                                    <td><?php echo htmlspecialchars($user['discord_id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['guild_id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['verified_at']); ?></td>
                                    <?php if ($is_super_admin): ?>
                                    <td><?php echo ($user['discord_id'] || $user['ip'] !== $last_ip) ? htmlspecialchars($user['ip'] ?? 'N/A') : "-"; ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($user['reputation'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['warnings'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['strikes'] ?? 'N/A'); ?></td>
                                    <td><?php echo isset($user['banned']) ? ($user['banned'] ? 'Yes' : 'No') : 'N/A'; ?></td>
                                    <td>
                                        <a href="user_details.php?discord_id=<?php echo $user['discord_id']; ?>&guild_id=<?php echo $user['guild_id']; ?>" 
                                           class="btn btn-action btn-sm">
                                            <i class="fas fa-info-circle me-1"></i>Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Bot Servers Section -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title">Bot Servers</h2>
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Server Name</th>
                                <th>Server ID</th>
                                <th>Owner Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bot_guilds as $guild): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($guild['name']); ?></td>
                                    <td><?php echo htmlspecialchars($guild['id']); ?></td>
                                    <td><?php echo htmlspecialchars($guild['owner_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Incidents Section -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title">Incidents</h2>
                <div class="incident-buttons">
                    <a href="incidents.php?type=ban" class="btn btn-danger">
                        <i class="fas fa-ban me-2"></i>View All Bans
                    </a>
                    <a href="incidents.php?type=strike" class="btn btn-warning">
                        <i class="fas fa-bolt me-2"></i>View All Strikes
                    </a>
                    <a href="incidents.php?type=warning" class="btn btn-info">
                        <i class="fas fa-exclamation-triangle me-2"></i>View All Warnings
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>User</th>
                                <th>Guild</th>
                                <th>Reason</th>
                                <th>Timestamp</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incidents as $incident): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(ucfirst($incident['action_type'])); ?></td>
                                    <td><?php echo htmlspecialchars(getDiscordUsername($incident['discord_id'])); ?></td>
                                    <td><?php echo htmlspecialchars($incident['guild_id']); ?></td>
                                    <td><?php echo htmlspecialchars($incident['reason']); ?></td>
                                    <td><?php echo htmlspecialchars($incident['timestamp']); ?></td>
                                    <td>
                                        <a href="incident_details.php?id=<?php echo $incident['id']; ?>" 
                                           class="btn btn-action btn-sm">
                                            <i class="fas fa-info-circle me-1"></i>Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>