<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole('seller');

$pageTitle = 'Buyurtmani Tahrirlash - Sotuvchi';
$sellerId = $_SESSION['user_id'];
$orderId = intval($_GET['id'] ?? 0);

// Get order and verify ownership
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND seller_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $orderId, $sellerId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: pending_orders.php");
    exit();
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_quantity') {
        $itemId = intval($_POST['item_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity > 0) {
            $stmt = $conn->prepare("
                UPDATE order_items oi
                JOIN orders o ON oi.order_id = o.id
                SET oi.quantity = ?, oi.subtotal = oi.price * ?
                WHERE oi.id = ? AND o.id = ? AND o.seller_id = ?
            ");
            $stmt->bind_param("iiiii", $quantity, $quantity, $itemId, $orderId, $sellerId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Recalculate total
        updateOrderTotal($conn, $orderId);
        header("Location: edit_order.php?id=$orderId&success=Yangilandi");
        exit();
        
    } elseif ($action === 'remove_item') {
        $itemId = intval($_POST['item_id']);
        
        $stmt = $conn->prepare("
            DELETE oi FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.id = ? AND o.id = ? AND o.seller_id = ?
        ");
        $stmt->bind_param("iii", $itemId, $orderId, $sellerId);
        $stmt->execute();
        $stmt->close();
        
        updateOrderTotal($conn, $orderId);
        header("Location: edit_order.php?id=$orderId&success=Mahsulot o'chirildi");
        exit();
        
    } elseif ($action === 'add_product') {
        $productId = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($productId > 0 && $quantity > 0) {
            // Get product price
            $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                $price = $product['price'];
                $subtotal = $price * $quantity;
                
                // Check if product already in order
                $stmt = $conn->prepare("SELECT id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $orderId, $productId);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($existing) {
                    // Update quantity
                    $newQty = $existing['quantity'] + $quantity;
                    $stmt = $conn->prepare("UPDATE order_items SET quantity = ?, subtotal = price * ? WHERE id = ?");
                    $stmt->bind_param("iii", $newQty, $newQty, $existing['id']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Add new item
                    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiidd", $orderId, $productId, $quantity, $price, $subtotal);
                    $stmt->execute();
                    $stmt->close();
                }
                
                updateOrderTotal($conn, $orderId);
                header("Location: edit_order.php?id=$orderId&success=Mahsulot qo'shildi");
                exit();
            }
            $stmt->close();
        }
    }
}

function updateOrderTotal($conn, $orderId) {
    $stmt = $conn->prepare("
        UPDATE orders 
        SET total_amount = (
            SELECT COALESCE(SUM(subtotal), 0) 
            FROM order_items 
            WHERE order_id = ?
        )
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $orderId, $orderId);
    $stmt->execute();
    $stmt->close();
}

// Get order items
$items = $conn->query("
    SELECT oi.*, p.name as product_name, p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $orderId
");

// Get all products for adding
$products = $conn->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY c.name, p.name
");

// Refresh order total
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

include '../includes/header.php';
?>

<style>
.edit-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--radius-md);
    margin-bottom: 0.75rem;
}

.qty-input {
    width: 80px;
    text-align: center;
    padding: 0.5rem;
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-md);
    font-weight: 600;
}
</style>

<div class="container" style="padding: 2rem 0;">
    <div style="margin-bottom: 2rem;">
        <a href="pending_orders.php" class="btn btn-outline">← Ortga</a>
    </div>
    
    <h1>Buyurtma #<?= $order['id'] ?> ni tahrirlash</h1>
    <p style="color: var(--gray-600); margin-bottom: 2rem;">Mahsulotlarni o'zgartiring</p>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Current Items -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Buyurtmadagi mahsulotlar</h3>
                </div>
                <div class="card-body">
                    <?php if ($items->num_rows > 0): ?>
                        <?php while ($item = $items->fetch_assoc()): ?>
                            <div class="edit-item">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600;"><?= htmlspecialchars($item['product_name']) ?></div>
                                    <div style="color: var(--gray-600); font-size: 0.9rem;">
                                        <?= formatCurrency($item['price']) ?> × <?= $item['quantity'] ?>
                                    </div>
                                </div>
                                
                                <form method="POST" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="hidden" name="action" value="update_quantity">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" class="qty-input" required>
                                    <button type="submit" class="btn btn-primary">✓</button>
                                </form>
                                
                                <div style="font-weight: 700; color: var(--success); min-width: 100px; text-align: right;">
                                    <?= formatCurrency($item['subtotal']) ?>
                                </div>
                                
                                <form method="POST" onsubmit="return confirm('O\'chirmoqchimisiz?')">
                                    <input type="hidden" name="action" value="remove_item">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-danger">🗑️</button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--gray-500); padding: 2rem;">
                            Mahsulotlar yo'q
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Add Product -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">Mahsulot qo'shish</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_product">
                        
                        <div class="form-group">
                            <label class="form-label">Mahsulot</label>
                            <select name="product_id" class="form-control" required>
                                <option value="">Tanlang...</option>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                    <option value="<?= $product['id'] ?>">
                                        <?= htmlspecialchars($product['name']) ?> - <?= formatCurrency($product['price']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Miqdor</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            ➕ Qo'shish
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Summary -->
        <div>
            <div class="card" style="position: sticky; top: 80px;">
                <div class="card-header">
                    <h3 class="card-title">Xulosa</h3>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 1rem;">
                        <div style="color: var(--gray-600); margin-bottom: 0.5rem;">Buyurtma:</div>
                        <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-600);">
                            #<?= $order['id'] ?>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <div style="color: var(--gray-600); margin-bottom: 0.5rem;">Turi:</div>
                        <div style="font-weight: 600;">
                            <?= $order['order_type'] === 'delivery' ? '🚚 Yetkazib berish' : '🍽️ Oddiy' ?>
                        </div>
                    </div>
                    
                    <div style="padding: 1.5rem 0; border-top: 2px solid var(--gray-200); border-bottom: 2px solid var(--gray-200); margin: 1.5rem 0;">
                        <div style="color: var(--gray-600); margin-bottom: 0.5rem;">JAMI SUMMA:</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--success);">
                            <?= formatCurrency($order['total_amount']) ?>
                        </div>
                    </div>
                    
                    <a href="pending_orders.php" class="btn btn-primary btn-lg" style="width: 100%;">
                        ✓ Tayyor
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>
