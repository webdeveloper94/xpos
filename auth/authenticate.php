<?php
/**
 * Authentication Handler
 * Fast Food Management System
 */

session_start();

require_once '../config/database.php';
require_once '../helpers/functions.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . baseUrl('login.php'));
    exit();
}

// Get and sanitize input
$login = sanitize($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($login) || empty($password)) {
    $_SESSION['error'] = 'Login va parolni kiriting';
    header("Location: " . baseUrl('login.php'));
    exit();
}

// Query user from database
$stmt = $conn->prepare("SELECT * FROM users WHERE login = ? LIMIT 1");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Login yoki parol noto\'g\'ri';
    header("Location: " . baseUrl('login.php'));
    exit();
}

$user = $result->fetch_assoc();

// Verify password
if (!verifyPassword($password, $user['password'])) {
    $_SESSION['error'] = 'Login yoki parol noto\'g\'ri';
    header("Location: " . baseUrl('login.php'));
    exit();
}

// Create session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_login'] = $user['login'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_phone'] = $user['phone'];

// Redirect to appropriate dashboard
$dashboardUrl = '';
switch ($user['role']) {
    case 'super_admin':
        $dashboardUrl = baseUrl('super_admin/dashboard.php');
        break;
    case 'manager':
        $dashboardUrl = baseUrl('manager/dashboard.php');
        break;
    case 'seller':
        $dashboardUrl = baseUrl('seller/dashboard.php');
        break;
    default:
        $_SESSION['error'] = 'Noma\'lum foydalanuvchi turi';
        header("Location: " . baseUrl('login.php'));
        exit();
}

$stmt->close();
$conn->close();

header("Location: $dashboardUrl");
exit();
?>
