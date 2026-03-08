<?php
// admin/products.php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // Check if product is in any orders
    $checkStmt = $pdo->prepare("SELECT id FROM order_items WHERE product_id = ? LIMIT 1");
    $checkStmt->execute([$id]);
    
    if ($checkStmt->fetch()) {
        setFlashMessage('error', 'Cannot delete product because it has been ordered. Try setting stock to 0 instead.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage('success', 'Product deleted successfully.');
    }
    redirect('/admin/products.php');
}

// Filter Inputs
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? 'all';
$stockStatus = $_GET['stock_status'] ?? 'all';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$isFeatured = $_GET['featured'] ?? 'all';

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Build Query
$query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.id = ?) ";
    $params[] = "%$search%";
    $params[] = is_numeric($search) ? $search : -1;
}

if ($categoryFilter !== 'all') {
    $query .= " AND p.category_id = ? ";
    $params[] = (int)$categoryFilter;
}

if ($stockStatus === 'low_stock') {
    $query .= " AND p.stock > 0 AND p.stock <= 10 ";
} elseif ($stockStatus === 'out_of_stock') {
    $query .= " AND p.stock <= 0 ";
}

if (!empty($minPrice)) {
    $query .= " AND p.price >= ? ";
    $params[] = (float)$minPrice;
}

if (!empty($maxPrice)) {
    $query .= " AND p.price <= ? ";
    $params[] = (float)$maxPrice;
}

if ($isFeatured !== 'all') {
    $query .= " AND p.is_featured = ? ";
    $params[] = ($isFeatured === 'yes' ? 1 : 0);
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
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
                <h1 class="admin-title">Products</h1>
                <a href="/admin/product_edit.php" class="admin-btn admin-btn-primary">+ Add Product</a>
            </header>

            <!-- Comprehensive Filter Bar -->
            <section class="admin-filters">
                <form action="/admin/products.php" method="GET" class="filter-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    <div>
                        <label class="admin-filter-label">Search Catalog</label>
                        <input type="text" name="search" class="admin-input" placeholder="Name or ID..." value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div>
                        <label class="admin-filter-label">Category</label>
                        <select name="category" class="admin-input">
                            <option value="all" <?= $categoryFilter === 'all' ? 'selected' : '' ?>>All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="admin-filter-label">Inventory Status</label>
                        <select name="stock_status" class="admin-input">
                            <option value="all" <?= $stockStatus === 'all' ? 'selected' : '' ?>>All Levels</option>
                            <option value="low_stock" <?= $stockStatus === 'low_stock' ? 'selected' : '' ?>>Low Stock (≤10)</option>
                            <option value="out_of_stock" <?= $stockStatus === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>

                    <div>
                        <label class="admin-filter-label">Price Range (K)</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="number" name="min_price" class="admin-input" placeholder="Min" value="<?= htmlspecialchars($minPrice) ?>" style="padding: 10px;">
                            <input type="number" name="max_price" class="admin-input" placeholder="Max" value="<?= htmlspecialchars($maxPrice) ?>" style="padding: 10px;">
                        </div>
                    </div>

                    <div>
                        <label class="admin-filter-label">Featured Status</label>
                        <select name="featured" class="admin-input">
                            <option value="all" <?= $isFeatured === 'all' ? 'selected' : '' ?>>All Products</option>
                            <option value="yes" <?= $isFeatured === 'yes' ? 'selected' : '' ?>>Featured Only</option>
                            <option value="no" <?= $isFeatured === 'no' ? 'selected' : '' ?>>Standard Only</option>
                        </select>
                    </div>

                    <div class="filter-btn-group">
                        <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
                        <a href="/admin/products.php" class="admin-btn admin-btn-secondary">Clear</a>
                    </div>
                </form>
            </section>
            
            <div class="admin-card">
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Img</th>
                                <th>Product Details</th>
                                <th>Category</th>
                                <th>Listing Price</th>
                                <th>Inventory</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" style="padding: 60px; text-align: center; color: var(--admin-muted);">Your catalog is currently empty.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($products as $product): ?>
                                <tr>
                                    <td>
                                        <div style="width: 50px; height: 50px; border-radius: 12px; overflow: hidden; background: #f5f5f5;">
                                            <img src="<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    </td>
                                    <td>
                                        <p style="font-weight: 700; color: var(--admin-accent); mb-1;"><?= htmlspecialchars($product['name']) ?></p>
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <span style="font-size: 11px; color: var(--admin-muted);">ID: #<?= $product['id'] ?></span>
                                            <?php if($product['is_featured']): ?>
                                                <span class="admin-badge badge-info" style="font-size: 9px; padding: 2px 8px;">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="admin-badge" style="background: #f0f0f0; color: var(--admin-muted); font-weight: 700;">
                                            <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <p style="font-weight: 800; color: var(--color-terracotta);"><?= formatPrice($product['price']) ?></p>
                                    </td>
                                    <td>
                                        <?php if($product['stock'] > 10): ?>
                                            <span style="color: var(--admin-success); font-weight: 700;"><?= $product['stock'] ?> in stock</span>
                                        <?php elseif($product['stock'] > 0): ?>
                                            <span style="color: var(--admin-warning); font-weight: 700;"><?= $product['stock'] ?> left!</span>
                                        <?php else: ?>
                                            <span class="admin-badge badge-danger">Out of Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 15px; justify-content: right; font-weight: 700; font-size: 13px;">
                                            <a href="/admin/product_edit.php?id=<?= $product['id'] ?>" style="color: var(--admin-accent);">Edit</a>
                                            <form action="/admin/products.php" method="POST" class="inline" onsubmit="return confirm('Archive/Delete this product?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
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
        </main>
    </div>
</body>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
