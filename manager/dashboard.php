<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole(['manager', 'super_admin']);

$pageTitle = 'Dashboard - Menejer';

// Get statistics
$today = getTodayRange();

// Total categories and products
$categoriesCount = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$productsCount = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

// Today's orders
$stmt = $conn->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE created_at BETWEEN ? AND ? AND status != 'cancelled'");
$stmt->bind_param("ss", $today['start'], $today['end']);
$stmt->execute();
$todayStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

include '../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <h1>Dashboard</h1>
    <p style="color: var(--gray-600); margin-bottom: 2rem;">Menejer paneli</p>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                📁
            </div>
            <div class="stat-content">
                <h3><?= $categoriesCount ?></h3>
                <p>Kategoriyalar</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                📦
            </div>
            <div class="stat-content">
                <h3><?= $productsCount ?></h3>
                <p>Mahsulotlar</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                🛒
            </div>
            <div class="stat-content">
                <h3><?= $todayStats['count'] ?></h3>
                <p>Bugungi buyurtmalar</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon blue">
                💰
            </div>
            <div class="stat-content">
                <h3><?= formatCurrency($todayStats['total']) ?></h3>
                <p>Bugungi savdo</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Tezkor harakatlar</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="categories.php" class="btn btn-primary btn-lg">
                    📁 Kategoriyalar
                </a>
                <a href="products.php" class="btn btn-primary btn-lg">
                    📦 Mahsulotlar
                </a>
                <a href="orders.php" class="btn btn-primary btn-lg">
                    🛒 Buyurtmalar
                </a>
                <a href="reports.php" class="btn btn-primary btn-lg">
                    📊 Hisobotlar
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>
