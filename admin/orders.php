<?php
// admin/orders.php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

// Filter Inputs
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';

$query = "
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND o.status = ? ";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $query .= " AND (o.id = ? OR u.name LIKE ? OR u.email LIKE ?) ";
    $params[] = is_numeric($search) ? $search : -1;
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($dateFrom)) {
    $query .= " AND DATE(o.created_at) >= ? ";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $query .= " AND DATE(o.created_at) <= ? ";
    $params[] = $dateTo;
}

if (!empty($minPrice)) {
    $query .= " AND o.total_amount >= ? ";
    $params[] = (float)$minPrice;
}

if (!empty($maxPrice)) {
    $query .= " AND o.total_amount <= ? ";
    $params[] = (float)$maxPrice;
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

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
                <a href="/admin/categories.php" class="admin-nav-item">
                    <span>📁</span> Categories
                </a>
                <a href="/admin/orders.php" class="admin-nav-item active">
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
                <h1 class="admin-title">Orders</h1>
            </header>

            <!-- Comprehensive Filter Bar -->
            <section class="admin-filters">
                <form action="/admin/orders.php" method="GET" class="filter-grid">
                    <div>
                        <label class="admin-filter-label">Search Order</label>
                        <input type="text" name="search" class="admin-input" placeholder="ID, Name, Email..." value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div>
                        <label class="admin-filter-label">Fulfillment Status</label>
                        <select name="status" class="admin-input">
                            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Orders</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="admin-filter-label">Date Range</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="date" name="date_from" class="admin-input" value="<?= htmlspecialchars($dateFrom) ?>" style="padding: 10px;">
                            <span style="color: var(--admin-border);">至</span>
                            <input type="date" name="date_to" class="admin-input" value="<?= htmlspecialchars($dateTo) ?>" style="padding: 10px;">
                        </div>
                    </div>

                    <div>
                        <label class="admin-filter-label">Amount (K)</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="number" name="min_price" class="admin-input" placeholder="Min" value="<?= htmlspecialchars($minPrice) ?>" style="padding: 10px;">
                            <input type="number" name="max_price" class="admin-input" placeholder="Max" value="<?= htmlspecialchars($maxPrice) ?>" style="padding: 10px;">
                        </div>
                    </div>

                    <div class="filter-btn-group">
                        <button type="submit" class="admin-btn admin-btn-primary">Apply Filters</button>
                        <a href="/admin/orders.php" class="admin-btn admin-btn-secondary">Reset</a>
                    </div>
                </form>
            </section>
            
            <div class="admin-card">
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Transaction Date</th>
                                <th>Customer Account</th>
                                <th>Delivery</th>
                                <th>Total Bill</th>
                                <th>Current Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" style="padding: 60px; text-align: center; color: var(--admin-muted);">No matching orders found.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($orders as $order): ?>
                                <tr>
                                    <td>
                                        <p style="font-weight: 700; color: var(--admin-accent);">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></p>
                                    </td>
                                    <td>
                                        <p style="font-size: 13px; color: var(--admin-muted);"><?= date('M j, Y • H:i', strtotime($order['created_at'])) ?></p>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="font-weight: 700; font-size: 14px;"><?= htmlspecialchars($order['user_name']) ?></span>
                                            <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($order['user_email']) ?></span>
                                         </div>
                                    </td>
                                    <td>
                                        <?php if($order['delivery_type'] === 'yango'): ?>
                                            <div style="display: flex; align-items: center; gap: 4px; color: #f59e0b;">
                                                <span style="font-size: 14px;">🚚</span>
                                                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Yango</span>
                                            </div>
                                        <?php else: ?>
                                            <div style="display: flex; align-items: center; gap: 4px; color: #6b7280;">
                                                <span style="font-size: 14px;">🏬</span>
                                                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase;">Pickup</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <p style="font-weight: 800; color: var(--color-dark);"><?= formatPrice($order['total_amount']) ?></p>
                                    </td>
                                    <td>
                                        <?php 
                                            $badgeClass = 'info';
                                            if ($order['status'] === 'pending') $badgeClass = 'pending';
                                            if ($order['status'] === 'cancelled') $badgeClass = 'danger';
                                            if ($order['status'] === 'delivered') $badgeClass = 'success';
                                        ?>
                                        <span class="admin-badge badge-<?= $badgeClass ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="/admin/order_details.php?id=<?= $order['id'] ?>" class="admin-btn admin-btn-secondary" style="padding: 8px 20px; font-size: 12px; border-radius: 8px;">Manage</a>
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
