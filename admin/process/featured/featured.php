<?php
require_once __DIR__ . '/../../../config/conn.php';
require_once __DIR__ . '/featured_api.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../../assets/Css/products.css">
</head>

<body class="bg-gray-50 min-h-screen">
    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <main class="md:ml-64 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-800 border border-red-200">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="font-semibold">Please fix the following errors:</span>
                    </div>
                    <ul class="list-disc list-inside ml-5">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="mb-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Featured Items</h1>
                        <p class="text-gray-600">Manage featured section content</p>
                    </div>
                    <div>
                        <button onclick="openAddModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex items-center gap-2">
                            <i class="fas fa-plus"></i> Add Featured
                        </button>
                    </div>
                </div>

                <!-- Featured Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-fade-in">
                    <!-- TOTAL FEATURED -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Featured</p>
                                <p class="text-2xl font-bold text-gray-900 mt-2">
                                    <?= number_format($stats['total'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-star text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- ACTIVE FEATURED -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Active Featured</p>
                                <p class="text-2xl font-bold text-gray-900 mt-2">
                                    <?= number_format($stats['active'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- POSITIONS USED -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Positions Used</p>
                                <p class="text-2xl font-bold text-gray-900 mt-2">
                                    <?= number_format($stats['positions'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-sort-numeric-up text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- INACTIVE FEATURED -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Inactive</p>
                                <p class="text-2xl font-bold text-gray-900 mt-2">
                                    <?= number_format($stats['inactive'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-pause-circle text-red-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $queryBase = $_GET;
            unset($queryBase['status'], $queryBase['page']);
            ?>

            <!-- Featured Status Tabs -->
            <div class="bg-white rounded-xl shadow mb-6 animate-fade-in">

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex gap-6 px-6 py-4 overflow-x-auto">

                        <!-- ALL -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['status' => ''])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
               <?= $status === ''
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            All Featured
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600">
                                <?= $statusCounts['all'] ?? 0 ?>
                            </span>
                        </a>

                        <!-- ACTIVE -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['status' => 'active'])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
               <?= $status === 'active'
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            Active
                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                <?= $statusCounts['active'] ?? 0 ?>
                            </span>
                        </a>

                        <!-- INACTIVE -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['status' => 'inactive'])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
               <?= $status === 'inactive'
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            Inactive
                            <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">
                                <?= $statusCounts['inactive'] ?? 0 ?>
                            </span>
                        </a>

                    </nav>
                </div>

                <!-- Filters -->
                <form method="GET" class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-end">

                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">
                                Search
                            </label>
                            <input type="text"
                                name="search"
                                value="<?= htmlspecialchars($search ?? '') ?>"
                                placeholder="Featured title or product name"
                                class="w-full px-4 py-2 border rounded-lg
                              focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- From Date -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">
                                From Date
                            </label>
                            <input type="date"
                                name="date_from"
                                value="<?= $date_from ?? '' ?>"
                                class="w-full px-3 py-2 border rounded-lg">
                        </div>

                        <!-- To Date -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">
                                To Date
                            </label>
                            <input type="date"
                                name="date_to"
                                value="<?= $date_to ?? '' ?>"
                                class="w-full px-3 py-2 border rounded-lg">
                        </div>

                        <!-- Sort -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">
                                Sort By
                            </label>
                            <select name="sort"
                                class="w-full px-3 py-2 border rounded-lg">
                                <option value="position">Position</option>
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 justify-end lg:col-span-5">
                            <a href="featured.php"
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

            <!-- Featured Items Table -->
            <?php if ($totalFeatured > 0): ?>
                <div class="overflow-x-auto bg-white rounded-xl shadow border border-gray-200 animate-fade-in">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">ID</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Product</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Title</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Position</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Image</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Created</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($featured as $f): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-6 text-sm text-gray-800">#<?= $f['featured_id'] ?></td>
                                    <td class="py-4 px-6 text-sm text-gray-800">
                                        <?= htmlspecialchars($f['product_name'] ?? 'N/A') ?>
                                        <span class="block text-xs text-gray-500">ID: <?= $f['product_id'] ?></span>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-800 font-medium"><?= htmlspecialchars($f['title']) ?></td>
                                    <td class="py-4 px-6 text-sm text-gray-800">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-indigo-100 text-indigo-800 rounded-full font-semibold">
                                            <?= $f['position'] ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?php if ($f['image_url']): ?>
                                            <img src="<?= htmlspecialchars($f['image_url']) ?>" alt="<?= htmlspecialchars($f['title']) ?>"
                                                class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <a href="?toggle=<?= $f['featured_id'] ?>&<?= http_build_query($queryBase) ?>"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $f['is_active'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">

                                            <?= $f['is_active'] ? 'Active' : 'Inactive' ?>
                                        </a>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-500">
                                        <?= date('M d, Y', strtotime($f['created_at'])) ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex gap-2">
                                            <button onclick="confirmEdit(
                                                    <?= $f['featured_id'] ?>,
                                                    <?= $f['product_id'] ?>,
                                                    '<?= addslashes($f['title']) ?>',
                                                    <?= $f['position'] ?>,
                                                    <?= $f['is_active'] ?>,
                                                    '<?= addslashes($f['image_url'] ?? '') ?>'
                                                )" class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition flex items-center gap-2 text-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="#" onclick="confirmDelete('featured.php?delete=<?= $f['featured_id'] ?>&<?= http_build_query($queryBase) ?>'); return false;"
                                                class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition flex items-center gap-2 text-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="py-16 text-center bg-white rounded-xl shadow border border-gray-200">
                    <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center bg-gray-100 rounded-full">
                        <i class="fas fa-layer-group text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No featured items found</h3>
                    <p class="text-gray-500 mb-6">Get started by adding your first featured item</p>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalFeatured > 0): ?>
                <div class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Showing <?= $totalFeatured ?> item<?= $totalFeatured !== 1 ? 's' : '' ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Overlay -->
    <div id="modalOverlay" class="fixed inset-0 bg-black/50 hidden z-40" onclick="closeModal()"></div>

    <!-- Modal -->
    <div id="featuredModal" class="fixed inset-0 hidden z-50 p-4 flex items-center justify-center">
        <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">Add Featured Item</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="featuredForm" method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="featured_id" id="featuredId" value="<?= $editMode ? $editData['featured_id'] : '' ?>">
                <input type="hidden" name="old_image" id="oldImage" value="<?= $editMode ? ($editData['image_url'] ?? '') : '' ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Product Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product *</label>
                            <select name="product_id" id="productId" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                                <option value="">Select a product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['product_id'] ?>"
                                        <?= $editMode && $editData['product_id'] == $product['product_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                            <input type="text" name="title" id="titleInput" required
                                value="<?= $editMode ? htmlspecialchars($editData['title']) : '' ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                                placeholder="Enter featured title">
                        </div>

                        <!-- Position -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="position" id="positionInput" min="0"
                                    value="<?= $editMode ? $editData['position'] : '0' ?>"
                                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                                    placeholder="Leave 0 for auto">
                                <span class="text-sm text-gray-500">(0 = auto assign)</span>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="flex items-center gap-3 p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition cursor-pointer">
                                <input type="checkbox" name="is_active" id="isActive"
                                    <?= $editMode && isset($editData['is_active']) && $editData['is_active'] ? 'checked' : 'checked' ?>
                                    class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>

                    <!-- Right Column - Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image *</label>
                        <!-- Image Preview -->
                        <div id="imagePreviewContainer" class="mb-4">
                            <?php if ($editMode && !empty($editData['image_url'])): ?>
                                <div class="relative">
                                    <img src="<?= htmlspecialchars($editData['image_url']) ?>"
                                        alt="Current featured image"
                                        class="w-full h-64 object-cover rounded-lg border-2 border-gray-300">
                                    <div class="absolute top-2 right-2 bg-white/80 rounded-full p-2">
                                        <span class="text-xs font-medium text-gray-700">Current</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Preview for new image -->
                        <div id="newImagePreview" class="hidden mt-4">
                            <div class="relative">
                                <img id="newImagePreviewImg" src=""
                                    class="w-full h-64 object-cover rounded-lg border-2 border-gray-300">
                                <button type="button" onclick="removeNewImage()"
                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600 transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <small class="text-gray-500">Max size: 2MB. Supported formats: JPG, PNG, GIF</small>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="closeModal()"
                        class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg transition font-medium">
                        Cancel
                    </button>
                    <button type="submit" name="save_featured"
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition font-medium flex items-center gap-2">
                        <?= $editMode ? 'Update Featured Item' : 'Add Featured Item' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>



    <script src="../../../assets/Js/featured.js"></script>
    <script>
        // Auto-fill featured image when a product is selected
        const productsMap = <?= json_encode(array_column($products, null, 'product_id')) ?>;
        const productSelect = document.getElementById('productId');

        function updatePreviewForProduct() {
            const pid = productSelect.value;
            const previewContainer = document.getElementById('imagePreviewContainer');
            const oldImageInput = document.getElementById('oldImage');

            if (pid && productsMap[pid] && productsMap[pid].image_url) {
                const img = productsMap[pid].image_url;
                oldImageInput.value = img;
                previewContainer.innerHTML = `
                    <div class="relative">
                        <img src="${img}" alt="Product image" class="w-full h-64 object-cover rounded-lg border-2 border-gray-300">
                        <div class="absolute top-2 right-2 bg-white/80 rounded-full p-2">
                            <span class="text-xs font-medium text-gray-700">Product image</span>
                        </div>
                    </div>
                `;
            } else {
                // if no product image, clear old_image but keep any current featured image
                oldImageInput.value = '';
                if (!document.getElementById('newImagePreview') || document.getElementById('newImagePreview').classList.contains('hidden')) {
                    previewContainer.innerHTML = '<p class="text-sm text-gray-500">No image currently set</p>';
                }
            }
        }

        productSelect?.addEventListener('change', updatePreviewForProduct);

        // if modal opened for add and product already selected, update preview
        productSelect?.addEventListener('focus', function() {
            /* no-op placeholder */
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($flash) && ($flash['type'] ?? '') === 'success'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '<?= addslashes($flash['text']) ?>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6'
                });
            <?php elseif (!empty($flash) && ($flash['type'] ?? '') === 'error'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?= addslashes($flash['text']) ?>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>