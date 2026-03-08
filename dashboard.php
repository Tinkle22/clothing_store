<?php
// dashboard.php
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId = getCurrentUserId();
$activeTab = $_GET['tab'] ?? 'orders';

// Fetch User Info
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = sanitize($_POST['name'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    
    $stmtUpdate = $pdo->prepare("UPDATE users SET name = ?, address = ? WHERE id = ?");
    if($stmtUpdate->execute([$name, $address, $userId])) {
        $_SESSION['user_name'] = $name;
        $user['name'] = $name;
        $user['address'] = $address;
        setFlashMessage('success', 'Profile updated successfully.');
    } else {
        setFlashMessage('error', 'Failed to update profile.');
    }
    redirect('/dashboard.php?tab=profile');
}

// Filter Parameters
$filterStatus = sanitize($_GET['status'] ?? '');
$filterSearch = sanitize($_GET['search'] ?? '');
$filterPeriod = sanitize($_GET['period'] ?? 'all');

// Build Filter Query
$query = "SELECT * FROM orders WHERE user_id = ?";
$params = [$userId];

if (!empty($filterStatus)) {
    $query .= " AND status = ?";
    $params[] = $filterStatus;
}

if (!empty($filterSearch)) {
    // Search by order ID (exact or partial)
    $cleanSearch = preg_replace('/[^0-9]/', '', $filterSearch);
    if (!empty($cleanSearch)) {
        $query .= " AND id LIKE ?";
        $params[] = "%$cleanSearch%";
    }
}

if ($filterPeriod === '30_days') {
    $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($filterPeriod === '6_months') {
    $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} elseif ($filterPeriod === '1_year') {
    $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

$query .= " ORDER BY created_at DESC";

$stmtOrders = $pdo->prepare($query);
$stmtOrders->execute($params);
$orders = $stmtOrders->fetchAll();

// Fetch Wishlist Items
$wishlistProducts = getUserWishlist($userId);
?>

<link rel="stylesheet" href="/assets/css/dashboard.css">

<div class="container dashboard-container">
    <div class="dashboard-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="dashboard-sidebar">
            <div class="user-profile-header">
                <div class="avatar-circle">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h3 class="user-name"><?= htmlspecialchars($user['name']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            
            <ul class="sidebar-nav">
                <li>
                    <a href="/dashboard.php?tab=orders" class="nav-link <?= $activeTab === 'orders' ? 'active' : '' ?>">
                        <span class="icon">📦</span>
                        <span>My Orders</span>
                    </a>
                </li>
                <li>
                    <a href="/dashboard.php?tab=profile" class="nav-link <?= $activeTab === 'profile' ? 'active' : '' ?>">
                        <span class="icon">👤</span>
                        <span>Profile Settings</span>
                    </a>
                </li>
                <li>
                    <a href="/dashboard.php?tab=wishlist" class="nav-link <?= $activeTab === 'wishlist' ? 'active' : '' ?>">
                        <span class="icon">✨</span>
                        <span>My Wishlist</span>
                    </a>
                </li>
                <li class="sign-out-btn">
                    <a href="/logout.php" class="nav-link text-terracotta">
                        <span class="icon">👋</span>
                        <span>Sign Out</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            
            <?php if ($activeTab === 'orders'): ?>
                <div class="tab-content-header">
                    <h2>Order History</h2>
                    <p>Track and manage your past purchases.</p>
                </div>

                <!-- Comprehensive Filter UI -->
                <div class="filter-bar">
                    <form action="/dashboard.php" method="GET" class="filter-form">
                        <input type="hidden" name="tab" value="orders">
                        
                        <div class="filter-group search">
                            <label class="filter-label">Search Order ID</label>
                            <div class="input-with-icon">
                                <span class="search-icon">🔍</span>
                                <input type="text" name="search" placeholder="e.g. 000013" value="<?= htmlspecialchars($filterSearch) ?>" class="filter-input">
                            </div>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Status</label>
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $filterStatus === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $filterStatus === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $filterStatus === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Time Period</label>
                            <select name="period" class="filter-select" onchange="this.form.submit()">
                                <option value="all" <?= $filterPeriod === 'all' ? 'selected' : '' ?>>All Time</option>
                                <option value="30_days" <?= $filterPeriod === '30_days' ? 'selected' : '' ?>>Last 30 Days</option>
                                <option value="6_months" <?= $filterPeriod === '6_months' ? 'selected' : '' ?>>Last 6 Months</option>
                                <option value="1_year" <?= $filterPeriod === '1_year' ? 'selected' : '' ?>>Last Year</option>
                            </select>
                        </div>

                        <?php if (!empty($filterStatus) || !empty($filterSearch) || $filterPeriod !== 'all'): ?>
                            <div class="filter-actions">
                                <a href="/dashboard.php?tab=orders" class="clear-filters">Clear All</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🏜️</div>
                        <p class="empty-text">You haven't placed any orders yet.</p>
                        <a href="/shop.php" class="btn btn-primary">Explore Our Shop</a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-id-group">
                                    <div class="order-icon">📄</div>
                                    <div>
                                        <p class="id-label">Receipt ID</p>
                                        <p class="id-value">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></p>
                                        <p class="order-date">Placed on <?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="order-status-group">
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </span>
                                    <p class="order-total"><?= formatPrice($order['total_amount']) ?></p>
                                </div>
                            </div>
                            
                            <div class="order-footer">
                                <div class="shipping-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <span class="address-text"><?= htmlspecialchars($order['shipping_address']) ?></span>
                                </div>
                                <a href="/order_details.php?id=<?= $order['id'] ?>" class="btn btn-outline">View Details</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($activeTab === 'profile'): ?>
                <div class="tab-content-header">
                    <h2>Account Settings</h2>
                    <p>Update your personal information and preferences.</p>
                </div>
                
                <div class="profile-card">
                    <form action="/dashboard.php?tab=profile" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-field-group">
                            <label class="field-label">Primary Email Address</label>
                            <input type="email" class="input-styled" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <p class="input-hint">Email address is managed by identity services and cannot be modified.</p>
                        </div>

                        <div class="form-field-group">
                            <label for="name" class="field-label">Display Name</label>
                            <input type="text" id="name" name="name" class="input-styled" value="<?= htmlspecialchars($user['name']) ?>" required placeholder="Full Name">
                        </div>
                        
                        <div class="form-field-group">
                            <label for="address" class="field-label">Default Shipping Destination</label>
                            <textarea id="address" name="address" class="input-styled" rows="5" style="resize: none;" placeholder="123 Harmony Street, Suite 400..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="save-btn">Validate & Save Profile</button>
                    </form>
                </div>
            <?php elseif ($activeTab === 'wishlist'): ?>
                <div class="tab-content-header">
                    <h2>My Wishlist</h2>
                    <p>Curated pieces you've saved for later.</p>
                </div>
                
                <?php if (empty($wishlistProducts)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">⭐</div>
                        <p class="empty-text">Your list is waiting for its first piece.</p>
                        <a href="/shop.php" class="btn btn-primary">Start Curating</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 wishlist-grid">
                        <?php foreach ($wishlistProducts as $product): ?>
                            <div class="wishlist-item reveal">
                                <div class="wishlist-img-wrap rounded-2xl overflow-hidden shadow-sm relative group mb-4">
                                    <img src="<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="w-full aspect-[4/5] object-cover transition-transform duration-700 group-hover:scale-110">
                                    <button onclick="removeFromWishlist(<?= $product['id'] ?>, this)" class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm w-8 h-8 rounded-full flex items-center justify-center text-dark hover:bg-terracotta hover:text-white transition-all shadow-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                                <div class="wishlist-info">
                                    <h3 class="text-xs font-bold uppercase tracking-widest mb-1"><?= htmlspecialchars($product['name']) ?></h3>
                                    <p class="text-sm font-serif italic mb-4"><?= formatPrice($product['price']) ?></p>
                                    <a href="/product.php?id=<?= $product['id'] ?>" class="btn btn-dark w-full text-[0.65rem] py-3 tracking-widest uppercase">View Piece</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
function removeFromWishlist(id, btn) {
    if(!confirm('Remove this item from your wishlist?')) return;
    
    fetch('/ajax/wishlist_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const card = btn.closest('.wishlist-item');
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            setTimeout(() => {
                card.remove();
                if(document.querySelectorAll('.wishlist-item').length === 0) {
                    location.reload();
                }
            }, 300);
        }
    });
}
</script>

<style>
.wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 2rem; }
.wishlist-item { transition: all 0.3s ease; }
.wishlist-img-wrap { border-radius: 1rem; position: relative; overflow: hidden; }
</style>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
