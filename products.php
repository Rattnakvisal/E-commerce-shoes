<?php
require_once __DIR__ . '/config/conn.php';

// Handle filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query with filters
$query = "
    SELECT p.product_id, p.NAME, p.DESCRIPTION, p.price, p.cost, p.stock,
           p.image_url, c.category_name, c.category_id,
           DATE(p.created_at) as created_date
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.STATUS = 'active'
";

$params = [];

// Apply category filter
if ($category_filter && $category_filter > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

// Apply price range filter
$query .= " AND p.price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;

// Apply stock filter
if ($stock_filter) {
    switch ($stock_filter) {
        case 'in_stock':
            $query .= " AND p.stock > 10";
            break;
        case 'low_stock':
            $query .= " AND p.stock BETWEEN 1 AND 10";
            break;
        case 'out_of_stock':
            $query .= " AND p.stock = 0";
            break;
    }
}

// Apply search filter
if ($search_term) {
    $query .= " AND (p.NAME LIKE ? OR p.DESCRIPTION LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
}

// Apply sorting
switch ($sort_by) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.NAME ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.NAME DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

$query .= " LIMIT 100";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter dropdown
$categories_stmt = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
$all_categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get price range for filter
$price_range_stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE STATUS = 'active'");
$price_range = $price_range_stmt->fetch(PDO::FETCH_ASSOC);
$min_price_db = $price_range['min_price'] ?? 0;
$max_price_db = $price_range['max_price'] ?? 1000;

// Product count by category for filter
$category_counts_stmt = $pdo->query("
    SELECT c.category_id, c.category_name, COUNT(p.product_id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id AND p.STATUS = 'active'
    GROUP BY c.category_id, c.category_name
    ORDER BY c.category_name
");
$category_counts = $category_counts_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Filter & Browse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-card {
            transition: all 0.3s ease;
        }

        .filter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .product-card {
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }

        .price-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e5e7eb;
            outline: none;
        }

        .price-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #4f46e5;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .price-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #4f46e5;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .active-filter {
            background-color: #4f46e5;
            color: white;
        }

        .filter-badge {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stock-in {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .stock-low {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .stock-out {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Product Catalog</h1>
            <p class="text-gray-600">Browse and filter our collection of products</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded-xl shadow p-6 sticky top-4 filter-card">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Filters</h2>
                        <button onclick="resetFilters()"
                            class="text-sm text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-redo mr-1"></i>
                            Reset All
                        </button>
                    </div>

                    <!-- Search Filter -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-search mr-2 text-gray-400"></i>
                            Search Products
                        </h3>
                        <div class="relative">
                            <input type="text"
                                id="searchInput"
                                value="<?php echo htmlspecialchars($search_term); ?>"
                                placeholder="Type to search..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <div class="absolute left-3 top-2.5 text-gray-400">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-tags mr-2 text-gray-400"></i>
                            Categories
                        </h3>
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                            <div class="flex items-center">
                                <input type="radio"
                                    name="category"
                                    id="cat_all"
                                    value=""
                                    <?php echo $category_filter === '' ? 'checked' : ''; ?>
                                    class="mr-3 text-indigo-600 focus:ring-indigo-500">
                                <label for="cat_all" class="text-sm text-gray-700 cursor-pointer flex-1">
                                    All Categories
                                </label>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                    <?php echo count($products); ?>
                                </span>
                            </div>

                            <?php foreach ($category_counts as $cat): ?>
                                <div class="flex items-center">
                                    <input type="radio"
                                        name="category"
                                        id="cat_<?php echo $cat['category_id']; ?>"
                                        value="<?php echo $cat['category_id']; ?>"
                                        <?php echo $category_filter == $cat['category_id'] ? 'checked' : ''; ?>
                                        class="mr-3 text-indigo-600 focus:ring-indigo-500">
                                    <label for="cat_<?php echo $cat['category_id']; ?>"
                                        class="text-sm text-gray-700 cursor-pointer flex-1 truncate">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </label>
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        <?php echo $cat['product_count']; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-dollar-sign mr-2 text-gray-400"></i>
                            Price Range
                        </h3>
                        <div class="px-1">
                            <input type="range"
                                id="priceSlider"
                                min="<?php echo $min_price_db; ?>"
                                max="<?php echo $max_price_db; ?>"
                                value="<?php echo $max_price; ?>"
                                class="price-slider mb-4">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>$<span id="minPriceValue"><?php echo $min_price; ?></span></span>
                                <span>$<span id="maxPriceValue"><?php echo $max_price; ?></span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Status Filter -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-box mr-2 text-gray-400"></i>
                            Stock Status
                        </h3>
                        <div class="grid grid-cols-1 gap-2">
                            <div class="flex items-center">
                                <input type="radio"
                                    name="stock"
                                    id="stock_all"
                                    value=""
                                    <?php echo $stock_filter === '' ? 'checked' : ''; ?>
                                    class="mr-3 text-indigo-600 focus:ring-indigo-500">
                                <label for="stock_all" class="text-sm text-gray-700 cursor-pointer flex-1">
                                    All Products
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="radio"
                                    name="stock"
                                    id="stock_in"
                                    value="in_stock"
                                    <?php echo $stock_filter === 'in_stock' ? 'checked' : ''; ?>
                                    class="mr-3 text-indigo-600 focus:ring-indigo-500">
                                <label for="stock_in" class="text-sm text-gray-700 cursor-pointer flex-1">
                                    In Stock (> 10)
                                </label>
                                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                            </div>
                            <div class="flex items-center">
                                <input type="radio"
                                    name="stock"
                                    id="stock_low"
                                    value="low_stock"
                                    <?php echo $stock_filter === 'low_stock' ? 'checked' : ''; ?>
                                    class="mr-3 text-indigo-600 focus:ring-indigo-500">
                                <label for="stock_low" class="text-sm text-gray-700 cursor-pointer flex-1">
                                    Low Stock (1-10)
                                </label>
                                <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                            </div>
                            <div class="flex items-center">
                                <input type="radio"
                                    name="stock"
                                    id="stock_out"
                                    value="out_of_stock"
                                    <?php echo $stock_filter === 'out_of_stock' ? 'checked' : ''; ?>
                                    class="mr-3 text-indigo-600 focus:ring-indigo-500">
                                <label for="stock_out" class="text-sm text-gray-700 cursor-pointer flex-1">
                                    Out of Stock
                                </label>
                                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Sort Filter -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-sort mr-2 text-gray-400"></i>
                            Sort By
                        </h3>
                        <select id="sortSelect"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name_asc" <?php echo $sort_by === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                            <option value="name_desc" <?php echo $sort_by === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                        </select>
                    </div>
                </div>

                <!-- Active Filters Display -->
                <div id="activeFilters" class="mt-4 space-y-2">
                    <?php if ($category_filter || $stock_filter || $search_term || $max_price < $max_price_db): ?>
                        <div class="text-sm font-medium text-gray-700 mb-2">Active Filters:</div>
                        <?php if ($category_filter):
                            $cat_name = '';
                            foreach ($category_counts as $cat) {
                                if ($cat['category_id'] == $category_filter) {
                                    $cat_name = $cat['category_name'];
                                    break;
                                }
                            }
                        ?>
                            <div class="filter-badge inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full">
                                Category: <?php echo htmlspecialchars($cat_name); ?>
                                <button onclick="removeFilter('category')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($stock_filter): ?>
                            <div class="filter-badge inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full">
                                Stock: <?php echo ucfirst(str_replace('_', ' ', $stock_filter)); ?>
                                <button onclick="removeFilter('stock')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($search_term): ?>
                            <div class="filter-badge inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full">
                                Search: "<?php echo htmlspecialchars($search_term); ?>"
                                <button onclick="removeFilter('search')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($max_price < $max_price_db): ?>
                            <div class="filter-badge inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full">
                                Max Price: $<?php echo $max_price; ?>
                                <button onclick="removeFilter('price')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="lg:w-3/4">
                <!-- Results Header -->
                <div class="bg-white rounded-xl shadow p-4 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                <?php echo count($products); ?> Products Found
                            </h2>
                            <p class="text-sm text-gray-600 mt-1">
                                <?php if ($category_filter || $stock_filter || $search_term): ?>
                                    Filtered results
                                <?php else: ?>
                                    All available products
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="mt-2 sm:mt-0">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">View:</span>
                                <button onclick="setView('grid')"
                                    id="gridViewBtn"
                                    class="px-3 py-1 rounded-lg bg-indigo-600 text-white">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button onclick="setView('list')"
                                    id="listViewBtn"
                                    class="px-3 py-1 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Grid/List -->
                <div id="productsContainer">
                    <?php if (empty($products)): ?>
                        <div class="bg-white rounded-xl shadow p-8 text-center">
                            <div class="mb-4">
                                <i class="fas fa-search text-5xl text-gray-300"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Products Found</h3>
                            <p class="text-gray-600 mb-6">Try adjusting your filters or search terms</p>
                            <button onclick="resetFilters()"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                <i class="fas fa-redo mr-2"></i>
                                Reset All Filters
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Grid View -->
                        <div id="gridView" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($products as $product): ?>
                                <?php
                                // Determine stock status
                                $stock_class = '';
                                if ($product['stock'] > 10) {
                                    $stock_class = 'stock-in';
                                } elseif ($product['stock'] > 0) {
                                    $stock_class = 'stock-low';
                                } else {
                                    $stock_class = 'stock-out';
                                }

                                $stock_text = $product['stock'] > 10 ? 'In Stock' : ($product['stock'] > 0 ? 'Low Stock' : 'Out of Stock');
                                ?>
                                <div class="product-card bg-white rounded-xl shadow overflow-hidden">
                                    <!-- Product Image -->
                                    <div class="h-48 bg-gray-100 overflow-hidden">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>"
                                                class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                                                alt="<?php echo htmlspecialchars($product['NAME']); ?>">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                                <i class="fas fa-box text-gray-400 text-4xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Product Info -->
                                    <div class="p-4">
                                        <!-- Category -->
                                        <div class="mb-2">
                                            <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded">
                                                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </div>

                                        <!-- Name -->
                                        <h3 class="font-semibold text-gray-900 mb-2 truncate">
                                            <?php echo htmlspecialchars($product['NAME']); ?>
                                        </h3>

                                        <!-- Description -->
                                        <p class="text-sm text-gray-600 mb-4 line-clamp-2 h-10">
                                            <?php echo htmlspecialchars($product['DESCRIPTION']); ?>
                                        </p>

                                        <!-- Price and Stock -->
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <span class="text-xl font-bold text-gray-900">
                                                    $<?php echo number_format($product['price'], 2); ?>
                                                </span>
                                                <?php if ($product['cost'] > 0): ?>
                                                    <div class="text-xs text-gray-500">
                                                        Cost: $<?php echo number_format($product['cost'], 2); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-xs font-semibold px-3 py-1 rounded-full <?php echo $stock_class; ?>">
                                                <?php echo $stock_text; ?>
                                            </span>
                                        </div>

                                        <!-- Action Button -->
                                        <button onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                            class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 text-white py-2.5 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                                            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-cart-plus mr-2"></i>
                                            <?php echo $product['stock'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- List View (Hidden by Default) -->
                        <div id="listView" class="hidden space-y-4">
                            <?php foreach ($products as $product): ?>
                                <?php
                                $stock_class = '';
                                if ($product['stock'] > 10) {
                                    $stock_class = 'stock-in';
                                } elseif ($product['stock'] > 0) {
                                    $stock_class = 'stock-low';
                                } else {
                                    $stock_class = 'stock-out';
                                }
                                $stock_text = $product['stock'] > 10 ? 'In Stock' : ($product['stock'] > 0 ? 'Low Stock' : 'Out of Stock');
                                ?>
                                <div class="product-card bg-white rounded-xl shadow p-4 flex flex-col md:flex-row gap-4">
                                    <!-- Image -->
                                    <div class="md:w-1/4">
                                        <div class="h-40 md:h-full bg-gray-100 rounded-lg overflow-hidden">
                                            <?php if (!empty($product['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>"
                                                    class="w-full h-full object-cover"
                                                    alt="<?php echo htmlspecialchars($product['NAME']); ?>">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                                    <i class="fas fa-box text-gray-400 text-3xl"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="md:w-3/4 flex flex-col">
                                        <div class="flex-1">
                                            <div class="flex flex-col md:flex-row md:items-start justify-between mb-3">
                                                <div>
                                                    <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded mb-2 inline-block">
                                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                                    </span>
                                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                                        <?php echo htmlspecialchars($product['NAME']); ?>
                                                    </h3>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-2xl font-bold text-gray-900 mb-1">
                                                        $<?php echo number_format($product['price'], 2); ?>
                                                    </div>
                                                    <span class="text-xs font-semibold px-3 py-1 rounded-full <?php echo $stock_class; ?>">
                                                        <?php echo $stock_text; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <p class="text-gray-600 mb-4">
                                                <?php echo htmlspecialchars($product['DESCRIPTION']); ?>
                                            </p>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div class="text-sm text-gray-500">
                                                Stock: <?php echo $product['stock']; ?> units
                                            </div>
                                            <button onclick="addToCart(<?php echo $product['product_id']; ?>)"
                                                class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-lg hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
                                                <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-cart-plus mr-2"></i>
                                                <?php echo $product['stock'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filter Elements
        const searchInput = document.getElementById('searchInput');
        const categoryInputs = document.querySelectorAll('input[name="category"]');
        const stockInputs = document.querySelectorAll('input[name="stock"]');
        const priceSlider = document.getElementById('priceSlider');
        const maxPriceValue = document.getElementById('maxPriceValue');
        const sortSelect = document.getElementById('sortSelect');
        const gridViewBtn = document.getElementById('gridViewBtn');
        const listViewBtn = document.getElementById('listViewBtn');
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');

        // Current filter values
        let currentFilters = {
            search: '<?php echo $search_term; ?>',
            category: '<?php echo $category_filter; ?>',
            max_price: <?php echo $max_price; ?>,
            stock: '<?php echo $stock_filter; ?>',
            sort: '<?php echo $sort_by; ?>'
        };

        // Price slider update
        priceSlider.addEventListener('input', function() {
            maxPriceValue.textContent = this.value;
            currentFilters.max_price = this.value;
            debounceApplyFilters();
        });

        // Search input with debounce
        searchInput.addEventListener('input', function() {
            currentFilters.search = this.value;
            debounceApplyFilters();
        });

        // Category filter
        categoryInputs.forEach(input => {
            input.addEventListener('change', function() {
                currentFilters.category = this.value;
                applyFilters();
            });
        });

        // Stock filter
        stockInputs.forEach(input => {
            input.addEventListener('change', function() {
                currentFilters.stock = this.value;
                applyFilters();
            });
        });

        // Sort filter
        sortSelect.addEventListener('change', function() {
            currentFilters.sort = this.value;
            applyFilters();
        });

        // Apply filters and update URL
        function applyFilters() {
            const params = new URLSearchParams();

            if (currentFilters.search) {
                params.set('search', currentFilters.search);
            }

            if (currentFilters.category) {
                params.set('category', currentFilters.category);
            }

            if (currentFilters.max_price < <?php echo $max_price_db; ?>) {
                params.set('max_price', currentFilters.max_price);
            }

            if (currentFilters.stock) {
                params.set('stock', currentFilters.stock);
            }

            if (currentFilters.sort !== 'newest') {
                params.set('sort', currentFilters.sort);
            }

            // Update URL without reloading page (for demo purposes)
            updateURL(params.toString());

            // In real application, you would make an AJAX request here
            // For now, we'll simulate filtering on client-side
            filterProductsOnClient();
        }

        // Debounce function for search and price slider
        let debounceTimer;

        function debounceApplyFilters() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applyFilters, 500);
        }

        // Client-side filtering (for demo)
        function filterProductsOnClient() {
            const products = document.querySelectorAll('.product-card');
            const searchTerm = currentFilters.search.toLowerCase();
            const categoryFilter = currentFilters.category;
            const maxPrice = parseFloat(currentFilters.max_price);
            const stockFilter = currentFilters.stock;

            let visibleCount = 0;

            products.forEach(product => {
                const productName = product.querySelector('h3').textContent.toLowerCase();
                const productDesc = product.querySelector('p').textContent.toLowerCase();
                const productPrice = parseFloat(product.querySelector('.text-xl').textContent.replace('$', ''));
                const productStock = product.querySelector('.text-xs.font-semibold').textContent;
                const productCategory = product.querySelector('.text-xs.font-medium').textContent;

                let isVisible = true;

                // Search filter
                if (searchTerm && !productName.includes(searchTerm) && !productDesc.includes(searchTerm)) {
                    isVisible = false;
                }

                // Price filter
                if (productPrice > maxPrice) {
                    isVisible = false;
                }

                // Stock filter
                if (stockFilter === 'in_stock' && !productStock.includes('In Stock')) {
                    isVisible = false;
                } else if (stockFilter === 'low_stock' && !productStock.includes('Low Stock')) {
                    isVisible = false;
                } else if (stockFilter === 'out_of_stock' && !productStock.includes('Out of Stock')) {
                    isVisible = false;
                }

                // Show/hide product
                product.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            // Update results count
            const resultsCount = document.querySelector('.text-lg.font-semibold.text-gray-900');
            if (resultsCount) {
                resultsCount.textContent = `${visibleCount} Products Found`;
            }

            // Update active filters display
            updateActiveFiltersDisplay();
        }

        // Update active filters display
        function updateActiveFiltersDisplay() {
            const container = document.getElementById('activeFilters');
            let html = '';

            if (currentFilters.search || currentFilters.category || currentFilters.stock || currentFilters.max_price < <?php echo $max_price_db; ?>) {
                html += '<div class="text-sm font-medium text-gray-700 mb-2">Active Filters:</div>';

                if (currentFilters.search) {
                    html += `
                    <div class="filter-badge inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full mr-2 mb-2">
                        Search: "${currentFilters.search}"
                        <button onclick="removeFilter('search')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                `;
                }

                if (currentFilters.category) {
                    const selectedCategory = document.querySelector(`input[name="category"][value="${currentFilters.category}"]`);
                    if (selectedCategory) {
                        const label = selectedCategory.nextElementSibling.textContent.trim();
                        html += `
                        <div class="filter-badge inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full mr-2 mb-2">
                            Category: ${label}
                            <button onclick="removeFilter('category')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    `;
                    }
                }

                if (currentFilters.stock) {
                    const stockText = currentFilters.stock.replace('_', ' ');
                    html += `
                    <div class="filter-badge inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full mr-2 mb-2">
                        Stock: ${stockText}
                        <button onclick="removeFilter('stock')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                `;
                }

                if (currentFilters.max_price < <?php echo $max_price_db; ?>) {
                    html += `
                    <div class="filter-badge inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full mr-2 mb-2">
                        Max Price: $${currentFilters.max_price}
                        <button onclick="removeFilter('price')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                `;
                }
            }

            container.innerHTML = html;
        }

        // Remove specific filter
        function removeFilter(filterType) {
            switch (filterType) {
                case 'search':
                    currentFilters.search = '';
                    searchInput.value = '';
                    break;
                case 'category':
                    currentFilters.category = '';
                    document.querySelector('input[name="category"][value=""]').checked = true;
                    break;
                case 'stock':
                    currentFilters.stock = '';
                    document.querySelector('input[name="stock"][value=""]').checked = true;
                    break;
                case 'price':
                    currentFilters.max_price = <?php echo $max_price_db; ?>;
                    priceSlider.value = <?php echo $max_price_db; ?>;
                    maxPriceValue.textContent = <?php echo $max_price_db; ?>;
                    break;
            }
            applyFilters();
        }

        // Reset all filters
        function resetFilters() {
            currentFilters = {
                search: '',
                category: '',
                max_price: <?php echo $max_price_db; ?>,
                stock: '',
                sort: 'newest'
            };

            searchInput.value = '';
            document.querySelector('input[name="category"][value=""]').checked = true;
            document.querySelector('input[name="stock"][value=""]').checked = true;
            priceSlider.value = <?php echo $max_price_db; ?>;
            maxPriceValue.textContent = <?php echo $max_price_db; ?>;
            sortSelect.value = 'newest';

            applyFilters();
        }

        // Set view (grid/list)
        function setView(view) {
            if (view === 'grid') {
                gridView.style.display = 'grid';
                listView.style.display = 'none';
                gridViewBtn.className = 'px-3 py-1 rounded-lg bg-indigo-600 text-white';
                listViewBtn.className = 'px-3 py-1 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50';
            } else {
                gridView.style.display = 'none';
                listView.style.display = 'block';
                gridViewBtn.className = 'px-3 py-1 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50';
                listViewBtn.className = 'px-3 py-1 rounded-lg bg-indigo-600 text-white';
            }
        }

        // Add to cart function
        function addToCart(productId) {
            // Show success message
            showToast('Product added to cart successfully!', 'success');
            console.log(`Added product ${productId} to cart`);
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            // Remove existing toast
            const existingToast = document.getElementById('custom-toast');
            if (existingToast) {
                existingToast.remove();
            }

            // Create new toast
            const toast = document.createElement('div');
            toast.id = 'custom-toast';
            toast.className = `fixed bottom-4 right-4 ${type === 'success' ? 'bg-green-500' : 'bg-blue-500'} text-white px-6 py-3 rounded-lg shadow-xl transition-all duration-300 z-50`;

            toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;

            document.body.appendChild(toast);

            // Remove after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Update URL without page reload (for demo)
        function updateURL(params) {
            const newURL = params ? `${window.location.pathname}?${params}` : window.location.pathname;
            window.history.replaceState(null, '', newURL);
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            updateActiveFiltersDisplay();

            // Set initial view to grid
            setView('grid');

            // Focus search if there's a search term
            if (currentFilters.search) {
                searchInput.focus();
            }
        });
    </script>
</body>

</html>