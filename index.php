<?php
/**
 * Index - Redirect to appropriate page
 * Fast Food Management System
 */

session_start();

// If logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $dashboardUrl = '';
    switch ($_SESSION['user_role']) {
        case 'super_admin':
            $dashboardUrl = 'super_admin/dashboard.php';
            break;
        case 'manager':
            $dashboardUrl = 'manager/dashboard.php';
            break;
        case 'seller':
            $dashboardUrl = 'seller/dashboard.php';
            break;
    }
    
    if ($dashboardUrl) {
        header("Location: $dashboardUrl");
        exit();
    }
}

// If not logged in, redirect to login
header("Location: login.php");
exit();
?>
