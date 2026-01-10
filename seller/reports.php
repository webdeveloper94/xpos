<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole('seller');

$pageTitle = 'Buyurtmalar Tarixi - Sotuvchi';
$sellerId = $_SESSION['user_id'];

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

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Personal statistics - all data
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(total_amount), 0) as total_sum
    FROM orders 
    WHERE seller_id = ? AND created_at BETWEEN ? AND ?
");
$stmt->bind_param("iss", $sellerId, $range['start'], $range['end']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate total pages
$totalPages = ceil($stats['total_orders'] / $perPage);

// Orders list with pagination
$stmt = $conn->prepare("
    SELECT o.*, u.name as seller_name
    FROM orders o
    LEFT JOIN users u ON o.seller_id = u.id
    WHERE o.seller_id = ? AND o.created_at BETWEEN ? AND ?
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("issii", $sellerId, $range['start'], $range['end'], $perPage, $offset);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

include '../includes/header.php';
?>

<style>
/* Date Range Filter */
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
@media (max-width:768px) {
    .filter-section {
        width: 100%;
        justify-content: flex-start;
    }
    
    .date-form {
        margin-left: 0;
        width: 100%;
        margin-top: 0.5rem;
    }
    
    .date-form input[type="date"] {
        flex: 1;
        min-width: 120px;
    }
    
    .date-form button {
        flex-shrink: 0;
    }
    
    /* Stats Grid - 1 column on mobile, 3 columns become 1 */
    .stats-grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Table - Better scroll */
    .table-responsive {
        border-radius: var(--radius-md);
        box-shadow: inset -5px 0 10px -5px rgba(0,0,0,0.15);
    }
    
    table {
        font-size: 0.85rem;
    }
    
    table th,
    table td {
        padding: 0.5rem;
        white-space: nowrap;
    }
    
    /* Hide less important columns on mobile - keep only ID, Summa, Status, Amallar */
    table th:nth-child(3),
    table td:nth-child(3),
    table th:nth-child(4),
    table td:nth-child(4),
    table th:nth-child(6),
    table td:nth-child(6) {
        display: none;
    }
}

@media (max-width: 480px) {
    /* Very compact filters */
    .filter-section .btn {
        padding: 10px 12px;
        font-size: 0.85rem;
        min-height: 44px;
    }
    
    .date-form {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .date-form input[type="date"] {
        width: 100%;
    }
    
    .date-form span {
        display: none;
    }
    
    .date-form button {
        width: 100%;
    }
    
    /* More compact table */
    table {
        font-size: 0.8rem;
    }
    
    table th,
    table td {
        padding: 0.375rem;
    }
    
    /* Pagination - Stack on mobile */
    .btn {
        font-size: 0.85rem;
        min-height: 40px;
    }
}
</style>

<div class="container" style="padding: 2rem 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Buyurtmalar tarixi</h1>
            <p style="color: var(--gray-600); margin: 0;">Shaxsiy statistika</p>
        </div>
        <div class="filter-section">
            <a href="?range=today" class="btn <?= $dateRange === 'today' ? 'btn-primary' : 'btn-outline' ?>">Bugun</a>
            <a href="?range=week" class="btn <?= $dateRange === 'week' ? 'btn-primary' : 'btn-outline' ?>">Bu hafta</a>
            <a href="?range=month" class="btn <?= $dateRange === 'month' ? 'btn-primary' : 'btn-outline' ?>">Bu oy</a>
            
            <form method="GET" class="date-form">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="start_date" class="form-control" style="width: auto; padding: 0.5rem;" value="<?= $_GET['start_date'] ?? date('Y-m-d') ?>" required>
                <span>-</span>
                <input type="date" name="end_date" class="form-control" style="width: auto; padding: 0.5rem;" value="<?= $_GET['end_date'] ?? date('Y-m-d') ?>" required>
                <button type="submit" class="btn btn-primary">🔍</button>
            </form>
        </div>
    </div>
    
    <h3 style="margin-bottom: 1rem; color: var(--gray-700);"><?= $rangeText ?> uchun statistika</h3>
    
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
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
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Buyurtmalar tarixi</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Summa</th>
                            <th>Sotuvchi</th>
                            <th>Turi</th>
                            <th>Status</th>
                            <th>Sana</th>
                            <th>Amallar</th>
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
                                    <td style="font-weight: 600; color: var(--success);">
                                        <?= formatCurrency($order['total_amount']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($order['seller_name'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <?= $order['order_type'] === 'delivery' ? '🚚 Yetkazish' : '🍽️ Oddiy' ?>
                                    </td>
                                    <td>
                                        <span style="color: <?= $statusColor ?>; font-weight: 600;">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($order['created_at']) ?></td>
                                    <td>
                                        <?php if ($order['status'] === 'completed'): ?>
                                            <a href="receipt.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary" target="_blank">
                                                🖨️ Chek
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--gray-500);">
                                    Bu davrda buyurtmalar topilmadi
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
                            <a href="?range=<?= $dateRange ?><?= $dateRange === 'custom' ? '&start_date='.$_GET['start_date'].'&end_date='.$_GET['end_date'] : '' ?>&page=<?= $page - 1 ?>" 
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
                            <a href="?range=<?= $dateRange ?><?= $dateRange === 'custom' ? '&start_date='.$_GET['start_date'].'&end_date='.$_GET['end_date'] : '' ?>&page=<?= $i ?>" 
                               class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" 
                               style="min-width: 50px;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?range=<?= $dateRange ?><?= $dateRange === 'custom' ? '&start_date='.$_GET['start_date'].'&end_date='.$_GET['end_date'] : '' ?>&page=<?= $page + 1 ?>" 
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

<?php
$conn->close();
include '../includes/footer.php';
?>
