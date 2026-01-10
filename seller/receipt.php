<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole(['seller', 'manager', 'super_admin']);

$orderId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Get order (sellers can only see their own, managers can see all)
if ($userRole === 'seller') {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND seller_id = ? AND status = 'completed'");
    $stmt->bind_param("ii", $orderId, $userId);
} else {
    // Manager can access any order
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND status = 'completed'");
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

// Get order items
$items = $conn->query("
    SELECT oi.*, p.name as product_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $orderId
");

// Get payment info from session if available
$paymentAmount = $_SESSION['payment_amount'] ?? $order['total_amount'];
$changeAmount = $_SESSION['change_amount'] ?? 0;

// Clear session data
unset($_SESSION['payment_amount']);
unset($_SESSION['change_amount']);
unset($_SESSION['last_order_id']);

$pageTitle = 'Chek - Buyurtma #' . $orderId;
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 320px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .receipt {
            font-size: 12px;
            line-height: 1.4;
        }
        
        .center {
            text-align: center;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .large {
            font-size: 16px;
        }
        
        .separator {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .double-separator {
            border-top: 2px solid #000;
            margin: 10px 0;
        }
        
        .row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        
        .item-row {
            margin: 8px 0;
        }
        
        .no-print {
            margin-top: 20px;
            text-align: center;
        }
        
        .btn {
            padding: 12px 24px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        
        .btn-outline {
            background: white;
            border: 2px solid #2563eb;
            color: #2563eb;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
                max-width: 58mm;
                padding: 0;
                box-shadow: none;
                margin: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                size: 58mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt">
            <!-- Header -->
            <div class="center large bold">
                FAST FOOD RESTAURANT
            </div>
            <div class="center" style="margin-top: 5px;">
                Toshkent, O'zbekiston
            </div>
            <div class="center">
                Tel: +998 90 123 45 67
            </div>
            
            <div class="double-separator"></div>
            
            <!-- Order Info -->
            <div class="row">
                <span class="bold">Buyurtma:</span>
                <span class="bold">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="row">
                <span>Vaqt:</span>
                <span><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="row">
                <span>Sotuvchi:</span>
                <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            </div>
            <?php if ($order['order_type'] === 'delivery'): ?>
                <div class="row">
                    <span>Turi:</span>
                    <span class="bold">YETKAZIB BERISH</span>
                </div>
            <?php endif; ?>
            
            <div class="separator"></div>
            
            <!-- Items -->
            <?php while ($item = $items->fetch_assoc()): ?>
                <div class="item-row">
                    <div class="bold"><?= htmlspecialchars($item['product_name']) ?></div>
                    <div class="row">
                        <span><?= formatCurrency($item['price']) ?> x <?= $item['quantity'] ?></span>
                        <span class="bold"><?= formatCurrency($item['subtotal']) ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <div class="double-separator"></div>
            
            <!-- Breakdown -->
            <div class="row">
                <span>Jami:</span>
                <span><?= formatCurrency($order['total_amount']) ?></span>
            </div>
            
            <?php if ($order['service_charge'] > 0): ?>
                <div class="row">
                    <span>Xizmat haqi:</span>
                    <span><?= formatCurrency($order['service_charge']) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($order['delivery_fee'] > 0): ?>
                <div class="row">
                    <span>Yetkazib berish:</span>
                    <span><?= formatCurrency($order['delivery_fee']) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($order['discount'] > 0): ?>
                <div class="row">
                    <span>Chegirma:</span>
                    <span>-<?= formatCurrency($order['discount']) ?></span>
                </div>
            <?php endif; ?>
            
            <div class="separator"></div>
            
            <!-- Grand Total -->
            <div class="row large bold">
                <span>JAMI TO'LOV:</span>
                <span><?= formatCurrency($order['grand_total'] ?? $order['total_amount']) ?></span>
            </div>
            
            <?php if ($paymentAmount > 0): ?>
                <div class="separator"></div>
                <div class="row">
                    <span>To'langan:</span>
                    <span><?= formatCurrency($paymentAmount) ?></span>
                </div>
                
            <?php endif; ?>
            
            <?php if ($order['order_type'] === 'delivery' && $order['customer_name']): ?>
                <div class="double-separator"></div>
                <div class="center bold">MIJOZ MA'LUMOTLARI</div>
                <div style="margin-top: 5px;">
                    <div>Ism: <?= htmlspecialchars($order['customer_name']) ?></div>
                    <div>Tel: <?= htmlspecialchars($order['customer_phone']) ?></div>
                    <div>Manzil: <?= htmlspecialchars($order['customer_address']) ?></div>
                </div>
            <?php endif; ?>
            
            <div class="double-separator"></div>
            
            <!-- Footer -->
            <div class="center bold large" style="margin: 15px 0;">
                RAHMAT!
            </div>
            <div class="center">
                Qaytib kelib turing!
            </div>
            
            <div style="margin-top: 15px; text-align: center; font-size: 10px;">
                <?= date('d.m.Y H:i:s') ?>
            </div>
        </div>
    </div>
    
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            🖨️ Chop etish
        </button>
        <button onclick="window.location.href='<?= ($userRole === 'manager' || $userRole === 'super_admin') ? '../manager/orders.php' : 'pending_orders.php' ?>'" class="btn btn-outline">
            ← Buyurtmalarga qaytish
        </button>
    </div>
    
    <script>
        // Auto print option (uncomment if needed)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>
<?php
$conn->close();
?>
