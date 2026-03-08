<?php
// await_payment.php
require_once __DIR__ . '/includes/header.php';
requireLogin();

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$lencoId = isset($_GET['lenco_id']) ? sanitize($_GET['lenco_id']) : '';

if (!$orderId || !$lencoId) {
    redirect('/shop.php');
}

// Ensure order belongs to user and is pending
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
$stmt->execute([$orderId, getCurrentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/dashboard.php'); // or order_success if already done
}
?>

<div class="await-payment-container py-20 px-4">
    <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-xl overflow-hidden text-center p-10 border border-sand">
        <!-- Spinner Section -->
        <div class="mb-8 relative">
            <div class="w-24 h-24 border-4 border-sand border-t-terracotta rounded-full animate-spin mx-auto"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-terracotta font-bold text-xl">K</span>
            </div>
        </div>

        <h2 class="text-3xl font-black text-dark mb-4">Awaiting Payment...</h2>
        <p class="text-soft-brown text-lg mb-8">
            We've sent a payment prompt to your phone. Please enter your <span class="font-bold text-dark">Mobile Money PIN</span> to authorize the transaction of <span class="font-bold text-terracotta"><?= formatPrice($order['total_amount']) ?></span>.
        </p>

        <div class="bg-sand bg-opacity-30 rounded-xl p-6 mb-8 text-left">
            <h3 class="text-sm font-bold text-soft-brown uppercase tracking-wider mb-2">Instructions</h3>
            <ul class="text-sm text-dark space-y-2">
                <li class="flex items-start gap-2">
                    <span class="bg-terracotta text-white w-5 h-5 rounded-full flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">1</span>
                    Check your mobile phone for the STK push notification.
                </li>
                <li class="flex items-start gap-2">
                    <span class="bg-terracotta text-white w-5 h-5 rounded-full flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">2</span>
                    Review the payment details and enter your secret PIN.
                </li>
                <li class="flex items-start gap-2">
                    <span class="bg-terracotta text-white w-5 h-5 rounded-full flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">3</span>
                    Once approved, this page will refresh automatically.
                </li>
            </ul>
        </div>

        <p class="text-xs text-soft-brown italic">
            Do not refresh or close this window. We are verifying your transaction in real-time...
        </p>
    </div>
</div>

<script>
    const orderId = <?= json_encode($orderId) ?>;
    const lencoId = <?= json_encode($lencoId) ?>;
    
    function checkStatus() {
        fetch(`verify_payment.php?order_id=${orderId}&lenco_id=${lencoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = `order_success.php?id=${orderId}`;
                } else if (data.status === 'failed') {
                    alert('Payment was declined or failed. Please try again.');
                    window.location.href = 'checkout.php';
                } else if (data.status === 'error') {
                    console.error('Gateway Error:', data.message);
                    // Continue polling in case it's a transient network error
                    setTimeout(checkStatus, 3000);
                } else {
                    // Still waiting
                    setTimeout(checkStatus, 3000);
                }
            })
            .catch(err => {
                console.error('Polling error:', err);
                setTimeout(checkStatus, 3000);
            });
    }

    // Start polling after 2 seconds
    setTimeout(checkStatus, 2000);
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
