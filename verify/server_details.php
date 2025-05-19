<?php
include("config/config.php");
session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: admin_login.php");
    exit;
}

$guild_id = $_GET['guild_id'] ?? null;

function getGuildDetails($guild_id) {
    global $bot_token;
    $url = "https://discord.com/api/v10/guilds/{$guild_id}?with_counts=true";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot {$bot_token}",
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function getGuildChannels($guild_id) {
    global $bot_token;
    $url = "https://discord.com/api/v10/guilds/{$guild_id}/channels";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot {$bot_token}",
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?? [];
}

function getGuildRoles($guild_id) {
    global $bot_token;
    $url = "https://discord.com/api/v10/guilds/{$guild_id}/roles";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot {$bot_token}",
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?? [];
}

$guild = getGuildDetails($guild_id);
$channels = getGuildChannels($guild_id);
$roles = getGuildRoles($guild_id);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Details - <?php echo htmlspecialchars($guild['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%);
            color: #ffffff;
            min-height: 100vh;
        }
        .server-header {
            background: rgba(44, 44, 44, 0.9);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .info-card {
            background: rgba(44, 44, 44, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }
        .stat-badge {
            background: rgba(114, 137, 218, 0.2);
            border: 1px solid #7289da;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            margin: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="server-header text-center">
            <h1 class="display-4 mb-4"><?php echo htmlspecialchars($guild['name']); ?></h1>
            <div class="d-flex justify-content-center gap-3">
                <a href="bot_control.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Control Panel
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Server Stats -->
            <div class="col-md-4">
                <div class="info-card p-4">
                    <h3><i class="fas fa-chart-bar me-2"></i>Statistics</h3>
                    <div class="d-flex flex-wrap">
                        <div class="stat-badge">
                            <i class="fas fa-users me-2"></i>
                            <?php echo number_format($guild['approximate_member_count']); ?> Members
                        </div>
                        <div class="stat-badge">
                            <i class="fas fa-user-check me-2"></i>
                            <?php echo number_format($guild['approximate_presence_count']); ?> Online
                        </div>
                        <div class="stat-badge">
                            <i class="fas fa-shield-alt me-2"></i>
                            Level <?php echo $guild['verification_level']; ?> Verification
                        </div>
                    </div>
                </div>
            </div>

            <!-- Channels -->
            <div class="col-md-4">
                <div class="info-card p-4">
                    <h3><i class="fas fa-hashtag me-2"></i>Channels</h3>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($channels as $channel): ?>
                            <?php if ($channel['type'] === 0): // Text channels only ?>
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-hashtag me-2"></i>
                                    <?php echo htmlspecialchars($channel['name']); ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Roles -->
            <div class="col-md-4">
                <div class="info-card p-4">
                    <h3><i class="fas fa-user-tag me-2"></i>Roles</h3>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($roles as $role): ?>
                            <?php if ($role['name'] !== '@everyone'): ?>
                                <li class="list-group-item bg-transparent">
                                    <span class="badge" style="background-color: #<?php echo dechex($role['color']); ?>">
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
