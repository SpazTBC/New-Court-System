<?php
include("config/config.php");

session_start();

// Add authentication check for admin users here

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sd-verification';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$discord_id = isset($_GET['discord_id']) ? $conn->real_escape_string($_GET['discord_id']) : '';
$guild_id = isset($_GET['guild_id']) ? $conn->real_escape_string($_GET['guild_id']) : '';

// Function to fetch username from Discord API
function getDiscordUsername($discord_id) {
    global $bot_token; // Replace with your actual bot token
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

$username = getDiscordUsername($discord_id);

$sql = "SELECT * FROM admin_actions WHERE discord_id = '$discord_id' AND guild_id = '$guild_id' ORDER BY timestamp DESC";
$result = $conn->query($sql);

$actions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['admin_username'] = getDiscordUsername($row['admin_discord_id']);
        $actions[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
    <!-- Add this button just below the <h1> tag -->
    <h1 class="mb-4">User Details</h1>
    <a href="javascript:history.back()" class="btn btn-secondary mb-3">
        <i class="fas fa-arrow-left"></i> Back
    </a>
        <h2>Username: <?php echo htmlspecialchars($username); ?></h2>
        <h3>Discord ID: <?php echo htmlspecialchars($discord_id); ?></h3>
        <h3>Guild ID: <?php echo htmlspecialchars($guild_id); ?></h3>

        <h4 class="mt-4">Actions</h4>
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>Action Type</th>
                    <th>Reason</th>
                    <th>Admin</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actions as $action): ?>
                <tr>
                    <td><?php echo htmlspecialchars($action['action_type']); ?></td>
                    <td><?php echo htmlspecialchars($action['reason']); ?></td>
                    <td><?php echo htmlspecialchars($action['admin_username']); ?></td>
                    <td><?php echo htmlspecialchars($action['timestamp']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4 class="mt-4">Add Action</h4>
        <form action="action.php" method="POST">
            <input type="hidden" name="discord_id" value="<?php echo htmlspecialchars($discord_id); ?>">
            <input type="hidden" name="guild_id" value="<?php echo htmlspecialchars($guild_id); ?>">
            <div class="mb-3">
                <label for="action_type" class="form-label">Action Type</label>
                <select class="form-select" id="action_type" name="action_type" required>
                    <option value="warning">Warning</option>
                    <option value="strike">Strike</option>
                    <option value="ban">Ban</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="reason" class="form-label">Reason</label>
                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Add Action</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>