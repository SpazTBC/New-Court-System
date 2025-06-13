<?php
$db_config = [
    'host' => 'localhost',
    'dbname' => 'courtsystem',
    'charset' => 'utf8mb4',
    'username' => 'root',
    'password' => ''
];

try {
    $conn = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}", 
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true
        ]
    );
} catch (PDOException $e) {
    // Log error to secure location
    error_log("Database connection error: " . $e->getMessage(), 0);
    // Display generic error message
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
    exit('Database connection error');
}
?>