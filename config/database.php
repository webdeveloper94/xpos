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

// ============================================
// BASE PATH CONFIGURATION
// ============================================
// LOCAL: '/xpos' (localhost/xpos)
// HOSTING: '' (if domain points to project root, e.g., xpos.andcs.uz)
// SUBDIRECTORY: '/myproject' (if in subdirectory, e.g., domain.com/myproject)
// ============================================
defined('BASE_PATH') or define('BASE_PATH', $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' ? '/xpos' : '');

// Auto-detect base URL for the application
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $host . BASE_PATH);

// Helper function to get URL with base path
function baseUrl($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

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
