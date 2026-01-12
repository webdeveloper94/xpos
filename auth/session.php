<?php
/**
 * Session Management
 * Fast Food Management System
 */

$SESSION_TIMEOUT = 3 * 60 * 60; // 3 soat (sekundda)

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /xpos/login.php");
        exit();
    }
}

/**
 * Require specific role
 */
function requireRole($allowedRoles) {
    requireLogin();
    
    if (is_array($allowedRoles)) {
        if (!in_array($_SESSION['user_role'], $allowedRoles)) {
            header("Location: /xpos/unauthorized.php");
            exit();
        }
    } else {
        if ($_SESSION['user_role'] !== $allowedRoles) {
            header("Location: /xpos/unauthorized.php");
            exit();
        }
    }
}

/**
 * Get dashboard URL based on role
 */
function getDashboardUrl($role) {
    switch ($role) {
        case 'super_admin':
            return '/xpos/super_admin/dashboard.php';
        case 'manager':
            return '/xpos/manager/dashboard.php';
        case 'seller':
            return '/xpos/seller/dashboard.php';
        default:
            return '/xpos/login.php';
    }
}

/**
 * Create user session
 */
function createSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_login'] = $user['login'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_phone'] = $user['phone'];
}

/**
 * Destroy user session
 */
function destroySession() {
    session_unset();
    session_destroy();
}
?>
