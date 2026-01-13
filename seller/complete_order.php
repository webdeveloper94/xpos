<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole(['seller', 'manager', 'super_admin']);

$pageTitle = 'To\'lov Qabul Qilish - Sotuvchi';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$orderId = intval($_GET['id'] ?? 0);

// Get order and verify ownership (sellers can only see their own, managers can see all)
if ($userRole === 'seller') {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND seller_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $orderId, $userId);
} else {
    // Manager can access any order
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $orderId);
}
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    if ($userRole === 'manager' || $userRole === 'super_admin') {
        $redirectUrl = '../manager/orders.php';
    } else {
        $redirectUrl = 'pending_orders.php';
    }
    header("Location: $redirectUrl");
    exit();
}

// Handle payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $paymentAmount = floatval($_POST['payment_amount'] ?? 0);
    $grandTotal = $order['grand_total'] ?? $order['total_amount'];
    
    if ($paymentAmount >= $grandTotal) {
        // Mark order as completed
        $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $stmt->close();
        
        // Store payment info in session for receipt
        $_SESSION['last_order_id'] = $orderId;
        $_SESSION['payment_amount'] = $paymentAmount;
        $_SESSION['change_amount'] = $paymentAmount - $grandTotal;
        
        // Show modal to ask for receipt
        $showReceiptModal = true;
    } else {
        $error = 'Tolov summasi buyurtma summasidan kam!';
    }
}

// Get order items
$items = $conn->query("
    SELECT oi.*, p.name as product_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $orderId
");

include '../includes/header.php';
?>

<style>
.payment-card {
    max-width: 600px;
    margin: 0 auto;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-200);
}

.summary-row.total {
    border-bottom: none;
    border-top: 2px solid var(--gray-300);
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--success);
}

.receipt-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    overflow-y: auto;
}

.receipt-modal.active {
    display: flex;
}

.receipt-modal-content {
    background: white;
    padding: 2.5rem 2rem;
    border-radius: var(--radius-xl);
    max-width: 450px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.receipt-modal-content .success-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
}

.receipt-modal-content h2 {
    margin: 0 0 0.75rem 0;
    color: var(--success);
    font-size: 1.75rem;
}

.receipt-modal-content .modal-description {
    color: var(--gray-600);
    margin: 0 0 2rem 0;
    font-size: 1rem;
    line-height: 1.5;
}

.receipt-modal-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .payment-card {
        max-width: 100%;
    }
    
    .summary-row.total {
        font-size: 1.25rem;
    }
    
    .receipt-modal {
        padding: 1rem;
    }
    
    .receipt-modal-content {
        padding: 2rem 1.5rem;
        max-height: 85vh;
    }
    
    .receipt-modal-content .success-icon {
        width: 70px;
        height: 70px;
        font-size: 2.5rem;
    }
    
    .receipt-modal-content h2 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .receipt-modal-content {
        padding: 1.5rem 1rem;
        border-radius: var(--radius-lg);
    }
    
    .receipt-modal-content .success-icon {
        width: 60px;
        height: 60px;
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    
    .receipt-modal-content h2 {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
    }
    
    .receipt-modal-content .modal-description {
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
}

</style>

<div class="container" style="padding: 2rem 0;">
    <div style="margin-bottom: 2rem;">
        <a href="pending_orders.php" class="btn btn-outline">← Ortga</a>
    </div>
    
    <div class="payment-card">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">To'lov Qabul Qilish</h2>
                <p style="margin: 0.5rem 0 0 0; color: var(--gray-600);">Buyurtma #<?= $order['id'] ?></p>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <!-- Order Summary -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="margin-bottom: 1rem;">Buyurtma tafsilotlari:</h4>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <div class="summary-row">
                            <span><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></span>
                            <span style="font-weight: 600;"><?= formatCurrency($item['subtotal']) ?></span>
                        </div>
                    <?php endwhile; ?>
                    
                    <!-- Breakdown -->
                    <div class="summary-row">
                        <span>Jami:</span>
                        <span style="font-weight: 600;"><?= formatCurrency($order['total_amount']) ?></span>
                    </div>
                    
                    <?php if ($order['service_charge'] > 0): ?>
                        <div class="summary-row">
                            <span>Xizmat haqi:</span>
                            <span style="font-weight: 600; color: var(--primary-600);"><?= formatCurrency($order['service_charge']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['delivery_fee'] > 0): ?>
                        <div class="summary-row">
                            <span>Yetkazib berish:</span>
                            <span style="font-weight: 600; color: var(--primary-600);"><?= formatCurrency($order['delivery_fee']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['discount'] > 0): ?>
                        <div class="summary-row">
                            <span>Chegirma:</span>
                            <span style="font-weight: 600; color: var(--danger);">-<?= formatCurrency($order['discount']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span>JAMI TO'LOV:</span>
                        <span><?= formatCurrency($order['grand_total'] ?? $order['total_amount']) ?></span>
                    </div>
                </div>
                
                <!-- Payment Form -->
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="complete_order" value="1">
                    
                    <div class="form-group">
                        <label class="form-label" style="font-size: 1.1rem; font-weight: 600;">
                            To'lov summasi (so'm)
                        </label>
                        <input 
                            type="number" 
                            name="payment_amount" 
                            id="paymentAmount"
                            class="form-control" 
                            style="font-size: 1.5rem; text-align: center; font-weight: 700;"
                            value="<?= $order['grand_total'] ?? $order['total_amount'] ?>"
                            min="<?= $order['grand_total'] ?? $order['total_amount'] ?>"
                            step="1"
                            required
                            autofocus
                        >
                    </div>
                    
                    
                    
                    <button type="submit" class="btn btn-success btn-lg" style="width: 100%; font-size: 1.25rem;">
                        ✅ Buyurtmani yakunlash
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<?php if (isset($showReceiptModal)): ?>
<div class="receipt-modal active" id="receiptModal">
    <div class="receipt-modal-content">
        <div class="success-icon">✅</div>
        <h2>Buyurtma yakunlandi!</h2>
        <p class="modal-description">
            Buyurtma #<?= $orderId ?> muvaffaqiyatli yakunlandi
        </p>
        
        <div class="receipt-modal-buttons">
            <a href="receipt.php?id=<?= $orderId ?>" class="btn btn-primary">
                🖨️ Chek chiqarish
            </a>
            <a href="<?= $userRole === 'manager' ? '../manager/orders.php' : 'pending_orders.php' ?>" class="btn btn-outline">
                ← Buyurtmalarga qaytish
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const paymentInput = document.getElementById('paymentAmount');
const changeDiv = document.getElementById('changeAmount');
const changeValue = document.getElementById('changeValue');
const totalAmount = <?= $order['total_amount'] ?>;

paymentInput.addEventListener('input', function() {
    const payment = parseFloat(this.value) || 0;
    const change = payment - totalAmount;
    
    if (change > 0) {
        changeDiv.style.display = 'block';
        changeValue.textContent = formatCurrency(change);
    } else {
        changeDiv.style.display = 'none';
    }
});

// Quick payment buttons
const quickAmounts = [totalAmount, Math.ceil(totalAmount / 10000) * 10000, Math.ceil(totalAmount / 50000) * 50000];
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>
