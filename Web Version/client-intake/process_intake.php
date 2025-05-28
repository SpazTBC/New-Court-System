<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
    $intakeDate = date('Y-m-d H:i:s');
    $intakeBy = $_SESSION['username'];
    
    // Using PDO prepared statement to insert data
    try {
        $stmt = $conn->prepare("INSERT INTO client_intake (
                first_name, middle_name, last_name, dob, ssn_last_four, 
                email, phone, address, city, state, zip, 
                case_type, referral_source, case_description, intake_date, intake_by
            ) VALUES (
                :firstName, :middleName, :lastName, :dob, :ssn, 
                :email, :phone, :address, :city, :state, :zip, 
                :caseType, :referralSource, :caseDescription, :intakeDate, :intakeBy
            )");
            
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
        $stmt->bindParam(':intakeDate', $intakeDate);
        $stmt->bindParam(':intakeBy', $intakeBy);
        
        $stmt->execute();
        
        // Success
        header("Location: index.php?success=true");
        exit();
    } catch(PDOException $e) {
        // Error
        echo "Error: " . $e->getMessage();
    }
} else {
    // If not a POST request, redirect to the form
    header("Location: index.php");
    exit();
}
?>