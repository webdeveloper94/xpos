<?php
/**
 * Helper Functions
 * Fast Food Management System
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Format currency (so'm)
 */
function formatCurrency($amount) {
    return number_format($amount, 0, '.', ' ') . ' so\'m';
}

/**
 * Format date
 */
function formatDate($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Generate random password
 */
function generatePassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

/**
 * Upload image
 */
function uploadImage($file, $targetDir = '../uploads/products/') {
    // Create directory if not exists
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Check if file is uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Rasm yuklanmadi'];
    }
    
    // Check file type
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Faqat rasm fayllari ruxsat etilgan (JPEG, PNG, GIF, WEBP)'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Rasm hajmi 5MB dan oshmasligi kerak'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
    } else {
        return ['success' => false, 'message' => 'Rasm saqlashda xatolik yuz berdi'];
    }
}

/**
 * Delete image
 */
function deleteImage($filename, $targetDir = '../uploads/products/') {
    $filePath = $targetDir . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
        return true;
    }
    return false;
}

/**
 * Check user permission
 */
function hasPermission($allowedRoles) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    if (is_array($allowedRoles)) {
        return in_array($_SESSION['user_role'], $allowedRoles);
    }
    
    return $_SESSION['user_role'] === $allowedRoles;
}

/**
 * Redirect to page
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Get current date start and end (for daily reports)
 */
function getTodayRange() {
    $start = date('Y-m-d 00:00:00');
    $end = date('Y-m-d 23:59:59');
    return ['start' => $start, 'end' => $end];
}

/**
 * Get week range
 */
function getWeekRange() {
    $start = date('Y-m-d 00:00:00', strtotime('monday this week'));
    $end = date('Y-m-d 23:59:59', strtotime('sunday this week'));
    return ['start' => $start, 'end' => $end];
}

/**
 * Get month range
 */
function getMonthRange() {
    $start = date('Y-m-01 00:00:00');
    $end = date('Y-m-t 23:59:59');
    return ['start' => $start, 'end' => $end];
}
?>
