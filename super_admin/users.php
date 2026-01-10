<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole('super_admin');

$pageTitle = 'Foydalanuvchilar - Super Admin';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $login = sanitize($_POST['login']);
        $password = $_POST['password'];
        $role = sanitize($_POST['role']);
        
        // Validate
        if (empty($name) || empty($phone) || empty($login) || empty($password) || empty($role)) {
            $error = 'Barcha maydonlarni to\'ldiring';
        } else {
            $hashedPassword = hashPassword($password);
            
            $stmt = $conn->prepare("INSERT INTO users (name, phone, login, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $phone, $login, $hashedPassword, $role);
            
            if ($stmt->execute()) {
                $success = 'Foydalanuvchi muvaffaqiyatli qo\'shildi';
            } else {
                $error = 'Xatolik: ' . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $userId = intval($_POST['user_id']);
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $success = 'Foydalanuvchi o\'chirildi';
        } else {
            $error = 'Xatolik: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all users
$users = $conn->query("SELECT * FROM users WHERE role != 'super_admin' ORDER BY created_at DESC");

include '../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>Foydalanuvchilar</h1>
            <p style="color: var(--gray-600); margin: 0;">Menejerlar va sotuvchilarni boshqarish</p>
        </div>
        <button onclick="openModal('addUserModal')" class="btn btn-primary">
            ➕ Yangi foydalanuvchi
        </button>
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
                            <th>Ism</th>
                            <th>Telefon</th>
                            <th>Login</th>
                            <th>Rol</th>
                            <th>Yaratilgan</th>
                            <th>Harakatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['phone']) ?></td>
                                    <td><?= htmlspecialchars($user['login']) ?></td>
                                    <td>
                                        <span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600; 
                                            background: <?= $user['role'] === 'manager' ? 'var(--primary-100)' : '#d1fae5' ?>; 
                                            color: <?= $user['role'] === 'manager' ? 'var(--primary-700)' : 'var(--success)' ?>;">
                                            <?= $user['role'] === 'manager' ? 'Menejer' : 'Sotuvchi' ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($user['created_at']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️ O'chirish</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--gray-500);">
                                    Hozircha foydalanuvchilar yo'q
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Yangi foydalanuvchi qo'shish</h2>
            <button class="close-modal" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Ism *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Telefon *</label>
                <input type="text" name="phone" class="form-control" placeholder="+998901234567" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Login *</label>
                <input type="text" name="login" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Parol *</label>
                <input type="text" name="password" class="form-control" required>
                <small style="color: var(--gray-500);">Yoki <a href="#" onclick="generateRandomPassword(); return false;" style="color: var(--primary-600);">tasodifiy parol</a> yaratish</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Rol *</label>
                <select name="role" class="form-control" required>
                    <option value="">Tanlang...</option>
                    <option value="manager">Menejer</option>
                    <option value="seller">Sotuvchi</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Bekor qilish</button>
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>
        </form>
    </div>
</div>

<script>
function generateRandomPassword() {
    const length = 8;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let password = '';
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    document.querySelector('input[name="password"]').value = password;
}
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>
