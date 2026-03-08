<?php
// shop.php
require_once __DIR__ . '/includes/header.php';

$categorySlug = $_GET['category'] ?? null;
$sort = $_GET['sort'] ?? 'newest';
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$inStockOnly = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';

$whereClauses = [];
$params = [];
$categorySlugs = (isset($_GET['category']) && !empty($_GET['category'])) ? (is_array($_GET['category']) ? $_GET['category'] : [$_GET['category']]) : [];
$categoryName = "All Products"; // Default for when no category is selected

// Category Filter
if (!empty($categorySlugs)) {
    $placeholders = implode(',', array_fill(0, count($categorySlugs), '?'));
    $whereClauses[] = "category_id IN (SELECT id FROM categories WHERE slug IN ($placeholders))";
    foreach($categorySlugs as $slug) {
        $params[] = $slug;
    }
    
    // For the title and breadcrumbs, we'll just show the first one or "Collection"
    if (count($categorySlugs) === 1) {
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE slug = ?");
        $stmt->execute([$categorySlugs[0]]);
        $categoryName = $stmt->fetchColumn() ?: "Collection";
    } else {
        $categoryName = "Filtered Pieces";
    }
}

// Price Filters
if ($minPrice !== null) {
    $whereClauses[] = "price >= ?";
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    if ($maxPrice > 0) {
        $whereClauses[] = "price <= ?";
        $params[] = $maxPrice;
    }
}

// Stock Filter
if ($inStockOnly) {
    $whereClauses[] = "stock > 0";
}

// Build WHERE clause
$whereSql = "";
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

// Sorting
$orderBy = "ORDER BY created_at DESC";
if ($sort === 'price_asc') {
    $orderBy = "ORDER BY price ASC";
} elseif ($sort === 'price_desc') {
    $orderBy = "ORDER BY price DESC";
}

$query = "SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id $whereSql $orderBy";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories for filter sidebar
$stmtCat = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$allCategories = $stmtCat->fetchAll();
?>

<link rel="stylesheet" href="/assets/css/shop.css">

<div class="shop-container">
    <!-- Breadcrumbs -->
    <div class="shop-breadcrumb">
        <a href="/index.php">Home</a>
        <span class="separator">/</span>
        <span>Collection</span>
    </div>

    <!-- Page Title -->
    <h1 class="shop-title-m"><?= htmlspecialchars($categoryName) ?></h1>

    <!-- Category Carousel/Grid -->
    <div class="shop-categories-wrap">
        <a href="/shop.php" class="shop-category-card">
            <div class="shop-category-img-wrap img-fit-cover">
                <img src="/assets/images/placeholder.jpg">
            </div>
            <span class="shop-category-name">Everything</span>
        </a>
        <?php foreach($allCategories as $cat): 
            $catImage = $cat['image'] ? '/uploads/' . $cat['image'] : '/assets/images/placeholder.jpg';
            // Fallback to specific images for better UI
            if ($cat['slug'] === 'tops') $catImage = '/uploads/linen-shirt.jpg';
            if ($cat['slug'] === 'bottoms') $catImage = '/uploads/pleated-trousers.jpg';
            if ($cat['slug'] === 'outerwear') $catImage = '/uploads/wool-coat.jpg';
            if ($cat['slug'] === 'knitwear') $catImage = '/uploads/knit-sweater.jpg';
        ?>
            <a href="/shop.php?category=<?= $cat['slug'] ?>" class="shop-category-card">
                <div class="shop-category-img-wrap img-fit-cover">
                    <img src="<?= $catImage ?>">
                </div>
                <span class="shop-category-name"><?= htmlspecialchars($cat['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="shop-layout">
        <!-- Sidebar Filters -->
        <aside class="shop-sidebar">
            <form action="/shop.php" method="GET" id="shop-filter-form">
                
                <!-- Availability Section -->
                <div class="filter-group">
                    <div class="filter-header" onclick="this.nextElementSibling.classList.toggle('hidden')">
                        <span>Availability</span>
                        <svg class="filter-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                    <div class="filter-options">
                        <label class="filter-checkbox-label">
                            <input type="checkbox" name="in_stock" value="1" <?= $inStockOnly ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span>In Stock</span>
                        </label>
                        <label class="filter-checkbox-label">
                            <input type="checkbox">
                            <span>Out Of Stock</span>
                        </label>
                    </div>
                </div>

                <!-- Product Type Section -->
                <div class="filter-group">
                    <div class="filter-header" onclick="this.nextElementSibling.classList.toggle('hidden')">
                        <span>Product type</span>
                        <svg class="filter-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                    <div class="filter-options">
                        <?php foreach($allCategories as $c): ?>
                            <label class="filter-checkbox-label">
                                <input type="checkbox" name="category[]" value="<?= htmlspecialchars($c['slug']) ?>" <?= in_array($c['slug'], $categorySlugs) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span><?= htmlspecialchars($c['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </aside>

        <!-- Main Product Area -->
        <main class="shop-main">
            <!-- Results Bar -->
            <div class="shop-controls">
                <span class="shop-results-count"><?= count($products) ?> of 30 results</span>
                
                <div class="shop-sort-wrap">
                    <span class="shop-sort-label">Sort By</span>
                    <select form="shop-filter-form" name="sort" onchange="this.form.submit()" class="shop-sort-select">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Relevance</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low-High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High-Low</option>
                    </select>
                    
                    <div class="shop-view-toggles">
                        <div class="view-toggle-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        </div>
                        <div class="view-toggle-btn active">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Filters Row -->
            <div class="active-filters">
                <?php if($inStockOnly): ?>
                    <div class="active-filter-chip">In Stock <span onclick="window.location='/shop.php?<?= http_build_query(array_merge($_GET, ['in_stock' => null])) ?>'">×</span></div>
                <?php endif; ?>
                <?php foreach($categorySlugs as $slug): 
                    $currName = "";
                    foreach($allCategories as $ac) if($ac['slug'] === $slug) $currName = $ac['name'];
                ?>
                    <div class="active-filter-chip"><?= htmlspecialchars($currName) ?> <span onclick="window.location='/shop.php?<?= http_build_query(array_diff_key($_GET, ['category' => ''])) . '&' . http_build_query(['category' => array_diff($categorySlugs, [$slug])]) ?>'">×</span></div>
                <?php endforeach; ?>
                
                <?php if($minPrice || $maxPrice): ?>
                     <div class="active-filter-chip"><?= '$'.($minPrice ?? 0) . '-$' . ($maxPrice ?? '99.00') ?> <span onclick="window.location='/shop.php?<?= http_build_query(array_merge($_GET, ['min_price' => null, 'max_price' => null])) ?>'">×</span></div>
                <?php endif; ?>
                
                <?php if(!empty($categorySlugs) || $inStockOnly || $minPrice || $maxPrice): ?>
                    <a href="/shop.php" class="clear-filters-btn">Clear All</a>
                <?php endif; ?>
            </div>

            <!-- Grid -->
            <?php if(empty($products)): ?>
                <div class="empty-state">
                    <h3>No matching pieces</h3>
                    <p>Try broadening your selection to find what you're looking for.</p>
                    <a href="/shop.php" class="btn btn-primary" style="border-radius:99px; padding: 1rem 2rem;">Clear All Filters</a>
                </div>
            <?php else: ?>
                <div class="shop-product-grid">
                    <?php foreach($products as $product): ?>
                        <a href="/product.php?id=<?= $product['id'] ?>" class="shop-product-card">
                            <div class="shop-product-img-wrap">
                                <img src="<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <div class="shop-product-badge"><?= htmlspecialchars($product['category_name']) ?></div>
                                <div class="wishlist-toggle-shop <?= isInWishlist($product['id']) ? 'active' : '' ?>" 
                                     data-id="<?= $product['id'] ?>"
                                     style="position: absolute; top: 10px; right: 10px; z-index: 20; background: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.3s ease;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="<?= isInWishlist($product['id']) ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: <?= isInWishlist($product['id']) ? '#ff4757' : '#1a1a1a' ?>;"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>
                                </div>
                            </div>
                            <span class="shop-product-title"><?= htmlspecialchars($product['name']) ?></span>
                            <span class="shop-product-price"><?= formatPrice($product['price']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
function toggleFilters() {
    const drawer = document.getElementById('filter-drawer');
    if(drawer) {
        drawer.classList.toggle('hidden');
        drawer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.wishlist-toggle-shop').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            const icon = this.querySelector('svg');
            
            fetch('/ajax/wishlist_toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.action === 'added') {
                        this.classList.add('active');
                        icon.setAttribute('fill', 'currentColor');
                        icon.style.color = '#ff4757';
                    } else {
                        this.classList.remove('active');
                        icon.setAttribute('fill', 'none');
                        icon.style.color = '#1a1a1a';
                    }
                    
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-4 right-4 bg-terracotta text-white p-4 rounded shadow-lg z-[9999] reveal';
                    toast.style.position = 'fixed';
                    toast.style.bottom = '20px';
                    toast.style.right = '20px';
                    toast.style.backgroundColor = '#1a1a1a';
                    toast.style.color = 'white';
                    toast.style.padding = '12px 24px';
                    toast.style.borderRadius = '8px';
                    toast.style.zIndex = '100000';
                    toast.innerText = data.message;
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        toast.style.transition = 'opacity 0.3s ease';
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);
                } else {
                    alert(data.message);
                }
            })
            .catch(err => console.error('Wishlist error:', err));
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
