<?php
require_once __DIR__ . '/../../../config/conn.php';
require_once __DIR__ . '/products_api.php';

$queryBase = $_GET ?? [];
unset($queryBase['status'], $queryBase['brand'], $queryBase['page']);

$currentStatus = $_GET['status'] ?? '';
$currentBrand  = $_GET['brand'] ?? '';

$tabs = [
    [
        'label' => 'All',
        'status' => '',
        'brand' => '',
        'count' => 'all',
        'pill'  => 'bg-gray-100 text-gray-600',
        'activeText' => 'text-indigo-600',
    ],
    [
        'label' => 'Active',
        'status' => 'active',
        'brand' => '',
        'count' => 'active',
        'pill'  => 'bg-green-100 text-green-700',
        'activeText' => 'text-green-600',
    ],
    [
        'label' => 'Inactive',
        'status' => 'inactive',
        'brand' => '',
        'count' => 'inactive',
        'pill'  => 'bg-yellow-100 text-yellow-700',
        'activeText' => 'text-yellow-600',
    ],
    [
        'label' => 'Nike',
        'status' => '',
        'brand' => 'Nike',
        'count' => 'Nike',
        'pill'  => 'bg-red-100 text-red-600',
        'activeText' => 'text-red-600',
    ],
    [
        'label' => 'Adidas',
        'status' => '',
        'brand' => 'Adidas',
        'count' => 'Adidas',
        'pill'  => 'bg-blue-100 text-blue-600',
        'activeText' => 'text-blue-600',
    ],
    [
        'label' => 'New Balance',
        'status' => '',
        'brand' => 'New Balance',
        'count' => 'New Balance',
        'pill'  => 'bg-emerald-100 text-emerald-700',
        'activeText' => 'text-emerald-600',
    ],
    [
        'label' => 'Other',
        'status' => '',
        'brand' => 'Other',
        'count' => 'Other',
        'pill'  => 'bg-purple-100 text-purple-600',
        'activeText' => 'text-purple-600',
    ],
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../../assets/Css/products.css">
    <link rel="stylesheet" href="../../../assets/Css/reports.css">
</head>

<body class="bg-gray-50">
    <!-- Include Admin Navbar -->
    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <!-- Main Content -->
    <main class="md:ml-64 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Page Header -->
            <div class="mb-6 animate-fade-in">
                <div class="flex flex-col lg:flex-row mb-6 lg:items-center lg:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">Products <span class="gradient-text font-extrabold">Management</span></h1>
                        </div>
                        <p class="text-gray-600 ml-1">Manage and track all products in your store.</p>
                    </div>
                    <!-- Actions -->
                    <div class="flex items-center gap-3">

                        <button
                            id="openAddProduct"
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg
                           hover:bg-indigo-700 transition">
                            <i class="fas fa-plus mr-2"></i>
                            Add Product
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in-up">
                    <!-- TOTAL PRODUCTS -->
                    <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden group hover:shadow-glow-blue">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <h3 class="text-sm font-medium text-gray-600 mb-1">Total Products</h3>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?= number_format($stats['total'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-boxes text-lg"></i>
                            </div>
                        </div>

                        <?php
                        $activePercent = ($stats['total'] ?? 0) > 0
                            ? ($stats['active'] / $stats['total']) * 100
                            : 0;
                        ?>

                        <div class="mt-4">
                            <div class="flex justify-between text-sm text-gray-500 mb-2">
                                <span>Active Products</span>
                                <span class="font-semibold"><?= number_format($activePercent, 1) ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200/50 rounded-full h-2">
                                <div class="h-2 rounded-full report-progress bg-gradient-to-r from-blue-500 to-indigo-500"
                                    style="--target-width: <?= $activePercent ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ACTIVE PRODUCTS -->
                    <div class="stat-card bg-gradient-to-br from-white to-emerald-50/50 rounded-2xl p-6 shadow-soft-xl border border-emerald-100/50 relative overflow-hidden group hover:shadow-glow-green">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <h3 class="text-sm font-medium text-gray-600 mb-1">Active Products</h3>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?= number_format($stats['active'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-check-circle text-lg"></i>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mt-4">Visible in store</p>
                    </div>

                    <!-- TOTAL STOCK -->
                    <div class="stat-card bg-gradient-to-br from-white to-purple-50/50 rounded-2xl p-6 shadow-soft-xl border border-purple-100/50 relative overflow-hidden group hover:shadow-glow-purple">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <h3 class="text-sm font-medium text-gray-600 mb-1">Total Stock</h3>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?= number_format($stats['total_stock'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-cubes text-lg"></i>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mt-4">Units in inventory</p>
                    </div>

                    <!-- INACTIVE PRODUCTS -->
                    <div class="stat-card bg-gradient-to-br from-red-500 to-rose-600 text-white rounded-2xl p-6 shadow-soft-xl border border-red-400/30 relative overflow-hidden group hover:shadow-glow-red">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-10 translate-x-10"></div>

                        <?php
                        $inactivePercent = ($stats['total'] ?? 0) > 0
                            ? ($stats['inactive'] / $stats['total']) * 100
                            : 0;
                        ?>

                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <h3 class="text-sm font-medium text-white/90 mb-1">Inactive Products</h3>
                                <p class="text-2xl font-bold text-white">
                                    <?= number_format($stats['inactive'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="bg-white/20 p-3 rounded-xl shadow-inner">
                                <i class="fas fa-pause-circle text-lg text-white"></i>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="flex justify-between text-sm text-white/80 mb-2">
                                <span>Inactive Rate</span>
                                <span class="font-semibold"><?= number_format($inactivePercent, 1) ?>%</span>
                            </div>
                            <div class="w-full bg-white/30 rounded-full h-2">
                                <div class="h-2 rounded-full report-progress bg-gradient-to-r from-white to-white/80"
                                    style="--target-width: <?= $inactivePercent ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Product Modal -->
                <div id="productModal" class="fixed inset-0 hidden z-50 p-4 flex items-center justify-center">
                    <div class="absolute inset-0 bg-black/50 modal-overlay"></div>
                    <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl z-10 max-h-[90vh] overflow-y-auto">
                        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                            <h3 id="modalTitle" class="text-lg font-semibold">Add Product</h3>
                            <button id="closeModal" class="text-gray-400 hover:text-gray-600 transition">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <form id="productForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="product_id" id="productId" value="">

                            <!-- Left column: fields -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input type="text" name="name" id="productName" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                                    <input type="text" name="sku" id="productSku" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea name="description" id="productDescription" rows="4"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                                        <select name="category_id" id="productCategory" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">-- Select Category --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                                        <input type="number" name="price" id="productPrice" step="0.01" min="0" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Cost</label>
                                        <input type="number" name="cost" id="productCost" step="0.01" min="0"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock *</label>
                                        <input type="number" name="stock" id="productStock" min="0" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                    <select name="status" id="productStatus" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div class="pt-2 border-t"></div>
                            </div>

                            <!-- Right column: image upload + preview -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>

                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition">
                                    <input type="file" name="image" id="productImage" accept="image/*"
                                        class="hidden">
                                    <label for="productImage" class="cursor-pointer">
                                        <div class="flex flex-col items-center">
                                            <div class="w-12 h-12 mb-3 flex items-center justify-center bg-indigo-50 rounded-full">
                                                <i class="fas fa-cloud-upload-alt text-indigo-600 text-xl"></i>
                                            </div>
                                            <p class="text-sm font-medium text-gray-700 mb-1">Upload Image</p>
                                            <p class="text-xs text-gray-500">Click to browse or drag and drop</p>
                                            <p class="text-xs text-gray-400 mt-2">PNG, JPG up to 2MB</p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Preview -->
                                <div id="imagePreview" class="mt-4 hidden">
                                    <div class="relative">
                                        <img id="previewImage" class="w-full h-64 object-cover rounded-lg border-2 border-gray-300">
                                    </div>
                                </div>
                                <small class="text-gray-500">Max size: 2MB. Supported formats: JPG, PNG, GIF</small>

                                <div class="mt-6 md:mt-12 flex justify-end gap-2">
                                    <button type="button" id="cancelBtn"
                                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" id="submitBtn"
                                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition font-medium flex items-center gap-2">
                                        <i class="fas fa-save"></i>
                                        <span id="submitText">Add Product</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="bg-white border-b border-gray-200">
                    <nav class="flex gap-6 px-6 py-4 overflow-x-auto">
                        <?php foreach ($tabs as $t): ?>
                            <?php
                            $isActive =
                                ($t['status'] === $currentStatus) &&
                                ($t['brand'] === $currentBrand);

                            $href = '?' . http_build_query(array_merge(
                                $queryBase,
                                ['status' => $t['status'], 'brand' => $t['brand']]
                            ));

                            $linkClass = $isActive
                                ? "{$t['activeText']} border-b-2 border-indigo-600"
                                : 'text-gray-500 hover:text-gray-700';

                            $count = (int)($statusCounts[$t['count']] ?? 0);
                            ?>

                            <a href="<?= htmlspecialchars($href) ?>"
                                class="flex items-center gap-2 pb-2 text-sm font-medium <?= $linkClass ?>">
                                <?= htmlspecialchars($t['label']) ?>

                                <span class="px-2 py-0.5 text-xs rounded-full <?= $t['pill'] ?>">
                                    <?= $count ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Product Filters -->
                <form method="GET" class="bg-white rounded-xl shadow mb-8 p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 items-end">

                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Search</label>
                            <input type="text"
                                name="search"
                                value="<?= htmlspecialchars($search ?? '') ?>"
                                placeholder="Product name, SKU..."
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- From -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">From Date</label>
                            <input type="date" name="date_from"
                                value="<?= $date_from ?? '' ?>"
                                class="w-full px-3 py-2 border rounded-lg">
                        </div>

                        <!-- To -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">To Date</label>
                            <input type="date" name="date_to"
                                value="<?= $date_to ?? '' ?>"
                                class="w-full px-3 py-2 border rounded-lg">
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Category</label>
                            <select name="category_id" class="w-full px-3 py-2 border rounded-lg">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"
                                        <?= $category_id == $cat['category_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Sort -->
                        <select name="sort" class="w-full px-3 py-2 border rounded-lg">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High → Low</option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low → High</option>
                        </select>

                        <!-- Actions -->
                        <div class="flex gap-2 justify-end lg:col-span-6">
                            <a href="products.php"
                                class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-100">
                                Clear
                            </a>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Apply
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- Products Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sku
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cost
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stock
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Image
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="productsTableBody">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                                        <p class="text-lg font-medium text-gray-900">No products found</p>
                                        <p class="text-gray-500 mt-1">Try adjusting your filters or add a new product</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $stock = (int)$product['stock'];
                                $stockClass = $stock <= 0 ? 'stock-out' : ($stock < 10 ? 'stock-low' : '');
                                $statusClass = $product['status'] === 'active' ? 'status-active' : 'status-inactive';
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors" data-id="<?php echo $product['product_id']; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $product['product_id']; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 truncate max-w-xs">
                                            <?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)); ?>
                                            <?php if (strlen($product['description'] ?? '') > 50): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($product['sku'] ?? ''); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="font-semibold">$<?php echo number_format($product['price'], 2); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $product['cost'] ? '$' . number_format($product['cost'], 2) : 'N/A'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $stockClass; ?>">
                                            <?php echo number_format($stock); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded object-cover"
                                                    src="<?php echo htmlspecialchars($product['image_url']); ?>"
                                                    alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-cell px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center space-x-2">
                                            <button type="button" onclick="editProduct(<?php echo $product['product_id']; ?>)"
                                                class="inline-flex items-center px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 text-sm hover-lift">
                                                <i class="fas fa-edit mr-2"></i> Edit
                                            </button>
                                            <button type="button" onclick="deleteProduct(<?php echo $product['product_id']; ?>)"
                                                class="inline-flex items-center px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 text-sm hover-lift">
                                                <i class="fas fa-trash mr-2"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                        <span class="font-medium"><?php echo min($offset + $limit, $totalProducts); ?></span> of
                        <span class="font-medium"><?php echo $totalProducts; ?></span> products
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&date_to=<?php echo urlencode($date_to ?? ''); ?>"
                                class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&date_to=<?php echo urlencode($date_to ?? ''); ?>"
                                class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $i === $page ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-700 bg-white hover:bg-gray-50'; ?> transition">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&date_to=<?php echo urlencode($date_to ?? ''); ?>"
                                class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <script src="../../../assets/js/products.js"></script>
    <script src="../../../assets/js/reports.js"></script>
    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($message): ?>
                showSuccess('Success', '<?php echo addslashes($message); ?>');
            <?php endif; ?>

            <?php if ($error): ?>
                showError('<?php echo addslashes($error); ?>');
            <?php endif; ?>
        });
    </script>
</body>

</html>