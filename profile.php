<?php
require_once 'auth/session.php';
require_once 'config/database.php';
require_once 'helpers/functions.php';

requireLogin();

$pageTitle = 'Profil';
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        
        if (empty($name) || empty($phone)) {
            $error = 'Ism va telefon raqamini kiriting';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $phone, $userId);
            
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_phone'] = $phone;
                $success = 'Profil muvaffaqiyatli yangilandi';
            } else {
                $error = 'Xatolik: ' . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $passwordError = 'Barcha maydonlarni to\'ldiring';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordError = 'Yangi parollar mos kelmadi';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!verifyPassword($currentPassword, $user['password'])) {
                $passwordError = 'Joriy parol noto\'g\'ri';
            } else {
                $hashedPassword = hashPassword($newPassword);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $userId);
                
                if ($stmt->execute()) {
                    $passwordSuccess = 'Parol muvaffaqiyatli o\'zgartirildi';
                } else {
                    $passwordError = 'Xatolik: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Determine role text
$roleText = '';
switch ($user['role']) {
    case 'super_admin':
        $roleText = 'Super Admin';
        break;
    case 'manager':
        $roleText = 'Menejer';
        break;
    case 'seller':
        $roleText = 'Sotuvchi';
        break;
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <h1>Profil</h1>
    <p style="color: var(--gray-600); margin-bottom: 2rem;">Shaxsiy ma'lumotlarni boshqarish</p>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Profile Info -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Shaxsiy ma'lumotlar</h2>
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label class="form-label">Ism *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Telefon *</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Login</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['login']) ?>" disabled>
                        <small style="color: var(--gray-500);">Login o'zgartirilmaydi</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Rol</label>
                        <input type="text" class="form-control" value="<?= $roleText ?>" disabled>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        💾 Saqlash
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Parolni o'zgartirish</h2>
            </div>
            <div class="card-body">
                <?php if (isset($passwordSuccess)): ?>
                    <div class="alert alert-success"><?= $passwordSuccess ?></div>
                <?php endif; ?>
                
                <?php if (isset($passwordError)): ?>
                    <div class="alert alert-danger"><?= $passwordError ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label class="form-label">Joriy parol *</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Yangi parol *</label>
                        <input type="password" name="new_password" class="form-control" minlength="4" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Parolni tasdiqlash *</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="4" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        🔒 Parolni o'zgartirish
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
