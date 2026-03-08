<?php
// admin/index.php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

// Get Metrics
$stmtUsers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$totalUsers = $stmtUsers->fetch()['count'];

$stmtOrders = $pdo->query("SELECT COUNT(*) as count FROM orders");
$totalOrders = $stmtOrders->fetch()['count'];

$stmtRevenue = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$totalRevenue = $stmtRevenue->fetch()['total'] ?? 0;

$stmtProducts = $pdo->query("SELECT COUNT(*) as count FROM products");
$totalProducts = $stmtProducts->fetch()['count'];

// Recent Orders
$stmtRecent = $pdo->query("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC LIMIT 5
");
$recentOrders = $stmtRecent->fetchAll();
?>

<link rel="stylesheet" href="/assets/css/admin.css">

<body class="admin-body">
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="admin-brand">aura admin</div>
            <nav class="admin-nav">
                <a href="/admin/index.php" class="admin-nav-item active">
                    <span>📊</span> Dashboard
                </a>
                <a href="/admin/products.php" class="admin-nav-item">
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
                <h1 class="admin-title">Store Overview</h1>
                <div class="flex gap-3">
                    <span class="admin-badge badge-success">System Online</span>
                </div>
            </header>
            
            <!-- Metrics Grid -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <p class="stat-label">Total Revenue</p>
                    <p class="stat-value"><?= formatPrice($totalRevenue) ?></p>
                </div>
                <div class="admin-stat-card">
                    <p class="stat-label">Total Orders</p>
                    <p class="stat-value"><?= number_format($totalOrders) ?></p>
                </div>
                <div class="admin-stat-card">
                    <p class="stat-label">Active Customers</p>
                    <p class="stat-value"><?= number_format($totalUsers) ?></p>
                </div>
                <div class="admin-stat-card">
                    <p class="stat-label">Product Catalog</p>
                    <p class="stat-value"><?= number_format($totalProducts) ?></p>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="admin-header" style="margin-top: 60px; margin-bottom: 24px;">
                <h2 style="font-size: 20px; font-weight: 800;">Recent Transaction Activity</h2>
                <a href="/admin/orders.php" style="color: var(--admin-info); font-size: 14px; font-weight: 700;">View All Orders &rarr;</a>
            </div>

            <div class="admin-card">
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer Name</th>
                                <th>Transaction Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Total Amount</th>
                                <th style="text-align: center;">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="6" style="padding: 60px; text-align: center; color: var(--admin-muted);">No sales activity recorded yet.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        <p style="font-weight: 700;">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></p>
                                    </td>
                                    <td><?= htmlspecialchars($order['user_name']) ?></td>
                                    <td style="color: var(--admin-muted);"><?= date('M j, Y • H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <span class="admin-badge badge-<?= $order['status'] === 'pending' ? 'pending' : ($order['status'] === 'cancelled' ? 'danger' : 'info') ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: var(--color-terracotta);">
                                        <?= formatPrice($order['total_amount']) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="/admin/order_details.php?id=<?= $order['id'] ?>" class="admin-btn admin-btn-secondary" style="padding: 6px 16px; font-size: 12px; border-radius: 8px;">View</a>
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
