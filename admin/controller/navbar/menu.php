<?php
require_once __DIR__ . '/../../../config/connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar Management Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../../../assets/Css/reports.css">
    <style>
        .success-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .primary-bg {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        }

        /* Modal styles */
        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>
    <!-- Header -->
    <main class="p-4 sm:p-6 lg:p-8 min-h-screen animate-fade-in">
        <div class="md:ml-64">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title -->
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">Menu<span class="gradient-text font-extrabold ml-2">Management</span></h1>
                        </div>
                        <p class="text-gray-600 ml-1">Manage and track all menu items in your store.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 fade-in-up">

                <!-- Parent Items -->
                <div class="stat-card group relative overflow-hidden rounded-2xl border border-blue-100/50 
                bg-gradient-to-br from-white to-blue-50/50 p-6 shadow-soft-xl 
                hover:shadow-glow-blue transition-all duration-300">

                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/5 rounded-full -translate-y-12 translate-x-12"></div>

                    <div class="relative z-10 flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 tracking-wide">Parent Items</h3>
                            <p class="mt-2 text-2xl font-bold text-gray-900 glow-text" id="parentCount">0</p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl
                        bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-md">
                            <i class="fas fa-layer-group text-lg"></i>
                        </div>
                    </div>

                    <div class="relative z-10 flex justify-between text-sm text-gray-500">
                        <span>Top-level menus</span>
                        <span class="font-semibold text-blue-600">100%</span>
                    </div>
                </div>

                <!-- Menu Groups -->
                <div class="stat-card group relative overflow-hidden rounded-2xl border border-green-100/50 
                bg-gradient-to-br from-white to-green-50/50 p-6 shadow-soft-xl 
                hover:shadow-glow-green transition-all duration-300">

                    <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/5 rounded-full -translate-y-12 translate-x-12"></div>

                    <div class="relative z-10 flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 tracking-wide">Menu Groups</h3>
                            <p class="mt-2 text-2xl font-bold text-gray-900" id="groupCount">0</p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl
                        bg-gradient-to-br from-green-500 to-green-600 text-white shadow-md">
                            <i class="fas fa-folder-tree text-lg"></i>
                        </div>
                    </div>

                    <div class="relative z-10 flex justify-between text-sm text-gray-500">
                        <span>Grouped navigation</span>
                        <span class="font-semibold text-green-600">—</span>
                    </div>
                </div>

                <!-- Menu Items -->
                <div class="stat-card group relative overflow-hidden rounded-2xl border border-purple-100/50 
                bg-gradient-to-br from-white to-purple-50/50 p-6 shadow-soft-xl 
                hover:shadow-glow-purple transition-all duration-300">

                    <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/5 rounded-full -translate-y-12 translate-x-12"></div>

                    <div class="relative z-10 flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 tracking-wide">Menu Items</h3>
                            <p class="mt-2 text-2xl font-bold text-gray-900" id="itemCount">0</p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl
                        bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-md">
                            <i class="fas fa-link text-lg"></i>
                        </div>
                    </div>

                    <div class="relative z-10 flex justify-between text-sm text-gray-500">
                        <span>Total links</span>
                        <span class="font-semibold text-purple-600">—</span>
                    </div>
                </div>
            </div>
            <!-- Creation Forms -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-plus-circle mr-2 text-indigo-600"></i>
                    Add New Menu Items
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- ADD PARENT -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 card-hover transition-all duration-300 fade-in">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <i class="fas fa-layer-group mr-2 text-blue-600"></i>
                                    Add Parent Menu
                                </h3>
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Level 1</span>
                            </div>

                            <form id="addParentForm" class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-heading mr-1 text-gray-400"></i>
                                        Title
                                    </label>
                                    <input type="text" name="title"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="e.g., Main Menu"
                                        required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-sort-numeric-down mr-1 text-gray-400"></i>
                                        Position
                                    </label>
                                    <input type="number" name="position" value="1"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                </div>
                                <button type="submit"
                                    class="w-full primary-bg text-white font-medium py-3 px-4 rounded-lg hover:opacity-90 transition duration-300 flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Parent
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- ADD GROUP -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 card-hover transition-all duration-300 fade-in">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <i class="fas fa-folder mr-2 text-green-600"></i>
                                    Add Menu Group
                                </h3>
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Level 2</span>
                            </div>

                            <form id="addGroupForm" class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-layer-group mr-1 text-gray-400"></i>
                                        Parent
                                    </label>
                                    <select name="parent_id" id="parentSelect"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                                        <option value="">-- No parent --</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-heading mr-1 text-gray-400"></i>
                                        Group Title
                                    </label>
                                    <input type="text" name="group_title"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                                        placeholder="e.g., Products"
                                        required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-link mr-1 text-gray-400"></i>
                                        Link URL (Optional)
                                    </label>
                                    <input type="text" name="link_url"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                                        placeholder="/products.php">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-sort-numeric-down mr-1 text-gray-400"></i>
                                        Position
                                    </label>
                                    <input type="number" name="position" value="1"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                                </div>
                                <button type="submit"
                                    class="w-full success-bg text-white font-medium py-3 px-4 rounded-lg hover:opacity-90 transition duration-300 flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Group
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- ADD ITEM -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 card-hover transition-all duration-300 fade-in">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <i class="fas fa-link mr-2 text-purple-600"></i>
                                    Add Menu Item
                                </h3>
                                <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Level 3</span>
                            </div>

                            <form id="addItemForm" class="space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-folder mr-1 text-gray-400"></i>
                                        Group
                                    </label>
                                    <select name="group_id" id="groupSelect"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition" required>
                                        <option value="">-- Select Group --</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-heading mr-1 text-gray-400"></i>
                                        Item Title
                                    </label>
                                    <input type="text" name="item_title"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                                        placeholder="e.g., Product Details"
                                        required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-link mr-1 text-gray-400"></i>
                                        Link URL
                                    </label>
                                    <input type="text" name="link_url"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                                        placeholder="/product-details.php"
                                        required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-sort-numeric-down mr-1 text-gray-400"></i>
                                        Position
                                    </label>
                                    <input type="number" name="position" value="1"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                                </div>
                                <button type="submit"
                                    class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium py-3 px-4 rounded-lg hover:opacity-90 transition duration-300 flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Item
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Menu Structure -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                <!-- Header -->
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-sitemap mr-2 text-indigo-600"></i>
                            Current Menu Structure
                        </h2>
                        <p class="text-gray-500 mt-1 text-sm">
                            Hierarchical view of your navigation menu
                        </p>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Scroll wrapper -->
                    <div class="max-h-[500px] overflow-y-auto pr-2">
                        <div id="menuStructure" class="space-y-4">
                            <div class="text-center py-10 text-gray-400">
                                <i class="fas fa-folder-open text-3xl mb-3"></i>
                                <p class="text-sm">No menu items yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Edit Modals -->
        <div id="editParentModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg w-full max-w-md p-6 m-4">
                <h3 class="text-lg font-semibold mb-4">Edit Parent Menu</h3>
                <form id="editParentForm" class="space-y-4">
                    <input type="hidden" name="id" id="editParentId">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input id="editParentTitle" name="title" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                        <input id="editParentPosition" name="position" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div class="flex justify-end space-x-2 pt-4">
                        <button type="button" onclick="closeModal('editParentModal')" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="editGroupModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg w-full max-w-md p-6 m-4">
                <h3 class="text-lg font-semibold mb-4">Edit Menu Group</h3>
                <form id="editGroupForm" class="space-y-4">
                    <input type="hidden" name="id" id="editGroupId">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Group Title</label>
                        <input id="editGroupTitle" name="group_title" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Link URL (optional)</label>
                        <input id="editGroupUrl" name="link_url" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parent</label>
                        <select id="editGroupParentSelect" name="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">-- No parent --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                        <input id="editGroupPosition" name="position" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    </div>
                    <div class="flex justify-end space-x-2 pt-4">
                        <button type="button" onclick="closeModal('editGroupModal')" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="editItemModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg w-full max-w-md p-6 m-4">
                <h3 class="text-lg font-semibold mb-4">Edit Menu Item</h3>
                <form id="editItemForm" class="space-y-4">
                    <input type="hidden" name="id" id="editItemId">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Title</label>
                        <input id="editItemTitle" name="item_title" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Link URL</label>
                        <input id="editItemUrl" name="link_url" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Group</label>
                        <select id="editItemGroupSelect" name="group_id" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                            <option value="">-- Select Group --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                        <input id="editItemPosition" name="position" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-purple-500 focus:border-purple-500" required>
                    </div>
                    <div class="flex justify-end space-x-2 pt-4">
                        <button type="button" onclick="closeModal('editItemModal')" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Add inline JavaScript -->
    <script src="../../../assets/Js/menu.js"></script>
    <script src="../../../assets/js/reports.js"></script>
</body>

</html>