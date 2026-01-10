<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole(['manager', 'super_admin']);

$pageTitle = 'Buyurtmalar - Menejer';

// Get date range
$dateRange = $_GET['range'] ?? 'today';

// Custom date range
if ($dateRange === 'custom' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $range = [
        'start' => $_GET['start_date'] . ' 00:00:00',
        'end' => $_GET['end_date'] . ' 23:59:59'
    ];
    $rangeText = date('d.m.Y', strtotime($_GET['start_date'])) . ' - ' . date('d.m.Y', strtotime($_GET['end_date']));
} else {
    switch ($dateRange) {
        case 'today':
            $range = getTodayRange();
            $rangeText = 'Bugun';
            break;
        case 'week':
            $range = getWeekRange();
            $rangeText = 'Bu hafta';
            break;
        case 'month':
            $range = getMonthRange();
            $rangeText = 'Bu oy';
            break;
        default:
            $range = getTodayRange();
            $rangeText = 'Bugun';
            break;
    }
}

// Get seller filter
$sellerFilter = isset($_GET['seller']) ? intval($_GET['seller']) : 0;

// Get status filter
$statusFilter = $_GET['status'] ?? 'all';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$whereConditions = ["o.created_at BETWEEN ? AND ?"];
$paramTypes = "ss";
$paramValues = [$range['start'], $range['end']];

if ($sellerFilter > 0) {
    $whereConditions[] = "o.seller_id = ?";
    $paramTypes .= "i";
    $paramValues[] = $sellerFilter;
}

if ($statusFilter !== 'all') {
    $whereConditions[] = "o.status = ?";
    $paramTypes .= "s";
    $paramValues[] = $statusFilter;
}

$whereClause = implode(" AND ", $whereConditions);

// Statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(total_amount), 0) as total_sum
    FROM orders o
    WHERE $whereClause
";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param($paramTypes, ...$paramValues);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate total pages
$totalPages = ceil($stats['total_orders'] / $perPage);

// Get orders with pagination
$ordersQuery = "
    SELECT o.*, u.name as seller_name
    FROM orders o
    LEFT JOIN users u ON o.seller_id = u.id
    WHERE $whereClause
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($ordersQuery);
$paramTypes .= "ii";
$paramValues[] = $perPage;
$paramValues[] = $offset;
$stmt->bind_param($paramTypes, ...$paramValues);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

// Get all sellers for filter dropdown
$sellers = $conn->query("
    SELECT id, name 
    FROM users 
    WHERE role = 'seller' 
    ORDER BY name ASC
");

include '../includes/header.php';
?>

<style>
/* Filter Section */
.filter-section {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.date-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-left: 1rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .filter-section {
        width: 100%;
    }
    
    .date-form {
        margin-left: 0;
        width: 100%;
        margin-top: 0.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 480px) {
    .date-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .date-form input, .date-form select {
        width: 100%;
    }
}
</style>

<div class="container" style="padding: 2rem 0;">
    <h1>Buyurtmalar</h1>
    <p style="color: var(--gray-600); margin-bottom: 2rem;">Barcha buyurtmalarni ko'rish va boshqarish</p>
    
    <!-- Filters -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div class="filter-section">
            <a href="?range=today<?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?><?= $statusFilter !== 'all' ? '&status='.$statusFilter : '' ?>" 
               class="btn <?= $dateRange === 'today' ? 'btn-primary' : 'btn-outline' ?>">Bugun</a>
            <a href="?range=week<?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?><?= $statusFilter !== 'all' ? '&status='.$statusFilter : '' ?>" 
               class="btn <?= $dateRange === 'week' ? 'btn-primary' : 'btn-outline' ?>">Bu hafta</a>
            <a href="?range=month<?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?><?= $statusFilter !== 'all' ? '&status='.$statusFilter : '' ?>" 
               class="btn <?= $dateRange === 'month' ? 'btn-primary' : 'btn-outline' ?>">Bu oy</a>
            
            <form method="GET" class="date-form">
                <input type="hidden" name="range" value="custom">
                <?php if ($sellerFilter > 0): ?>
                    <input type="hidden" name="seller" value="<?= $sellerFilter ?>">
                <?php endif; ?>
                <input type="date" name="start_date" class="form-control" style="width: auto;" 
                       value="<?= $_GET['start_date'] ?? date('Y-m-d') ?>" required>
                <span>-</span>
                <input type="date" name="end_date" class="form-control" style="width: auto;" 
                       value="<?= $_GET['end_date'] ?? date('Y-m-d') ?>" required>
                <button type="submit" class="btn btn-primary">🔍</button>
            </form>
        </div>
        
        <!-- Seller Filter -->
        <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="hidden" name="range" value="<?= $dateRange ?>">
            <?php if ($dateRange === 'custom'): ?>
                <input type="hidden" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>">
                <input type="hidden" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>">
            <?php endif; ?>
            <?php if ($statusFilter !== 'all'): ?>
                <input type="hidden" name="status" value="<?= $statusFilter ?>">
            <?php endif; ?>
            <select name="seller" class="form-control" onchange="this.form.submit()" style="min-width: 150px;">
                <option value="0">Barcha sotuvchilar</option>
                <?php while ($seller = $sellers->fetch_assoc()): ?>
                    <option value="<?= $seller['id'] ?>" <?= $sellerFilter == $seller['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($seller['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
        
        <!-- Status Filter -->
        <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="hidden" name="range" value="<?= $dateRange ?>">
            <?php if ($dateRange === 'custom'): ?>
                <input type="hidden" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>">
                <input type="hidden" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>">
            <?php endif; ?>
            <?php if ($sellerFilter > 0): ?>
                <input type="hidden" name="seller" value="<?= $sellerFilter ?>">
            <?php endif; ?>
            <select name="status" class="form-control" onchange="this.form.submit()" style="min-width: 180px;">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Barcha statuslar</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>⏳ Kutilmoqda</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>✅ Yakunlangan</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>❌ Bekor qilingan</option>
            </select>
        </form>
    </div>
    
    <!-- Statistics -->
    <h3 style="margin-bottom: 1rem; color: var(--gray-700);"><?= $rangeText ?> uchun statistika</h3>
    
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon blue">
                🛒
            </div>
            <div class="stat-content">
                <h3><?= $stats['total_orders'] ?></h3>
                <p>Jami buyurtmalar</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                💰
            </div>
            <div class="stat-content">
                <h3><?= formatCurrency($stats['total_revenue']) ?></h3>
                <p>Yakunlangan savdo</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                📊
            </div>
            <div class="stat-content">
                <h3><?= formatCurrency($stats['total_sum']) ?></h3>
                <p>Jami summa</p>
            </div>
        </div>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sotuvchi</th>
                            <th>Summa</th>
                            <th>Turi</th>
                            <th>Status</th>
                            <th>Sana</th>
                            <th>Harakatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders->num_rows > 0): ?>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <?php
                                $statusColor = $order['status'] === 'completed' ? 'var(--success)' : 
                                             ($order['status'] === 'cancelled' ? 'var(--danger)' : 'var(--warning)');
                                $statusText = $order['status'] === 'completed' ? 'Yakunlangan' : 
                                            ($order['status'] === 'cancelled' ? 'Bekor qilingan' : 'Kutilmoqda');
                                ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['seller_name']) ?></td>
                                    <td style="font-weight: 600; color: var(--success);">
                                        <?= formatCurrency($order['total_amount']) ?>
                                    </td>
                                    <td><?= $order['order_type'] === 'delivery' ? '🚚 Yetkazish' : '🍽️ Oddiy' ?></td>
                                    <td>
                                        <span style="color: <?= $statusColor ?>; font-weight: 600;">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($order['created_at']) ?></td>
                                    <td>
                                        <button onclick="viewOrder(<?= $order['id'] ?>)" class="btn btn-primary btn-sm">
                                            👁️ Ko'rish
                                        </button>
                                        
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <a href="../seller/complete_order.php?id=<?= $order['id'] ?>" class="btn btn-success btn-sm">
                                                💰 To'lov
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] === 'completed'): ?>
                                            <a href="../seller/receipt.php?id=<?= $order['id'] ?>" class="btn btn-primary btn-sm" target="_blank">
                                                🖨️ Chek
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--gray-500);">
                                    Buyurtmalar topilmadi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid var(--gray-200);">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <a href="?range=<?= $dateRange ?><?= $dateRange === 'custom' ? '&start_date='.$_GET['start_date'].'&end_date='.$_GET['end_date'] : '' ?><?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?><?= $statusFilter !== 'all' ? '&status='.$statusFilter : '' ?>&page=<?= $page - 1 ?>" 
                               class="btn btn-outline" style="min-width: 100px;">
                                ← Oldingi
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline" disabled style="min-width: 100px; opacity: 0.5; cursor: not-allowed;">
                                ← Oldingi
                            </button>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?range=<?= $dateRange ?><?= $dateRange === 'custom' ? '&start_date='.$_GET['start_date'].'&end_date='.$_GET['end_date'] : '' ?><?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?><?= $statusFilter !== 'all' ? '&status='.$statusFilter : '' ?>&page=<?= $i ?>" 
                               class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" 
                               style="min-width: 50px;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?range=<?= $dateRange ?><?= $dateRange === 'custom' ? '&start_date='.$_GET['start_date'].'&end_date='.$_GET['end_date'] : '' ?><?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?><?= $statusFilter !== 'all' ? '&status='.$statusFilter : '' ?>&page=<?= $page + 1 ?>" 
                               class="btn btn-outline" style="min-width: 100px;">
                                Keyingi →
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline" disabled style="min-width: 100px; opacity: 0.5; cursor: not-allowed;">
                                Keyingi →
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Page Info -->
                    <div style="text-align: center; color: var(--gray-600); font-size: 0.9rem;">
                        Sahifa <?= $page ?> / <?= $totalPages ?> (Jami: <?= $stats['total_orders'] ?>)
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Buyurtma tafsilotlari</h2>
            <button class="close-modal" onclick="closeModal('orderDetailsModal')">&times;</button>
        </div>
        <div id="orderDetailsContent" style="padding: 1rem 0;">
            <p style="text-align: center; color: var(--gray-500);">Yuklanmoqda...</p>
        </div>
    </div>
</div>

<script>
async function viewOrder(orderId) {
    openModal('orderDetailsModal');
    
    try {
        const response = await fetch(`/xpos/api/orders.php?action=get_details&id=${orderId}`);
        const data = await response.json();
        
        if (data.success) {
            let html = '<div class="table-responsive"><table>';
            html += '<thead><tr><th>Mahsulot</th><th>Narx</th><th>Miqdor</th><th>Jami</th></tr></thead>';
            html += '<tbody>';
            
            data.items.forEach(item => {
                html += `<tr>
                    <td>${item.product_name}</td>
                    <td>${formatCurrency(item.price)}</td>
                    <td>${item.quantity}</td>
                    <td style="font-weight: 600;">${formatCurrency(item.subtotal)}</td>
                </tr>`;
            });
            
            html += `<tr style="background: var(--primary-50); font-weight: 700;">
                <td colspan="3" style="text-align: right;">JAMI:</td>
                <td style="color: var(--success); font-size: 1.25rem;">${formatCurrency(data.order.total_amount)}</td>
            </tr>`;
            html += '</tbody></table></div>';
            
            document.getElementById('orderDetailsContent').innerHTML = html;
        } else {
            document.getElementById('orderDetailsContent').innerHTML = `<p style="text-align: center; color: var(--danger);">${data.message}</p>`;
        }
    } catch (error) {
        document.getElementById('orderDetailsContent').innerHTML = '<p style="text-align: center; color: var(--danger);">Xatolik yuz berdi</p>';
    }
}
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>
