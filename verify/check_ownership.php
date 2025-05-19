<?php
include("config/config.php");
session_start();

$access_token = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : '';

$guilds_url = "https://discord.com/api/users/@me/guilds";
$header = array("Authorization: Bearer " . $access_token);

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $guilds_url,
    CURLOPT_HTTPHEADER => $header,
    CURLOPT_RETURNTRANSFER => true
));

$guilds_response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$owned_guilds = array();
if ($http_code == 200) {
    $guilds_data = json_decode($guilds_response, true);
    if (is_array($guilds_data)) {
        foreach ($guilds_data as $guild) {
            if (isset($guild['owner']) && $guild['owner']) {
                $owned_guilds[] = $guild['id'];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($owned_guilds);
?>