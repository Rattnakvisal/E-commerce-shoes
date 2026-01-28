<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Bar Slideshow</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ===============================
           Top Bar Container
        =============================== */
        .topbar {
            position: relative;
            height: 38px;
            background: linear-gradient(90deg, #0f172a, #111827);
            color: #fff;
            overflow: hidden;
            font-size: 14px;
            z-index: -1000;
        }

        /* ===============================
           Slide Base
        =============================== */
        .topbar-slide {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            opacity: 0;
            transform: translateY(-12px);
            transition:
                opacity 0.45s ease,
                transform 0.45s ease;

            white-space: nowrap;
            pointer-events: none;
        }

        /* Active slide */
        .topbar-slide.is-active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        /* Leaving slide (smooth exit) */
        .topbar-slide.is-leaving {
            opacity: 0;
            transform: translateY(12px);
        }

        /* Icon styling */
        .topbar-slide i {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Pause on hover */
        .topbar:hover {
            cursor: default;
        }

        /* Mobile fix */
        @media (max-width: 480px) {
            .topbar {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

    <!-- ===============================
     Top Bar Slideshow
=============================== -->
    <div id="topBar" class="topbar">
        <div class="topbar-slide is-active">
            <i class="fas fa-truck"></i>
            <span>Free Shipping on Orders Over $50</span>
        </div>

        <div class="topbar-slide">
            <i class="fas fa-tags"></i>
            <span>Holiday Sale — Up to 50% Off</span>
        </div>

        <div class="topbar-slide">
            <i class="fas fa-qrcode"></i>
            <span>Pay with QR & Get 10% Cashback</span>
        </div>

    </div>

    <!-- ===============================
     JavaScript
=============================== -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const slides = document.querySelectorAll('.topbar-slide');
            if (slides.length <= 1) return;

            let current = 0;
            let interval = null;
            const delay = 4000;

            const showSlide = (next) => {
                const currentSlide = slides[current];
                const nextSlide = slides[next];

                currentSlide.classList.remove('is-active');
                currentSlide.classList.add('is-leaving');

                nextSlide.classList.add('is-active');

                setTimeout(() => {
                    currentSlide.classList.remove('is-leaving');
                }, 450);

                current = next;
            };

            const start = () => {
                interval = setInterval(() => {
                    const next = (current + 1) % slides.length;
                    showSlide(next);
                }, delay);
            };

            const stop = () => clearInterval(interval);

            // Auto start
            start();

            // Pause on hover
            const bar = document.getElementById('topBar');
            bar.addEventListener('mouseenter', stop);
            bar.addEventListener('mouseleave', start);
        });
    </script>

</body>

</html>