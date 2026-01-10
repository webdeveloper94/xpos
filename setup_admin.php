<?php
/**
 * Create Super Admin Profile
 * One-time setup page
 */

require_once 'config/database.php';
require_once 'helpers/functions.php';

$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $login = sanitize($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($name) || empty($phone) || empty($login) || empty($password)) {
        $error = 'Barcha maydonlarni to\'ldiring!';
    } else {
        // Check if super admin already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
        $checkStmt->execute();
        $existingAdmin = $checkStmt->get_result();
        $checkStmt->close();
        
        if ($existingAdmin->num_rows > 0) {
            $error = 'Super admin allaqachon mavjud!';
        } else {
            // Hash password
            $hashedPassword = hashPassword($password);
            
            // Insert super admin
            $stmt = $conn->prepare("INSERT INTO users (name, phone, login, password, role) VALUES (?, ?, ?, ?, 'super_admin')");
            $stmt->bind_param("ssss", $name, $phone, $login, $hashedPassword);
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = 'Xatolik yuz berdi: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Yaratish - Fast Food</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card" style="max-width: 500px;">
            <div class="login-header">
                <div class="login-logo">👑</div>
                <h1 class="login-title">Super Admin Yaratish</h1>
                <p class="login-subtitle">Tizim administratori profilini yaratish</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                    ✅ Super admin muvaffaqiyatli yaratildi!
                </div>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="login.php" class="btn btn-primary btn-lg">
                        🔑 Login sahifasiga o'tish
                    </a>
                </div>
            <?php else: ?>
                <?php if (!empty($error)): ?>
                    <div class="error-message" style="margin-bottom: 1.5rem;">
                        <span>⚠️</span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label class="form-label" style="color: var(--gray-700); text-align: left; display: block; margin-bottom: 0.5rem;">
                            Ism *
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            class="form-control" 
                            placeholder="Super Admin"
                            required
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="color: var(--gray-700); text-align: left; display: block; margin-bottom: 0.5rem;">
                            Telefon raqami *
                        </label>
                        <input 
                            type="text" 
                            name="phone" 
                            class="form-control" 
                            placeholder="+998901234567"
                            required
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="color: var(--gray-700); text-align: left; display: block; margin-bottom: 0.5rem;">
                            Login *
                        </label>
                        <input 
                            type="text" 
                            name="login" 
                            class="form-control" 
                            placeholder="admin"
                            required
                            value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="color: var(--gray-700); text-align: left; display: block; margin-bottom: 0.5rem;">
                            Parol *
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Parol kiriting"
                            required
                            minlength="4"
                        >
                    </div>

                    <button type="submit" class="login-btn" style="margin-top: 1.5rem;">
                        👑 Super Admin Yaratish
                    </button>
                </form>

                <div class="login-footer">
                    <a href="login.php" style="color: var(--primary-600); text-decoration: none;">
                        ← Login sahifasiga qaytish
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
