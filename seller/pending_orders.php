<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole('seller');

$pageTitle = 'Buyurtmalar - Sotuvchi';
$sellerId = $_SESSION['user_id'];

// Handle order cancellation
if (isset($_POST['cancel_order'])) {
    $orderId = intval($_POST['order_id']);
    
    // Verify ownership
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND seller_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $orderId, $sellerId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', cancelled_by = ? WHERE id = ?");
        $stmt->bind_param("ii", $sellerId, $orderId);
        $stmt->execute();
        $stmt->close();
        
        $success = "Buyurtma #$orderId bekor qilindi";
    }
}

// Get all pending orders for this seller
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE seller_id = ? AND status = 'pending'
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $sellerId);
$stmt->execute();
$pendingOrders = $stmt->get_result();
$stmt->close();

include '../includes/header.php';
?>

<style>
.order-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-md);
    border-left: 4px solid var(--warning);
    transition: all var(--transition-fast);
}

.order-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--gray-200);
}

.order-items {
    margin-bottom: 1rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    color: var(--gray-700);
}

.order-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.delivery-info {
    background: var(--primary-50);
    padding: 0.75rem;
    border-radius: var(--radius-md);
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .order-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .order-header > div:last-child {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .order-actions {
        width: 100%;
    }
    
    .order-actions a,
    .order-actions button {
        flex: 1;
        min-width: 140px;
        text-align: center;
        justify-content: center;
        min-height: 48px;
        font-size: 0.95rem;
    }
    
    .order-item {
        flex-wrap: wrap;
        gap: 0.25rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--gray-100);
    }
    
    .order-item:last-child {
        border-bottom: none;
    }
    
    .delivery-info {
        font-size: 0.875rem;
        line-height: 1.6;
    }
}

@media (max-width: 480px) {
    .order-card {
        padding: 0.75rem;
        border-radius: var(--radius-md);
    }
    
    .order-header h3 {
        font-size: 1.125rem;
    }
    
    .order-header > div:last-child {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .order-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .order-actions a,
    .order-actions button {
        width: 100%;
        min-width: auto;
    }
    
    .order-item {
        font-size: 0.9rem;
    }
}

</style>

<div class="container" style="padding: 2rem 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Pending Buyurtmalar</h1>
            <p style="color: var(--gray-600); margin: 0;">Yakunlanmagan buyurtmalar</p>
        </div>
        <a href="orders.php" class="btn btn-primary">
            ➕ Yangi buyurtma
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if ($pendingOrders->num_rows > 0): ?>
        <?php while ($order = $pendingOrders->fetch_assoc()): ?>
            <?php
            // Get order items
            $stmt = $conn->prepare("
                SELECT oi.*, p.name as product_name
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param("i", $order['id']);
            $stmt->execute();
            $items = $stmt->get_result();
            $stmt->close();
            ?>
            
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <h3 style="margin: 0; color: var(--primary-600);">
                            Buyurtma #<?= $order['id'] ?>
                        </h3>
                        <p style="margin: 0.25rem 0 0 0; color: var(--gray-600); font-size: 0.9rem;">
                            📅 <?= formatDate($order['created_at']) ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">
                            <?= formatCurrency($order['total_amount']) ?>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--gray-600);">
                            <?= $order['order_type'] === 'delivery' ? '🚚 Yetkazib berish' : '🍽️ Oddiy' ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($order['order_type'] === 'delivery'): ?>
                    <div class="delivery-info">
                        <strong>📍 Mijoz ma'lumotlari:</strong><br>
                        👤 <?= htmlspecialchars($order['customer_name']) ?><br>
                        📞 <?= htmlspecialchars($order['customer_phone']) ?><br>
                        🏠 <?= htmlspecialchars($order['customer_address']) ?>
                    </div>
                <?php endif; ?>
                
                <div class="order-items">
                    <strong style="color: var(--gray-700);">Mahsulotlar:</strong>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <div class="order-item">
                            <span><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></span>
                            <span style="font-weight: 600;"><?= formatCurrency($item['subtotal']) ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="order-actions">
                    <a href="edit_order.php?id=<?= $order['id'] ?>" class="btn btn-primary">
                        ✏️ Tahrirlash
                    </a>
                    <a href="complete_order.php?id=<?= $order['id'] ?>" class="btn btn-success">
                        💰 To'lov qabul qilish
                    </a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Buyurtmani bekor qilmoqchimisiz?')">
                        <input type="hidden" name="cancel_order" value="1">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" class="btn btn-danger">
                            ❌ Bekor qilish
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                <h3 style="color: var(--gray-600); margin-bottom: 1rem;">Buyurtmalar yo'q</h3>
                <p style="color: var(--gray-500); margin-bottom: 1.5rem;">
                    Hozircha buyurtmalar mavjud emas
                </p>
                <a href="orders.php" class="btn btn-primary btn-lg">
                    ➕ Yangi buyurtma yaratish
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>
