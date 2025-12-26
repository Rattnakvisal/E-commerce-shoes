<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   FILTER INPUTS
========================= */
$category = $_GET['category'] ?? '';
$sort     = $_GET['sort'] ?? 'newest';
$gender   = $_GET['gender'] ?? '';
$type     = $_GET['type'] ?? '';
$price_min = (int)($_GET['price_min'] ?? 0);
$price_max = (int)($_GET['price_max'] ?? 1000);
$availability = $_GET['availability'] ?? '';

/* =========================
   FETCH CATEGORIES WITH COUNTS
========================= */
$category_counts = $pdo->query(
    "SELECT c.category_id, c.category_name, COUNT(p.product_id) as product_count
     FROM categories c
     LEFT JOIN products p ON c.category_id = p.category_id AND p.status = 'active'
     GROUP BY c.category_id, c.category_name
     ORDER BY c.category_name"
)->fetchAll(PDO::FETCH_ASSOC);

// Get total product count
$total_products = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch()['count'];

/* =========================
   BUILD PRODUCT QUERY
========================= */
$sql = "
    SELECT p.product_id, p.name, p.price, p.image_url, c.category_name,
           p.created_at, p.stock
    FROM products p
    LEFT JOIN categories c ON c.category_id = p.category_id
    WHERE p.status = 'active'
";
$params = [];

// Category filter
if ($category !== '') {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

// Gender filter (assuming you have a gender column or category structure)
if ($gender !== '') {
    $sql .= " AND (c.category_name LIKE ? OR p.name LIKE ?)";
    $params[] = "%$gender%";
    $params[] = "%$gender%";
}

// Price range filter
if ($price_min > 0 || $price_max < 1000) {
    $sql .= " AND p.price BETWEEN ? AND ?";
    $params[] = $price_min;
    $params[] = $price_max;
}

// Availability filter
if ($availability === 'in_stock') {
    $sql .= " AND p.stock > 0";
} elseif ($availability === 'pick_up_today') {
    $sql .= " AND p.stock > 10";
}

// Sorting
$sql .= match ($sort) {
    'price_low'  => " ORDER BY p.price ASC",
    'price_high' => " ORDER BY p.price DESC",
    'name_asc'   => " ORDER BY p.name ASC",
    'name_desc'  => " ORDER BY p.name DESC",
    default      => " ORDER BY p.created_at DESC"
};

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get price range for slider
$price_range = $pdo->query("SELECT MIN(price) as min, MAX(price) as max FROM products WHERE status = 'active'")->fetch();
$db_min_price = (int)($price_range['min'] ?? 0);
$db_max_price = (int)($price_range['max'] ?? 1000);

// Ensure filters are within range
$price_min = max($price_min, $db_min_price);
$price_max = min($price_max, $db_max_price);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>New Releases | Nike Style</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'nike-black': '#111111',
                        'nike-gray': '#757575',
                        'nike-light-gray': '#f5f5f5',
                        'nike-red': '#e41c24',
                    },
                    fontFamily: {
                        'sans': ['Helvetica Neue', 'Arial', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-card:hover {
            transform: translateY(-4px);
        }

        .filter-section {
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }

        .filter-section::-webkit-scrollbar {
            width: 4px;
        }

        .filter-section::-webkit-scrollbar-track {
            background: transparent;
        }

        .filter-section::-webkit-scrollbar-thumb {
            background-color: #d1d5db;
            border-radius: 2px;
        }

        .price-slider {
            -webkit-appearance: none;
            height: 2px;
            background: #d1d5db;
        }

        .price-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #111111;
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        input[type="checkbox"]:checked {
            background-color: #111111;
            border-color: #111111;
        }

        input[type="radio"]:checked {
            background-color: #111111;
            border-color: #111111;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sticky-sidebar {
            position: sticky;
            top: 100px;
        }
    </style>
</head>

<body class="bg-white text-nike-black">

    <!-- Navigation -->
    <?php require_once '../includes/navbar.php'; ?>

    <div class="max-w-[1440px] mx-auto px-4 py-6">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- ================= FILTER SIDEBAR ================= -->
            <div class="lg:w-72 flex-shrink-0">
                <div class="sticky-sidebar">
                    <!-- Header with Hide Filters -->
                    <div class="flex justify-between items-center mb-6 pb-4 border-b">
                        <h2 class="text-lg font-bold">Filters</h2>
                        <button onclick="clearFilters()" class="text-sm text-nike-gray hover:text-nike-black">
                            Clear All
                        </button>
                    </div>

                    <form method="GET" class="filter-section space-y-8 max-h-[calc(100vh-200px)] overflow-y-auto pr-2">
                        <!-- Pick Up Today -->
                        <div>
                            <h3 class="font-bold mb-4 flex items-center justify-between">
                                <span>Pick Up Today</span>
                                <span class="text-xs text-nike-gray">(<?= $total_products ?>)</span>
                            </h3>
                            <div class="space-y-2">
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="checkbox"
                                        name="availability"
                                        value="pick_up_today"
                                        <?= $availability === 'pick_up_today' ? 'checked' : '' ?>
                                        class="rounded border-gray-300 text-nike-black focus:ring-nike-black">
                                    <span class="text-sm group-hover:text-nike-black">Available for Pickup</span>
                                </label>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div>
                            <h3 class="font-bold mb-4">Categories</h3>
                            <div class="space-y-2">
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="radio"
                                        name="category"
                                        value=""
                                        <?= $category === '' ? 'checked' : '' ?>
                                        class="text-nike-black focus:ring-nike-black">
                                    <span class="text-sm group-hover:text-nike-black">All Categories</span>
                                    <span class="ml-auto text-xs text-nike-gray">(<?= $total_products ?>)</span>
                                </label>

                                <?php foreach ($category_counts as $cat): ?>
                                    <label class="flex items-center space-x-3 cursor-pointer group">
                                        <input type="radio"
                                            name="category"
                                            value="<?= $cat['category_id'] ?>"
                                            <?= (string)$category === (string)$cat['category_id'] ? 'checked' : '' ?>
                                            class="text-nike-black focus:ring-nike-black">
                                        <span class="text-sm group-hover:text-nike-black"><?= htmlspecialchars($cat['category_name']) ?></span>
                                        <span class="ml-auto text-xs text-nike-gray">(<?= $cat['product_count'] ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Gender -->
                        <div>
                            <h3 class="font-bold mb-4">Gender</h3>
                            <div class="space-y-2">
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="radio"
                                        name="gender"
                                        value="men"
                                        <?= ($gender === 'men' || $gender === '') ? 'checked' : '' ?>
                                        class="text-nike-black focus:ring-nike-black">
                                    <span class="text-sm group-hover:text-nike-black">Men</span>
                                </label>
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="radio"
                                        name="gender"
                                        value="women"
                                        <?= $gender === 'women' ? 'checked' : '' ?>
                                        class="text-nike-black focus:ring-nike-black">
                                    <span class="text-sm group-hover:text-nike-black">Women</span>
                                </label>
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="radio"
                                        name="gender"
                                        value="kids"
                                        <?= $gender === 'kids' ? 'checked' : '' ?>
                                        class="text-nike-black focus:ring-nike-black">
                                    <span class="text-sm group-hover:text-nike-black">Kids</span>
                                </label>
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div>
                            <h3 class="font-bold mb-4">Price</h3>
                            <div class="px-2">
                                <input type="range"
                                    id="priceSlider"
                                    min="<?= $db_min_price ?>"
                                    max="<?= $db_max_price ?>"
                                    value="<?= $price_max ?>"
                                    class="price-slider w-full mb-4">
                                <div class="flex justify-between text-sm text-nike-gray mb-4">
                                    <span>$<span id="minPriceValue"><?= $price_min ?></span></span>
                                    <span>$<span id="maxPriceValue"><?= $price_max ?></span></span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-nike-gray mb-1">Min</label>
                                        <input type="number"
                                            name="price_min"
                                            value="<?= $price_min ?>"
                                            min="<?= $db_min_price ?>"
                                            max="<?= $db_max_price ?>"
                                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-nike-gray mb-1">Max</label>
                                        <input type="number"
                                            name="price_max"
                                            value="<?= $price_max ?>"
                                            min="<?= $db_min_price ?>"
                                            max="<?= $db_max_price ?>"
                                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Availability -->
                        <div>
                            <h3 class="font-bold mb-4">Availability</h3>
                            <div class="space-y-2">
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="radio"
                                        name="availability"
                                        value=""
                                        <?= $availability === '' ? 'checked' : '' ?>
                                        class="text-nike-black focus:ring-nike-black">
                                    <span class="text-sm group-hover:text-nike-black">All</span>
                                </label>
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="radio"
                                        name="availability"
                                        value="in_stock"
                                        <?= $availability === 'in_stock' ? 'checked' : '' ?>
                                        class="text-nike-black focus:ring-nike-black">
                                    <span class="text-sm group-hover:text-nike-black">In Stock</span>
                                </label>
                            </div>
                        </div>

                        <!-- Hidden fields for current sort -->
                        <input type="hidden" name="sort" value="<?= $sort ?>">

                        <!-- Apply Filters Button -->
                        <button type="submit"
                            class="w-full bg-nike-black text-white py-3 text-sm font-bold rounded hover:bg-gray-800 transition">
                            Apply Filters
                        </button>
                    </form>
                </div>
            </div>

            <!-- ================= MAIN CONTENT ================= -->
            <div class="flex-1">
                <!-- Header with counts and sort -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 pb-6 border-b">
                    <div>
                        <h1 class="text-2xl font-bold mb-2">Men's New Releases</h1>
                        <p class="text-nike-gray"><?= count($products) ?> products</p>
                    </div>

                    <div class="flex items-center space-x-4 mt-4 sm:mt-0">
                        <!-- Sort Dropdown -->
                        <form method="GET" class="relative">
                            <input type="hidden" name="category" value="<?= $category ?>">
                            <input type="hidden" name="gender" value="<?= $gender ?>">
                            <input type="hidden" name="price_min" value="<?= $price_min ?>">
                            <input type="hidden" name="price_max" value="<?= $price_max ?>">
                            <input type="hidden" name="availability" value="<?= $availability ?>">

                            <select name="sort"
                                onchange="this.form.submit()"
                                class="appearance-none bg-white border border-gray-300 px-4 py-2 pr-8 rounded text-sm focus:outline-none focus:border-nike-black cursor-pointer">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort By: Newest</option>
                                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Sort By: Price: Low–High</option>
                                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Sort By: Price: High–Low</option>
                                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Sort By: Name: A–Z</option>
                                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Sort By: Name: Z–A</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </form>

                        <!-- Filter Toggle for Mobile -->
                        <button onclick="toggleMobileFilters()"
                            class="lg:hidden flex items-center space-x-2 text-sm">
                            <i class="fas fa-filter"></i>
                            <span>Filters</span>
                        </button>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card group">
                            <!-- Image Container -->
                            <div class="relative bg-nike-light-gray aspect-square overflow-hidden mb-4">
                                <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

                                <!-- Badge -->
                                <div class="absolute top-3 left-3">
                                    <span class="bg-white px-2 py-1 text-xs font-bold">JUST IN</span>
                                </div>

                                <!-- Quick Actions -->
                                <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <button class="bg-white w-8 h-8 rounded-full flex items-center justify-center shadow hover:bg-gray-100"
                                        onclick="addToWishlist(<?= $product['product_id'] ?>)">
                                        <i class="far fa-heart text-sm"></i>
                                    </button>
                                </div>

                                <!-- Quick Add to Cart -->
                                <div class="absolute bottom-0 left-0 right-0 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                    <button onclick="addToCart(<?= $product['product_id'] ?>)"
                                        class="w-full bg-nike-black text-white py-3 text-sm font-bold hover:bg-gray-800 transition">
                                        Quick Add
                                    </button>
                                </div>
                            </div>

                            <!-- Product Info -->
                            <div class="space-y-1">
                                <p class="text-xs text-nike-gray uppercase tracking-wide">Just In</p>
                                <h3 class="font-medium line-clamp-2"><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="text-sm text-nike-gray"><?= htmlspecialchars($product['category_name']) ?></p>
                                <div class="flex items-center justify-between mt-2">
                                    <p class="font-bold">$<?= number_format((float)$product['price'], 2) ?></p>
                                    <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                        <p class="text-xs text-nike-red font-medium">Almost Gone</p>
                                    <?php elseif ($product['stock'] == 0): ?>
                                        <p class="text-xs text-nike-gray font-medium">Out of Stock</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Add to Cart Button -->
                            <button onclick="addToCart(<?= $product['product_id'] ?>)"
                                class="mt-4 w-full border border-nike-black py-3 text-sm font-bold rounded hover:bg-nike-black hover:text-white transition-colors duration-300">
                                Add to Cart
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Empty State -->
                <?php if (empty($products)): ?>
                    <div class="text-center py-16">
                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-bold mb-2">No products found</h3>
                        <p class="text-nike-gray mb-6">Try adjusting your filters or search terms</p>
                        <button onclick="clearFilters()"
                            class="bg-nike-black text-white px-6 py-3 text-sm font-bold rounded hover:bg-gray-800 transition">
                            Clear All Filters
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Load More (Optional) -->
                <?php if (!empty($products) && count($products) >= 12): ?>
                    <div class="text-center mt-12">
                        <button class="border border-nike-black px-8 py-3 text-sm font-bold rounded hover:bg-nike-black hover:text-white transition">
                            Load More
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Filters Overlay -->
    <div id="mobileFilters" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden lg:hidden">
        <div class="absolute right-0 top-0 bottom-0 w-80 bg-white p-6 overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-bold">Filters</h2>
                <button onclick="toggleMobileFilters()" class="text-2xl">&times;</button>
            </div>
            <!-- Mobile filter content would go here (same as desktop filters) -->
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 bg-nike-black text-white px-6 py-3 rounded shadow-lg hidden z-50">
        <div class="flex items-center space-x-3">
            <i class="fas fa-check"></i>
            <span id="toastMessage"></span>
        </div>
    </div>

    <script>
        // Price slider update
        const priceSlider = document.getElementById('priceSlider');
        const maxPriceValue = document.getElementById('maxPriceValue');
        const minPriceValue = document.getElementById('minPriceValue');

        if (priceSlider) {
            priceSlider.addEventListener('input', function() {
                maxPriceValue.textContent = this.value;
            });
        }

        // Add to cart function
        function addToCart(productId) {
            fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'add',
                        product_id: productId,
                        quantity: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count in navbar: support both class and id
                        const cartCountElements = document.querySelectorAll('.cart-count');
                        cartCountElements.forEach(el => {
                            el.textContent = data.cart_count ?? data.count ?? 0;
                        });
                        const cartCountEl = document.getElementById('cartCount');
                        if (cartCountEl) cartCountEl.textContent = data.cart_count ?? data.count ?? 0;

                        // Show toast
                        showToast('Added to cart!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error adding to cart', 'error');
                });
        }

        // Add to wishlist function
        function addToWishlist(productId) {
            fetch('wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'add',
                        product_id: productId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // update wishlist badge
                        const wishlistEls = document.querySelectorAll('.wishlist-count');
                        wishlistEls.forEach(el => el.textContent = data.wishlist_count ?? data.count ?? 0);
                        const wishlistIdEl = document.getElementById('wishlistCount');
                        if (wishlistIdEl) wishlistIdEl.textContent = data.wishlist_count ?? data.count ?? 0;

                        showToast('Added to wishlist!');
                    }
                })
                .catch(err => {
                    console.error('Wishlist error', err);
                    showToast('Error adding to wishlist', 'error');
                });
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');

            toast.className = `fixed bottom-4 right-4 ${type === 'success' ? 'bg-nike-black' : 'bg-red-600'} text-white px-6 py-3 rounded shadow-lg flex items-center space-x-3 z-50`;
            toastMessage.textContent = message;
            toast.classList.remove('hidden');

            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }

        // Clear all filters
        function clearFilters() {
            window.location.href = 'products.php';
        }

        // Toggle mobile filters
        function toggleMobileFilters() {
            const filters = document.getElementById('mobileFilters');
            filters.classList.toggle('hidden');
        }

        // Apply filters on change (for checkboxes/radios)
        document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.type === 'radio') {
                    // For radios, submit the form
                    this.closest('form').submit();
                }
            });
        });

        // Price input validation
        document.querySelectorAll('input[name="price_min"], input[name="price_max"]').forEach(input => {
            input.addEventListener('change', function() {
                const minInput = document.querySelector('input[name="price_min"]');
                const maxInput = document.querySelector('input[name="price_max"]');

                let min = parseInt(minInput.value) || <?= $db_min_price ?>;
                let max = parseInt(maxInput.value) || <?= $db_max_price ?>;

                // Ensure min <= max
                if (min > max) {
                    if (this === minInput) {
                        minInput.value = max;
                    } else {
                        maxInput.value = min;
                    }
                }

                // Update slider
                if (priceSlider) {
                    priceSlider.value = max;
                    maxPriceValue.textContent = max;
                }
                minPriceValue.textContent = min;
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.getElementById('mobileFilters').classList.add('hidden');
                document.getElementById('toast').classList.add('hidden');
            }

            // Ctrl/Cmd + F to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.querySelector('input[type="search"]');
                if (searchInput) searchInput.focus();
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Update price display on load
            if (priceSlider) {
                priceSlider.value = <?= $price_max ?>;
            }
        });
    </script>
</body>

</html>