<?php
session_start();
include("../include/database.php");

if (isset($_POST['submit'])) {
    try {
        $id = $_POST['id'];
        $caseid = $_POST['caseid'];
        $assigneduser = $_POST['assigneduser'];
        $assigned = $_POST['assigned'];
        $type = $_POST['type'];
        $details = $_POST['details'];
        $supervisor = $_POST['supervisor'];
        
        // Handle shared users
        $shared01 = !empty($_POST['shared01']) ? $_POST['shared01'] : null;
        $shared02 = !empty($_POST['shared02']) ? $_POST['shared02'] : null;
        $shared03 = !empty($_POST['shared03']) ? $_POST['shared03'] : null;
        $shared04 = !empty($_POST['shared04']) ? $_POST['shared04'] : null;
        
        // Handle hearing information if provided
        $hearing_date = !empty($_POST['hearing_date']) ? $_POST['hearing_date'] : null;
        $courtroom = !empty($_POST['courtroom']) ? $_POST['courtroom'] : null;
        $hearing_status = !empty($_POST['hearing_status']) ? $_POST['hearing_status'] : null;
        $hearing_notes = !empty($_POST['hearing_notes']) ? $_POST['hearing_notes'] : null;

        // Prepare the SQL statement
        $sql = "UPDATE cases SET 
                caseid = ?, 
                assigneduser = ?, 
                assigned = ?, 
                type = ?, 
                details = ?, 
                supervisor = ?,
                shared01 = ?,
                shared02 = ?,
                shared03 = ?,
                shared04 = ?";
        
        $params = [$caseid, $assigneduser, $assigned, $type, $details, $supervisor, 
                  $shared01, $shared02, $shared03, $shared04];
        
        // Add hearing fields if they exist in the form
        if (isset($_POST['hearing_date'])) {
            $sql .= ", hearing_date = ?, courtroom = ?, hearing_status = ?, hearing_notes = ?";
            $params = array_merge($params, [$hearing_date, $courtroom, $hearing_status, $hearing_notes]);
        }
        
        $sql .= ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $params[] = $id;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // Redirect with success message
        header("Location: view.php?id=" . $id . "&success=modified");
        exit();

    } catch (PDOException $e) {
        // Redirect with error message
        header("Location: modify.php?id=" . $id . "&error=database");
        exit();
    }
} else {
    // Redirect if accessed without POST
    header("Location: index.php");
    exit();
}
?>
