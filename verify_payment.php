<?php
// verify_payment.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Ensure user is logged in for this request
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// This is an AJAX endpoint
header('Content-Type: application/json');

if (!isset($_GET['order_id']) || !isset($_GET['lenco_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$orderId = (int)$_GET['order_id'];
$lencoId = $_GET['lenco_id'];

// 1. Fetch Order from DB
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, getCurrentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Order record not found']);
    exit;
}

// If already processing or delivered, redirect to success immediately
if ($order['status'] !== 'pending') {
    echo json_encode(['status' => 'success', 'payment_status' => $order['status'], 'already_done' => true]);
    exit;
}

// 2. Call Lenco API to check status
$lencoConfig = require __DIR__ . '/config/lenco.php';
$lencoSecretKey = $lencoConfig['secret_key'];

$targetUrl = trim($lencoConfig['base_url']) . '/collections/' . $lencoId;

$headerAuth = escapeshellarg("Authorization: Bearer " . $lencoSecretKey);
$headerAccept = escapeshellarg("Accept: application/json");
$headerUserAgent = escapeshellarg("User-Agent: AuraStore/1.0 Verify-cURL");

$cmd = "curl.exe -s -X GET " . escapeshellarg($targetUrl) . " -H $headerAuth -H $headerAccept -H $headerUserAgent";

if (!$lencoConfig['verify_ssl']) {
    $cmd .= " -k";
}

$response = shell_exec($cmd);

// Remove potential BOM or whitespace from curl output
$cleanResponse = trim($response);
$result = json_decode($cleanResponse, true);

if ($result === null) {
    echo json_encode(['status' => 'waiting', 'message' => 'Waiting for gateway response...', 'raw' => $response]);
    exit;
}

if (isset($result['status']) && $result['status'] === true && isset($result['data']['status'])) {
    $rawStatus = $result['data']['status'];
    $paymentStatus = strtolower(trim($rawStatus));

    // Typical Lenco/Broadpay success statuses: 'successful', 'success', 'completed', 'paid', 'settled', 'charged'
    $successStates = ['success', 'successful', 'completed', 'paid', 'cleared', 'settled', 'charged'];
    $failedStates = ['failed', 'cancelled', 'declined', 'expired', 'insufficient_funds', 'timeout'];

    if (in_array($paymentStatus, $successStates)) {
        // Update Order to processing
        $updateStmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
        $updateStmt->execute([$orderId]);
        
        echo json_encode(['status' => 'success', 'payment_status' => $rawStatus]);
        exit;
    } elseif (in_array($paymentStatus, $failedStates)) {
        // Update Order to cancelled
        $updateStmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $updateStmt->execute([$orderId]);
        
        echo json_encode(['status' => 'failed', 'payment_status' => $rawStatus, 'message' => 'Transaction failed: ' . $rawStatus]);
        exit;
    } else {
        // Still pending (likely 'pending', 'started', or 'processing')
        echo json_encode(['status' => 'waiting', 'payment_status' => $rawStatus]);
        exit;
    }
} elseif (isset($result['status']) && is_string($result['status']) && in_array(strtolower($result['status']), ['success', 'completed', 'successful'])) {
    // Some versions of the API return the status directly in the top-level 'status' field as a string
    $updateStmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
    $updateStmt->execute([$orderId]);
    
    echo json_encode(['status' => 'success', 'payment_status' => $result['status']]);
    exit;
} else {
    // If we're here, the response structure didn't match. 
    // Return original response for debugging in the browser console.
    echo json_encode([
        'status' => 'waiting', 
        'message' => 'Querying gateway...', 
        'payment_status' => 'Unknown',
        'debug_raw' => $result
    ]);
    exit;
}
