<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if ID is provided
    if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        header("Location: view_intakes.php");
        exit();
    }
    
    $id = $_POST['id'];
    
    // Database connection
    require_once "../include/database.php";
    
    // Collect form data
    $firstName = $_POST['firstName'];
    $middleName = isset($_POST['middleName']) ? $_POST['middleName'] : '';
    $lastName = $_POST['lastName'];
    $dob = $_POST['dob'];
    $ssn = isset($_POST['ssn']) ? $_POST['ssn'] : '';
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip = $_POST['zip'];
    $caseType = $_POST['caseType'];
    $referralSource = isset($_POST['referralSource']) ? $_POST['referralSource'] : '';
    $caseDescription = $_POST['caseDescription'];
    
    // Using PDO prepared statement to update data
    try {
        $stmt = $conn->prepare("UPDATE client_intake SET 
                first_name = :firstName, 
                middle_name = :middleName, 
                last_name = :lastName, 
                dob = :dob, 
                ssn_last_four = :ssn, 
                email = :email, 
                phone = :phone, 
                address = :address, 
                city = :city, 
                state = :state, 
                zip = :zip, 
                case_type = :caseType, 
                referral_source = :referralSource, 
                case_description = :caseDescription
            WHERE id = :id");
            
        $stmt->bindParam(':firstName', $firstName);
        $stmt->bindParam(':middleName', $middleName);
        $stmt->bindParam(':lastName', $lastName);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':ssn', $ssn);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':city', $city);
        $stmt->bindParam(':state', $state);
        $stmt->bindParam(':zip', $zip);
        $stmt->bindParam(':caseType', $caseType);
        $stmt->bindParam(':referralSource', $referralSource);
        $stmt->bindParam(':caseDescription', $caseDescription);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        $stmt->execute();
        
        // Success
        header("Location: edit_intake.php?id=$id&success=true");
        exit();
    } catch(PDOException $e) {
        // Error
        echo "Error: " . $e->getMessage();
    }
} else {
    // If not a POST request, redirect to the list
    header("Location: view_intakes.php");
    exit();
}
?>