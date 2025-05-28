<?php
// Character-based authentication system
function getCharacterData($characterName) {
    global $conn;
    
    $characterName = trim($characterName);
    
    // Query to find character by charactername or username
    $stmt = $conn->prepare("SELECT * FROM users WHERE charactername = ? OR username = ?");
    $stmt->execute([$characterName, $characterName]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function validateCharacterAccess($characterName, $requiredRoles = []) {
    $character = getCharacterData($characterName);
    
    if (!$character) {
        return ['valid' => false, 'message' => 'Character not found'];
    }
    
    // For court system, allow all characters (remove role restriction for now)
    if (!empty($requiredRoles) && !in_array($character['job'], $requiredRoles)) {
        return ['valid' => false, 'message' => 'Insufficient permissions'];
    }
    
    return ['valid' => true, 'character' => $character];
}

// Get character info from POST/GET data
function getCurrentCharacter() {
    // Try to get character name from different possible parameters
    $characterName = $_POST['character_name'] ?? $_GET['character_name'] ?? 
                    $_POST['charactername'] ?? $_GET['charactername'] ?? 
                    $_POST['first_name'] ?? $_GET['first_name'] ?? '';
    
    // If we got first_name and last_name separately, combine them
    if (empty($characterName) && isset($_GET['first_name']) && isset($_GET['last_name'])) {
        $characterName = trim($_GET['first_name'] . ' ' . $_GET['last_name']);
    }
    
    if (empty($characterName)) {
        return null;
    }
    
    return getCharacterData($characterName);
}

// Check if character has access to court system
function hasCourtAccess($character) {
    if (!$character) return false;
    
    $allowedJobs = ['police', 'lawyer', 'judge', 'admin'];
    return in_array($character['job'], $allowedJobs) || $character['job_approved'] == 1;
}
?>