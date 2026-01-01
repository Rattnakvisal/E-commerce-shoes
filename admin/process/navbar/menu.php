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
    <style>
        .fade-in {
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

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

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
    <main class="md:ml-64 min-h-screen">
        <!-- Page Header -->
        <div class="mb-6 animate-fade-in">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 py-4">

                    <!-- Title -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Navbar Manager
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Manage your website navigation menu
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6 flex items-center">
                    <div class="rounded-full bg-blue-100 p-3 mr-4">
                        <i class="fas fa-layer-group text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="parentCount">0</h3>
                        <p class="text-gray-500">Parent Items</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6 flex items-center">
                    <div class="rounded-full bg-green-100 p-3 mr-4">
                        <i class="fas fa-folder text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="groupCount">0</h3>
                        <p class="text-gray-500">Menu Groups</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6 flex items-center">
                    <div class="rounded-full bg-purple-100 p-3 mr-4">
                        <i class="fas fa-link text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="itemCount">0</h3>
                        <p class="text-gray-500">Menu Items</p>
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
</body>

</html>