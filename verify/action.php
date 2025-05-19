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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discord_id = $conn->real_escape_string($_POST['discord_id']);
    $guild_id = $conn->real_escape_string($_POST['guild_id']);
    $action_type = $conn->real_escape_string($_POST['action_type']);
    $reason = $conn->real_escape_string($_POST['reason']);
    $admin_discord_id = $conn->real_escape_string($_SESSION['discord_id']); // Assuming admin's discord_id is stored in session

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert the action
        $sql = "INSERT INTO admin_actions (discord_id, guild_id, action_type, reason, admin_discord_id) 
                VALUES ('$discord_id', '$guild_id', '$action_type', '$reason', '$admin_discord_id')";
        $conn->query($sql);

        // Update user_reputation
        $reputation_change = 0;
        switch ($action_type) {
            case 'warning':
                $reputation_change = -5;
                break;
            case 'strike':
                $reputation_change = -10;
                break;
            case 'ban':
                $reputation_change = -20;
                break;
        }

        $sql = "INSERT INTO user_reputation (discord_id, guild_id, reputation, warnings, strikes, banned) 
                VALUES ('$discord_id', '$guild_id', 100 + $reputation_change, 0, 0, 0) 
                ON DUPLICATE KEY UPDATE 
                reputation = GREATEST(0, reputation + $reputation_change), 
                warnings = warnings + " . ($action_type == 'warning' ? 1 : 0) . ", 
                strikes = strikes + " . ($action_type == 'strike' ? 1 : 0) . ", 
                banned = " . ($action_type == 'ban' ? 1 : 'banned');

        $conn->query($sql);

        // Commit transaction
        $conn->commit();

        header("Location: user_details.php?discord_id=$discord_id&guild_id=$guild_id");
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}

$conn->close();
?>