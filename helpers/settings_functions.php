<?php
/**
 * Settings Helper Functions
 * Manage system settings for service charges, delivery fees, and discounts
 */

/**
 * Get setting value from database
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return string Setting value
 */
function getSetting($key, $default = null) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['setting_value'];
    }
    
    $stmt->close();
    return $default;
}

/**
 * Update setting value
 * @param string $key Setting key
 * @param mixed $value New value
 * @param int $userId User ID making the change
 * @return bool Success
 */
function updateSetting($key, $value, $userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value, updated_by) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?
    ");
    $stmt->bind_param("ssisi", $key, $value, $userId, $value, $userId);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Calculate order total with all fees and discounts
 * @param float $subtotal Order subtotal (sum of items)
 * @param string $orderType Order type: 'dine_in', 'takeaway', 'delivery'
 * @return array Breakdown of all charges
 */
function calculateOrderTotal($subtotal, $orderType = 'dine_in') {
    // Get settings
    $serviceChargePercentage = floatval(getSetting('service_charge_percentage', 0));
    $deliveryFeeType = getSetting('delivery_fee_type', 'fixed');
    $deliveryFeeValue = floatval(getSetting('delivery_fee_value', 0));
    $discountPercentage = floatval(getSetting('discount_percentage', 0));
    
    // Calculate service charge (only for dine-in orders, not for delivery)
    $serviceCharge = ($orderType === 'dine_in') ? ($subtotal * $serviceChargePercentage) / 100 : 0;
    
    // Calculate delivery fee (only for delivery orders)
    $deliveryFee = 0;
    if ($orderType === 'delivery') {
        if ($deliveryFeeType === 'percentage') {
            $deliveryFee = ($subtotal * $deliveryFeeValue) / 100;
        } else {
            $deliveryFee = $deliveryFeeValue;
        }
    }
    
    // Calculate discount
    $discount = ($subtotal * $discountPercentage) / 100;
    
    // Calculate grand total
    $grandTotal = $subtotal + $serviceCharge + $deliveryFee - $discount;
    
    return [
        'subtotal' => round($subtotal, 2),
        'service_charge' => round($serviceCharge, 2),
        'service_charge_percentage' => $serviceChargePercentage,
        'delivery_fee' => round($deliveryFee, 2),
        'delivery_fee_type' => $deliveryFeeType,
        'delivery_fee_value' => $deliveryFeeValue,
        'discount' => round($discount, 2),
        'discount_percentage' => $discountPercentage,
        'grand_total' => round($grandTotal, 2)
    ];
}

/**
 * Get all settings as associative array
 * @return array All settings
 */
function getAllSettings() {
    global $conn;
    
    $result = $conn->query("SELECT setting_key, setting_value, setting_type, description FROM settings");
    $settings = [];
    
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row;
    }
    
    return $settings;
}
