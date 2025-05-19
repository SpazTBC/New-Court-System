<?php
include("config/config.php");
session_start();
date_default_timezone_set('America/Chicago');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);


$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sd-verification';


// Database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Log incoming data
error_log("Received GET data: " . print_r($_GET, true));

// Check if we're coming from index.php or Discord OAuth
if (isset($_GET['guild_id']) && isset($_GET['user_id'])) {
    // Coming from index.php
    $guild_id = $_GET['guild_id'];
    $discord_id = $_GET['user_id'];

    // Check if the user is already verified
    if (isset($_SESSION['verified']) && $_SESSION['verified'] && isset($_SESSION['discord_id'])) {
        $discord_id = $_SESSION['discord_id'];
        $username = $_SESSION['username'];
        $avatar = $_SESSION['avatar'];
        $access_token = $_SESSION['access_token'];

        // Proceed with verification for the new guild
        verifyUser($conn, $discord_id, $username, $avatar, $guild_id);
    } else {
        // If not verified, redirect to Discord OAuth
        $auth_url = "https://discord.com/api/oauth2/authorize?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=identify%20guilds&state=" . urlencode($guild_id);
        header("Location: $auth_url");
        exit;
    }
} elseif (isset($_GET['code'])) {
    // Coming from Discord OAuth
    $code = $_GET['code'];
    $guild_id = isset($_GET['state']) ? $_GET['state'] : null;

    $token_url = 'https://discord.com/api/oauth2/token';
    $token_data = array(
        "client_id" => $client_id,
        "client_secret" => $client_secret,
        "grant_type" => "authorization_code",
        "code" => $code,
        "redirect_uri" => $redirect_uri
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $token_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($token_data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        die("cURL Error: " . $err);
    }

    $result = json_decode($response, true);

    if (isset($result['access_token'])) {
        $access_token = $result['access_token'];

        // Fetch user information
        $user_url = 'https://discord.com/api/users/@me';
        $header = array("Authorization: Bearer $access_token");

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $user_url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_RETURNTRANSFER => true
        ));

        $user_response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            die("cURL Error: " . $err);
        }

        $user = json_decode($user_response, true);

        if ($user && isset($user['id'])) {
            $discord_id = $user['id'];
            $username = $user['username'];
            $avatar = $user['avatar'] ? "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.png" : "https://cdn.discordapp.com/embed/avatars/0.png";

            // Set session variables
            $_SESSION['verified'] = true;
            $_SESSION['discord_id'] = $discord_id;
            $_SESSION['username'] = $username;
            $_SESSION['avatar'] = $avatar;
            $_SESSION['access_token'] = $access_token;

            // Verify the user
            verifyUser($conn, $discord_id, $username, $avatar, $guild_id);
        } else {
            die("Error: Unable to fetch user data from Discord API. Response: " . print_r($user, true));
        }
    } else {
        die("Error: Unable to obtain access token from Discord. Response: " . print_r($result, true));
    }
} else {
    die("Error: Invalid request. GET parameters: " . print_r($_GET, true));
}

function verifyUser($conn, $discord_id, $username, $avatar, $guild_id) {
    $discord_id = $conn->real_escape_string($discord_id);
    $username = $conn->real_escape_string($username);
    $ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR']);
    $verified = 1;
    $verified_at = date('Y-m-d H:i:s');

    // Check if the user is an admin
    $admin_check_sql = "SELECT * FROM admins WHERE discord_id = '$discord_id'";
    $admin_result = $conn->query($admin_check_sql);
    
    if ($admin_result === false) {
        die("Error checking admin status: " . $conn->error);
    }

    if ($admin_result->num_rows > 0) {
        $_SESSION['is_admin'] = true;
    } else {
        $_SESSION['is_admin'] = false;
    }

    if ($guild_id) {
        // If guild_id is provided, verify for that specific guild
        $check_sql = "SELECT * FROM verifications WHERE discord_id = '$discord_id' AND guild_id = '$guild_id'";
        $result = $conn->query($check_sql);

        if ($result === false) {
            die("Error executing query: " . $conn->error);
        }

        if ($result->num_rows == 0) {
            // Insert new entry
            $sql = "INSERT INTO verifications (discord_id, guild_id, ip, verified, verified_at) 
                    VALUES ('$discord_id', '$guild_id', '$ip', $verified, '$verified_at')";
        } else {
            // Update existing entry
            $sql = "UPDATE verifications SET ip = '$ip', verified = $verified, verified_at = '$verified_at' 
                    WHERE discord_id = '$discord_id' AND guild_id = '$guild_id'";
        }

        if ($conn->query($sql) !== TRUE) {
            die("Error: " . $sql . "<br>" . $conn->error);
        }

        error_log("Verification successful for user $discord_id in guild $guild_id");
    } else {
        error_log("User $discord_id logged in without specific guild verification");
    }

    // Redirect back to index.php
    header("Location: index.php");
    exit;
}

$conn->close();
?>