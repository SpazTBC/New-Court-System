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
    WHERE id = ? 
    AND (shared01 = ? OR shared02 = ? OR shared03 = ? OR shared04 = ? OR assigneduser = ?)
");
$stmt->execute([$caseId, $_SESSION['username'], $_SESSION['username'], $_SESSION['username'], $_SESSION['username'], $_SESSION['username']]);
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
    // Update case status to active and clear closed fields
    $update_stmt = $conn->prepare("UPDATE cases SET status = 'active', closed_date = NULL, closed_by = NULL WHERE id = ?");
    $update_stmt->execute([$caseId]);
    
    header("Location: view.php?id=" . $caseId . "&success=case_reopened");
    exit();
} catch (PDOException $e) {
    header("Location: view.php?id=" . $caseId . "&error=reopen_failed");
    exit();
}
?>