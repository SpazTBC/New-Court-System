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

$action_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$valid_types = ['ban', 'strike', 'warning', 'all'];

if (!in_array($action_type, $valid_types)) {
    $action_type = 'all';
}

$sql = "SELECT * FROM admin_actions";
if ($action_type !== 'all') {
    $sql .= " WHERE action_type = ?";
}
$sql .= " ORDER BY timestamp DESC";

$stmt = $conn->prepare($sql);

if ($action_type !== 'all') {
    $stmt->bind_param("s", $action_type);
}

$stmt->execute();
$result = $stmt->get_result();

$incidents = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $incidents[] = $row;
    }
}

$stmt->close();
$conn->close();

// Function to fetch username from Discord API
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
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidents Dashboard - <?php echo ucfirst($action_type); ?></title>
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
        .incidents-card {
            background: rgba(44, 44, 44, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .filter-buttons {
            gap: 10px;
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .btn-filter {
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .btn-filter.active {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: rgba(114, 137, 218, 0.1);
            border-bottom: 2px solid #7289da;
            padding: 1rem;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .incident-type {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
        }
        .type-warning { 
            background-color: #ffc107; 
            color: #000; 
        }
        .type-strike { 
            background-color: #fd7e14; 
            color: #fff; 
        }
        .type-ban { 
            background-color: #dc3545; 
            color: #fff; 
        }
        .btn-details {
            background-color: #7289da;
            border-color: #7289da;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
        }
        .btn-details:hover {
            background-color: #5e77d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(114, 137, 218, 0.4);
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Dashboard Header -->
        <div class="dashboard-header text-center">
            <h1 class="display-4 mb-4">Incident Management</h1>
            <p class="lead text-muted">Viewing <?php echo ucfirst($action_type); ?> Incidents</p>
            <a href="admin.php" class="btn btn-details">
                <i class="fas fa-arrow-left me-2"></i>Back to Admin Panel
            </a>
        </div>

        <!-- Filter Buttons -->
        <div class="incidents-card">
            <div class="card-body">
                <div class="filter-buttons">
                    <a href="?type=all" class="btn btn-filter btn-secondary <?php echo $action_type === 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-list me-2"></i>All Incidents
                    </a>
                    <a href="?type=ban" class="btn btn-filter btn-danger <?php echo $action_type === 'ban' ? 'active' : ''; ?>">
                        <i class="fas fa-ban me-2"></i>Bans
                    </a>
                    <a href="?type=strike" class="btn btn-filter btn-warning <?php echo $action_type === 'strike' ? 'active' : ''; ?>">
                        <i class="fas fa-bolt me-2"></i>Strikes
                    </a>
                    <a href="?type=warning" class="btn btn-filter btn-info <?php echo $action_type === 'warning' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle me-2"></i>Warnings
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>User</th>
                                <th>Guild ID</th>
                                <th>Reason</th>
                                <th>Timestamp</th>
                                <th>Admin</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incidents as $incident): ?>
                                <tr>
                                    <td>
                                        <span class="incident-type type-<?php echo strtolower($incident['action_type']); ?>">
                                            <?php echo ucfirst($incident['action_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(getDiscordUsername($incident['discord_id'])); ?></td>
                                    <td><?php echo htmlspecialchars($incident['guild_id']); ?></td>
                                    <td><?php echo htmlspecialchars($incident['reason']); ?></td>
                                    <td><?php echo htmlspecialchars($incident['timestamp']); ?></td>
                                    <td><?php echo htmlspecialchars(getDiscordUsername($incident['admin_discord_id'])); ?></td>
                                    <td>
                                        <a href="incident_details.php?id=<?php echo $incident['id']; ?>" 
                                           class="btn btn-details">
                                            <i class="fas fa-info-circle me-2"></i>Details
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