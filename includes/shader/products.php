<?php
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../contract/products.php';
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
    <link rel="stylesheet" href="../../view/assets/css/products.css">
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
</head>

<body class="bg-white text-nike-black">
    <!-- Navigation -->
    <?php
    require_once __DIR__ . '/../../includes/shader/topbar.php';
    require_once __DIR__ . '/../../includes/shader/navbar.php';
    require_once __DIR__ . '/../../includes/shader/slide.php';
    ?>
    <div class="max-w-7xl mx-auto px-4 py-6">
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
                    <div class="flex flex-col">
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900">
                            New Releases
                        </h1>

                        <p class="mt-1 text-sm font-medium text-gray-500">
                            <?= count($products) ?> products
                        </p>
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

                            <?php if ((int)$product['stock'] > 0): ?>
                                <button onclick="addToCart(<?= $product['product_id'] ?>)"
                                    class="mt-4 w-full border border-nike-black py-3 text-sm font-bold rounded hover:bg-nike-black hover:text-white transition-colors">
                                    Add to Cart
                                </button>
                            <?php else: ?>
                                <button disabled
                                    class="mt-4 w-full border border-gray-200 bg-gray-100 text-gray-500 py-3 text-sm font-bold rounded cursor-not-allowed">
                                    Out of Stock
                                </button>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <?php
                    $baseParams = [];
                    foreach (['category', 'gender', 'price_min', 'price_max', 'availability', 'pickup', 'sort'] as $f) {
                        if (isset($$f) && $$f !== '') $baseParams[$f] = $$f;
                    }
                    ?>
                    <div class="mt-8">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-nike-gray">
                                Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                                <span class="font-medium"><?php echo min($offset + $limit, $total); ?></span> of
                                <span class="font-medium"><?php echo $total; ?></span> products
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1):
                                    $qp = array_merge($baseParams, ['page' => $page - 1]);
                                ?>
                                    <a href="?<?php echo http_build_query($qp); ?>" class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-nike-gray bg-white hover:bg-gray-50 transition">Previous</a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++):
                                    $qp = array_merge($baseParams, ['page' => $i]);
                                ?>
                                    <a href="?<?php echo http_build_query($qp); ?>" class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $i === $page ? 'bg-nike-black text-white border-nike-black' : 'text-nike-gray bg-white hover:bg-gray-50'; ?> transition"><?php echo $i; ?></a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages):
                                    $qp = array_merge($baseParams, ['page' => $page + 1]);
                                ?>
                                    <a href="?<?php echo http_build_query($qp); ?>" class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-nike-gray bg-white hover:bg-gray-50 transition">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
    <?php require_once __DIR__ . '/footer.php'; ?>
    <script src="../../view/assets/Js/products.js"></script>
    <script>
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