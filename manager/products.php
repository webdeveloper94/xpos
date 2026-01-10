<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../helpers/functions.php';

requireRole(['manager', 'super_admin']);

$pageTitle = 'Mahsulotlar - Menejer';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $categoryId = intval($_POST['category_id']);
        $description = sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price']);
        $createdBy = $_SESSION['user_id'];
        
        $imagePath = null;
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['image']);
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['filename'];
            } else {
                $error = $uploadResult['message'];
            }
        }
        
        if (empty($name) || $categoryId <= 0 || $price <= 0) {
            $error = 'Barcha maydonlarni to\'g\'ri to\'ldiring';
        } elseif (!isset($error)) {
            $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, image, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdsi", $categoryId, $name, $description, $price, $imagePath, $createdBy);
            
            if ($stmt->execute()) {
                $success = 'Mahsulot muvaffaqiyatli qo\'shildi';
            } else {
                $error = 'Xatolik: ' . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $productId = intval($_POST['product_id']);
        
        // Get image to delete
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['image']) {
                deleteImage($row['image']);
            }
        }
        $stmt->close();
        
        // Delete product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        
        if ($stmt->execute()) {
            $success = 'Mahsulot o\'chirildi';
        } else {
            $error = 'Xatolik: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Get search and filter
$search = $_GET['search'] ?? '';
$filterCategory = $_GET['category'] ?? '';

// Get all products with filters
$query = "
    SELECT p.*, c.name as category_name, u.name as creator_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.created_by = u.id
    WHERE 1=1
";

if (!empty($search)) {
    $query .= " AND p.name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

if (!empty($filterCategory)) {
    $query .= " AND p.category_id = " . intval($filterCategory);
}

$query .= " ORDER BY p.created_at DESC";
$products = $conn->query($query);

// Get categories for filter and form
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

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

/* Page header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    gap: 1rem;
}

/* Filter form */
.filter-form {
    display: flex;
    gap: 1rem;
    align-items: end;
}

/* Tablet Responsive (< 1024px) */
@media (max-width: 1024px) {
    .filter-form {
        flex-wrap: wrap;
    }
    
    .filter-form .form-group {
        min-width: 200px;
    }
}

/* Mobile Responsive (< 768px) */
@media (max-width: 768px) {
    /* Container */
    .container {
        max-width: 100vw;
        overflow-x: hidden;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    /* Page header */
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .page-header .btn {
        width: 100%;
        justify-content: center;
    }
    
    /* Filter form */
    .filter-form {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .filter-form .form-group {
        flex: none !important;
        width: 100%;
        margin: 0 !important;
    }
    
    .filter-form .btn {
        width: 100%;
        justify-content: center;
    }
    
    /* Card */
    .card {
        border-radius: var(--radius-md);
        overflow: hidden;
        max-width: 100%;
    }
    
    .card-body {
        padding: 1rem;
        overflow: hidden;
    }
    
    /* Table responsive */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        box-shadow: inset -5px 0 10px -5px rgba(0,0,0,0.15);
        max-width: 100%;
        width: 100%;
    }
    
    table {
        min-width: 600px;
        font-size: 0.85rem;
        width: 100%;
    }
    
    table th,
    table td {
        padding: 0.5rem 0.75rem;
        white-space: nowrap;
    }
    
    /* Sticky first column (image) */
    table th:first-child,
    table td:first-child {
        position: sticky;
        left: 0;
        background: white;
        z-index: 1;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }
    
    /* Modal */
    .modal-content {
        margin: 1rem;
        max-width: calc(100vw - 2rem);
    }
}

/* Small Mobile (< 480px) */
@media (max-width: 480px) {
    .container {
        padding: 1rem 0.5rem !important;
    }
    
    /* Smaller table */
    table {
        min-width: 500px;
        font-size: 0.8rem;
    }
    
    table th,
    table td {
        padding: 0.375rem 0.5rem;
    }
    
    /* Product image smaller */
    table img,
    table div[style*="width: 50px"] {
        width: 40px !important;
        height: 40px !important;
    }
    
    /* Buttons */
    .btn-sm {
        padding: 0.375rem 0.5rem;
        font-size: 0.875rem;
    }
    
    /* Modal */
    .modal-content {
        margin: 0.5rem;
        max-width: calc(100vw - 1rem);
        max-height: calc(100vh - 1rem);
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 1rem;
    }
    
    .modal-title {
        font-size: 1.125rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
}

/* Very Small Mobile (< 360px) */
@media (max-width: 360px) {
    h1 {
        font-size: 1.5rem;
    }
    
    table {
        min-width: 450px;
        font-size: 0.75rem;
    }
    
    table img,
    table div[style*="width: 50px"] {
        width: 35px !important;
        height: 35px !important;
    }
}
</style>

<div class="container" style="padding: 2rem 0;">
    <div class="page-header">
        <div>
            <h1>Mahsulotlar</h1>
            <p style="color: var(--gray-600); margin: 0;">Mahsulotlarni boshqarish</p>
        </div>
        <button onclick="openModal('addProductModal')" class="btn btn-primary">
            ➕ Yangi mahsulot
        </button>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="form-group" style="flex: 1; margin: 0;">
                    <label class="form-label">Qidirish</label>
                    <input type="text" name="search" class="form-control" placeholder="Mahsulot nomi..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="form-group" style="flex: 1; margin: 0;">
                    <label class="form-label">Kategoriya</label>
                    <select name="category" class="form-control">
                        <option value="">Barchasi</option>
                        <?php
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()):
                        ?>
                            <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Qidirish</button>
                <a href="products.php" class="btn btn-secondary">♻️ Tozalash</a>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Rasm</th>
                            <th>Nom</th>
                            <th>Kategoriya</th>
                            <th>Narx</th>
                            <th>Yaratdi</th>
                            <th>Sana</th>
                            <th>Harakatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="/xpos/uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: var(--gray-200); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                📦
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                                    <td style="color: var(--success); font-weight: 600;"><?= formatCurrency($product['price']) ?></td>
                                    <td><?= htmlspecialchars($product['creator_name']) ?></td>
                                    <td><?= formatDate($product['created_at']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--gray-500);">
                                    Mahsulotlar topilmadi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Yangi mahsulot qo'shish</h2>
            <button class="close-modal" onclick="closeModal('addProductModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Mahsulot nomi *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Kategoriya *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Tanlang...</option>
                    <?php
                    $categories->data_seek(0);
                    while ($cat = $categories->fetch_assoc()):
                    ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Narx (so'm) *</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tavsif</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Rasm</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">Bekor qilish</button>
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>
