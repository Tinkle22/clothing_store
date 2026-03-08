<?php
// includes/functions.php

/**
 * Sanitize user input to prevent XSS
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect and exit
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Format price
 */
function formatPrice($price) {
    return 'K' . number_format((float)$price, 2, '.', '');
}

/**
 * Generate a friendly URL slug
 */
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Display toast notification (sets session flash data)
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info'
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        
        $type = sanitize($flash['type']);
        $message = sanitize($flash['message']);
        
        // CSS classes based on type (will integrate with warm aesthetic)
        $colors = [
            'success' => 'bg-terracotta text-white',
            'error' => 'bg-red-500 text-white',
            'info' => 'bg-sand text-dark'
        ];
        
        $colorClass = $colors[$type] ?? $colors['info'];
        
        echo "<div id='toast' class='fixed bottom-4 right-4 p-4 rounded shadow-md z-50 transition-opacity duration-300 $colorClass'>";
        echo $message;
        echo "</div>";
        echo "<script>setTimeout(() => { document.getElementById('toast').style.opacity = '0'; setTimeout(() => document.getElementById('toast').remove(), 300); }, 3000);</script>";
    }
}
/**
 * Check if a product is in the user's wishlist
 */
function isInWishlist($productId) {
    if (!isLoggedIn()) return false;
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([getCurrentUserId(), $productId]);
    return (bool)$stmt->fetch();
}

/**
 * Get all products in a user's wishlist
 */
function getUserWishlist($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM products p 
        JOIN wishlist w ON p.id = w.product_id 
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
