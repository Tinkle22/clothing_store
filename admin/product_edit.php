<?php
// admin/product_edit.php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        setFlashMessage('error', 'Product not found.');
        redirect('/admin/products.php');
    }
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $slug = createSlug($name);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $sizes = sanitize($_POST['sizes'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Handle Image Upload
    $imageName = $product ? $product['image'] : '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        // Basic unique hashing for filename
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newName = uniqid('prod_') . '.' . $ext;
        
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
            $imageName = $newName;
        } else {
            setFlashMessage('error', 'Failed to upload image.');
        }
    }

    if ($id > 0) {
        // Update
        $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, stock = ?, sizes = ?, image = ?, is_featured = ? WHERE id = ?");
        $stmt->execute([$category_id, $name, $slug, $description, $price, $stock, $sizes, $imageName, $is_featured, $id]);
        setFlashMessage('success', 'Product updated successfully.');
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, description, price, stock, sizes, image, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $slug, $description, $price, $stock, $sizes, $imageName, $is_featured]);
        setFlashMessage('success', 'Product created successfully.');
    }
    
    redirect('/admin/products.php');
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
                <a href="/admin/products.php" class="admin-nav-item active">
                    <span>📦</span> Products
                </a>
                <a href="/admin/categories.php" class="admin-nav-item">
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
                <h1 class="admin-title"><?= $product ? 'Edit Product' : 'Add New Product' ?></h1>
                <a href="/admin/products.php" class="admin-btn admin-btn-secondary">&larr; Back to Catalog</a>
            </header>
            
            <div class="admin-form-container">
                <section class="form-section">
                    <form action="/admin/product_edit.php<?= $product ? '?id='.$product['id'] : '' ?>" method="POST" enctype="multipart/form-data">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div class="admin-input-group">
                                <label class="admin-label">Product Name *</label>
                                <input type="text" name="name" class="admin-input" value="<?= $product ? htmlspecialchars($product['name']) : '' ?>" required placeholder="e.g. Classic Linen Shirt">
                            </div>
                            
                            <div class="admin-input-group">
                                <label class="admin-label">Category *</label>
                                <select name="category_id" class="admin-input" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($product && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div class="admin-input-group">
                                <label class="admin-label">Price (K) *</label>
                                <input type="number" step="0.01" name="price" class="admin-input" value="<?= $product ? htmlspecialchars($product['price']) : '' ?>" required placeholder="0.00">
                            </div>
                            
                            <div class="admin-input-group">
                                <label class="admin-label">Stock Inventory *</label>
                                <input type="number" name="stock" class="admin-input" value="<?= $product ? htmlspecialchars($product['stock']) : '0' ?>" required>
                            </div>
                        </div>

                        <div class="admin-input-group">
                            <label class="admin-label">Available Sizes (Comma separated)</label>
                            <input type="text" name="sizes" class="admin-input" value="<?= $product ? htmlspecialchars($product['sizes']) : 'S,M,L,XL' ?>" placeholder="S,M,L,XL or 30,32,34">
                        </div>

                        <div class="admin-input-group">
                            <label class="admin-label">Comprehensive Description *</label>
                            <textarea name="description" class="admin-input" rows="6" required placeholder="Describe the material, fit, and style..."><?= $product ? htmlspecialchars($product['description']) : '' ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 items-end">
                            <div class="admin-input-group">
                                <label class="admin-label">Product Imagery</label>
                                <div class="image-upload-wrapper" style="display: flex; gap: 20px; align-items: start;">
                                    <div class="image-preview-admin" style="width: 154px; height: 180px; background: #f5f5f5; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid var(--admin-border);">
                                        <?php if($product && $product['image']): ?>
                                            <img src="/uploads/<?= htmlspecialchars($product['image']) ?>" alt="Product Preview" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <span style="font-size: 32px;">👕</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <input type="file" name="image" class="admin-input" accept="image/*">
                                        <p style="font-size: 11px; color: var(--admin-muted); margin-top: 10px;">Dimensions: 1000x1200px recommended.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-input-group" style="padding-bottom: 20px;">
                                <div style="display: flex; align-items: center; gap: 12px; background: #f9f9f9; padding: 20px; border-radius: 12px; border: 1px solid var(--admin-border);">
                                    <input type="checkbox" id="is_featured" name="is_featured" style="width: 20px; height: 20px; cursor: pointer;" <?= ($product && $product['is_featured']) ? 'checked' : '' ?>>
                                    <label for="is_featured" style="font-size: 14px; font-weight: 700; cursor: pointer; color: var(--admin-accent);">Featured Product</label>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px; margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--admin-border);">
                            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 14px 40px;">Publish Product</button>
                            <a href="/admin/products.php" class="admin-btn admin-btn-secondary" style="padding: 14px 40px;">Discard</a>
                        </div>
                        
                    </form>
                </section>
            </div>
        </main>
    </div>
</body>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
