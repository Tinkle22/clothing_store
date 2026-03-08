<?php
// order_details.php
require_once __DIR__ . '/includes/header.php';
requireLogin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = getCurrentUserId();

// Verify order belongs to user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Order not found.');
    redirect('/dashboard.php');
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

<div class="container py-20 min-h-[80vh]">
    <div class="reveal">
        <!-- Back Link -->
        <div class="mb-16">
            <a href="/dashboard.php" class="inline-flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-soft-brown hover:text-dark transition-all group">
                <span class="transform transition-transform group-hover:-translate-x-1">&larr;</span> 
                Back to Order History
            </a>
        </div>

        <!-- Order Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-20">
            <div>
                <div class="flex items-center gap-4 mb-3">
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em] px-3 py-1 bg-dark text-white rounded-full">Order Details</span>
                    <p class="m-0 text-xs font-medium text-soft-brown">Placed on <?= date('F j, Y', strtotime($order['created_at'])) ?></p>
                </div>
                <h1 class="text-5xl md:text-6xl font-serif font-bold tracking-tight">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
            </div>
            
            <div class="mt-8 md:mt-0 text-right">
                <p class="text-xs uppercase tracking-widest font-bold text-soft-brown mb-2">Fulfillment Status</p>
                <div class="flex items-center gap-3 justify-end">
                    <span class="w-3 h-3 rounded-full <?= $order['status'] === 'delivered' ? 'bg-green-500' : ($order['status'] === 'cancelled' ? 'bg-red-500' : 'bg-terracotta') ?>"></span>
                    <span class="text-2xl font-serif font-bold italic capitalize"><?= htmlspecialchars($order['status']) ?></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-20">
            
            <!-- Items Section -->
            <div class="lg:col-span-8">
                <h3 class="text-xs font-bold uppercase tracking-widest mb-10 pb-4 border-b border-sand">Items Ordered</h3>
                
                <div class="flex flex-col gap-12">
                    <?php foreach ($items as $item): ?>
                    <div class="flex flex-col sm:flex-row gap-8 items-start sm:items-center group">
                        <div class="w-full sm:w-32 aspect-[3/4] bg-beige overflow-hidden">
                            <img src="<?= $item['image'] ? '/uploads/' . htmlspecialchars($item['image']) : '/assets/images/placeholder.jpg' ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="w-full h-full object-cover grayscale-[0.2] group-hover:grayscale-0 transition-all duration-700">
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-lg font-bold mb-1 hover:text-accent transition-colors">
                                        <a href="/product.php?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                                    </h4>
                                    <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-soft-brown">
                                        <p class="m-0">Size: <span class="text-dark font-medium"><?= htmlspecialchars($item['size']) ?></span></p>
                                        <p class="m-0">Quantity: <span class="text-dark font-medium"><?= $item['quantity'] ?></span></p>
                                        <p class="m-0">Unit Price: <span class="text-dark font-medium"><?= formatPrice($item['price']) ?></span></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-serif font-bold italic"><?= formatPrice($item['price'] * $item['quantity']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-20 pt-10 border-t border-sand">
                    <div class="flex gap-4 items-center p-8 bg-beige/30 rounded-lg">
                        <div class="w-12 h-12 rounded-full border border-dark/10 flex items-center justify-center shrink-0">
                            <span class="text-xl"><?= $order['delivery_type'] === 'yango' ? '🚚' : '🏬' ?></span>
                        </div>
                        <div>
                            <p class="text-sm font-bold mb-1"><?= $order['delivery_type'] === 'yango' ? 'Yango Delivery to' : 'Selected Pickup Location' ?></p>
                            <p class="text-sm text-soft-brown leading-relaxed m-0 italic"><?= $order['delivery_type'] === 'yango' ? htmlspecialchars($order['shipping_address']) : 'Aura Showroom, Lusaka Central' ?></p>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-dark mt-3">Contact: <?= htmlspecialchars($order['phone']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="lg:col-span-4">
                <div class="sticky top-10">
                    <div class="bg-dark text-white p-12 rounded-2xl shadow-2xl relative overflow-hidden">
                        <!-- Abstract Design Element -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                        
                        <h3 class="text-xs font-bold uppercase tracking-widest text-white/40 mb-10 pb-4 border-b border-white/10">Order Total</h3>
                        
                        <div class="flex flex-col gap-6 mb-12">
                            <div class="flex justify-between items-center text-sm font-medium">
                                <span class="text-white/60">Subtotal</span>
                                <span><?= formatPrice($order['total_amount'] - ($order['delivery_type'] === 'yango' ? 25 : 0)) ?></span>
                            </div>
                            <div class="flex justify-between items-center text-sm font-medium">
                                <span class="text-white/60">Delivery Fee</span>
                                <span><?= $order['delivery_type'] === 'yango' ? 'K25.00' : 'FREE' ?></span>
                            </div>
                            <div class="flex justify-between items-center text-sm font-medium">
                                <span class="text-white/60">Handling & Tax</span>
                                <span>Included</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-end pt-10 border-t border-white/20">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-2">Total Paid</p>
                                <p class="text-5xl font-serif font-bold italic text-white leading-none"><?= formatPrice($order['total_amount']) ?></p>
                            </div>
                        </div>
                        
                        <div class="mt-12 pt-10 border-t border-white/10">
                            <p class="text-[9px] font-medium text-white/30 leading-relaxed uppercase tracking-widest">
                                Payment verified. All transactions are securely encrypted. For help with this order, please quote ID: #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 p-8 border border-sand rounded-2xl text-center">
                        <p class="text-xs font-bold uppercase tracking-widest mb-4">Need Help?</p>
                        <a href="mailto:support@aurastore.com" class="text-sm text-soft-brown hover:text-dark transition-colors block mb-2">support@aurastore.com</a>
                        <p class="text-[10px] text-soft-brown m-0">Available Mon-Fri 9am - 6pm</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Scoped styles for refinement */
.font-serif { font-family: 'Playfair Display', serif; }
.bg-beige\/30 { background-color: rgba(245, 245, 245, 0.3); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
