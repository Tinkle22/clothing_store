<?php
// admin/order_details.php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $newStatus = sanitize($_POST['status'] ?? '');
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($newStatus, $validStatuses)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        setFlashMessage('success', "Order status updated to " . ucfirst($newStatus) . ".");
    } else {
        setFlashMessage('error', 'Invalid status.');
    }
    redirect("/admin/order_details.php?id=$orderId");
}

// Fetch order
$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Order not found.');
    redirect('/admin/orders.php');
}

// Fetch order items
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();
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
                <div>
                    <h1 class="admin-title">Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
                    <p style="color: var(--admin-muted); font-size: 14px; margin-top: 5px;">Created on <?= date('M d, Y • H:i', strtotime($order['created_at'])) ?></p>
                </div>
                <a href="/admin/orders.php" class="admin-btn admin-btn-secondary">&larr; Back to List</a>
            </header>

            <div class="admin-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="admin-stat-card">
                    <p class="stat-label">Customer Information</p>
                    <p style="font-weight: 700; margin-bottom: 5px;"><?= htmlspecialchars($order['user_name']) ?></p>
                    <p style="color: var(--admin-muted); font-size: 13px;"><?= htmlspecialchars($order['user_email']) ?></p>
                    <p style="color: var(--admin-accent); font-weight: 700; font-size: 13px; margin-top: 8px;">📞 <?= htmlspecialchars($order['phone'] ?? 'N/A') ?></p>
                    <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid var(--admin-border);">
                        <p class="stat-label" style="font-size: 10px; opacity: 0.6;">Delivery Type</p>
                        <p style="font-weight: 800; text-transform: uppercase; font-size: 12px; color: <?= $order['delivery_type'] === 'yango' ? '#f59e0b' : '#6b7280' ?>;">
                            <?= $order['delivery_type'] === 'yango' ? '🚚 Yango Delivery' : '🏬 Shop Pickup' ?>
                        </p>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <p class="stat-label">Shipping Destination</p>
                    <p style="font-size: 13px; line-height: 1.6;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                </div>
                <div class="admin-stat-card">
                    <p class="stat-label">Current Fulfillment</p>
                    <?php 
                        $badgeClass = 'info';
                        if ($order['status'] === 'pending') $badgeClass = 'pending';
                        if ($order['status'] === 'cancelled') $badgeClass = 'danger';
                        if ($order['status'] === 'delivered') $badgeClass = 'success';
                    ?>
                    <span class="admin-badge badge-<?= $badgeClass ?>" style="margin-bottom: 15px; font-size: 13px; padding: 6px 16px;">
                        <?= ucfirst($order['status']) ?>
                    </span>
                    <form action="/admin/order_details.php?id=<?= $orderId ?>" method="POST" style="display: flex; gap: 8px;">
                        <input type="hidden" name="action" value="update_status">
                        <select name="status" class="admin-input" style="padding: 8px 12px; font-size: 12px; flex: 1;">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="admin-btn admin-btn-primary" style="padding: 0 15px; font-size: 11px;">Update</button>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Item</th>
                                <th>Product Details</th>
                                <th style="text-align: center;">Unit Price</th>
                                <th style="text-align: center;">Qty</th>
                                <th style="text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div style="width: 50px; height: 60px; border-radius: 8px; overflow: hidden; background: #f5f5f5;">
                                        <img src="<?= $item['image'] ? '/uploads/' . htmlspecialchars($item['image']) : '/assets/images/placeholder.jpg' ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </td>
                                <td>
                                    <p style="font-weight: 700; color: var(--admin-accent); margin-bottom: 4px;"><?= htmlspecialchars($item['name']) ?></p>
                                    <p style="font-size: 11px; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 0.5px;">Size: <?= htmlspecialchars($item['size']) ?> | SKU: #<?= $item['product_id'] ?></p>
                                </td>
                                <td style="text-align: center; color: var(--admin-muted);">
                                    <?= formatPrice($item['price']) ?>
                                </td>
                                <td style="text-align: center; font-weight: 700;">
                                    <?= $item['quantity'] ?>
                                </td>
                                <td style="text-align: right; font-weight: 800; color: var(--admin-accent);">
                                    <?= formatPrice($item['price'] * $item['quantity']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: right; padding: 24px; border-top: 2px solid var(--admin-border);">
                                    <p style="color: var(--admin-muted); margin-bottom: 8px; font-size: 13px;">Delivery Fee</p>
                                    <p style="font-weight: 700; font-size: 20px;">Grand Total</p>
                                </td>
                                <td style="text-align: right; padding: 24px; border-top: 2px solid var(--admin-border);">
                                    <p style="color: var(--admin-accent); font-weight: 700; margin-bottom: 8px; font-size: 13px;"><?= $order['delivery_type'] === 'yango' ? 'K25.00' : 'FREE' ?></p>
                                    <p style="font-weight: 800; font-size: 24px; color: var(--admin-accent);"><?= formatPrice($order['total_amount']) ?></p>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
