<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("config/config.php");
session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    
    error_log("Discord API Response: " . $response);
    return [];
}

function createInvite($guild_id, $channel_id) {
    global $bot_token;
    $url = "https://discord.com/api/v10/channels/{$channel_id}/invites";
    
    $data = json_encode([
        'max_age' => 0,
        'max_uses' => 0,
        'temporary' => false,
        'unique' => true  // This forces Discord to generate a new code each time
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

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$response = ['success' => false, 'message' => 'Unknown error'];

try {
    switch ($action) {
        case 'create_invite':
            $guild_id = $input['guild_id'];
            $channels = getGuildChannels($guild_id);
            
            $text_channels = array_filter($channels, function($channel) {
                return $channel['type'] === 0;
            });
            
            $system_channel = reset($text_channels);
            
            if ($system_channel && isset($system_channel['id'])) {
                $invite = createInvite($guild_id, $system_channel['id']);
                $response = [
                    'success' => true,
                    'invite' => $invite
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'No suitable text channel found'
                ];
            }
            break;
            
        case 'revoke_invite':
            $code = $input['code'];
            $result = deleteInvite($code);
            $response = [
                'success' => $result !== false,
                'message' => $result ? 'Invite revoked' : 'Failed to revoke invite'
            ];
            break;
        
            case 'get_roles':
                $guild_id = $_GET['guild_id'];
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
                
                echo $response;
                break;

        default:
            $response = [
                'success' => false,
                'message' => 'Invalid action'
            ];
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ];
}

echo json_encode($response);

function deleteInvite($code) {
    global $bot_token;
    $url = "https://discord.com/api/v10/invites/{$code}";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot {$bot_token}",
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200;
}
