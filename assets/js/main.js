// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // Menu Toggle (Drawer + Overlay)
    const hamburger = document.getElementById('hamburger');
    const drawer = document.querySelector('.menu-drawer');
    const overlay = document.querySelector('.overlay');

    if (hamburger && drawer && overlay) {
        const toggleMenu = () => {
            drawer.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = drawer.classList.contains('active') ? 'hidden' : '';
        };

        hamburger.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    }

    // Enhance images with smooth loading
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.5s ease-in-out';
        
        if (img.complete) {
            img.style.opacity = '1';
        } else {
            img.addEventListener('load', () => {
                img.style.opacity = '1';
            });
        }
    });

    // Form submission styling
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if(submitBtn) {
                setTimeout(() => {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.7';
                }, 10);
            }
        });
    });
    // Intersection Observer for reveal animations
    const revealElements = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
                entry.target.classList.add('visible'); // Optional: for CSS-only logic
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    revealElements.forEach(el => {
        el.style.opacity = '0'; // Ensure it's hidden initially
        el.style.animationPlayState = 'paused';
        revealObserver.observe(el);
    });
});

