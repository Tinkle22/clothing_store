<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Get cart count
$cartCount = 0;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $result = $stmt->fetch();
    $cartCount = $result['total'] ? (int)$result['total'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aura | Modern Clothing Boutique</title>
    <!-- Google Fonts: Inter & Playfair Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    
    <!-- Flash Messages -->
    <?php displayFlashMessage(); ?>

    <div class="menu-drawer">
        <ul>
            <li><a href="/index.php">Home</a></li>
            <li><a href="/shop.php">Shop All</a></li>
            <li><a href="/shop.php?category=tops">Tops</a></li>
            <li><a href="/shop.php?category=bottoms">Bottoms</a></li>
            <li><a href="/shop.php?category=outerwear">Outerwear</a></li>
            <li class="mt-8 pt-8 border-t border-sand">
                <?php if(isLoggedIn()): ?>
                    <a href="<?= getRedirectPath() ?>" class="text-sm font-bold text-soft-brown">Dashboard</a>
                <?php else: ?>
                    <a href="/login.php" class="text-sm font-bold text-soft-brown">Login</a>
                <?php endif; ?>
            </li>
        </ul>
    </div>
    <div class="overlay"></div>

    <header class="header">
        <div class="container flex justify-between items-center relative">
            
            <div class="header-left">
                <div class="hamburger" id="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>

            <a href="/index.php" class="logo">aura</a>

            <div class="header-action">

                <?php if(isLoggedIn()): ?>
                    <a href="<?= getRedirectPath() ?>" class="hidden md:block">My Account</a>
                <?php else: ?>
                    <a href="/login.php" class="hidden md:block">Login</a>
                <?php endif; ?>
                
                <a href="/cart.php" class="cart-icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    <?php if($cartCount > 0): ?>
                        <span class="cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </div>

        </div>
    </header>

    <main>
