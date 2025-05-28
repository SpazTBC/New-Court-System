<?php
// Database connection
require_once "../include/database.php";

try {
    // Create client_intake table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS client_intake (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50),
        last_name VARCHAR(50) NOT NULL,
        dob DATE NOT NULL,
        ssn_last_four VARCHAR(4),
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address VARCHAR(255) NOT NULL,
        city VARCHAR(50) NOT NULL,
        state VARCHAR(2) NOT NULL,
        zip VARCHAR(10) NOT NULL,
        case_type VARCHAR(50) NOT NULL,
        referral_source VARCHAR(50),
        case_description TEXT NOT NULL,
        intake_date DATETIME NOT NULL,
        intake_by VARCHAR(50) NOT NULL
    )";
    
    $conn->exec($sql);
    echo "Client intake table created successfully!";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>