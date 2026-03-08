<?php
// cart.php
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId = getCurrentUserId();

// Handle Actions (Update quantity, Remove item)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if ($quantity > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cartId, $userId]);
            setFlashMessage('success', 'Cart updated.');
        } else {
            // Remove if quantity is 0
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cartId, $userId]);
            setFlashMessage('info', 'Item removed from cart.');
        }
        redirect('/cart.php');
    }
    
    if ($action === 'remove') {
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cartId, $userId]);
        setFlashMessage('info', 'Item removed from cart.');
        redirect('/cart.php');
    }
}

// Fetch Cart Items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal;
?>

<link rel="stylesheet" href="/assets/css/cart.css">

<div class="cart-page">
    <h1 class="cart-header">Cart</h1>

    <?php if (empty($cartItems)): ?>
        <div class="cart-empty">
            <div class="cart-empty-icon">🛒</div>
            <h3 class="cart-empty-title">Your bag is empty</h3>
            <p class="cart-empty-desc">Discover our new arrivals and find something special to add to your collection.</p>
            <a href="/shop.php" class="cart-empty-btn">Explore Collection</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            
            <!-- Items List -->
            <div class="cart-items">
                <?php 
                $isFirst = true;
                foreach ($cartItems as $item): 
                ?>
                <div class="cart-item <?= $isFirst ? 'first-item' : '' ?>">
                    <img src="<?= $item['image'] ? '/uploads/' . htmlspecialchars($item['image']) : '/assets/images/placeholder.jpg' ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-image">
                    
                    <div class="cart-item-content">
                        <div class="cart-item-details">
                            <a href="/product.php?id=<?= $item['product_id'] ?>" class="cart-item-title"><?= htmlspecialchars($item['name']) ?></a>
                            <p class="cart-item-attr">Varient: Agora</p>
                            <p class="cart-item-attr">Size: <?= htmlspecialchars($item['size'] ?? 'XXL') ?></p>
                            <p class="cart-item-attr">Color: Black</p>
                            
                            <div class="cart-item-actions">
                                <button type="button" class="cart-icon-btn" aria-label="Save for later">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                    </svg>
                                </button>
                                
                                <form action="/cart.php" method="POST" class="inline-flex m-0">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="cart-icon-btn" aria-label="Remove item">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="cart-item-right">
                            <span class="cart-item-price"><?= formatPrice($item['price']) ?></span>
                            
                            <form action="/cart.php" method="POST" class="m-0">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                
                                <div class="cart-item-qty">
                                    <button type="button" class="cart-qty-btn" onclick="let input = this.nextElementSibling; input.value = Math.max(0, parseInt(input.value) - 1); input.form.submit();">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </button>
                                    
                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="0" class="cart-qty-input" onchange="this.form.submit()">
                                    
                                    <button type="button" class="cart-qty-btn" onclick="let input = this.previousElementSibling; input.value = parseInt(input.value) + 1; input.form.submit();">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php 
                $isFirst = false;
                endforeach; 
                ?>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3 class="summary-title">Order Summary</h3>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?= formatPrice($subtotal) ?></span>
                </div>
                

                
                <div class="summary-row">
                    <span>Discount</span>
                    <span>-</span>
                </div>
                
                <div class="summary-row total">
                    <span>Total</span>
                    <span><?= formatPrice($total) ?></span>
                </div>
                
                <a href="/checkout.php" class="checkout-btn">Checkout</a>
                <a href="#" class="promo-code-link">User a promo code</a>
            </div>

        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
