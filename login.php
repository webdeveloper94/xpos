<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fast Food Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">🍔</div>
                <h1 class="login-title">Fast Food</h1>
                <p class="login-subtitle">Tizimga kirish uchun login va parolni kiriting</p>
            </div>

            <?php
            session_start();
            
            // Check if already logged in
            if (isset($_SESSION['user_id'])) {
                $dashboardUrl = '';
                switch ($_SESSION['user_role']) {
                    case 'super_admin':
                        $dashboardUrl = 'super_admin/dashboard.php';
                        break;
                    case 'manager':
                        $dashboardUrl = 'manager/dashboard.php';
                        break;
                    case 'seller':
                        $dashboardUrl = 'seller/dashboard.php';
                        break;
                }
                if ($dashboardUrl) {
                    header("Location: $dashboardUrl");
                    exit();
                }
            }

            // Display error message if exists
            if (isset($_SESSION['error'])) {
                echo '<div class="error-message">';
                echo '<span>⚠️</span>';
                echo '<span>' . htmlspecialchars($_SESSION['error']) . '</span>';
                echo '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <form action="auth/authenticate.php" method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <input 
                        type="text" 
                        name="login" 
                        id="login" 
                        class="form-control" 
                        placeholder="Login"
                        required
                        autocomplete="username"
                    >
                    <span class="input-icon">👤</span>
                </div>

                <div class="form-group">
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-control" 
                        placeholder="Parol"
                        required
                        autocomplete="current-password"
                    >
                    <span class="input-icon">🔒</span>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    Kirish
                </button>
            </form>

            <div class="login-footer">
                © 2026 Fast Food Management System
            </div>
        </div>
    </div>

    <script>
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.textContent = 'Tekshirilmoqda...';
        });

        // Auto-focus on login field
        document.getElementById('login').focus();
    </script>
</body>
</html>
