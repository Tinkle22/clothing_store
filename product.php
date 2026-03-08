<?php
// product.php
require_once __DIR__ . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlashMessage('error', 'Product not found.');
    redirect('/shop.php');
}

$sizes = explode(',', $product['sizes']);

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    if (!isLoggedIn()) {
        setFlashMessage('info', 'Please log in to add items to your cart.');
        redirect('/login.php');
    }

    $size = isset($_POST['size']) ? sanitize($_POST['size']) : null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if (empty($size)) {
        setFlashMessage('error', 'Please select a size.');
    } else {
        $userId = getCurrentUserId();
        
        // Check if item already in cart
        $checkStmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size = ?");
        $checkStmt->execute([$userId, $id, $size]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $updateStmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
            $updateStmt->execute([$quantity, $existing['id']]);
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, size) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$userId, $id, $quantity, $size]);
        }
        
        setFlashMessage('success', 'Added to cart successfully!');
        redirect("/product.php?id=$id");
    }
}
?>

<link rel="stylesheet" href="/assets/css/product.css">

<div class="product-container">
    <!-- Breadcrumbs -->
    <nav class="breadcrumbs">
        <a href="/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Home
        </a>
        <span class="separator">•</span>
        <span>Product details</span>
    </nav>

    <div class="product-layout">
        
        <!-- Left: Product Gallery -->
        <div class="product-gallery">
            <!-- Main Image -->
            <div class="product-image-wrap">
                <img id="main-product-image" src="<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>
            
            <!-- Thumbnails Overlapping at Bottom -->
            <div class="product-thumbnails">
                <div class="thumbnail-wrap active" onclick="updateMainImage(this, '<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>')">
                    <img src="<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>" alt="Primary View">
                </div>
                <div class="thumbnail-wrap" onclick="updateMainImage(this, '<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>')">
                    <img src="<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>" alt="Secondary View">
                </div>
                <div class="thumbnail-wrap" onclick="updateMainImage(this, '<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>')">
                    <img src="<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>" alt="Detail View">
                </div>
            </div>
        </div>

        <!-- Right: Product Info -->
        <div class="product-details">
            <!-- Category Pill -->
            <div class="product-category-pill">
                <?= htmlspecialchars($product['category_name'] ?? 'Man Fashion') ?>
            </div>

            <!-- Title & Price Header -->
            <h1 class="product-title-m"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price-m">$<?= number_format($product['price'], 2) ?></div>

            <form method="POST" action="/product.php?id=<?= $id ?>" id="purchase-form">
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="id" value="<?= $id ?>">

                <!-- Size Selection -->
                <div class="mb-10">
                    <h4 class="size-section-title">Select Size</h4>
                    <div class="size-selector">
                        <?php 
                        $all_sizes = ['S', 'M', 'L', 'XL', 'XXL'];
                        $available_sizes = !empty($product['sizes']) ? array_map('trim', explode(',', $product['sizes'])) : $all_sizes;
                        foreach($all_sizes as $index => $size): 
                            // Defaulting 'S' to checked if available, else first available
                            $is_available = in_array($size, $available_sizes);
                            $is_checked = ($is_available && $size === 'S') ? 'checked' : '';
                        ?>
                            <div class="size-option">
                                <input type="radio" name="size" id="size_<?= $index ?>" value="<?= htmlspecialchars($size) ?>" class="sr-only" <?= $is_available ? '' : 'disabled' ?> <?= $is_checked ?> required>
                                <label for="size_<?= $index ?>" class="size-label">
                                    <?= htmlspecialchars($size) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div class="purchase-actions-m">
                    <button type="submit" class="btn-add-cart">
                        Add to Cart
                    </button>
                    
                    <button type="button" class="btn-favorite">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    </button>
                </div>
            </form>

            <!-- Accordions -->
            <div class="accordion-wrapper">
                <!-- Description -->
                <div class="accordion-group active">
                    <button type="button" class="accordion-trigger-m" onclick="toggleAccordion(this)">
                        <span>Description & Fit</span>
                        <svg class="accordion-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
                    </button>
                    <div class="accordion-content-m">
                        <?= nl2br(htmlspecialchars($product['description'] ?: 'Loose-fit sweatshirt hoodie in medium weight cotton-blend fabric with a generous, but not oversized silhouette. Jersey-lined, drawstring hood, dropped shoulders, long sleeves, and a kangaroo pocket. Wide ribbing at cuffs and hem. Soft, brushed inside.')) ?>
                    </div>
                </div>
            </div>

            <div class="accordion-wrapper">
                <!-- Shipping -->
                <div class="accordion-group active">
                    <button type="button" class="accordion-trigger-m" onclick="toggleAccordion(this)">
                        <span>Shipping</span>
                        <svg class="accordion-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
                    </button>
                    <div class="accordion-content-m">
                        <div class="shipping-grid">

                            
                            <!-- Package -->
                            <div class="shipping-item">
                                <div class="shipping-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                                </div>
                                <div class="shipping-text">
                                    <span class="shipping-label">Package</span>
                                    <span class="shipping-value">Regular Package</span>
                                </div>
                            </div>

                            <!-- Delivery Time -->
                            <div class="shipping-item">
                                <div class="shipping-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </div>
                                <div class="shipping-text">
                                    <span class="shipping-label">Delivery Time</span>
                                    <span class="shipping-value">2-4 Working Days</span>
                                </div>
                            </div>

                            <!-- Estimation Arrive -->
                            <div class="shipping-item">
                                <div class="shipping-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="14" rx="2"></rect><path d="M7 4v16"></path><path d="M17 4v16"></path><path d="M2 8h20"></path></svg>
                                </div>
                                <div class="shipping-text">
                                    <span class="shipping-label">Estimation Arrive</span>
                                    <span class="shipping-value">10 - 12 October 2024</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function toggleAccordion(btn) {
    const item = btn.closest('.accordion-group');
    item.classList.toggle('active');
}

function updateMainImage(thumb, url) {
    document.getElementById('main-product-image').src = url;
    
    // Reset all thumbnails
    const thumbs = thumb.parentElement.children;
    for(let i=0; i<thumbs.length; i++) {
        thumbs[i].classList.remove('active');
    }
    
    // Set selected
    thumb.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
