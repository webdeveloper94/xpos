<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$userRole = $_SESSION['user_role'] ?? '';
$userName = $_SESSION['user_name'] ?? '';
$userInitial = substr($userName, 0, 1);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Fast Food Management System' ?></title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>?v=<?= time() ?>">
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <a href="<?= baseUrl() ?>" class="navbar-brand">
                <span>🍔</span>
                <span>Fast Food</span>
            </a>
            
            <!-- Sidebar Overlay -->
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileMenu()"></div>
            
            <ul class="navbar-menu" id="navbarMenu">
                <?php if ($userRole === 'super_admin'): ?>
                    <li>
                        <a href="<?= baseUrl('super_admin/dashboard.php') ?>" class="navbar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('super_admin/users.php') ?>" class="navbar-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
                            Foydalanuvchilar
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('manager/orders.php') ?>" class="navbar-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>">
                            Buyurtmalar
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('super_admin/reports.php') ?>" class="navbar-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                            Hisobotlar
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('super_admin/settings.php') ?>" class="navbar-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                            Sozlamalar
                        </a>
                    </li>
                <?php elseif ($userRole === 'manager'): ?>
                    <li>
                        <a href="<?= baseUrl('manager/dashboard.php') ?>" class="navbar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('manager/categories.php') ?>" class="navbar-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                            Kategoriyalar
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('manager/products.php') ?>" class="navbar-link <?= $currentPage === 'products.php' ? 'active' : '' ?>">
                            Mahsulotlar
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('manager/orders.php') ?>" class="navbar-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>">
                            Buyurtmalar
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('manager/reports.php') ?>" class="navbar-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                            Hisobotlar
                        </a>
                    </li>
                <?php elseif ($userRole === 'seller'): ?>
                    <li>
                        <a href="<?= baseUrl('seller/dashboard.php') ?>" class="navbar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('seller/orders.php') ?>" class="navbar-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>">
                            Buyurtmalar
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('seller/pending_orders.php') ?>" class="navbar-link <?= $currentPage === 'pending_orders.php' ? 'active' : '' ?>">
                            Ochiq buyurtmalar
                        </a>
                    </li>
                    <li>
                        <a href="<?= baseUrl('seller/reports.php') ?>" class="navbar-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                            Hisobotlar
                        </a>
                    </li>
                <?php endif; ?>
                
                <li class="user-dropdown">
                    <button class="user-button" onclick="toggleDropdown()">
                        <div class="user-avatar"><?= $userInitial ?></div>
                        <span><?= htmlspecialchars($userName) ?></span>
                        <span>▼</span>
                    </button>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="<?= baseUrl('profile.php') ?>" class="dropdown-item">👤 Profil</a>
                        <a href="<?= baseUrl('auth/logout.php') ?>" class="dropdown-item">🚪 Chiqish</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    
    <script>
    // Mobile Menu Functions (Inline to ensure availability)
    function toggleMobileMenu() {
        const navbarMenu = document.getElementById('navbarMenu');
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('mobileMenuToggle');
        
        if (!navbarMenu || !overlay || !menuToggle) return;
        
        navbarMenu.classList.toggle('show');
        overlay.classList.toggle('show');
        menuToggle.classList.toggle('active');
        
        // Prevent body scroll when menu is open
        if (navbarMenu.classList.contains('show')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    function closeMobileMenu() {
        const navbarMenu = document.getElementById('navbarMenu');
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('mobileMenuToggle');
        
        if (!navbarMenu || !overlay || !menuToggle) return;
        
        navbarMenu.classList.remove('show');
        overlay.classList.remove('show');
        menuToggle.classList.remove('active');
        document.body.style.overflow = '';
    }
    </script>
    
    <main class="main-content">
