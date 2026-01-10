<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole(['manager', 'super_admin']);

$pageTitle = 'Hisobotlar - Menejer';

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

// Build WHERE clause for statistics
$whereClause = "created_at BETWEEN ? AND ?";
$paramTypes = "ss";
$paramValues = [$range['start'], $range['end']];

if ($sellerFilter > 0) {
    $whereClause .= " AND seller_id = ?";
    $paramTypes .= "i";
    $paramValues[] = $sellerFilter;
}

// 1. Overall statistics (8 metrics)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(total_amount), 0) as total_sum,
        COALESCE(AVG(CASE WHEN status = 'completed' THEN total_amount ELSE NULL END), 0) as avg_order,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
    FROM orders 
    WHERE $whereClause
");
$stmt->bind_param($paramTypes, ...$paramValues);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Active sellers count
$sellerCountWhere = "o.created_at BETWEEN ? AND ?";
$sellerParamTypes = "ss";
$sellerParamValues = [$range['start'], $range['end']];

if ($sellerFilter > 0) {
    $sellerCountWhere .= " AND o.seller_id = ?";
    $sellerParamTypes .= "i";
    $sellerParamValues[] = $sellerFilter;
}

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.seller_id) as active_sellers
    FROM orders o
    WHERE $sellerCountWhere
");
$stmt->bind_param($sellerParamTypes, ...$sellerParamValues);
$stmt->execute();
$activeSellers = $stmt->get_result()->fetch_assoc();
$stats['active_sellers'] = $activeSellers['active_sellers'];
$stmt->close();

// 3. Top products with pagination
$productsPage = isset($_GET['products_page']) ? max(1, intval($_GET['products_page'])) : 1;
$productsPerPage = 10;
$productsOffset = ($productsPage - 1) * $productsPerPage;

$productWhere = "o.created_at BETWEEN ? AND ? AND o.status = 'completed'";
$productParamTypes = "ss";
$productParamValues = [$range['start'], $range['end']];

if ($sellerFilter > 0) {
    $productWhere .= " AND o.seller_id = ?";
    $productParamTypes .= "i";
    $productParamValues[] = $sellerFilter;
}

// Get total count for pagination
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT p.id) as total
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE $productWhere
");
$stmt->bind_param($productParamTypes, ...$productParamValues);
$stmt->execute();
$productsCount = $stmt->get_result()->fetch_assoc()['total'];
$productsTotalPages = ceil($productsCount / $productsPerPage);
$stmt->close();

// Get products
$productParamTypes .= "ii";
$productParamValues[] = $productsPerPage;
$productParamValues[] = $productsOffset;

$stmt = $conn->prepare("
    SELECT 
        p.name as product_name,
        c.name as category_name,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE $productWhere
    GROUP BY p.id
    ORDER BY total_quantity DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param($productParamTypes, ...$productParamValues);
$stmt->execute();
$topProducts = $stmt->get_result();
$stmt->close();

// 4. Best employees/sellers performance
$employeesPage = isset($_GET['employees_page']) ? max(1, intval($_GET['employees_page'])) : 1;
$employeesPerPage = 10;
$employeesOffset = ($employeesPage - 1) * $employeesPerPage;

$employeeWhere = "o.created_at BETWEEN ? AND ?";
$employeeCountTypes = "ss";
$employeeCountValues = [$range['start'], $range['end']];

if ($sellerFilter > 0) {
    $employeeWhere .= " AND o.seller_id = ?";
    $employeeCountTypes .= "i";
    $employeeCountValues[] = $sellerFilter;
}

// Get total sellers count
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.seller_id) as total
    FROM orders o
    WHERE $employeeWhere
");
$stmt->bind_param($employeeCountTypes, ...$employeeCountValues);
$stmt->execute();
$employeesCount = $stmt->get_result()->fetch_assoc()['total'];
$employeesTotalPages = ceil($employeesCount / $employeesPerPage);
$stmt->close();

// Get employees data
$employeeParamTypes = $employeeCountTypes . "ii";
$employeeParamValues = array_merge($employeeCountValues, [$employeesPerPage, $employeesOffset]);

$stmt = $conn->prepare("
    SELECT 
        u.name as seller_name,
        COUNT(o.id) as total_orders,
        COUNT(CASE WHEN o.status = 'completed' THEN 1 END) as completed_orders,
        COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(AVG(CASE WHEN o.status = 'completed' THEN o.total_amount END), 0) as avg_order
    FROM users u
    JOIN orders o ON u.id = o.seller_id
    WHERE u.role = 'seller' AND $employeeWhere
    GROUP BY u.id
    ORDER BY total_revenue DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param($employeeParamTypes, ...$employeeParamValues);
$stmt->execute();
$bestEmployees = $stmt->get_result();
$stmt->close();

// 5. Sales by category
$categoryWhere = "o.created_at BETWEEN ? AND ? AND o.status = 'completed'";
$categoryParamTypes = "ss";
$categoryParamValues = [$range['start'], $range['end']];

if ($sellerFilter > 0) {
    $categoryWhere .= " AND o.seller_id = ?";
    $categoryParamTypes .= "i";
    $categoryParamValues[] = $sellerFilter;
}

$stmt = $conn->prepare("
    SELECT 
        c.name as category_name,
        COUNT(DISTINCT o.id) as order_count,
        SUM(oi.subtotal) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE $categoryWhere
    GROUP BY c.id
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt->bind_param($categoryParamTypes, ...$categoryParamValues);
$stmt->execute();
$categoryStats = $stmt->get_result();
$stmt->close();

// 6. Hourly/Daily trends
$trendsWhere = "created_at BETWEEN ? AND ? AND status = 'completed'";
$trendsParamTypes = "ss";
$trendsParamValues = [$range['start'], $range['end']];

if ($sellerFilter > 0) {
    $trendsWhere .= " AND seller_id = ?";
    $trendsParamTypes .= "i";
    $trendsParamValues[] = $sellerFilter;
}

// For today: hourly breakdown, otherwise daily
if ($dateRange === 'today') {
    $stmt = $conn->prepare("
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue
        FROM orders
        WHERE $trendsWhere
        GROUP BY HOUR(created_at)
        ORDER BY hour
    ");
} else {
    $stmt = $conn->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue
        FROM orders
        WHERE $trendsWhere
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
}
$stmt->bind_param($trendsParamTypes, ...$trendsParamValues);
$stmt->execute();
$trends = $stmt->get_result();
$stmt->close();

// 7. Get all sellers for filter dropdown
$sellers = $conn->query("
    SELECT id, name 
    FROM users 
    WHERE role = 'seller' 
    ORDER BY name ASC
");

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

/* Responsive filters */
.filter-section {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
    max-width: 100%;
}

.date-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-left: 1rem;
    max-width: 100%;
}

/* Progress bar for products */
.progress-bar {
    height: 6px;
    background: var(--gray-200);
    border-radius: 3px;
    overflow: hidden;
    margin-top: 0.25rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
    transition: width 0.3s ease;
}

/* Two column grid */
.two-column-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin-bottom: 2rem;
}

/* Tablet Responsive (768px - 1024px) */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        max-width: 100%;
    }
    
    .two-column-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        max-width: 100%;
    }
    
    .card {
        max-width: 100%;
    }
}

/* Mobile Responsive (< 768px) */
@media (max-width: 768px) {
    /* Global container */
    .container {
        max-width: 100vw;
        overflow-x: hidden;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    /* Filters */
    .filter-section {
        width: 100%;
        max-width: 100%;
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-section .btn {
        width: 100%;
        max-width: 100%;
        justify-content: center;
    }
    
    .date-form {
        margin-left: 0;
        width: 100%;
        max-width: 100%;
        margin-top: 0;
    }
    
    .date-form input,
    .date-form select,
    .date-form .btn {
        max-width: 100%;
    }
    
    /* Stats Grid - 1 column */
    .stats-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
        max-width: 100%;
        width: 100%;
    }
    
    .stat-card {
        padding: 1rem;
        max-width: 100%;
        width: 100%;
    }
    
    .stat-content h3 {
        font-size: 1.5rem;
    }
    
    /* Two column becomes one */
    .two-column-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        max-width: 100%;
        width: 100%;
    }
    
    /* Tables */
    .card {
        border-radius: var(--radius-md);
        overflow: hidden; /* Prevent overflow */
    }
    
    .card-body {
        padding: 1rem;
        overflow: hidden; /* Prevent overflow */
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: var(--radius-md);
        box-shadow: inset -5px 0 10px -5px rgba(0,0,0,0.15);
        max-width: 100%; /* Ensure it doesn't overflow parent */
        width: 100%;
    }
    
    table {
        font-size: 0.85rem;
        min-width: 400px; /* Reduced from 600px for better mobile fit */
        width: 100%;
        margin: 0;
    }
    
    table th, table td {
        padding: 0.5rem 0.75rem;
        white-space: nowrap;
    }
    
    table th:first-child,
    table td:first-child {
        position: sticky;
        left: 0;
        background: white;
        z-index: 1;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1); /* Better visual separation */
    }
    
    /* Pagination */
    .pagination-wrapper {
        margin-top: 1rem;
        display: flex;
        justify-content: center;
        gap: 0.25rem;
        flex-wrap: wrap;
    }
    
    .pagination-wrapper .btn {
        min-width: 44px;
        min-height: 44px;
        padding: 0.5rem;
        font-size: 0.9rem;
    }
}

/* Small Mobile (< 480px) */
@media (max-width: 480px) {
    .container {
        padding: 1rem 0.5rem !important;
        max-width: 100vw; /* Prevent overflow */
        overflow-x: hidden;
    }
    
    .two-column-grid {
        overflow: hidden; /* Prevent grid overflow */
    }
    
    /* Filters */
    .date-form {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    
    .date-form input,
    .date-form select {
        width: 100%;
    }
    
    .date-form span {
        display: none;
    }
    
    .date-form .btn {
        width: 100%;
    }
    
    /* Card */
    .card {
        margin-bottom: 1rem;
    }
    
    .card-header {
        padding: 1rem;
    }
    
    .card-title {
        font-size: 1.125rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    /* Stats */
    .stat-card {
        padding: 0.75rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
    
    .stat-content h3 {
        font-size: 1.25rem;
    }
    
    .stat-content p {
        font-size: 0.875rem;
    }
    
    /* Table */
    table {
        font-size: 0.8rem;
        min-width: 350px; /* Reduced from 500px for better mobile fit */
    }
    
    table th,
    table td {
        padding: 0.375rem 0.5rem;
    }
    
    /* Hide ranks on very small screens for employees table */
    table tbody tr td:first-child {
        display: table-cell;
    }
    
    /* Compact pagination */
    .pagination-wrapper {
        gap: 0.25rem;
    }
    
    .pagination-wrapper .btn {
        min-width: 40px;
        min-height: 40px;
        padding: 0.375rem;
        font-size: 0.85rem;
    }
    
    /* Limit visible pagination buttons */
    .pagination-wrapper .btn:nth-child(n+6):nth-last-child(n+6) {
        display: none;
    }
}

/* Very Small Mobile (< 360px) */
@media (max-width: 360px) {
    h1 {
        font-size: 1.5rem;
    }
    
    h2, h3 {
        font-size: 1.125rem;
    }
    
    .stat-content h3 {
        font-size: 1.125rem;
    }
    
    table {
        font-size: 0.75rem;
        min-width: 300px; /* Reduced from 450px for ultra-compact mobile */
    }
}
</style>

<div class="container" style="padding: 2rem 0;">
    <!-- Header & Filters -->
    <div style="margin-bottom: 2rem;">
        <h1>Hisobotlar</h1>
        <p style="color: var(--gray-600); margin: 0.5rem 0 1.5rem 0;">Keng qamrovli savdo va statistika tahlili</p>
        
        <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
            <!-- Date filters -->
            <div class="filter-section">
                <a href="?range=today<?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?>" 
                   class="btn <?= $dateRange === 'today' ? 'btn-primary' : 'btn-outline' ?>">Bugun</a>
                <a href="?range=week<?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?>" 
                   class="btn <?= $dateRange === 'week' ? 'btn-primary' : 'btn-outline' ?>">Bu hafta</a>
                <a href="?range=month<?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?>" 
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
            
            <!-- Seller filter -->
            <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="hidden" name="range" value="<?= $dateRange ?>">
                <?php if ($dateRange === 'custom'): ?>
                    <input type="hidden" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>">
                    <input type="hidden" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>">
                <?php endif; ?>
                <select name="seller" class="form-control" onchange="this.form.submit()" style="min-width: 180px;">
                    <option value="0">Barcha sotuvchilar</option>
                    <?php while ($seller = $sellers->fetch_assoc()): ?>
                        <option value="<?= $seller['id'] ?>" <?= $sellerFilter == $seller['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($seller['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>
    </div>
    
    <h3 style="margin-bottom: 1.5rem; color: var(--gray-700);"><?= $rangeText ?> uchun statistika</h3>
    
    <!-- 8 Statistics Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon blue">🛒</div>
            <div class="stat-content">
                <h3><?= $stats['total_orders'] ?></h3>
                <p>Jami buyurtmalar</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-content">
                <h3><?= formatCurrency($stats['total_revenue']) ?></h3>
                <p>Yakunlangan savdo</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">📊</div>
            <div class="stat-content">
                <h3><?= formatCurrency($stats['total_sum']) ?></h3>
                <p>Jami summa</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purple">📈</div>
            <div class="stat-content">
                <h3><?= formatCurrency($stats['avg_order']) ?></h3>
                <p>O'rtacha buyurtma</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">✅</div>
            <div class="stat-content">
                <h3><?= $stats['completed_orders'] ?></h3>
                <p>Yakunlangan</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">⏳</div>
            <div class="stat-content">
                <h3><?= $stats['pending_orders'] ?></h3>
                <p>Kutilmoqda</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon danger">❌</div>
            <div class="stat-content">
                <h3><?= $stats['cancelled_orders'] ?></h3>
                <p>Bekor qilingan</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <div class="stat-content">
                <h3><?= $stats['active_sellers'] ?></h3>
                <p>Faol sotuvchilar</p>
            </div>
        </div>
    </div>
    
    <!-- Best Employees Section -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title">🏆 Eng yaxshi xodimlar</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Sotuvchi</th>
                            <th>Buyurtmalar</th>
                            <th>Yakunlangan</th>
                            <th>Daromad</th>
                            <th>O'rtacha</th>
                            <th>Muvaffaqiyat %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bestEmployees->num_rows > 0): ?>
                            <?php 
                            $rank = ($employeesPage - 1) * $employeesPerPage + 1;
                            while ($employee = $bestEmployees->fetch_assoc()): 
                                $successRate = $employee['total_orders'] > 0 ? 
                                    ($employee['completed_orders'] / $employee['total_orders']) * 100 : 0;
                            ?>
                                <tr>
                                    <td><strong><?= $rank++ ?></strong></td>
                                    <td><?= htmlspecialchars($employee['seller_name']) ?></td>
                                    <td><?= $employee['total_orders'] ?></td>
                                    <td><?= $employee['completed_orders'] ?></td>
                                    <td style="font-weight: 600; color: var(--success);">
                                        <?= formatCurrency($employee['total_revenue']) ?>
                                    </td>
                                    <td><?= formatCurrency($employee['avg_order']) ?></td>
                                    <td>
                                        <span style="color: <?= $successRate >= 80 ? 'var(--success)' : ($successRate >= 50 ? 'var(--warning)' : 'var(--danger)') ?>; font-weight: 600;">
                                            <?= number_format($successRate, 1) ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--gray-500);">
                                    Ma'lumot topilmadi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($employeesTotalPages > 1): ?>
                <div class="pagination-wrapper">
                    <?php for ($i = 1; $i <= $employeesTotalPages; $i++): ?>
                        <a href="?range=<?= $dateRange ?><?= $dateRange === 'custom' ? '&start_date='.$_GET['start_date'].'&end_date='.$_GET['end_date'] : '' ?><?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?>&employees_page=<?= $i ?>" 
                           class="btn <?= $i === $employeesPage ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Two Column Layout for Products & Category -->
    <div class="two-column-grid">
        <!-- Top Products -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📦 Eng ko'p sotiladigan mahsulotlar</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Mahsulot</th>
                                <th>Miqdor</th>
                                <th>Summa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($topProducts->num_rows > 0): ?>
                                <?php 
                                $rank = ($productsPage - 1) * $productsPerPage + 1;
                                while ($product = $topProducts->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><strong><?= $rank++ ?></strong></td>
                                        <td>
                                            <div><?= htmlspecialchars($product['product_name']) ?></div>
                                            <?php if ($product['category_name']): ?>
                                                <small style="color: var(--gray-500);"><?= htmlspecialchars($product['category_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $product['total_quantity'] ?> dona</td>
                                        <td style="font-weight: 600; color: var(--success);">
                                            <?= formatCurrency($product['total_revenue']) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--gray-500);">
                                        Ma'lumot topilmadi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($productsTotalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <?php for ($i = 1; $i <= min(10, $productsTotalPages); $i++): ?>
                            <a href="?range=<?= $dateRange ?><?= $dateRange === 'custom' ? '&start_date='.$_GET['start_date'].'&end_date='.$_GET['end_date'] : '' ?><?= $sellerFilter > 0 ? '&seller='.$sellerFilter : '' ?>&products_page=<?= $i ?>" 
                               class="btn <?= $i === $productsPage ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🗂️ Kategoriya bo'yicha savdo</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kategoriya</th>
                                <th>Buyurtmalar</th>
                                <th>Daromad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categoryStats->num_rows > 0): ?>
                                <?php while ($category = $categoryStats->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($category['category_name']) ?></td>
                                        <td><?= $category['order_count'] ?> ta</td>
                                        <td style="font-weight: 600; color: var(--success);">
                                            <?= formatCurrency($category['revenue']) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--gray-500);">
                                        Ma'lumot topilmadi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Hourly/Daily Trends -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">⏰ <?= $dateRange === 'today' ? 'Soatlik' : 'Kunlik' ?> tahlil</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= $dateRange === 'today' ? 'Soat' : 'Sana' ?></th>
                            <th>Buyurtmalar</th>
                            <th>Daromad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($trends->num_rows > 0): ?>
                            <?php while ($trend = $trends->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if ($dateRange === 'today'): ?>
                                            <?= sprintf('%02d:00 - %02d:59', $trend['hour'], $trend['hour']) ?>
                                        <?php else: ?>
                                            <?= date('d.m.Y', strtotime($trend['date'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $trend['order_count'] ?> ta</td>
                                    <td style="font-weight: 600; color: var(--success);">
                                        <?= formatCurrency($trend['revenue']) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--gray-500);">
                                    Ma'lumot topilmadi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>
