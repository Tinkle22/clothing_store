<?php
// ajax/wishlist_toggle.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to use the wishlist.']);
    exit;
}

$productId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$userId = getCurrentUserId();

if ($productId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
    exit;
}

try {
    // Check if it's already in wishlist
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove it
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ?");
        $stmt->execute([$existing['id']]);
        echo json_encode(['status' => 'success', 'action' => 'removed', 'message' => 'Removed from wishlist.']);
    } else {
        // Add it
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Added to wishlist.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
