<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';
require_once '../helpers/settings_functions.php';

requireRole('super_admin');

$pageTitle = 'Sozlamalar - Super Admin';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    
    // Get form data
    $serviceCharge = floatval($_POST['service_charge_percentage'] ?? 0);
    $deliveryFeeType = $_POST['delivery_fee_type'] ?? 'fixed';
    $deliveryFeeValue = floatval($_POST['delivery_fee_value'] ?? 0);
    $discount = floatval($_POST['discount_percentage'] ?? 0);
    
    // Validate percentages (0-100)
    if ($serviceCharge < 0 || $serviceCharge > 100) {
        $error = 'Xizmat haqi 0-100% oraligida bolishi kerak';
    } elseif ($discount < 0 || $discount > 100) {
        $error = 'Chegirma 0-100% oraligida bolishi kerak';
    } elseif ($deliveryFeeType === 'percentage' && ($deliveryFeeValue < 0 || $deliveryFeeValue > 100)) {
        $error = 'Yetkazib berish tolovi foizi 0-100% oraligida bolishi kerak';
    } elseif ($deliveryFeeValue < 0) {
        $error = 'Yetkazib berish tolovi 0 dan katta bolishi kerak';
    } else {
        // Update settings
        $success = true;
        $success = $success && updateSetting('service_charge_percentage', $serviceCharge, $userId);
        $success = $success && updateSetting('delivery_fee_type', $deliveryFeeType, $userId);
        $success = $success && updateSetting('delivery_fee_value', $deliveryFeeValue, $userId);
        $success = $success && updateSetting('discount_percentage', $discount, $userId);
        
        if ($success) {
            $successMsg = 'Sozlamalar muvaffaqiyatli saqlandi';
        } else {
            $error = 'Xatolik yuz berdi. Qaytadan urinib koring.';
        }
    }
}

// Get current settings
$settings = getAllSettings();
$serviceCharge = $settings['service_charge_percentage']['setting_value'] ?? '0';
$deliveryFeeType = $settings['delivery_fee_type']['setting_value'] ?? 'fixed';
$deliveryFeeValue = $settings['delivery_fee_value']['setting_value'] ?? '0';
$discount = $settings['discount_percentage']['setting_value'] ?? '0';

include '../includes/header.php';
?>

<style>
/* Global overflow prevention */
* {
    box-sizing: border-box;
}

html, body {
    overflow-x: hidden;
    max-width: 100vw;
}

body {
    position: relative;
}

/* Settings sections */
.settings-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    border-left: 4px solid var(--primary-500);
}

.settings-section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.settings-section-desc {
    color: var(--gray-600);
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
}

.radio-group {
    display: flex;
    gap: 2rem;
    margin-bottom: 1rem;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.radio-option input[type="radio"] {
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.radio-option label {
    cursor: pointer;
    margin: 0;
}

.input-with-unit {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.input-with-unit .form-control {
    flex: 1;
    max-width: 200px;
}

.unit-label {
    font-weight: 600;
    color: var(--primary-600);
    min-width: 50px;
}

/* Example box */
.example-box {
    background: white;
    padding: 1rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--gray-300);
    margin-top: 1rem;
}

.example-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.example-calculation {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    color: var(--gray-600);
    line-height: 1.8;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .container {
        max-width: 100vw;
        overflow-x: hidden;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    .settings-section {
        padding: 1rem;
    }
    
    .settings-section-title {
        font-size: 1.125rem;
    }
    
    .radio-group {
        flex-direction: column;
        gap: 1rem;
    }
    
    .input-with-unit .form-control {
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 1rem 0.5rem !important;
    }
    
    .settings-section {
        padding: 0.75rem;
        margin-bottom: 1rem;
    }
    
    .settings-section-title {
        font-size: 1rem;
    }
    
    .example-box {
        padding: 0.75rem;
    }
}
</style>

<div class="container" style="padding: 2rem 0;">
    <div style="margin-bottom: 2rem;">
        <h1>⚙️ Tizim sozlamalari</h1>
        <p style="color: var(--gray-600); margin: 0.5rem 0 0 0;">Xizmat haqi, yetkazib berish to'lovi va chegirmalarni boshqarish</p>
    </div>
    
    <?php if (isset($successMsg)): ?>
        <div class="alert alert-success"><?= $successMsg ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST" onsubmit="return validateSettings()">
        <!-- Service Charge Section -->
        <div class="settings-section">
            <div class="settings-section-title">
                📊 Qo'shimcha xizmat haqi
            </div>
            <div class="settings-section-desc">
                Barcha buyurtmalarga qo'shimcha xizmat haqi (foizda). Jami summaga qo'shiladi.
            </div>
            
            <div class="input-with-unit">
                <input type="number" 
                       name="service_charge_percentage" 
                       id="service_charge" 
                       class="form-control" 
                       min="0" 
                       max="100" 
                       step="0.01" 
                       value="<?= htmlspecialchars($serviceCharge) ?>" 
                       required>
                <span class="unit-label">%</span>
            </div>
            
            <div class="example-box">
                <div class="example-title">Misol:</div>
                <div class="example-calculation">
                    Jami: 100,000 so'm<br>
                    Xizmat haqi (<?= htmlspecialchars($serviceCharge) ?>%): <?= formatCurrency(100000 * floatval($serviceCharge) / 100) ?><br>
                    <strong>Jami to'lov: <?= formatCurrency(100000 + (100000 * floatval($serviceCharge) / 100)) ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Delivery Fee Section -->
        <div class="settings-section">
            <div class="settings-section-title">
                🚚 Yetkazib berish to'lovi
            </div>
            <div class="settings-section-desc">
                Faqat yetkazib berish buyurtmalari uchun. Belgilangan summa yoki foizda bo'lishi mumkin.
            </div>
            
            <div class="radio-group">
                <div class="radio-option">
                    <input type="radio" 
                           name="delivery_fee_type" 
                           id="type_fixed" 
                           value="fixed" 
                           <?= $deliveryFeeType === 'fixed' ? 'checked' : '' ?>
                           onchange="toggleDeliveryFeeUnit()">
                    <label for="type_fixed">Belgilangan summa</label>
                </div>
                <div class="radio-option">
                    <input type="radio" 
                           name="delivery_fee_type" 
                           id="type_percentage" 
                           value="percentage" 
                           <?= $deliveryFeeType === 'percentage' ? 'checked' : '' ?>
                           onchange="toggleDeliveryFeeUnit()">
                    <label for="type_percentage">Foizda</label>
                </div>
            </div>
            
            <div class="input-with-unit">
                <input type="number" 
                       name="delivery_fee_value" 
                       id="delivery_fee" 
                       class="form-control" 
                       min="0" 
                       step="0.01" 
                       value="<?= htmlspecialchars($deliveryFeeValue) ?>" 
                       required>
                <span class="unit-label" id="delivery_unit">
                    <?= $deliveryFeeType === 'percentage' ? '%' : "so'm" ?>
                </span>
            </div>
            
            <div class="example-box">
                <div class="example-title">Misol (yetkazib berish buyurtmasi):</div>
                <div class="example-calculation" id="delivery_example">
                    <?php
                    $deliveryAmount = $deliveryFeeType === 'percentage' 
                        ? (100000 * floatval($deliveryFeeValue) / 100)
                        : floatval($deliveryFeeValue);
                    ?>
                    Jami: 100,000 so'm<br>
                    Yetkazib berish: <?= formatCurrency($deliveryAmount) ?><br>
                    <strong>Jami to'lov: <?= formatCurrency(100000 + $deliveryAmount) ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Discount Section -->
        <div class="settings-section">
            <div class="settings-section-title">
                💰 Chegirma
            </div>
            <div class="settings-section-desc">
                Barcha buyurtmalarga chegirma (foizda). Jami summadan ayriladi.
            </div>
            
            <div class="input-with-unit">
                <input type="number" 
                       name="discount_percentage" 
                       id="discount" 
                       class="form-control" 
                       min="0" 
                       max="100" 
                       step="0.01" 
                       value="<?= htmlspecialchars($discount) ?>" 
                       required>
                <span class="unit-label">%</span>
            </div>
            
            <div class="example-box">
                <div class="example-title">Misol:</div>
                <div class="example-calculation">
                    Jami: 100,000 so'm<br>
                    Chegirma (<?= htmlspecialchars($discount) ?>%): -<?= formatCurrency(100000 * floatval($discount) / 100) ?><br>
                    <strong>Jami to'lov: <?= formatCurrency(100000 - (100000 * floatval($discount) / 100)) ?></strong>
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">💾 Saqlash</button>
        </div>
    </form>
</div>

<script>
function toggleDeliveryFeeUnit() {
    const typeFixed = document.getElementById('type_fixed').checked;
    const unitLabel = document.getElementById('delivery_unit');
    const deliveryFee = document.getElementById('delivery_fee');
    
    if (typeFixed) {
        unitLabel.textContent = "so'm";
        deliveryFee.max = '';
    } else {
        unitLabel.textContent = '%';
        deliveryFee.max = '100';
    }
    
    updateDeliveryExample();
}

function updateDeliveryExample() {
    const typeFixed = document.getElementById('type_fixed').checked;
    const deliveryFee = parseFloat(document.getElementById('delivery_fee').value) || 0;
    const deliveryAmount = typeFixed ? deliveryFee : (100000 * deliveryFee / 100);
    
    document.getElementById('delivery_example').innerHTML = `
        Jami: 100,000 so'm<br>
        Yetkazib berish: ${formatNumber(deliveryAmount)} so'm<br>
        <strong>Jami to'lov: ${formatNumber(100000 + deliveryAmount)} so'm</strong>
    `;
}

function formatNumber(num) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(num).replace(/,/g, ' ');
}

function validateSettings() {
    const serviceCharge = parseFloat(document.getElementById('service_charge').value);
    const discount = parseFloat(document.getElementById('discount').value);
    const deliveryFee = parseFloat(document.getElementById('delivery_fee').value);
    const typeFixed = document.getElementById('type_fixed').checked;
    
    if (serviceCharge < 0 || serviceCharge > 100) {
        alert('Xizmat haqi 0-100% oraligida bolishi kerak');
        return false;
    }
    
    if (discount < 0 || discount > 100) {
        alert('Chegirma 0-100% oraligida bolishi kerak');
        return false;
    }
    
    if (!typeFixed && (deliveryFee < 0 || deliveryFee > 100)) {
        alert('Yetkazib berish tolovi foizi 0-100% oraligida bolishi kerak');
        return false;
    }
    
    if (deliveryFee < 0) {
        alert('Yetkazib berish tolovi 0 dan katta bolishi kerak');
        return false;
    }
    
    return true;
}

// Update examples on input change
document.getElementById('delivery_fee').addEventListener('input', updateDeliveryExample);
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>
