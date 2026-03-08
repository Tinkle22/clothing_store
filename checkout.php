<?php
// checkout.php
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId = getCurrentUserId();

// Fetch Cart Items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    setFlashMessage('info', 'Your cart is empty.');
    redirect('/shop.php');
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 0;
$taxes = 0;
$total = $subtotal;

// Fetch User Address Details
$stmtUser = $pdo->prepare("SELECT name, email, address FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// Split name into first and last for the form
$nameParts = explode(' ', $user['name'] ?? '', 2);
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[1] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstNamePost = sanitize($_POST['first_name'] ?? '');
    $lastNamePost = sanitize($_POST['last_name'] ?? '');
    $fullName = trim($firstNamePost . ' ' . $lastNamePost);
    
    $deliveryType = sanitize($_POST['delivery_type'] ?? 'pickup');
    $phoneNumber = sanitize(($_POST['country_code'] ?? '+260') . ' ' . ($_POST['phone'] ?? ''));
    
    $addressInfo = sanitize($_POST['address'] ?? ''); // Maps to Street Address
    if (isset($_POST['area'])) $addressInfo .= ', ' . sanitize($_POST['area']);
    if (isset($_POST['city'])) $addressInfo .= ', ' . sanitize($_POST['city']);
    if (isset($_POST['province'])) $addressInfo .= ', ' . sanitize($_POST['province']);
    
    if (empty($firstNamePost) || empty($_POST['city'])) {
        $error = "Please fill in all required shipping details.";
    } else {
        // Shipping and taxes are 0
        $shipping = 0;
        $taxes = 0;
        $total = $subtotal;
        
        try {
            $pdo->beginTransaction();

            // 1. Create Order
            // Setup for Lecno by Broadpay dummy integration
            $reference = uniqid('LECNO_');
            
            $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, phone, total_amount, status, delivery_type, shipping_address) VALUES (?, ?, ?, 'pending', ?, ?)");
            $orderStmt->execute([$userId, $phoneNumber, $total, $deliveryType, $addressInfo]);
            $orderId = $pdo->lastInsertId();

            // 2. Create Order Items and Update Stock
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, size) VALUES (?, ?, ?, ?, ?)");
            $updateStockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($cartItems as $item) {
                // Insert item
                $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price'], $item['size']]);
                // Reduce stock
                $updateStockStmt->execute([$item['quantity'], $item['product_id']]);
            }

            // 3. Clear Cart
            $clearStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearStmt->execute([$userId]);

            // 4. Update user's default address if they don't have one
            if (empty($user['address'])) {
                $updateUserStmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
                $updateUserStmt->execute([$addressInfo, $userId]);
            }

            $pdo->commit();
            
            // Lenco by Broadpay Payment Gateway API Integration
            $lencoConfig = require __DIR__ . '/config/lenco.php';
            $lencoPublicKey = $lencoConfig['public_key']; 
            $lencoSecretKey = $lencoConfig['secret_key']; 
            
            // The URL to return back to after successful/failed payment
            $returnUrl = 'http://' . $_SERVER['HTTP_HOST'] . "/order_success.php?id=$orderId&ref=$reference";

            // Determine operator from phone number prefix (Zambian prefixes)
            $phoneNum = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
            $operator = 'mtn'; // default
            if (preg_match('/^(?:260|0)?(97|77)/', $phoneNum)) {
                $operator = 'airtel';
            } elseif (preg_match('/^(?:260|0)?(96|76)/', $phoneNum)) {
                $operator = 'mtn';
            }

            // Payment Payload structure for Lenco V2 Mobile Money collection
            $paymentData = [
                'amount' => (float)$total,
                'reference' => $reference,
                'phone' => $_POST['phone'] ?? '',
                'operator' => $operator,
                'country' => 'zm',
                'bearer' => 'customer'
            ];

            // Since the local PHP lacks OpenSSL/cURL extensions, fallback to system curl.exe
            // Write JSON to a temp file because Windows command-line escapes JSON double-quotes improperly
            $targetUrl = trim($lencoConfig['base_url']) . '/collections/mobile-money';
            $payloadFile = tempnam(sys_get_temp_dir(), 'lenco_');
            file_put_contents($payloadFile, json_encode($paymentData));
            
            $headerAuth = escapeshellarg("Authorization: Bearer " . $lencoSecretKey);
            $headerContent = escapeshellarg("Content-Type: application/json");
            $headerAccept = escapeshellarg("Accept: application/json");
            $headerUserAgent = escapeshellarg("User-Agent: AuraStore/1.0 Fallback-cURL");
            
            $cmd = "curl.exe -s -X POST $targetUrl -H $headerAuth -H $headerContent -H $headerAccept -H $headerUserAgent -d @" . escapeshellarg($payloadFile);
            
            if (!$lencoConfig['verify_ssl']) {
                $cmd .= " -k"; // Insecure mode for local dev
            }
            
            $response = shell_exec($cmd);
            @unlink($payloadFile); // Clean up temp file
            
            if ($response === null || $response === false) {
                throw new Exception("Payment gateway connection error: Failed to execute system cURL.");
            }

            $result = json_decode($response, true);
            
            // Check for successful STK push initiation
            if (isset($result['status']) && $result['status'] === true) {
                $lencoCollectionId = $result['data']['id'];
                setFlashMessage('success', 'Follow the instructions on your phone.');
                redirect("/await_payment.php?order_id=$orderId&lenco_id=$lencoCollectionId");
            } else {
                // Return the actual response from Broadpay on error
                $errorDetails = is_array($result) && isset($result['message']) ? $result['message'] : $response;
                error_log("Lenco API Error: " . $response);
                throw new Exception('Payment gateway error: ' . $errorDetails);
            }
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}
?>

<link rel="stylesheet" href="/assets/css/checkout.css">

<div class="checkout-page">
    <!-- Processing Overlay -->
    <div id="processing-overlay" style="display: none; position: fixed; inset: 0; background: rgba(255,255,255,0.9); z-index: 9999; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="w-16 h-16 border-4 border-sand border-t-terracotta rounded-full animate-spin mb-4"></div>
        <h3 class="text-xl font-black text-dark">Contacting Gateway...</h3>
        <p class="text-soft-brown">Please wait while we secure your transaction.</p>
    </div>
    
    <?php if (isset($error)): ?>
        <div style="background: #fee2e2; color: #ef4444; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #fca5a5;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="/checkout.php" method="POST" id="checkout-form">
        <div class="checkout-layout">
            
            <!-- Forms (Left Column) -->
            <div class="checkout-main">
                
                <div class="checkout-section">
                    <h3 class="checkout-section-title">Shipping Address</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name<span>*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($firstName) ?>" required placeholder="Chanda">
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name<span>*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($lastName) ?>" required placeholder="Mulenga">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="form-label">Email<span>*</span></label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required placeholder="chanda@example.zm">
                        </div>
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone number<span>*</span></label>
                            <div class="phone-input-group">
                                <select name="country_code" aria-label="Country Code">
                                    <option value="ZM">ZM (+260) ⌄</option>
                                    <option value="ZA">ZA (+27) ⌄</option>
                                    <option value="MW">MW (+265) ⌄</option>
                                </select>
                                <input type="tel" id="phone" name="phone" required placeholder="097 1234567">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city" class="form-label">City/Town<span>*</span></label>
                            <input type="text" id="city" name="city" class="form-control" required placeholder="Lusaka">
                        </div>
                        <div class="form-group">
                            <label for="province" class="form-label">Province<span>*</span></label>
                            <input type="text" id="province" name="province" class="form-control" required placeholder="Lusaka Province">
                        </div>
                        <div class="form-group">
                            <label for="area" class="form-label">Area/Township<span>*</span></label>
                            <input type="text" id="area" name="area" class="form-control" required placeholder="Kabulonga">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="address" class="form-label">Street Address<span>*</span></label>
                        <textarea id="address" name="address" class="form-control" required placeholder="123 Independence Ave, House #..."></textarea>
                    </div>
                </div>

                <div class="checkout-section">
                    <h3 class="checkout-section-title">Delivery Type</h3>
                    <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="document.getElementById('type_pickup').checked = true;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="radio" name="delivery_type" id="type_pickup" value="pickup" checked style="accent-color: #000; width: 16px; height: 16px;">
                            <div>
                                <label for="type_pickup" style="font-weight: 600; font-size: 0.95rem; cursor: pointer; display: block;">Self-Pickup</label>
                                <span style="font-size: 0.75rem; color: #666;">Collect from our Lusaka Showroom</span>
                            </div>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem;">FREE</div>
                    </div>
                    <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="document.getElementById('type_yango').checked = true;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="radio" name="delivery_type" id="type_yango" value="yango" style="accent-color: #000; width: 16px; height: 16px;">
                            <div>
                                <label for="type_yango" style="font-weight: 600; font-size: 0.95rem; cursor: pointer; display: block;">Yango Delivery</label>
                                <span style="font-size: 0.75rem; color: #666;">Flash delivery across Lusaka</span>
                            </div>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem;">K25.00</div>
                    </div>
                </div>

                <div class="checkout-section">
                    <h3 class="checkout-section-title">Payment Method</h3>
                    <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.5rem; background: #fafafa;">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                            <input type="radio" checked style="accent-color: #000; width: 16px; height: 16px;">
                            <span style="font-weight: 600; font-size: 1rem;">Lecno by Broadpay</span>
                        </div>
                        <p style="font-size: 0.85rem; color: #666; margin: 0 0 0 2rem; line-height: 1.5;">You will be redirected to the secure Lecno gateway to complete your payment.</p>
                    </div>
                </div>

            </div>

            <!-- Order Summary (Right Column) -->
            <div class="checkout-summary">
                <h3 class="checkout-summary-title">Your Cart</h3>
                
                <div class="summary-items">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="summary-item">
                        <div class="summary-item-left">
                            <div class="summary-item-image">
                                <span class="summary-item-qty"><?= $item['quantity'] ?></span>
                                <img src="<?= $item['image'] ? '/uploads/' . htmlspecialchars($item['image']) : '/assets/images/placeholder.jpg' ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            </div>
                            <div class="summary-item-details">
                                <h4 class="summary-item-title"><?= htmlspecialchars($item['name']) ?></h4>
                                <p class="summary-item-variant">Men's Black</p>
                            </div>
                        </div>
                        <div class="summary-item-price">
                            <?= formatPrice($item['price'] * $item['quantity']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="discount-code">
                    <input type="text" placeholder="Discount code">
                    <button type="button">Apply</button>
                </div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span style="font-weight: 500; color: #111;"><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="summary-row" style="display: none;">
                        <span>Shipping</span>
                        <span style="font-weight: 500; color: #111;"><?= formatPrice($shipping) ?></span>
                    </div>
                    <div class="summary-row" style="display: none;">
                        <span>Estimated taxes ?</span>
                        <span style="font-weight: 500; color: #111;"><?= formatPrice($taxes) ?></span>
                    </div>
                </div>

                <div class="summary-row total">
                    <span>Total</span>
                    <span><?= formatPrice($total) ?></span>
                </div>

                <button type="submit" class="submit-btn" style="margin-top: 2rem;">Continue to Payment</button>
            </div>

        </div>
    </form>
</div>

<script>
    const subtotal = <?= (float)$subtotal ?>;
    const taxes = <?= (float)$taxes ?>;
    const shippingEl = document.querySelector('.summary-row:nth-child(2) span:last-child');
    const totalEl = document.querySelector('.summary-row.total span:last-child');
    
    function updateTotals() {
        const shipping = 0;
        const total = subtotal + taxes; // taxes is 0 anyway
        
        shippingEl.innerText = 'K' + shipping.toFixed(2);
        totalEl.innerText = 'K' + total.toFixed(2);
    }

    document.querySelectorAll('input[name="delivery_type"]').forEach(radio => {
        radio.addEventListener('change', updateTotals);
    });

    document.getElementById('checkout-form').addEventListener('submit', function() {
        document.getElementById('processing-overlay').style.display = 'flex';
        this.querySelector('.submit-btn').disabled = true;
    });
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

