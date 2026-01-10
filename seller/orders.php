<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';
require_once '../helpers/settings_functions.php';

requireRole('seller');

$pageTitle = 'Buyurtma Qabul Qilish - Sotuvchi';
$sellerId = $_SESSION['user_id'];

// Get today's stats for display
$today = getTodayRange();
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as order_count,
        COALESCE(SUM(total_amount), 0) as total_amount
    FROM orders 
    WHERE seller_id = ? AND created_at BETWEEN ? AND ? AND status = 'completed'
");
$stmt->bind_param("iss", $sellerId, $today['start'], $today['end']);
$stmt->execute();
$todayStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $items = json_decode($_POST['items'], true);
    $orderType = sanitize($_POST['order_type'] ?? 'dine_in');
    
    // Delivery information
    $customerName = $orderType === 'delivery' ? sanitize($_POST['customer_name'] ?? '') : null;
    $customerPhone = $orderType === 'delivery' ? sanitize($_POST['customer_phone'] ?? '') : null;
    $customerAddress = $orderType === 'delivery' ? sanitize($_POST['customer_address'] ?? '') : null;
    
    if (empty($items)) {
        $error = 'Kamida bitta mahsulot tanlang';
    } elseif ($orderType === 'delivery' && (empty($customerName) || empty($customerPhone) || empty($customerAddress))) {
        $error = 'Yetkazib berish uchun mijoz ma\'lumotlarini to\'ldiring';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Calculate subtotal
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            // Calculate total with settings (service charge, delivery fee, discount)
            $calculations = calculateOrderTotal($subtotal, $orderType);
            
            // Insert order with breakdown
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    seller_id, total_amount, service_charge, delivery_fee, discount,  grand_total,
                    status, order_type, customer_name, customer_phone, customer_address
                 ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "idddddssss",
                $sellerId,
                $calculations['subtotal'],
                $calculations['service_charge'],
                $calculations['delivery_fee'],
                $calculations['discount'],
                $calculations['grand_total'],
                $orderType,
                $customerName,
                $customerPhone,
                $customerAddress
            );
            $stmt->execute();
            $orderId = $conn->insert_id;
            $stmt->close();
            
            // Insert order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $itemSubtotal = $item['price'] * $item['quantity'];
                $stmt->bind_param("iiidd", $orderId, $item['id'], $item['quantity'], $item['price'], $itemSubtotal);
                $stmt->execute();
            }
            $stmt->close();
            
            $conn->commit();
            
            // Redirect to pending orders
            header("Location: pending_orders.php?success=Buyurtma yaratildi! #" . $orderId);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Xatolik: ' . $e->getMessage();
        }
    }
}

// Get all products with categories
$products = $conn->query("
    SELECT p.*, c.id as category_id, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY c.name, p.name
");

// Get all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Group products by category
$productsByCategory = [];
while ($product = $products->fetch_assoc()) {
    $catId = $product['category_id'];
    $catName = $product['category_name'] ?? 'Boshqa';
    if (!isset($productsByCategory[$catId])) {
        $productsByCategory[$catId] = [
            'name' => $catName,
            'products' => []
        ];
    }
    $productsByCategory[$catId]['products'][] = $product;
}

// Get settings for cart calculations
$serviceChargePercentage = floatval(getSetting('service_charge_percentage', 0));
$deliveryFeeType = getSetting('delivery_fee_type', 'fixed');
$deliveryFeeValue = floatval(getSetting('delivery_fee_value', 0));
$discountPercentage = floatval(getSetting('discount_percentage', 0));

include '../includes/header.php';
?>

<style>
/* Category Tabs */
.category-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
}

.category-tab {
    padding: 0.75rem 1.5rem;
    background: white;
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition-fast);
    white-space: nowrap;
    font-weight: 600;
    font-size: 1rem;
}

.category-tab:hover {
    border-color: var(--primary-600);
    background: var(--primary-50);
}

.category-tab.active {
    background: var(--primary-600);
    color: white;
    border-color: var(--primary-600);
}

/* Product Grid */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.product-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: 1rem;
    box-shadow: var(--shadow-md);
    cursor: pointer;
    transition: all var(--transition-fast);
    border: 2px solid transparent;
    text-align: center;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-600);
}

.product-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: var(--radius-md);
    margin-bottom: 0.75rem;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

/* Cart Section */
.order-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2rem;
}

.cart-section {
    position: sticky;
    top: 80px;
    height: fit-content;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: var(--radius-md);
    margin-bottom: 0.5rem;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 30px;
    height: 30px;
    border: none;
    background: var(--primary-600);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    font-weight: 600;
    transition: all var(--transition-fast);
}

.quantity-btn:hover {
    background: var(--primary-700);
    transform: scale(1.1);
}

/* Order Type */
.order-type-selector {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.order-type-btn {
    flex: 1;
    padding: 0.875rem;
    border: 2px solid var(--gray-300);
    background: white;
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition-fast);
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.order-type-btn:hover {
    border-color: var(--primary-600);
}

.order-type-btn.active {
    border-color: var(--primary-600);
    background: var(--primary-50);
    color: var(--primary-600);
}

.delivery-form {
    display: none;
    margin-bottom: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--radius-md);
}

.delivery-form.show {
    display: block;
}

.category-products {
    display: none;
}

.category-products.active {
    display: block;
}

/* Stats Bar */
.stats-bar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-mini {
    flex: 1;
    background: white;
    padding: 1rem;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.stat-mini-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .order-layout {
        grid-template-columns: 1fr;
    }
    
    .cart-section {
        position: static;
        margin-top: 2rem;
    }
}

@media (max-width: 768px) {
    /* Category Tabs - Better Touch */
    .category-tabs {
        gap: 0.5rem;
        padding-bottom: 1rem;
        -webkit-overflow-scrolling: touch;
    }
    
    .category-tab {
        padding: 12px 20px;
        font-size: 1rem;
        min-height: 48px;
        display: flex;
        align-items: center;
    }
    
    /* Product Grid - 2 columns */
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .product-card {
        padding: 0.75rem;
        min-height: 200px;
    }
    
    .product-image {
        height: 100px;
    }
    
    /* Stats Bar */
    .stats-bar {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .stat-mini {
        padding: 0.75rem;
    }
    
    /* Quantity Buttons - Touch Friendly */
    .quantity-btn {
        width: 40px;
        height: 40px;
        font-size: 1.125rem;
    }
    
    /* Cart Items */
    .cart-item {
        padding: 0.75rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    /* Order Type Buttons */
    .order-type-btn {
        padding: 14px;
        font-size: 1rem;
        min-height: 52px;
    }
}

@media (max-width: 480px) {
    /* Product Grid - 2 columns for quick selection */
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    
    .product-card {
        padding: 0.5rem;
        min-height: auto;
    }
    
    .product-image {
        height: 80px;
        font-size: 2rem;
    }
    
    .product-card div {
        font-size: 0.85rem;
    }
    
    /* Category Tabs - Compact */
    .category-tabs {
        position: relative;
        margin-bottom: 1rem;
    }
    
    .category-tab {
        padding: 10px 16px;
        font-size: 0.9rem;
        min-height: 44px;
    }
    
    .category-tabs::after {
        content: '→';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        background: linear-gradient(to left, var(--primary-100) 20%, transparent);
        padding: 0.5rem 1rem;
        pointer-events: none;
        font-size: 1.25rem;
        color: var(--primary-600);
    }
    
    /* Larger Touch Targets for Quantity */
    .quantity-btn {
        width: 44px;
        height: 44px;
    }
    
    /* Cart Section - Compact at Bottom */
    .cart-section {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 100;
        max-height: 35vh; /* Much smaller - only 35% of screen */
        overflow-y: auto;
        margin-top: 0;
        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.2);
        background: white;
    }
    
    .cart-section .card {
        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        margin-bottom: 0;
        padding: 0.75rem;
    }
    
    .cart-section .card-header {
        padding: 0.5rem 0;
        margin-bottom: 0.5rem;
    }
    
    .cart-section .card-title {
        font-size: 1rem;
    }
    
    .cart-section .card-body {
        padding: 0;
    }
    
    /* Compact Cart Items */
    #cartItems .cart-item {
        padding: 0.5rem;
        font-size: 0.85rem;
    }
    
    #cartItems .cart-item > div:first-child {
        flex: 1;
        min-width: 0;
    }
    
    #cartItems .cart-item div {
        font-size: 0.8rem !important;
    }
    
    /* Order Type Buttons - Smaller */
    .order-type-selector {
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .order-type-btn {
        padding: 10px;
        font-size: 0.85rem;
        min-height: 44px;
    }
    
    /* Delivery Form - Compact */
    .delivery-form {
        padding: 0.5rem;
    }
    
    .delivery-form .form-group {
        margin-bottom: 0.5rem;
    }
    
    .delivery-form input,
    .delivery-form textarea {
        padding: 8px;
        font-size: 14px;
    }
    
    /* Total Section - Compact */
    .cart-section #cartTotal {
        font-size: 1.125rem;
    }
    
    /* Submit Button - Visible */
    #submitOrderBtn {
        padding: 12px;
        font-size: 0.95rem;
        min-height: 48px;
    }
    
    /* Add LESS padding to bottom of products */
    .category-products {
        padding-bottom: 40vh; /* Space for compact cart */
    }
    
    /* Stats Bar - Compact */
    .stats-bar {
        margin-bottom: 1rem;
    }
    
    .stat-mini {
        padding: 0.5rem;
    }
    
    .stat-mini-icon {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
    
    .stat-mini div {
        font-size: 1.25rem !important;
    }
    
    .stat-mini div:last-child {
        font-size: 0.75rem !important;
    }
}

/* Very small screens - 3 columns */
@media (max-width: 360px) {
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Slightly larger screens - 3 columns */
@media (min-width: 481px) and (max-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
}

</style>

<div class="container" style="padding: 2rem 0;">
    <!-- Today's Stats -->
    <div class="stats-bar">
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background: var(--primary-100); color: var(--primary-600);">
                🛒
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--gray-900);">
                    <?= $todayStats['order_count'] ?>
                </div>
                <div style="font-size: 0.875rem; color: var(--gray-600);">
                    Bugungi buyurtmalar
                </div>
            </div>
        </div>
        
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background: #d1fae5; color: var(--success);">
                💰
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">
                    <?= formatCurrency($todayStats['total_amount']) ?>
                </div>
                <div style="font-size: 0.875rem; color: var(--gray-600);">
                    Bugungi savdo
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <h2 style="margin-bottom: 0.5rem;">Mahsulotlarni tanlang va buyurtma yarating</h2>
    
    <!-- Category Tabs -->
    <div class="category-tabs">
        <?php
        $categories->data_seek(0);
        $first = true;
        foreach ($productsByCategory as $catId => $catData):
        ?>
            <button class="category-tab <?= $first ? 'active' : '' ?>" onclick="selectCategory(<?= $catId ?>)">
                <?= htmlspecialchars($catData['name']) ?>
            </button>
        <?php 
            $first = false;
        endforeach; 
        ?>
    </div>
    
    <div class="order-layout">
        <!-- Products Section -->
        <div>
            <?php foreach ($productsByCategory as $catId => $catData): ?>
                <div class="category-products <?= $catId === array_key_first($productsByCategory) ? 'active' : '' ?>" id="category-<?= $catId ?>">
                    <div class="product-grid">
                        <?php foreach ($catData['products'] as $product): ?>
                            <div class="product-card" onclick="addToCart(<?= htmlspecialchars(json_encode($product)) ?>)">
                                <?php if ($product['image']): ?>
                                    <img src="/xpos/uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                                         class="product-image" 
                                         alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <div class="product-image">📦</div>
                                <?php endif; ?>
                                <div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 0.95rem;">
                                    <?= htmlspecialchars($product['name']) ?>
                                </div>
                                <div style="color: var(--success); font-weight: 700; font-size: 1.1rem;">
                                    <?= formatCurrency($product['price']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($productsByCategory)): ?>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 3rem;">
                        <p style="color: var(--gray-500); font-size: 1.125rem;">
                            Hozircha mahsulotlar yo'q
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Cart Section -->
        <div class="cart-section">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Savat</h3>
                </div>
                <div class="card-body">
                    <!-- Order Type Selector -->
                    <div class="order-type-selector">
                        <div class="order-type-btn active" id="dineInBtn" onclick="selectOrderType('dine_in')">
                            🍽️ Oddiy
                        </div>
                        <div class="order-type-btn" id="deliveryBtn" onclick="selectOrderType('delivery')">
                            🚚 Yetkazib berish
                        </div>
                    </div>
                    
                    <!-- Delivery Form -->
                    <div class="delivery-form" id="deliveryForm">
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label class="form-label" style="font-size: 0.875rem;">Mijoz ismi *</label>
                            <input type="text" id="customerName" class="form-control" placeholder="Ism" style="padding: 0.5rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label class="form-label" style="font-size: 0.875rem;">Telefon *</label>
                            <input type="text" id="customerPhone" class="form-control" placeholder="+998901234567" style="padding: 0.5rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 0.875rem;">Manzil *</label>
                            <textarea id="customerAddress" class="form-control" placeholder="Yetkazib berish manzili" style="padding: 0.5rem; min-height: 60px;"></textarea>
                        </div>
                    </div>
                    
                    <div id="cartItems">
                        <p style="text-align: center; color: var(--gray-500); padding: 2rem 0;">Savat bo'sh</p>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid var(--gray-200);">
                        <!-- Breakdown -->
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--gray-600);">Jami:</span>
                            <span id="cartSubtotal" style="font-weight: 600;">0 so'm</span>
                        </div>
                        
                        <div id="serviceChargeRow" style="display: none; margin-bottom: 0.5rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--gray-600);">Xizmat haqi (<?= $serviceChargePercentage ?>%):</span>
                                <span id="serviceChargeAmount" style="font-weight: 600; color: var(--primary-600);">0 so'm</span>
                            </div>
                        </div>
                        
                        <div id="deliveryFeeRow" style="display: none; margin-bottom: 0.5rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--gray-600);">Yetkazib berish:</span>
                                <span id="deliveryFeeAmount" style="font-weight: 600; color: var(--primary-600);">0 so'm</span>
                            </div>
                        </div>
                        
                        <div id="discountRow" style="display: none; margin-bottom: 0.5rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--gray-600);">Chegirma (<?= $discountPercentage ?>%):</span>
                                <span id="discountAmount" style="font-weight: 600; color: var(--danger);">0 so'm</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700; margin-top: 1rem; padding-top: 0.75rem; border-top: 2px solid var(--gray-300);">
                            <span>JAMI TO'LOV:</span>
                            <span id="cartTotal" style="color: var(--success);">0 so'm</span>
                        </div>
                        
                        <form method="POST" id="orderForm">
                            <input type="hidden" name="create_order" value="1">
                            <input type="hidden" name="items" id="cartData" value="[]">
                            <input type="hidden" name="order_type" id="orderTypeInput" value="dine_in">
                            <input type="hidden" name="customer_name" id="customerNameInput">
                            <input type="hidden" name="customer_phone" id="customerPhoneInput">
                            <input type="hidden" name="customer_address" id="customerAddressInput">
                            
                            <button type="submit" class="btn btn-success btn-lg" style="width: 100%;" id="submitOrderBtn" disabled>
                                ✅ Buyurtmani tasdiqlash
                            </button>
                        </form>
                        
                        <button onclick="clearCart()" class="btn btn-danger" style="width: 100%; margin-top: 0.5rem;">
                            🗑️ Savatni tozalash
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let orderType = 'dine_in';

console.log('🚀 Script loaded successfully');

// Global functions for quantity buttons
window.decreaseQty = function(productId) {
    console.log('🔽 Decrease clicked for product:', productId);
    changeQuantity(productId, -1);
};

window.increaseQty = function(productId) {
    console.log('🔼 Increase clicked for product:', productId);
    changeQuantity(productId, 1);
};

function selectCategory(categoryId) {
    console.log('📁 Category selected:', categoryId);
    // Hide all categories
    document.querySelectorAll('.category-products').forEach(div => {
        div.classList.remove('active');
    });
    document.querySelectorAll('.category-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected category
    document.getElementById('category-' + categoryId).classList.add('active');
    event.target.classList.add('active');
}

function selectOrderType(type) {
    console.log('📝 Order type selected:', type);
    orderType = type;
    document.getElementById('orderTypeInput').value = type;
    
    // Update buttons
    document.getElementById('dineInBtn').classList.toggle('active', type === 'dine_in');
    document.getElementById('deliveryBtn').classList.toggle('active', type === 'delivery');
    
    // Show/hide delivery form
    document.getElementById('deliveryForm').classList.toggle('show', type === 'delivery');
}

function addToCart(product) {
    console.log('➕ Adding to cart:', product.name);
    console.log('   Product ID (original):', product.id, 'Type:', typeof product.id);
    
    // Ensure ID is integer
    const productId = parseInt(product.id);
    console.log('   Product ID (converted):', productId, 'Type:', typeof productId);
    
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity++;
        console.log('   Increased quantity to:', existingItem.quantity);
    } else {
        cart.push({
            id: productId,  // Store as integer
            name: product.name,
            price: parseFloat(product.price),
            quantity: 1
        });
        console.log('   New item added to cart');
    }
    
    console.log('🛒 Current cart:', cart);
    updateCart();
}

function changeQuantity(productId, change) {
    // Ensure productId is integer
    productId = parseInt(productId);
    
    console.log('🔄 changeQuantity called - Product ID:', productId, 'Type:', typeof productId, 'Change:', change);
    console.log('   Cart IDs:', cart.map(item => ({id: item.id, type: typeof item.id, name: item.name})));
    
    const item = cart.find(item => item.id === productId);
    
    if (item) {
        console.log('   ✅ Found item:', item.name, 'Current quantity:', item.quantity);
        item.quantity += change;
        console.log('   New quantity:', item.quantity);
        
        if (item.quantity <= 0) {
            console.log('   Removing item from cart');
            cart = cart.filter(item => item.id !== productId);
        }
        console.log('🛒 Cart after change:', cart);
        updateCart();
    } else {
        console.error('❌ Item not found in cart!');
        console.error('   Looking for Product ID:', productId, 'Type:', typeof productId);
        console.error('   Cart contents:', cart);
    }
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCart();
}

function updateCart() {
    console.log('🔄 updateCart called');
    const cartItemsDiv = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const cartData = document.getElementById('cartData');
    const submitBtn = document.getElementById('submitOrderBtn');
    
    if (cart.length === 0) {
        console.log('   Cart is empty');
        cartItemsDiv.innerHTML = '<p style="text-align: center; color: var(--gray-500); padding: 2rem 0;">Savat bo\'sh</p>';
        cartTotal.textContent = '0 so\'m';
        cartData.value = '[]';
        submitBtn.disabled = true;
        
        // Reset breakdown display
        const subtotalEl = document.getElementById('cartSubtotal');
        if (subtotalEl) subtotalEl.textContent = '0 so\'m';
        
        // Hide breakdown rows
        const serviceChargeRow = document.getElementById('serviceChargeRow');
        const deliveryFeeRow = document.getElementById('deliveryFeeRow');
        const discountRow = document.getElementById('discountRow');
        
        if (serviceChargeRow) serviceChargeRow.style.display = 'none';
        if (deliveryFeeRow) deliveryFeeRow.style.display = 'none';
        if (discountRow) discountRow.style.display = 'none';
        
        return;
    }
    
    console.log('   Rendering', cart.length, 'item(s)');
    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        
        html += `
            <div class="cart-item">
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 0.95rem;">${item.name}</div>
                    <div style="color: var(--gray-600); font-size: 0.8rem;">${formatCurrency(item.price)} × ${item.quantity}</div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="quantity-control">
                        <button type="button" class="quantity-btn" onclick="window.decreaseQty(${item.id}); return false;">−</button>
                        <span style="font-weight: 600; min-width: 25px; text-align: center; font-size: 0.95rem;">${item.quantity}</span>
                        <button type="button" class="quantity-btn" onclick="window.increaseQty(${item.id}); return false;">+</button>
                    </div>
                    <div style="font-weight: 700; color: var(--success); min-width: 90px; text-align: right; font-size: 0.95rem;">
                        ${formatCurrency(subtotal)}
                    </div>
                </div>
            </div>
        `;
    });
    
    cartItemsDiv.innerHTML = html;
    cartTotal.textContent = formatCurrency(total);
    cartData.value = JSON.stringify(cart);
    submitBtn.disabled = false;
    console.log('✅ Cart updated. Total:', formatCurrency(total));
}

function clearCart() {
    if (confirm('Savatni tozalamoqchimisiz?')) {
        cart = [];
        updateCart();
    }
}

// Form validation and confirmation before submit
document.getElementById('orderForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Always prevent default first
    
    if (orderType === 'delivery') {
        const name = document.getElementById('customerName').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();
        const address = document.getElementById('customerAddress').value.trim();
        
        if (!name || !phone || !address) {
            alert('Yetkazib berish uchun mijoz ma\'lumotlarini to\'ldiring!');
            return false;
        }
        
        // Set hidden inputs
        document.getElementById('customerNameInput').value = name;
        document.getElementById('customerPhoneInput').value = phone;
        document.getElementById('customerAddressInput').value = address;
    }
    
    // Show custom confirmation modal
    showOrderConfirmation();
});

// Custom confirmation modal
function showOrderConfirmation() {
    const itemCount = cart.length;
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    const modalHtml = `
        <div id="confirmModal" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        ">
            <div style="
                background: white;
                border-radius: 16px;
                padding: 2rem;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            ">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div style="
                        width: 64px;
                        height: 64px;
                        background: var(--primary-100);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 1rem;
                        font-size: 2rem;
                    ">✓</div>
                    <h3 style="margin: 0 0 0.5rem 0; color: var(--gray-900); font-size: 1.5rem;">
                        Buyurtmani tasdiqlaysizmi?
                    </h3>
                    <p style="color: var(--gray-600); margin: 0;">
                        ${itemCount} ta mahsulot • ${formatCurrency(total)}
                    </p>
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button onclick="closeConfirmModal()" style="
                        flex: 1;
                        padding: 14px;
                        background: var(--gray-200);
                        border: none;
                        border-radius: 12px;
                        font-size: 1rem;
                        font-weight: 600;
                        cursor: pointer;
                        color: var(--gray-700);
                        transition: all 0.2s;
                    " onmouseover="this.style.background='var(--gray-300)'" 
                       onmouseout="this.style.background='var(--gray-200)'">
                        ❌ Bekor qilish
                    </button>
                    
                    <button onclick="confirmOrder()" style="
                        flex: 1;
                        padding: 14px;
                        background: var(--success);
                        border: none;
                        border-radius: 12px;
                        font-size: 1rem;
                        font-weight: 600;
                        cursor: pointer;
                        color: white;
                        transition: all 0.2s;
                    " onmouseover="this.style.background='#059669'" 
                       onmouseout="this.style.background='var(--success)'">
                        ✅ Tasdiqlash
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.remove();
    }
}

function confirmOrder() {
    closeConfirmModal();
    // Actually submit the form
    document.getElementById('orderForm').submit();
}

// Remove beforeunload warning - we have custom confirmation now
// (No beforeunload event listener)

// Settings for breakdown calculation
const settings = {
    serviceChargePercentage: <?= $serviceChargePercentage ?>,
    deliveryFeeType: '<?= $deliveryFeeType ?>',
    deliveryFeeValue: <?= $deliveryFeeValue ?>,
    discountPercentage: <?= $discountPercentage ?>
};

// Update cart breakdown display
function updateCartBreakdown() {
    // If cart is empty, reset all values to 0
    if (cart.length === 0) {
        const subtotalEl = document.getElementById('cartSubtotal');
        const totalEl = document.getElementById('cartTotal');
        
        if (subtotalEl) subtotalEl.textContent = '0 so\'m';
        if (totalEl) totalEl.textContent = '0 so\'m';
        
        // Hide all breakdown rows
        const serviceChargeRow = document.getElementById('serviceChargeRow');
        const deliveryFeeRow = document.getElementById('deliveryFeeRow');
        const discountRow = document.getElementById('discountRow');
        
        if (serviceChargeRow) serviceChargeRow.style.display = 'none';
        if (deliveryFeeRow) deliveryFeeRow.style.display = 'none';
        if (discountRow) discountRow.style.display = 'none';
        
        return; // Exit early
    }
    
    // Calculate subtotal directly from cart array
    let subtotal = 0;
    cart.forEach(item => {
        subtotal += item.price * item.quantity;
    });
    
    console.log('Calculated subtotal from cart:', subtotal);
    
    // Get current order type
    const orderType = document.getElementById('dineInBtn')?.classList.contains('active') ? 'dine_in' : 'delivery';
    
    // Calculate service charge (only for dine-in orders, not for delivery)
    const serviceCharge = orderType === 'dine_in' ? (subtotal * settings.serviceChargePercentage) / 100 : 0;
    
    // Calculate delivery fee (only for delivery orders)
    let deliveryFee = 0;
    if (orderType === 'delivery') {
        if (settings.deliveryFeeType === 'percentage') {
            deliveryFee = (subtotal * settings.deliveryFeeValue) / 100;
        } else {
            deliveryFee = settings.deliveryFeeValue;
        }
    }
    
    // Calculate discount
    const discount = (subtotal * settings.discountPercentage) / 100;
    
    // Calculate grand total
    const grandTotal = subtotal + serviceCharge + deliveryFee - discount;
    
    console.log('Breakdown:', {subtotal, serviceCharge, deliveryFee, discount, grandTotal});
    
    // Update display - make sure elements exist
    const subtotalEl = document.getElementById('cartSubtotal');
    const totalEl = document.getElementById('cartTotal');
    
    if (subtotalEl) subtotalEl.textContent = formatCurrency(subtotal);
    if (totalEl) totalEl.textContent = formatCurrency(grandTotal);
    
    // Show/hide service charge
    const serviceChargeRow = document.getElementById('serviceChargeRow');
    if (serviceChargeRow) {
        if (serviceCharge > 0) {
            serviceChargeRow.style.display = 'block';
            const amountEl = document.getElementById('serviceChargeAmount');
            if (amountEl) amountEl.textContent = formatCurrency(serviceCharge);
        } else {
            serviceChargeRow.style.display = 'none';
        }
    }
    
    // Show/hide delivery fee
    const deliveryFeeRow = document.getElementById('deliveryFeeRow');
    if (deliveryFeeRow) {
        if (deliveryFee > 0) {
            deliveryFeeRow.style.display = 'block';
            const amountEl = document.getElementById('deliveryFeeAmount');
            if (amountEl) amountEl.textContent = formatCurrency(deliveryFee);
        } else {
            deliveryFeeRow.style.display = 'none';
        }
    }
    
    // Show/hide discount
    const discountRow = document.getElementById('discountRow');
    if (discountRow) {
        if (discount > 0) {
            discountRow.style.display = 'block';
            const amountEl = document.getElementById('discountAmount');
            if (amountEl) amountEl.textContent = '-' + formatCurrency(discount);
        } else {
            discountRow.style.display = 'none';
        }
    }
}

// Override existing renderCart if it exists
if (typeof window.renderCart === 'function') {
    const originalRenderCart = window.renderCart;
    window.renderCart = function(...args) {
        originalRenderCart(...args);
        setTimeout(updateCartBreakdown, 100);
    };
}

// Helper - format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount).replace(/,/g, ' ') + " so'm";
}

// Update breakdown periodically and on interactions
setInterval(updateCartBreakdown, 500);

// Listen for order type button clicks
document.addEventListener('click', function(e) {
    const target = e.target;
    if (target.id === 'dineInBtn' || target.id === 'deliveryBtn') {
        setTimeout(updateCartBreakdown, 100);
    }
});

// Initial breakdown update
setTimeout(updateCartBreakdown, 500);

</script>

<?php
$conn->close();
include '../includes/footer.php';
?>
