<?php
/**
 * Logout Handler
 * Fast Food Management System
 */

session_start();

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header("Location: /xpos/login.php");
exit();
?>
