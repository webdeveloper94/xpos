<?php
/**
 * Database Configuration
 * Fast Food Management System
 */

// Database credentials
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'xpos_db');

// Create connection
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Get database connection instance
$conn = getDbConnection();
?>
