<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole(['manager', 'super_admin']);

$pageTitle = 'Kategoriyalar - Menejer';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description'] ?? '');
        $createdBy = $_SESSION['user_id'];
        
        if (empty($name)) {
            $error = 'Kategoriya nomini kiriting';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, description, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $description, $createdBy);
            
            if ($stmt->execute()) {
                $success = 'Kategoriya muvaffaqiyatli qo\'shildi';
            } else {
                $error = 'Xatolik: ' . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $categoryId = intval($_POST['category_id']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Kategoriya nomini kiriting';
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $categoryId);
            
            if ($stmt->execute()) {
                $success = 'Kategoriya muvaffaqiyatli yangilandi';
            } else {
                $error = 'Xatolik: ' . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $categoryId = intval($_POST['category_id']);
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $categoryId);
        
        if ($stmt->execute()) {
            $success = 'Kategoriya o\'chirildi';
        } else {
            $error = 'Xatolik: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all categories
$categories = $conn->query("
    SELECT c.*, u.name as creator_name,
    (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
    FROM categories c
    LEFT JOIN users u ON c.created_by = u.id
    ORDER BY c.created_at DESC
");

include '../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>Kategoriyalar</h1>
            <p style="color: var(--gray-600); margin: 0;">Mahsulot kategoriyalarini boshqarish</p>
        </div>
        <button onclick="openModal('addCategoryModal')" class="btn btn-primary">
            ➕ Yangi kategoriya
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
                            <th>Nom</th>
                            <th>Tavsif</th>
                            <th>Mahsulotlar soni</th>
                            <th>Yaratdi</th>
                            <th>Sana</th>
                            <th>Harakatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories->num_rows > 0): ?>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $category['id'] ?></td>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($category['name']) ?></td>
                                    <td><?= htmlspecialchars($category['description'] ?? '-') ?></td>
                                    <td><?= $category['product_count'] ?> ta</td>
                                    <td><?= htmlspecialchars($category['creator_name']) ?></td>
                                    <td><?= formatDate($category['created_at']) ?></td>
                                    <td>
                                        <button onclick="openEditCategoryModal(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($category['description'] ?? '', ENT_QUOTES) ?>')" class="btn btn-primary btn-sm">✏️</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('Bu kategoriyani o\'chirmoqchimisiz?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--gray-500);">
                                    Hozircha kategoriyalar yo'q
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Yangi kategoriya qo'shish</h2>
            <button class="close-modal" onclick="closeModal('addCategoryModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Kategoriya nomi *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tavsif</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryModal')">Bekor qilish</button>
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Kategoriyani tahrirlash</h2>
            <button class="close-modal" onclick="closeModal('editCategoryModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="category_id" id="edit_category_id">
            
            <div class="form-group">
                <label class="form-label">Kategoriya nomi *</label>
                <input type="text" name="name" id="edit_category_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tavsif</label>
                <textarea name="description" id="edit_category_description" class="form-control" rows="3"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editCategoryModal')">Bekor qilish</button>
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditCategoryModal(id, name, description) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    document.getElementById('edit_category_description').value = description;
    openModal('editCategoryModal');
}
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>
