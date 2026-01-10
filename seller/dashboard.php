<?php
require_once '../auth/session.php';
requireRole('seller');

// Redirect to orders page (main seller interface)
header("Location: orders.php");
exit();
?>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Tezkor harakatlar</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="orders.php" class="btn btn-primary btn-lg">
                    ➕ Yangi buyurtma
                </a>
                <a href="reports.php" class="btn btn-primary btn-lg">
                    📊 Hisobotlar
                </a>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
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
                            <th>Summa</th>
                            <th>Status</th>
                            <th>Sana</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recentOrders = $conn->query("
                            SELECT * FROM orders 
                            WHERE seller_id = $sellerId 
                            ORDER BY created_at DESC 
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
                                <td colspan="4" style="text-align: center; color: var(--gray-500);">
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
