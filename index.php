<?php
// index.php
require_once __DIR__ . '/includes/header.php';

// Fetch all products - limited to a reasonable number for the front page
$stmt = $pdo->prepare("SELECT * FROM products ORDER BY created_at DESC");
$stmt->execute();
$allProducts = $stmt->fetchAll();

// Fetch categories - Get up to 10 to demonstrate carousel
$catStmt = $pdo->prepare("SELECT * FROM categories ORDER BY RAND() LIMIT 10");
$catStmt->execute();
$categories = $catStmt->fetchAll();
?>

<!-- Hero Section (30% Height Carousel) -->
<section class="hero-carousel-section mb-24">
    <div class="carousel-container relative overflow-hidden group" style="height: 30vh; position: relative; overflow: hidden;">
        <div class="carousel-track flex transition-transform duration-700 ease-in-out" style="display: flex; height: 100%; transition: transform 0.7s ease-in-out;">
            <!-- Slide 1 -->
            <div class="carousel-slide relative h-full" style="min-width: 100%; position: relative; height: 100%;">
                <div class="absolute inset-0 bg-cover bg-center" style="position: absolute; inset: 0; background-size: cover; background-position: center; background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('/assets/images/editorial.png');"></div>
                <div class="container relative z-10 flex items-center justify-center" style="position: relative; z-index: 10; height: 100%; display: flex; align-items: center; justify-content: center;">
                    <h2 class="text-white text-3xl md:text-5xl font-serif italic tracking-tighter text-center" style="color: white; font-style: italic; text-align: center; margin-bottom: 0;">New Season <br> Styles</h2>
                </div>
            </div>
            <!-- Slide 2 -->
            <div class="carousel-slide relative h-full" style="min-width: 100%; position: relative; height: 100%;">
                <div class="absolute inset-0 bg-cover bg-center" style="position: absolute; inset: 0; background-size: cover; background-position: center; background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('/uploads/linen-shirt.jpg');"></div>
                <div class="container relative z-10 flex items-center justify-center" style="position: relative; z-index: 10; height: 100%; display: flex; align-items: center; justify-content: center;">
                    <h2 class="text-white text-3xl md:text-5xl font-serif italic tracking-tighter text-center" style="color: white; font-style: italic; text-align: center; margin-bottom: 0;">Timeless <br> Essentials</h2>
                </div>
            </div>
            <!-- Slide 3 -->
            <div class="carousel-slide relative h-full" style="min-width: 100%; position: relative; height: 100%;">
                <div class="absolute inset-0 bg-cover bg-center" style="position: absolute; inset: 0; background-size: cover; background-position: center; background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('/uploads/silk-blouse.jpg');"></div>
                <div class="container relative z-10 flex items-center justify-center" style="position: relative; z-index: 10; height: 100%; display: flex; align-items: center; justify-content: center;">
                    <h2 class="text-white text-3xl md:text-5xl font-serif italic tracking-tighter text-center" style="color: white; font-style: italic; text-align: center; margin-bottom: 0;">Effortless <br> Elegance</h2>
                </div>
            </div>
        </div>
        
        <!-- Hero Navigation Arrows -->
        <button id="hero-prev" class="absolute left-6 top-1/2 -translate-y-1/2 z-30 w-12 h-12 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white flex items-center justify-center hover:bg-white hover:text-dark transition-all duration-300 transform hover:scale-110 opacity-0 group-hover:opacity-100 hidden md:flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button id="hero-next" class="absolute right-6 top-1/2 -translate-y-1/2 z-30 w-12 h-12 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white flex items-center justify-center hover:bg-white hover:text-dark transition-all duration-300 transform hover:scale-110 opacity-0 group-hover:opacity-100 hidden md:flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </button>

        <!-- Navigation Dots -->
        <div class="absolute flex gap-3 z-30" style="position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%); display: flex; gap: 12px; z-index: 30;">
            <button class="carousel-dot" style="width: 8px; height: 8px; border-radius: 50%; border: none; background: white; cursor: pointer; transition: all 0.3s; opacity: 1; transform: scale(1.2);"></button>
            <button class="carousel-dot" style="width: 8px; height: 8px; border-radius: 50%; border: none; background: white; opacity: 0.5; cursor: pointer; transition: all 0.3s;"></button>
            <button class="carousel-dot" style="width: 8px; height: 8px; border-radius: 50%; border: none; background: white; opacity: 0.5; cursor: pointer; transition: all 0.3s;"></button>
        </div>
    </div>
</section>

<!-- Category Cards Section (Carousel) -->
<section class="py-24 bg-white overflow-hidden">
    <div class="container relative">
        <div class="flex justify-between items-end mb-12">
            <div class="reveal">
                <h2 class="text-sm font-extrabold uppercase tracking-[0.2em] mb-0">Shop by Category</h2>
            </div>
            <!-- Carousel Controls -->
            <div class="flex gap-3">
                <button id="cat-prev" class="w-12 h-12 rounded-full border border-sand bg-white flex items-center justify-center hover:bg-dark hover:text-white hover:border-dark transition-all duration-300 shadow-sm hover:shadow-md group">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-transform group-hover:-translate-x-0.5"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button id="cat-next" class="w-12 h-12 rounded-full border border-sand bg-white flex items-center justify-center hover:bg-dark hover:text-white hover:border-dark transition-all duration-300 shadow-sm hover:shadow-md group">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-transform group-hover:translate-x-0.5"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>
        
        <div class="cat-carousel-track-wrapper" style="overflow: hidden;">
            <div id="cat-track" class="flex gap-6 transition-transform duration-500 ease-in-out" style="display: flex; gap: 24px; transition: transform 0.5s ease-in-out;">
                <?php foreach($categories as $category): ?>
                    <a href="/shop.php?category=<?= urlencode($category['slug']) ?>" class="group block shrink-0 cat-card" style="flex: 0 0 auto;">
                        <div class="relative aspect-[1/1] overflow-hidden rounded-2xl mb-4 bg-beige shadow-sm" style="position: relative; aspect-ratio: 1/1; overflow: hidden; border-radius: 16px; margin-bottom: 16px;">
                            <img src="<?= $category['image'] ? '/uploads/' . htmlspecialchars($category['image']) : '/assets/images/placeholder.jpg' ?>" 
                                 alt="<?= htmlspecialchars($category['name']) ?>" 
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                 style="width: 100%; h-full; object-fit: cover;"
                                 loading="lazy">
                            <div class="absolute inset-0 bg-black/5 group-hover:bg-black/0 transition-colors"></div>
                        </div>
                        <h3 class="text-center text-[0.7rem] font-bold uppercase tracking-widest m-0" style="text-align: center; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; margin: 0;"><?= htmlspecialchars($category['name']) ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- All Products Section -->
<section class="py-24 bg-white w-full flex justify-center mt-12" style="display: flex; justify-content: center;">
    <div class="container mx-auto">
        <div class="text-center mb-16 reveal flex flex-col items-center justify-center mx-auto" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <h2 class="text-2xl font-extrabold uppercase tracking-tight mb-2 text-center">Our Catalog</h2>
            <p class="text-xs text-soft-brown uppercase tracking-widest text-center">Exploration of our complete collection</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8 featured-products" style="display: grid; gap: 32px; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
            <?php foreach($allProducts as $product): ?>
            <div class="product-card reveal">
                <div class="relative product-img-rounded mb-4 shadow-sm group" style="position: relative; margin-bottom: 16px;">
                    <a href="/product.php?id=<?= $product['id'] ?>" class="block overflow-hidden" style="display: block; overflow: hidden; border-radius: 12px;">
                        <img src="<?= $product['image'] ? '/uploads/' . htmlspecialchars($product['image']) : '/assets/images/placeholder.jpg' ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="w-full object-cover transition-transform duration-700 group-hover:scale-110"
                             style="width: 100%; aspect-ratio: 4/5; object-fit: cover;"
                             loading="lazy">
                    </a>
                    <div class="wishlist-btn wishlist-toggle <?= isInWishlist($product['id']) ? 'active' : '' ?> transition" 
                         data-id="<?= $product['id'] ?>"
                         style="position: absolute; top: 12px; right: 12px; z-index: 10; cursor: pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="<?= isInWishlist($product['id']) ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>
                    </div>
                </div>
                <div class="px-1 text-left">
                    <div class="flex justify-between items-start" style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <h3 class="text-[0.75rem] font-bold uppercase tracking-tight mb-0" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0;"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="text-[0.75rem] font-bold" style="font-size: 0.75rem; font-weight: 700;">K<?= number_format($product['price'], 2) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-12" style="text-align: center; margin-top: 48px;">
            <a href="/shop.php" class="btn btn-rounded bg-dark text-white px-10 text-[0.6rem]">Browse Shop</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Hero Carousel
    const heroTrack = document.querySelector('.hero-carousel-section .carousel-track');
    const heroDots = Array.from(document.querySelectorAll('.hero-carousel-section .carousel-dot'));
    let heroIndex = 0;

    const updateHero = (index) => {
        heroTrack.style.transform = `translateX(-${index * 100}%)`;
        heroDots.forEach((dot, i) => {
            dot.style.opacity = i === index ? '1' : '0.5';
            dot.style.transform = i === index ? 'scale(1.2)' : 'scale(1)';
        });
        heroIndex = index;
    };

    heroDots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            updateHero(index);
            resetAutoPlay();
        });
    });

    const nextHero = () => {
        heroIndex = (heroIndex + 1) % heroDots.length;
        updateHero(heroIndex);
    };

    const prevHero = () => {
        heroIndex = (heroIndex - 1 + heroDots.length) % heroDots.length;
        updateHero(heroIndex);
    };

    document.getElementById('hero-next').addEventListener('click', () => {
        nextHero();
        resetAutoPlay();
    });

    document.getElementById('hero-prev').addEventListener('click', () => {
        prevHero();
        resetAutoPlay();
    });

    let autoPlay = setInterval(nextHero, 5000);

    const resetAutoPlay = () => {
        clearInterval(autoPlay);
        autoPlay = setInterval(nextHero, 5000);
    };

    // Category Carousel
    const catTrack = document.getElementById('cat-track');
    const next = document.getElementById('cat-next');
    const prev = document.getElementById('cat-prev');
    let scrollPos = 0;

    const getScrollParams = () => {
        const firstCard = document.querySelector('.cat-card');
        if (!firstCard) return { cardWidth: 0, maxScroll: 0 };
        const cardWidth = firstCard.offsetWidth + 24; // Width + Gap
        const maxScroll = catTrack.scrollWidth - catTrack.parentElement.offsetWidth;
        return { cardWidth, maxScroll };
    };

    next.addEventListener('click', () => {
        const { cardWidth, maxScroll } = getScrollParams();
        scrollPos = Math.min(scrollPos + cardWidth, maxScroll);
        catTrack.style.transform = `translateX(-${scrollPos}px)`;
    });

    prev.addEventListener('click', () => {
        const { cardWidth } = getScrollParams();
        scrollPos = Math.max(scrollPos - cardWidth, 0);
        catTrack.style.transform = `translateX(-${scrollPos}px)`;
    });

    // Wishlist Toggle Logic
    document.querySelectorAll('.wishlist-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const icon = this.querySelector('svg');
            
            fetch('/ajax/wishlist_toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.action === 'added') {
                        this.classList.add('active');
                        icon.setAttribute('fill', 'currentColor');
                    } else {
                        this.classList.remove('active');
                        icon.setAttribute('fill', 'none');
                    }
                    
                    // Show a quick toast
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-4 right-4 bg-terracotta text-white p-4 rounded shadow-lg z-[9999] reveal';
                    toast.innerText = data.message;
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);
                } else {
                    alert(data.message);
                }
            })
            .catch(err => console.error('Wishlist error:', err));
        });
    });
});
</script>

<style>
.wishlist-toggle.active { color: #ff4757 !important; }
.wishlist-toggle:hover { color: #ff4757; }
/* Category width logic */
.cat-card {
    width: calc((100% - (4 * 24px)) / 5);
}

@media (max-width: 1024px) {
    .cat-card { width: calc((100% - (2 * 24px)) / 3.2); }
}

@media (max-width: 640px) {
    .cat-card { width: calc((100% - (1 * 16px)) / 2.2); }
}

.carousel-dot { background: white; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
