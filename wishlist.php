<?php
// wishlist.php
require_once __DIR__ . '/includes/header.php';

// Check if logged in for persistent wishlist
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    $wishlistProducts = getUserWishlist($userId);
} else {
    // Fallback to session for guest users
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    
    // Handle Add/Remove for session
    if (isset($_GET['action'])) {
        $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($productId > 0) {
            if ($_GET['action'] === 'remove') {
                $_SESSION['wishlist'] = array_filter($_SESSION['wishlist'], fn($id) => $id !== $productId);
                setFlashMessage('info', 'Removed from wishlist');
                redirect('/wishlist.php');
            }
        }
    }

    $wishlistProducts = [];
    if (!empty($_SESSION['wishlist'])) {
        $placeholders = str_repeat('?,', count($_SESSION['wishlist']) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_values($_SESSION['wishlist']));
        $wishlistProducts = $stmt->fetchAll();
    }
}
?>

<div class="container py-24 min-h-[70vh]">
    <div class="max-w-6xl mx-auto">
        <header class="mb-16 reveal">
            <h1 class="text-3xl font-serif italic mb-2">My Wishlist</h1>
            <p class="text-xs uppercase tracking-widest text-soft-brown">Pieces you've saved for your collection</p>
        </header>
        
        <?php if(empty($wishlistProducts)): ?>
            <div class="text-center py-24 bg-beige/30 rounded-3xl border border-dashed border-sand reveal">
                <div class="text-4xl mb-6">✨</div>
                <h3 class="text-xl font-bold mb-4">Your wishlist is empty</h3>
                <p class="text-soft-brown mb-12 max-w-sm mx-auto">Discover our latest collection and save the pieces you love most.</p>
                <a href="/shop.php" class="btn btn-dark px-12 py-4 text-xs tracking-widest uppercase">Explore Shop</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12">
                <?php foreach($wishlistProducts as $product): ?>
                <div class="product-card group reveal">
                    <div class="relative aspect-[4/5] overflow-hidden rounded-2xl mb-6 bg-white shadow-soft">
                        <img src="<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110"
                             loading="lazy">
                        
                        <!-- Remove Action -->
                        <?php if(isLoggedIn()): ?>
                            <button onclick="removeFromWishlistPage(<?= $product['id'] ?>, this)" class="absolute top-4 right-4 w-10 h-10 bg-white/90 backdrop-blur shadow-lg rounded-full flex items-center justify-center text-dark hover:bg-terracotta hover:text-white transition-all transform translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        <?php else: ?>
                            <a href="/wishlist.php?action=remove&id=<?= $product['id'] ?>" class="absolute top-4 right-4 w-10 h-10 bg-white/90 backdrop-blur shadow-lg rounded-full flex items-center justify-center text-dark hover:bg-terracotta hover:text-white transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center px-2">
                        <h3 class="text-[0.7rem] font-bold uppercase tracking-[0.2em] mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="text-sm font-serif italic mb-6"><?= formatPrice($product['price']) ?></p>
                        <a href="/product.php?id=<?= $product['id'] ?>" class="text-[0.6rem] font-extrabold uppercase tracking-widest border-b border-dark/20 pb-1 hover:border-dark transition">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function removeFromWishlistPage(id, btn) {
    fetch('/ajax/wishlist_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const card = btn.closest('.product-card');
            card.style.opacity = '0';
            setTimeout(() => {
                location.reload();
            }, 300);
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
