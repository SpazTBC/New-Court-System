<?php
session_start();
include("../include/database.php");

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$caseId = $_GET['id'] ?? 0;

// Get current user's job
$user_stmt = $conn->prepare("SELECT job FROM users WHERE username = ?");
$user_stmt->execute([$_SESSION['username']]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
$current_user_job = $user_data['job'] ?? '';

// Check if user has permission and case exists
$stmt = $conn->prepare("
    SELECT * FROM cases 
    WHERE id = :caseId 
    AND (shared01 = :username OR shared02 = :username OR shared03 = :username OR shared04 = :username OR assigneduser = :username)
");
$stmt->execute([
    'username' => $_SESSION['username'],
    'caseId' => $caseId
]);
$case = $stmt->fetch();

if (!$case) {
    header("Location: index.php?error=case_not_found");
    exit();
}

// Check permissions
if ($current_user_job === "Civilian" || $_SESSION['username'] === $case['defendent']) {
    header("Location: index.php?error=access_denied");
    exit();
}

try {
    // Update case status to closed
    $update_stmt = $conn->prepare("UPDATE cases SET status = 'closed', closed_date = NOW(), closed_by = ? WHERE id = ?");
    $update_stmt->execute([$_SESSION['username'], $caseId]);
    
    header("Location: view.php?id=" . $caseId . "&success=case_closed");
    exit();
} catch (PDOException $e) {
    header("Location: view.php?id=" . $caseId . "&error=close_failed");
    exit();
}
?>