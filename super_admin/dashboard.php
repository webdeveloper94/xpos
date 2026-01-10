<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole('super_admin');

$pageTitle = 'Dashboard - Super Admin';

// Get statistics
$today = getTodayRange();

// Total users count
$managersCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='manager'")->fetch_assoc()['count'];
$sellersCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='seller'")->fetch_assoc()['count'];

// Today's orders
$stmt = $conn->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE created_at BETWEEN ? AND ? AND status != 'cancelled'");
$stmt->bind_param("ss", $today['start'], $today['end']);
$stmt->execute();
$todayStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Total products
$totalProducts = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

include '../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <h1>Dashboard</h1>
    <p style="color: var(--gray-600); margin-bottom: 2rem;">Tizim umumiy ko'rinishi</p>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                👥
            </div>
            <div class="stat-content">
                <h3><?= $managersCount ?></h3>
                <p>Menejerlar</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                👤
            </div>
            <div class="stat-content">
                <h3><?= $sellersCount ?></h3>
                <p>Sotuvchilar</p>
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
                <a href="users.php" class="btn btn-primary btn-lg">
                    👥 Foydalanuvchilar
                </a>
                <a href="reports.php" class="btn btn-primary btn-lg">
                    📊 Hisobotlar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">So'nggi buyurtmalar</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sotuvchi</th>
                            <th>Summa</th>
                            <th>Status</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recentOrders = $conn->query("
                            SELECT o.*, u.name as seller_name 
                            FROM orders o 
                            LEFT JOIN users u ON o.seller_id = u.id 
                            ORDER BY o.created_at DESC 
                            LIMIT 10
                        ");
                        
                        if ($recentOrders->num_rows > 0):
                            while ($order = $recentOrders->fetch_assoc()):
                                $statusColor = $order['status'] === 'completed' ? 'var(--success)' : 
                                             ($order['status'] === 'cancelled' ? 'var(--danger)' : 'var(--warning)');
                                $statusText = $order['status'] === 'completed' ? 'Yakunlangan' : 
                                            ($order['status'] === 'cancelled' ? 'Bekor qilingan' : 'Kutilmoqda');
                        ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['seller_name']) ?></td>
                                <td><?= formatCurrency($order['total_amount']) ?></td>
                                <td>
                                    <span style="color: <?= $statusColor ?>; font-weight: 600;">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td><?= formatDate($order['created_at']) ?></td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--gray-500);">
                                    Hozircha buyurtmalar yo'q
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
