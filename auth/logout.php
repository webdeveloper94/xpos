<?php
/**
 * Logout Handler
 * Fast Food Management System
 */

require_once '../config/database.php';

session_start();

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header("Location: " . baseUrl('login.php'));
exit();
?>
