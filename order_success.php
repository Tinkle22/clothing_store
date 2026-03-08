<?php
// order_success.php
require_once __DIR__ . '/includes/header.php';
requireLogin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = getCurrentUserId();

// Verify order belongs to user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/shop.php');
}
?>

<div class="container py-24 flex justify-center min-h-[70vh]">
    <div class="bg-white p-12 rounded-lg shadow-soft border border-beige text-center max-w-xl w-full">
        
        <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-sm border border-green-200" style="background-color: #d1fae5; color: #10b981; border-color: #a7f3d0;">
            ✓
        </div>
        
        <h1 class="mb-4 text-3xl">Thank You for Your Order!</h1>
        <p class="text-soft-brown text-lg mb-8">We've received your order and are getting it ready to ship.</p>
        
        <div class="bg-beige p-6 rounded-lg mb-8 text-left border border-sand">
            <h3 class="text-base mb-4 border-b border-sand pb-2">Order Details</h3>
            <div class="flex justify-between mb-2 text-sm">
                <span class="text-soft-brown">Order Number:</span>
                <span class="font-medium text-dark">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="flex justify-between mb-2 text-sm">
                <span class="text-soft-brown">Date:</span>
                <span class="font-medium text-dark"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-soft-brown">Total Amount:</span>
                <span class="font-medium text-terracotta"><?= formatPrice($order['total_amount']) ?></span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/shop.php" class="btn btn-primary px-8">Continue Shopping</a>
            <a href="/dashboard.php" class="btn btn-outline px-8">View Order History</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
