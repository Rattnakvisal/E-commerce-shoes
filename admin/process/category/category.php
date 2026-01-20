<?php
require_once __DIR__ . '/../../../config/conn.php'; // PDO connection
require_once __DIR__ . '/api_category.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../../assets/Css/reports.css">
    <style>
        .success-bg {
            background-color: #d1fae5;
            border-color: #10b981;
        }

        .error-bg {
            background-color: #fee2e2;
            border-color: #ef4444;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <main class="md:ml-64 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Page Header -->
            <div class="mb-6 animate-fade-in">
                <div class="flex flex-col lg:flex-row mb-6 lg:items-center lg:justify-between gap-4">
                    <!-- Title -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Category Management
                        </h1>
                        <p class="text-gray-600 mt-1">
                            Manage product categories and organize your inventory
                        </p>
                    </div>
                </div>
            </div>
            <!-- Summary Stats (Analytics Style) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 fade-in-up">

                <!-- Total Categories -->
                <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Total Categories</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format((int)$totalCategories) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-tags text-xl"></i>
                        </div>
                    </div>

                    <div class="mt-4 relative z-10">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                            <div>All categories</div>
                            <div>100%</div>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-blue-500 w-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Total Products -->
                <div class="stat-card bg-gradient-to-br from-white to-green-50/50 rounded-2xl p-6 shadow-soft-xl border border-green-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Total Products</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format((int)$totalProducts) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-boxes text-xl"></i>
                        </div>
                    </div>

                    <div class="mt-4 relative z-10">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                            <div>Across all categories</div>
                            <div>100%</div>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-green-500 w-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Uncategorized Products -->
                <div class="stat-card bg-gradient-to-br from-white to-yellow-50/50 rounded-2xl p-6 shadow-soft-xl border border-yellow-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-yellow-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Uncategorized Products</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format((int)$uncategorizedCount) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-question-circle text-xl"></i>
                        </div>
                    </div>

                    <div class="mt-4 relative z-10">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                            <div><?= $uncategorizedCount > 0 ? 'Needs review' : 'All categorized' ?></div>
                            <div>
                                <?= round(($uncategorizedCount / max($totalProducts, 1)) * 100, 1) ?>%
                            </div>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-yellow-500"
                                style="width: <?= round(($uncategorizedCount / max($totalProducts, 1)) * 100, 1) ?>%">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Add Category Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-plus-circle text-blue-600 mr-2"></i> Add New Category
                        </h2>

                        <form id="addCategoryForm" method="POST">
                            <input type="hidden" name="action" value="add_category">

                            <div class="mb-4">
                                <label for="category_name" class="block text-gray-700 text-sm font-medium mb-2">
                                    Category Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                    id="category_name"
                                    name="category_name"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    placeholder="Enter category name"
                                    required>
                                <p class="text-xs text-gray-500 mt-1">Enter a descriptive name for your new category</p>
                            </div>

                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                                <i class="fas fa-save mr-2"></i> Add Category
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-list-alt text-blue-600 mr-2"></i> Existing Categories
                                    <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        <?php echo $totalCategories; ?>
                                    </span>
                                </h2>

                                <div class="flex items-center space-x-2">
                                    <div class="relative">
                                        <input type="text"
                                            id="searchCategory"
                                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Search categories...">
                                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <?php if (empty($categories)): ?>
                                <div class="text-center py-12">
                                    <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                                    <p class="text-gray-500 text-lg">No categories found</p>
                                    <p class="text-gray-400 mt-2">Add your first category using the form on the left</p>
                                </div>
                            <?php else: ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Category Name
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Products
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Created Date
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="categoriesTableBody">
                                        <?php foreach ($categories as $category): ?>
                                            <tr class="hover:bg-gray-50 transition duration-150" id="category-row-<?php echo $category['category_id']; ?>">
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                                        <?php echo isset($productCounts[$category['category_id']]) && $productCounts[$category['category_id']] > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                        <i class="fas fa-box mr-1"></i>
                                                        <?php echo isset($productCounts[$category['category_id']]) ? $productCounts[$category['category_id']] : 0; ?> products
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <div class="flex items-center space-x-2">
                                                        <!-- Edit Button -->
                                                        <button onclick="editCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($category['category_name'])); ?>')"
                                                            class="inline-flex items-center px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 text-sm hover-lift">
                                                            <i class="fas fa-edit mr-2"></i> Edit
                                                        </button>

                                                        <!-- Delete Button -->
                                                        <button onclick="deleteCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($category['category_name'])); ?>', <?php echo isset($productCounts[$category['category_id']]) ? $productCounts[$category['category_id']] : 0; ?>)"
                                                            class="inline-flex items-center px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 text-sm hover-lift">
                                                            <i class="fas fa-trash mr-2"></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Category Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-edit text-blue-600 mr-2"></i> Edit Category
                    </h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="editCategoryForm" class="mt-4">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" id="edit_category_id" name="category_id">

                    <div class="mb-4">
                        <label for="edit_category_name" class="block text-gray-700 text-sm font-medium mb-2">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            id="edit_category_name"
                            name="category_name"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button"
                            onclick="closeEditModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                            Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../../assets/Js/category.js"></script>
    <script>
        function showLoading(message = 'Loading...') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: message,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            } else {
                console.log('Loading:', message);
            }
        }

        function showToast(message, icon = 'success') {
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: icon,
                    title: message
                });
            } else {
                console.log(message);
            }
        }

        function showError(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    confirmButtonColor: '#3b82f6'
                });
            } else {
                console.error(message);
            }
        }
    </script>
</body>

</html>