<?php
require_once __DIR__ . '/../config/conn.php';

$featured = [];
try {
    $stmt = $conn->prepare("
        SELECT f.*, p.name AS product_name
        FROM featured_items f
        LEFT JOIN products p ON f.product_id = p.product_id
        WHERE f.is_active = 1
        ORDER BY f.position ASC
        LIMIT 8
    ");
    $stmt->execute();
    $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $featured = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#1e40af',
                        accent: '#f59e0b',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': {
                                opacity: '0'
                            },
                            '100%': {
                                opacity: '1'
                            },
                        },
                        slideUp: {
                            '0%': {
                                transform: 'translateY(20px)',
                                opacity: '0'
                            },
                            '100%': {
                                transform: 'translateY(0)',
                                opacity: '1'
                            },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }
    </style>
</head>

<body>

    <?php if (!empty($featured)): ?>
        <section class="w-full py-12 md:py-16 lg:py-20 bg-gradient-to-b from-gray-50 to-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Section Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 md:mb-12">
                    <div class="mb-6 md:mb-0">
                        <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-3">
                            Featured <span class="text-primary">Collections</span>
                        </h2>
                        <p class="text-gray-600 text-lg">Discover our curated selection of premium products</p>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center space-x-3">
                        <button onclick="scrollFeatured('left')"
                            class="w-10 h-10 rounded-full bg-white border border-gray-300 flex items-center justify-center hover:bg-gray-50 hover:border-gray-400 transition-all duration-300 shadow-sm">
                            <i class="fas fa-chevron-left text-gray-700"></i>
                        </button>
                        <button onclick="scrollFeatured('right')"
                            class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center hover:bg-secondary transition-all duration-300 shadow-lg">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Featured Items Container -->
                <div class="relative">
                    <!-- Horizontal Scroll Container -->
                    <div id="featured-scroll"
                        class="flex gap-6 overflow-x-auto custom-scrollbar pb-4 snap-x snap-mandatory scroll-smooth no-scrollbar">

                        <?php foreach ($featured as $item):
                            $title = htmlspecialchars($item['title'] ?: $item['product_name']);
                            $img   = htmlspecialchars($item['image_url']);
                            $link  = "../view/products.php?product_id=" . urlencode($item['product_id']);
                            $price = isset($item['price']) ? number_format($item['price'], 2) : '';
                            $discount = isset($item['discount']) ? $item['discount'] : null;
                        ?>

                            <a href="<?= $link ?>"
                                class="group relative flex-none w-[280px] sm:w-[340px] lg:w-[380px] h-[420px] sm:h-[480px] lg:h-[520px] snap-start overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 animate-fade-in">

                                <!-- Image Container -->
                                <div class="relative w-full h-full overflow-hidden">
                                    <img src="<?= $img ?>"
                                        alt="<?= $title ?>"
                                        class="w-full h-full object-cover transform transition-transform duration-700 group-hover:scale-110">

                                    <!-- Gradient Overlay -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent opacity-80 group-hover:opacity-90 transition-opacity duration-500"></div>

                                    <!-- Discount Badge -->
                                    <?php if ($discount): ?>
                                        <div class="absolute top-4 left-4 bg-accent text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg transform -rotate-6 animate-pulse">
                                            -<?= $discount ?>% OFF
                                        </div>
                                    <?php endif; ?>

                                    <!-- Favorite Button -->
                                    <button class="absolute top-4 right-4 w-10 h-10 bg-white/90 rounded-full flex items-center justify-center hover:bg-white hover:scale-110 transition-all duration-300 shadow-lg">
                                        <i class="far fa-heart text-gray-700 group-hover:text-red-500"></i>
                                    </button>
                                </div>

                                <!-- Content Overlay -->
                                <div class="absolute bottom-0 left-0 right-0 p-6 text-white transform translate-y-0 group-hover:-translate-y-2 transition-transform duration-500">

                                    <!-- Category Tag -->
                                    <div class="mb-3">
                                        <span class="inline-block bg-white/20 backdrop-blur-sm text-white text-xs font-semibold px-3 py-1 rounded-full">
                                            Featured
                                        </span>
                                    </div>
                                    <!-- CTA Button -->
                                    <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-500">
                                        <span class="inline-flex items-center justify-center bg-white text-gray-900 text-sm font-semibold px-6 py-3 rounded-full hover:bg-gray-100 hover:scale-105 transition-all duration-300 shadow-lg">
                                            <?= $title ?>
                                            <i class="fas fa-arrow-right ml-2 text-sm"></i>
                                        </span>
                                    </div>
                                </div>

                                <!-- Hover Border Effect -->
                                <div class="absolute inset-0 border-2 border-transparent group-hover:border-white/30 rounded-2xl transition-all duration-500"></div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Scroll Indicator -->
                    <div class="flex justify-center mt-8 space-x-2">
                        <?php for ($i = 0; $i < count($featured); $i++): ?>
                            <span class="scroll-indicator w-2 h-2 rounded-full bg-gray-300 cursor-pointer hover:bg-primary transition-colors duration-300" onclick="scrollToItem(<?= $i ?>)"></span>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </section>

        <script>
            // Scroll functionality
            function scrollFeatured(direction) {
                const container = document.getElementById('featured-scroll');
                const scrollAmount = 400;

                if (direction === 'left') {
                    container.scrollLeft -= scrollAmount;
                } else {
                    container.scrollLeft += scrollAmount;
                }

                updateScrollIndicators();
            }

            // Scroll to specific item
            function scrollToItem(index) {
                const container = document.getElementById('featured-scroll');
                const items = container.querySelectorAll('a');
                if (items[index]) {
                    items[index].scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'center'
                    });
                }
            }

            // Update scroll indicators
            function updateScrollIndicators() {
                const container = document.getElementById('featured-scroll');
                const indicators = document.querySelectorAll('.scroll-indicator');
                const scrollPercentage = container.scrollLeft / (container.scrollWidth - container.clientWidth);
                const activeIndex = Math.round(scrollPercentage * (indicators.length - 1));

                indicators.forEach((indicator, index) => {
                    if (index === activeIndex) {
                        indicator.classList.add('bg-primary', 'w-4');
                        indicator.classList.remove('bg-gray-300', 'w-2');
                    } else {
                        indicator.classList.remove('bg-primary', 'w-4');
                        indicator.classList.add('bg-gray-300', 'w-2');
                    }
                });
            }

            // Initialize
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('featured-scroll');
                container.addEventListener('scroll', updateScrollIndicators);
                updateScrollIndicators(); // Initial call
            });
        </script>
    <?php endif; ?>
</body>

</html>