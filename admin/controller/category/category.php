<?php
require_once __DIR__ . '/../../../config/conn.php';
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
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">
                                Category <span class="gradient-text font-extrabold">Management</span>
                            </h1>
                        </div>
                        <p class="text-gray-600 ml-1">Manage and track all categories in your store.</p>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 fade-in-up">

                <!-- Total Categories -->
                <div class="stat-card bg-gradient-to-br from-white to-indigo-50/50 rounded-2xl p-6 shadow-soft-xl border border-indigo-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Total Categories</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format((int)$totalCategories) ?></p>
                        </div>
                        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-tags text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                            <div>All categories</div>
                            <div>100%</div>
                        </div>
                    </div>
                </div>

                <!-- Total Products -->
                <div class="stat-card bg-gradient-to-br from-white to-green-50/50 rounded-2xl p-6 shadow-soft-xl border border-green-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Total Products</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format((int)$totalProducts) ?></p>
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
                    </div>
                </div>

                <!-- Uncategorized -->
                <div class="stat-card bg-gradient-to-br from-white to-yellow-50/50 rounded-2xl p-6 shadow-soft-xl border border-yellow-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-yellow-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Uncategorized Products</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format((int)$uncategorizedCount) ?></p>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-question-circle text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                            <div><?= $uncategorizedCount > 0 ? 'Needs review' : 'All categorized' ?></div>
                            <div><?= round(($uncategorizedCount / max($totalProducts, 1)) * 100, 1) ?>%</div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- LEFT: Add Category (button -> show panel) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-plus-circle text-indigo-600 mr-2"></i> Add Category
                            </h2>

                            <button type="button"
                                id="toggleAddCategory"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                <i class="fas fa-plus mr-2"></i> New
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- Hidden panel -->
                            <div id="addCategoryPanel" class="hidden">
                                <div class="mb-4 p-4 rounded-lg border border-indigo-100 bg-indigo-50/40">
                                    <div class="flex items-center justify-between">
                                        <div class="font-semibold text-gray-800 flex items-center">
                                            <i class="fas fa-tags text-indigo-600 mr-2"></i> New Category
                                        </div>
                                        <button type="button" id="closeAddCategory" class="text-gray-400 hover:text-gray-700">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Create a new category for your products.</p>
                                </div>

                                <form id="addCategoryForm" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="add_category">

                                    <div>
                                        <label for="category_name" class="block text-sm font-medium text-gray-700 mb-1">
                                            Category Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text"
                                            id="category_name"
                                            name="category_name"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg
                                                focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            placeholder="Enter category name"
                                            required>
                                        <p class="text-xs text-gray-500 mt-1">Example: Nike, Adidas, New Balance</p>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button type="button"
                                            id="cancelAddCategory"
                                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                            Cancel
                                        </button>

                                        <button type="submit"
                                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition inline-flex items-center">
                                            <i class="fas fa-save mr-2"></i> Save
                                        </button>
                                    </div>
                                </form>

                                <hr class="my-6">
                            </div>

                            <div class="text-sm text-gray-600">
                                Click <span class="font-semibold text-gray-800">New</span> to add a category.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Categories List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-list-alt text-indigo-600 mr-2"></i> Existing Categories
                                    <span class="ml-2 bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        <?= (int)$totalCategories ?>
                                    </span>
                                </h2>

                                <div class="flex items-center space-x-2">
                                    <div class="relative">
                                        <input type="text"
                                            id="searchCategory"
                                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
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
                                    <p class="text-gray-400 mt-2">Add your first category using the New button</p>
                                </div>
                            <?php else: ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="categoriesTableBody">
                                        <?php foreach ($categories as $category): ?>
                                            <?php
                                            $cid = (int)($category['category_id'] ?? 0);
                                            $cname = (string)($category['category_name'] ?? '');
                                            $pcount = (int)($productCounts[$cid] ?? 0);
                                            $createdAt = !empty($category['created_at']) ? date('M d, Y', strtotime((string)$category['created_at'])) : '—';
                                            ?>
                                            <tr class="hover:bg-gray-50 transition" id="category-row-<?= $cid ?>">
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                </td>

                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                        <?= $pcount > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                        <i class="fas fa-box mr-1"></i>
                                                        <?= $pcount ?> products
                                                    </span>
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <div class="flex items-center space-x-2">
                                                        <button
                                                            onclick="editCategory(<?= $cid ?>, '<?= htmlspecialchars(addslashes($cname)) ?>')"
                                                            class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 text-sm">
                                                            <i class="fas fa-edit mr-2"></i> Edit
                                                        </button>

                                                        <button
                                                            onclick="deleteCategory(<?= $cid ?>, '<?= htmlspecialchars(addslashes($cname)) ?>', <?= $pcount ?>)"
                                                            class="inline-flex items-center px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 text-sm">
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

    <!-- Edit Category Modal (keep yours) -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-edit text-indigo-600 mr-2"></i> Edit Category
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
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button"
                            onclick="closeEditModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Your existing JS -->
    <script src="../../../assets/Js/category.js"></script>
    <script src="../../../assets/js/reports.js"></script>
    <!-- Toggle Add Category panel -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const btnOpen = document.getElementById("toggleAddCategory");
            const btnClose = document.getElementById("closeAddCategory");
            const btnCancel = document.getElementById("cancelAddCategory");
            const panel = document.getElementById("addCategoryPanel");
            const input = document.getElementById("category_name");

            if (!btnOpen || !panel) return;

            const openPanel = () => {
                panel.classList.remove("hidden");
                setTimeout(() => input?.focus(), 50);
            };

            const closePanel = () => {
                panel.classList.add("hidden");
                if (input) input.value = "";
            };

            btnOpen.addEventListener("click", openPanel);
            btnClose?.addEventListener("click", closePanel);
            btnCancel?.addEventListener("click", closePanel);
        });
    </script>
</body>

</html>