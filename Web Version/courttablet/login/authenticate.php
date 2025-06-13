<?php
session_start();
require_once '../include/database.php';
require_once '../auth/character_auth.php';

if ($_POST && isset($_POST['charactername'])) {
    $characterName = trim($_POST['charactername']);
    
    if (empty($characterName)) {
        header("Location: index.php?error=empty&charactername=" . urlencode($characterName));
        exit();
    }
    
    $character = getCharacterData($characterName);
    
    if (!$character) {
        header("Location: index.php?error=not_found&charactername=" . urlencode($characterName));
        exit();
    }
    
    // Check if character is banned
    if (isset($character['banned']) && $character['banned'] == 1) {
        header("Location: index.php?error=banned&charactername=" . urlencode($characterName));
        exit();
    }
    
    // Check if character has court system access (has a job other than civilian or is approved)
    $allowedJobs = ['police', 'lawyer', 'judge', 'admin'];
    if (!in_array($character['job'], $allowedJobs) && $character['job_approved'] != 1) {
        header("Location: index.php?error=no_access&charactername=" . urlencode($characterName));
        exit();
    }
    
    // Set session variables
    $_SESSION['charactername'] = $character['charactername'];
    $_SESSION['character_id'] = $character['userid'];
    $_SESSION['job'] = $character['job'];
    $_SESSION['success'] = true;
    
    // Redirect to dashboard
    header("Location: home.php?charactername=" . urlencode($character['charactername']));
    exit();
    
} else {
    header("Location: index.php");
    exit();
}
?>