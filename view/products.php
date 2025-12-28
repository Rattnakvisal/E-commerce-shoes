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
$sort = $_GET['sort'] ?? 'newest';
$gender = $_GET['gender'] ?? '';
$price_min = (int)($_GET['price_min'] ?? 0);
$price_max = (int)($_GET['price_max'] ?? 1000);
$availability = $_GET['availability'] ?? '';
$pickup = $_GET['pickup'] ?? '';

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

// Gender filter
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
}

// Pick up today filter (separate parameter to avoid name collision with availability)
if ($pickup === 'pick_up_today') {
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

// Helper function for safe output
function e($string): string
{
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>New Releases | Nike Style</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- External Resources -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind Config -->
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

    <!-- Custom Styles -->
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

        input[type="checkbox"]:checked,
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

        .transition-transform {
            transition: transform 0.3s ease;
        }

        .transition-opacity {
            transition: opacity 0.3s ease;
        }

        .transition-colors {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>
</head>

<body class="bg-white text-nike-black">
    <!-- Navigation -->
    <?php require_once '../includes/navbar.php'; ?>
    <div class="max-w-[1440px] mx-auto px-4 py-6">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- DESKTOP FILTER SIDEBAR (hidden on mobile) -->
            <aside class="hidden lg:block lg:w-72 flex-shrink-0">
                <div class="sticky-sidebar">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b">
                        <h2 class="text-lg font-bold">Filters</h2>
                        <button onclick="clearFilters()" class="text-sm text-nike-gray hover:text-nike-black">
                            Clear All
                        </button>
                    </div>

                    <form id="desktopFiltersForm" method="GET" class="filter-section space-y-8 max-h-[calc(100vh-200px)] overflow-y-auto pr-2">
                        <!-- Filter content here (same as before) -->
                        <!-- Pick Up Today -->
                        <section>
                            <h3 class="font-bold mb-4 flex items-center justify-between">
                                <span>Pick Up Today</span>
                                <span class="text-xs text-nike-gray">(<?= $total_products ?>)</span>
                            </h3>
                            <div class="space-y-2">
                                <label class="flex items-center space-x-3 cursor-pointer group">
                                    <input type="checkbox"
                                        name="pickup"
                                        value="pick_up_today"
                                        <?= $pickup === 'pick_up_today' ? 'checked' : '' ?>
                                        class="rounded border-gray-300 text-nike-black focus:ring-nike-black">
                                    <span class="text-sm group-hover:text-nike-black">Available for Pickup</span>
                                </label>
                            </div>
                        </section>

                        <!-- Categories -->
                        <section>
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
                                            value="<?= e($cat['category_id']) ?>"
                                            <?= (string)$category === (string)$cat['category_id'] ? 'checked' : '' ?>
                                            class="text-nike-black focus:ring-nike-black">
                                        <span class="text-sm group-hover:text-nike-black"><?= e($cat['category_name']) ?></span>
                                        <span class="ml-auto text-xs text-nike-gray">(<?= $cat['product_count'] ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <!-- Price Range -->
                        <section>
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
                        </section>

                        <!-- Availability -->
                        <section>
                            <h3 class="font-bold mb-4">Availability</h3>
                            <div class="space-y-2">
                                <?php foreach (['' => 'All', 'in_stock' => 'In Stock'] as $val => $label): ?>
                                    <label class="flex items-center space-x-3 cursor-pointer group">
                                        <input type="radio"
                                            name="availability"
                                            value="<?= $val ?>"
                                            <?= $availability === $val ? 'checked' : '' ?>
                                            class="text-nike-black focus:ring-nike-black">
                                        <span class="text-sm group-hover:text-nike-black"><?= $label ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <input type="hidden" name="sort" value="<?= e($sort) ?>">
                        <button type="submit"
                            class="w-full bg-nike-black text-white py-3 text-sm font-bold rounded hover:bg-gray-800 transition-colors">
                            Apply Filters
                        </button>
                    </form>
                </div>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="flex-1">
                <!-- Header with Filter Button for Mobile -->
                <header class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 pb-6 border-b">
                    <div>
                        <h1 class="text-2xl font-bold mb-2">Men's New Releases</h1>
                        <p class="text-nike-gray"><?= count($products) ?> products</p>
                    </div>

                    <div class="flex items-center space-x-4 mt-4 sm:mt-0">
                        <!-- Sort Dropdown -->
                        <form method="GET" class="relative">
                            <?php foreach (['category', 'gender', 'price_min', 'price_max', 'availability'] as $field): ?>
                                <input type="hidden" name="<?= $field ?>" value="<?= e($$field) ?>">
                            <?php endforeach; ?>

                            <select name="sort"
                                onchange="this.form.submit()"
                                class="appearance-none bg-white border border-gray-300 px-4 py-2 pr-8 rounded text-sm focus:outline-none focus:border-nike-black cursor-pointer">
                                <?php
                                $sortOptions = [
                                    'newest' => 'Sort By: Newest',
                                    'price_low' => 'Sort By: Price: Low–High',
                                    'price_high' => 'Sort By: Price: High–Low',
                                    'name_asc' => 'Sort By: Name: A–Z',
                                    'name_desc' => 'Sort By: Name: Z–A'
                                ];
                                foreach ($sortOptions as $value => $text): ?>
                                    <option value="<?= $value ?>" <?= $sort === $value ? 'selected' : '' ?>>
                                        <?= $text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </form>

                        <!-- Mobile Filter Button -->
                        <button onclick="toggleMobileFilters()"
                            class="lg:hidden flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50">
                            <i class="fas fa-filter"></i>
                            <span>Filters</span>
                            <?php if ($category || $gender || $price_min > $db_min_price || $price_max < $db_max_price || $availability): ?>
                                <span class="bg-nike-black text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                                    <?php
                                    $activeFilters = 0;
                                    if ($category) $activeFilters++;
                                    if ($gender) $activeFilters++;
                                    if ($price_min > $db_min_price) $activeFilters++;
                                    if ($price_max < $db_max_price) $activeFilters++;
                                    if ($availability) $activeFilters++;
                                    echo $activeFilters > 9 ? '9+' : $activeFilters;
                                    ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    </div>
                </header>

                <!-- Mobile Filters Overlay/Drawer -->
                <div id="mobileFiltersOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden lg:hidden transition-opacity duration-300">
                    <div id="mobileFiltersDrawer" class="absolute inset-y-0 right-0 w-full max-w-sm bg-white shadow-xl transform translate-x-full transition-transform duration-300">
                        <div class="h-full flex flex-col">
                            <!-- Header -->
                            <div class="flex justify-between items-center px-6 py-4 border-b">
                                <h2 class="text-xl font-bold">Filters</h2>
                                <div class="flex items-center space-x-4">
                                    <button onclick="clearFilters()" class="text-sm text-nike-gray hover:text-nike-black">
                                        Clear All
                                    </button>
                                    <button onclick="toggleMobileFilters()" class="text-2xl">
                                        &times;
                                    </button>
                                </div>
                            </div>

                            <!-- Filters Content -->
                            <div class="flex-1 overflow-y-auto px-6 py-4">
                                <form id="mobileFiltersForm" method="GET" class="space-y-6">
                                    <!-- Pick Up Today -->
                                    <section>
                                        <h3 class="font-bold mb-3 flex items-center justify-between">
                                            <span>Pick Up Today</span>
                                            <span class="text-xs text-nike-gray">(<?= $total_products ?>)</span>
                                        </h3>
                                        <div class="space-y-2">
                                            <label class="flex items-center space-x-3 cursor-pointer py-2">
                                                <input type="checkbox"
                                                    name="pickup"
                                                    value="pick_up_today"
                                                    <?= $pickup === 'pick_up_today' ? 'checked' : '' ?>
                                                    class="rounded border-gray-300 text-nike-black focus:ring-nike-black">
                                                <span class="text-sm">Available for Pickup</span>
                                            </label>
                                        </div>
                                    </section>

                                    <!-- Categories -->
                                    <section>
                                        <h3 class="font-bold mb-3">Categories</h3>
                                        <div class="space-y-2">
                                            <label class="flex items-center space-x-3 cursor-pointer py-2">
                                                <input type="radio"
                                                    name="category"
                                                    value=""
                                                    <?= $category === '' ? 'checked' : '' ?>
                                                    class="text-nike-black focus:ring-nike-black">
                                                <span class="text-sm">All Categories</span>
                                                <span class="ml-auto text-xs text-nike-gray">(<?= $total_products ?>)</span>
                                            </label>

                                            <?php foreach ($category_counts as $cat): ?>
                                                <label class="flex items-center space-x-3 cursor-pointer py-2">
                                                    <input type="radio"
                                                        name="category"
                                                        value="<?= e($cat['category_id']) ?>"
                                                        <?= (string)$category === (string)$cat['category_id'] ? 'checked' : '' ?>
                                                        class="text-nike-black focus:ring-nike-black">
                                                    <span class="text-sm"><?= e($cat['category_name']) ?></span>
                                                    <span class="ml-auto text-xs text-nike-gray">(<?= $cat['product_count'] ?>)</span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                    <!-- Price Range -->
                                    <section>
                                        <h3 class="font-bold mb-3">Price</h3>
                                        <div class="px-2">
                                            <input type="range"
                                                id="mobilePriceSlider"
                                                min="<?= $db_min_price ?>"
                                                max="<?= $db_max_price ?>"
                                                value="<?= $price_max ?>"
                                                class="price-slider w-full mb-4">
                                            <div class="flex justify-between text-sm text-nike-gray mb-4">
                                                <span>$<span id="mobileMinPriceValue"><?= $price_min ?></span></span>
                                                <span>$<span id="mobileMaxPriceValue"><?= $price_max ?></span></span>
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
                                    </section>

                                    <!-- Availability -->
                                    <section>
                                        <h3 class="font-bold mb-3">Availability</h3>
                                        <div class="space-y-2">
                                            <?php foreach (['' => 'All', 'in_stock' => 'In Stock'] as $val => $label): ?>
                                                <label class="flex items-center space-x-3 cursor-pointer py-2">
                                                    <input type="radio"
                                                        name="availability"
                                                        value="<?= $val ?>"
                                                        <?= $availability === $val ? 'checked' : '' ?>
                                                        class="text-nike-black focus:ring-nike-black">
                                                    <span class="text-sm"><?= $label ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>

                                    <input type="hidden" name="sort" value="<?= e($sort) ?>">
                                </form>
                            </div>

                            <!-- Footer with Apply Button -->
                            <div class="border-t p-6">
                                <button type="button"
                                    onclick="applyMobileFilters()"
                                    class="w-full bg-nike-black text-white py-3 text-sm font-bold rounded hover:bg-gray-800 transition-colors">
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($products as $product):
                        $stockClass = match (true) {
                            $product['stock'] == 0 => 'text-nike-gray',
                            $product['stock'] <= 5 => 'text-nike-red',
                            default => ''
                        };
                        $stockText = match (true) {
                            $product['stock'] == 0 => 'Out of Stock',
                            $product['stock'] <= 5 => 'Almost Gone',
                            default => ''
                        };
                    ?>
                        <article class="product-card group">
                            <div class="relative bg-nike-light-gray aspect-square overflow-hidden mb-4">
                                <img src="<?= e($product['image_url']) ?>"
                                    alt="<?= e($product['name']) ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

                                <div class="absolute top-3 left-3">
                                    <span class="bg-white px-2 py-1 text-xs font-bold">JUST IN</span>
                                </div>

                                <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="bg-white w-8 h-8 rounded-full flex items-center justify-center shadow hover:bg-gray-100"
                                        onclick="addToWishlist(<?= $product['product_id'] ?>)">
                                        <i class="far fa-heart text-sm"></i>
                                    </button>
                                </div>

                                <div class="absolute bottom-0 left-0 right-0 translate-y-full group-hover:translate-y-0 transition-transform">
                                    <button onclick="addToCart(<?= $product['product_id'] ?>)"
                                        class="w-full bg-nike-black text-white py-3 text-sm font-bold hover:bg-gray-800 transition-colors">
                                        Quick Add
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <p class="text-xs text-nike-gray uppercase tracking-wide">Just In</p>
                                <h3 class="font-medium line-clamp-2"><?= e($product['name']) ?></h3>
                                <p class="text-sm text-nike-gray"><?= e($product['category_name']) ?></p>
                                <div class="flex items-center justify-between mt-2">
                                    <p class="font-bold">$<?= number_format((float)$product['price'], 2) ?></p>
                                    <?php if ($stockText): ?>
                                        <p class="text-xs font-medium <?= $stockClass ?>"><?= $stockText ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <button onclick="addToCart(<?= $product['product_id'] ?>)"
                                class="mt-4 w-full border border-nike-black py-3 text-sm font-bold rounded hover:bg-nike-black hover:text-white transition-colors">
                                Add to Cart
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>

            </main>
        </div>
    </div>
    <script src="../view/assets/Js/prodcuts.js"></script>
    <script>
        /**
         * Validate min/max price input
         */
        document
            .querySelectorAll('#mobileFiltersForm [name="price_min"], #mobileFiltersForm [name="price_max"]')
            .forEach(input => {
                input.addEventListener('change', validateMobilePriceRange);
            });

        function validateMobilePriceRange() {
            const minInput = document.querySelector('#mobileFiltersForm [name="price_min"]');
            const maxInput = document.querySelector('#mobileFiltersForm [name="price_max"]');

            let min = parseInt(minInput.value) || <?= $db_min_price ?>;
            let max = parseInt(maxInput.value) || <?= $db_max_price ?>;

            if (min > max) {
                min = max;
                minInput.value = max;
            }

            mobilePriceSlider && (mobilePriceSlider.value = max);
            updateMobilePriceUI(min, max);
        }
    </script>
</body>

</html>