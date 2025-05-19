<?php
include("config/config.php");
session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: admin_login.php");
    exit;
}

// Fetch guild data with member counts
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

// Get guild invites
function getGuildInvites($guild_id) {
    global $bot_token;
    $url = "https://discord.com/api/v10/guilds/{$guild_id}/invites";
    
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

// Create new invite
function createInvite($guild_id, $channel_id) {
    global $bot_token;
    $url = "https://discord.com/api/v10/channels/{$channel_id}/invites";
    
    $data = json_encode([
        'max_age' => 0,
        'max_uses' => 0,
        'temporary' => false
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot {$bot_token}",
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function getBotGuilds() {
    global $bot_token;
    $url = "https://discord.com/api/v10/users/@me/guilds";
    
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




$bot_guilds = getBotGuilds();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%);
            color: #ffffff;
            min-height: 100vh;
        }
        .control-header {
            background: rgba(44, 44, 44, 0.9);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .server-card {
            background: rgba(44, 44, 44, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        .server-card:hover {
            transform: translateY(-5px);
        }
        .invite-list {
            max-height: 200px;
            overflow-y: auto;
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
        <div class="control-header text-center">
            <h1 class="display-4 mb-4">Bot Control Panel</h1>
            <p class="lead text-muted">Manage Discord Servers and Bot Settings</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="admin.php" class="btn btn-action">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <div class="row">
            <?php foreach ($bot_guilds as $guild): 
                $guild_details = getGuildDetails($guild['id']);
                $guild_invites = getGuildInvites($guild['id']);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="server-card p-4">
                        <h3 class="text-center mb-3"><?php echo htmlspecialchars($guild['name']); ?></h3>
                        
                        <div class="d-flex flex-wrap justify-content-center mb-3">
                            <div class="stat-badge">
                                <i class="fas fa-users me-2"></i>
                                <?php echo number_format($guild_details['approximate_member_count'] ?? 0); ?> Members
                            </div>
                            <div class="stat-badge">
                                <i class="fas fa-user-check me-2"></i>
                                <?php echo number_format($guild_details['approximate_presence_count'] ?? 0); ?> Online
                            </div>
                        </div>

                        <div class="mb-3">
                            <h5>Active Invites</h5>
                            <div class="invite-list">
                                <?php if (!empty($guild_invites)): ?>
                                    <?php foreach ($guild_invites as $invite): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <code>discord.gg/<?php echo htmlspecialchars($invite['code']); ?></code>
                                            <button class="btn btn-danger btn-sm" onclick="revokeInvite('<?php echo $invite['code']; ?>')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No active invites</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="createNewInvite('<?php echo $guild['id']; ?>')">
                                <i class="fas fa-plus me-2"></i>Create New Invite
                            </button>
                            <button class="btn btn-info" onclick="viewServerDetails('<?php echo $guild['id']; ?>')">
                                <i class="fas fa-info-circle me-2"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function createNewInvite(guildId) {
            fetch('bot_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create_invite',
                    guild_id: guildId
                })
            })
            .then(response => response.text())  // Changed from response.json()
            .then(text => {
                console.log('Raw response:', text);  // Log the raw response
                const data = JSON.parse(text);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to create invite: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating invite. Check console for details.');
            });
        }

    function revokeInvite(code) {
        if (confirm('Are you sure you want to revoke this invite?')) {
            fetch('bot_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'revoke_invite',
                    code: code
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to revoke invite: ' + data.message);
                }
            });
        }
    }

    function viewServerDetails(guildId) {
        window.location.href = `server_details.php?guild_id=${guildId}`;
    }
</script>
</body>
</html>
