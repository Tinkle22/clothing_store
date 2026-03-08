<?php
// admin/categories.php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $slug = createSlug($name);
        
        // Handle Image Upload
        $imageName = $_POST['existing_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['image']['tmp_name'];
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newFileName = 'cat_' . time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = __DIR__ . '/../uploads/' . $newFileName;
            
            if (move_uploaded_file($tmpName, $uploadPath)) {
                $imageName = $newFileName;
                // Delete old image if exists
                if (!empty($_POST['existing_image'])) {
                    @unlink(__DIR__ . '/../uploads/' . $_POST['existing_image']);
                }
            }
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $description, $imageName, $id]);
            setFlashMessage('success', 'Category updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $description, $imageName]);
            setFlashMessage('success', 'New category created successfully.');
        }
        redirect('/admin/categories.php');
    }
    
    if ($postAction === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        try {
            // Check for image to delete
            $stmtImg = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
            $stmtImg->execute([$id]);
            $row = $stmtImg->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                if (!empty($row['image'])) {
                    @unlink(__DIR__ . '/../uploads/' . $row['image']);
                }
                setFlashMessage('success', 'Category deleted.');
            }
        } catch (PDOException $e) {
            setFlashMessage('error', 'Cannot delete category (likely contains products).');
        }
        redirect('/admin/categories.php');
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$editCategory = null;

if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editCategory = $stmt->fetch();
}
?>

<link rel="stylesheet" href="/assets/css/admin.css">

<body class="admin-body">
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="admin-brand">aura admin</div>
            <nav class="admin-nav">
                <a href="/admin/index.php" class="admin-nav-item">
                    <span>📊</span> Dashboard
                </a>
                <a href="/admin/products.php" class="admin-nav-item">
                    <span>📦</span> Products
                </a>
                <a href="/admin/categories.php" class="admin-nav-item active">
                    <span>📁</span> Categories
                </a>
                <a href="/admin/orders.php" class="admin-nav-item">
                    <span>📃</span> Orders
                </a>
                <div class="admin-nav-divider"></div>
                <a href="/dashboard.php" class="admin-nav-item">
                    <span>🏠</span> Back to Shop
                </a>
                <a href="/logout.php" class="admin-nav-item" style="color: var(--admin-danger); margin-top: auto;">
                    <span>👋</span> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1 class="admin-title"><?= ($action === 'add' || $action === 'edit') ? ($editCategory ? 'Edit Category' : 'Add Category') : 'Categories' ?></h1>
                <?php if($action !== 'add' && $action !== 'edit'): ?>
                    <a href="/admin/categories.php?action=add" class="admin-btn admin-btn-primary">+ New Category</a>
                <?php endif; ?>
            </header>
            
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <div class="admin-form-container">
                    <section class="form-section">
                        <form action="/admin/categories.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save">
                            <?php if($editCategory): ?>
                                <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                                <input type="hidden" name="existing_image" value="<?= $editCategory['image'] ?>">
                            <?php endif; ?>
                            
                            <div class="admin-input-group">
                                <label class="admin-label">Category Name</label>
                                <input type="text" name="name" class="admin-input" value="<?= $editCategory ? htmlspecialchars($editCategory['name']) : '' ?>" required placeholder="e.g. Summer Collection">
                            </div>

                            <div class="admin-input-group">
                                <label class="admin-label">Category Image</label>
                                <div class="image-upload-wrapper">
                                    <div class="image-preview-admin">
                                        <?php if($editCategory && $editCategory['image']): ?>
                                            <img src="/uploads/<?= htmlspecialchars($editCategory['image']) ?>" alt="Preview">
                                        <?php else: ?>
                                            <span style="font-size: 24px;">🖼️</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <input type="file" name="image" class="admin-input" accept="image/*">
                                        <p style="font-size: 11px; color: var(--admin-muted); mt-2;">Recommended: Square ratio, clean background.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="admin-input-group">
                                <label class="admin-label">Description (Optional)</label>
                                <textarea name="description" class="admin-input" rows="4" placeholder="Briefly describe what's in this category..."><?= $editCategory ? htmlspecialchars($editCategory['description']) : '' ?></textarea>
                            </div>
                            
                            <div class="flex gap-4" style="margin-top: 40px;">
                                <button type="submit" class="admin-btn admin-btn-primary">Save Category</button>
                                <a href="/admin/categories.php" class="admin-btn admin-btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </section>
                </div>
            <?php else: ?>
                <div class="admin-card">
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Img</th>
                                    <th>Category Name</th>
                                    <th>Slug</th>
                                    <th>Description</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" style="padding: 60px; text-align: center; color: var(--admin-muted);">No categories added yet.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <div style="width: 40px; height: 40px; border-radius: 8px; overflow: hidden; background: #f5f5f5;">
                                                <?php if($category['image']): ?>
                                                    <img src="/uploads/<?= htmlspecialchars($category['image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 14px;">📁</div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <p style="font-weight: 700; color: var(--admin-accent);"><?= htmlspecialchars($category['name']) ?></p>
                                        </td>
                                        <td><code style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?= htmlspecialchars($category['slug']) ?></code></td>
                                        <td>
                                            <p style="color: var(--admin-muted); font-size: 13px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?= htmlspecialchars($category['description'] ? $category['description'] : 'No description') ?>
                                            </p>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; gap: 15px; justify-content: right;">
                                                <a href="/admin/categories.php?action=edit&id=<?= $category['id'] ?>" style="color: var(--admin-info); font-weight: 700;">Edit</a>
                                                <form action="/admin/categories.php" method="POST" class="inline" onsubmit="return confirm('Delete this category? All products under it will lose their association.');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                                    <button type="submit" style="color: var(--admin-danger); background: transparent; border: none; font-weight: 700; cursor: pointer; padding: 0;">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
