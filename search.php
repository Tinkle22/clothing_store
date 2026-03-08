<?php
// search.php
require_once __DIR__ . '/includes/header.php';

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$products = [];

if (!empty($query)) {
    // Search in product name and description
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ?");
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $products = $stmt->fetchAll();
}
?>

<div class="container py-16 min-h-[60vh]">
    <div class="text-center mb-12">
        <h1 class="mb-4">Search Results</h1>
        <form action="/search.php" method="GET" class="flex max-w-lg mx-auto gap-2">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search for products..." class="form-control rounded-full" required>
            <button type="submit" class="btn btn-primary rounded-full px-6">Search</button>
        </form>
    </div>

    <?php if (!empty($query)): ?>
        <p class="text-soft-brown mb-8">Found <?= count($products) ?> result(s) for "<strong><?= htmlspecialchars($query) ?></strong>"</p>
        
        <?php if(empty($products)): ?>
            <div class="text-center py-16 bg-white rounded-lg border border-beige">
                <p class="text-soft-brown text-lg">No products found matching your search.</p>
                <a href="/shop.php" class="btn btn-outline mt-4">Browse All Products</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php foreach($products as $product): ?>
                <a href="/product.php?id=<?= $product['id'] ?>" class="product-card">
                    <div class="product-img-wrapper" style="padding-top: 130%;">
                        <img src="/assets/images/placeholder.jpg" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                    </div>
                    <div class="product-info">
                        <h3 class="product-title text-base"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="product-price"><?= formatPrice($product['price']) ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
