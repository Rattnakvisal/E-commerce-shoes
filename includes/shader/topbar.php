<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Top Bar Slideshow</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

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
            z-index: -9999;
            /* FIX: was -1000 (hidden behind) */
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
            transition: opacity 0.45s ease, transform 0.45s ease;

            white-space: nowrap;
            pointer-events: none;
            padding: 0 12px;
            /* small padding for mobile */
        }

        /* Active slide */
        .topbar-slide.is-active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        /* Leaving slide */
        .topbar-slide.is-leaving {
            opacity: 0;
            transform: translateY(12px);
        }

        /* Icon styling */
        .topbar-slide i {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Better mobile behavior */
        .topbar-slide span {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 480px) {
            .topbar {
                font-size: 13px;
            }
        }

        /* Accessibility: reduce motion */
        @media (prefers-reduced-motion: reduce) {
            .topbar-slide {
                transition: none;
                transform: none;
            }
        }
    </style>
</head>

<body>

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

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const bar = document.getElementById("topBar");
            const slides = Array.from(document.querySelectorAll(".topbar-slide"));
            if (!bar || slides.length <= 1) return;

            let current = 0;
            let intervalId = null;

            const delay = 4000;
            const animMs = 450;

            function showSlide(nextIndex) {
                if (nextIndex === current) return;

                const currentSlide = slides[current];
                const nextSlide = slides[nextIndex];

                // Mark current leaving
                currentSlide.classList.add("is-leaving");

                // Prepare next
                nextSlide.classList.add("is-active");

                // After animation, fully deactivate old one
                setTimeout(() => {
                    currentSlide.classList.remove("is-active", "is-leaving");
                    current = nextIndex;
                }, animMs);
            }

            function start() {
                if (intervalId) return; // FIX: prevent multiple intervals
                intervalId = setInterval(() => {
                    const next = (current + 1) % slides.length;
                    showSlide(next);
                }, delay);
            }

            function stop() {
                if (!intervalId) return;
                clearInterval(intervalId);
                intervalId = null;
            }

            // Auto start
            start();

            // Pause on hover (desktop)
            bar.addEventListener("mouseenter", stop);
            bar.addEventListener("mouseleave", start);

            // Optional: pause when tab not visible (saves CPU)
            document.addEventListener("visibilitychange", () => {
                if (document.hidden) stop();
                else start();
            });
        });
    </script>

</body>

</html>