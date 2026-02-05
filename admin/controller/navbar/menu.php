<?php
require_once __DIR__ . '/../../../config/conn.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar Management Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Your CSS -->
    <link rel="stylesheet" href="../../../assets/Css/reports.css">

    <style>
        .success-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .primary-bg {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        }

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

            <!-- ===============================
          Menu Management Header
      ================================ -->
            <div class="mb-8">
                <div class="relative rounded-3xl border bg-white shadow-soft p-6 sm:p-8">

                    <!-- Soft background accent -->
                    <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-black/[0.04] via-transparent to-black/[0.06] pointer-events-none"></div>

                    <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

                        <!-- Left -->
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-black text-white shadow">
                                    <i class="fas fa-bars"></i>
                                </span>

                                <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                                    Menu <span class="gradient-text ml-2">Management</span>
                                </h1>
                            </div>

                            <p class="text-gray-600 text-sm sm:text-base max-w-2xl">
                                Manage, organize, and control all menu items and navigation structure in your store.
                            </p>

                            <!-- Meta badges -->
                            <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-500">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-compass"></i>
                                    Navigation Structure
                                </span>

                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-route"></i>
                                    Links & Routes
                                </span>

                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Menu Active
                                </span>
                            </div>
                        </div>

                        <!-- Right -->
                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                onclick="window.location.reload()"
                                class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border hover:bg-gray-50 transition"
                                title="Refresh">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Stats -->
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

            <!-- ===============================
            Creation (Better UX)
            ================================ -->
            <section class="mb-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-gray-900 flex items-center gap-2">
                            <span class="inline-flex w-9 h-9 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow">
                                <i class="fa-solid fa-plus"></i>
                            </span>
                            Add Menu (Easy Mode)
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Create Parent → Group → Item with a cleaner flow.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <!-- LEFT: Tabs + Forms -->
                    <div class="xl:col-span-7">
                        <div class="rounded-3xl border bg-white shadow-soft overflow-hidden">
                            <!-- Tabs -->
                            <div class="p-4 sm:p-5 border-b bg-white/70 backdrop-blur">
                                <div class="flex flex-wrap gap-2" id="menuTabs">
                                    <button type="button" data-tab="tab-parent"
                                        class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-2xl border bg-indigo-600 text-white shadow-sm">
                                        <i class="fa-solid fa-layer-group"></i> Parent
                                    </button>

                                    <button type="button" data-tab="tab-group"
                                        class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-2xl border hover:bg-gray-50">
                                        <i class="fa-solid fa-folder-tree text-green-600"></i> Group
                                    </button>

                                    <button type="button" data-tab="tab-item"
                                        class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-2xl border hover:bg-gray-50">
                                        <i class="fa-solid fa-link text-purple-600"></i> Item
                                    </button>
                                </div>
                            </div>

                            <!-- Forms -->
                            <div class="p-5 sm:p-7">
                                <!-- PARENT -->
                                <div id="tab-parent" class="tab-panel">
                                    <div class="flex items-start justify-between gap-4 mb-5">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900">Add Parent Menu</h3>
                                            <p class="text-sm text-gray-500 mt-1">Top level navigation title (e.g., “Shop”, “Pages”).</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-blue-50 text-blue-700 px-3 py-1 text-xs font-semibold">
                                            Level 1
                                        </span>
                                    </div>

                                    <form id="addParentForm" class="space-y-5">
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                            <div class="sm:col-span-8">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
                                                <input type="text" name="title" required
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                                    placeholder="e.g., Main Menu">
                                                <p class="text-xs text-gray-500 mt-2">Shown as the main clickable label.</p>
                                            </div>

                                            <div class="sm:col-span-4">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Position</label>
                                                <input type="number" name="position" value="1" min="1"
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                                <p class="text-xs text-gray-500 mt-2">Small number = earlier.</p>
                                            </div>
                                        </div>

                                        <button type="submit"
                                            class="w-full primary-bg text-white font-semibold py-3.5 rounded-2xl hover:opacity-95 transition flex items-center justify-center gap-2 shadow-sm">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                            Save Parent
                                        </button>
                                    </form>
                                </div>

                                <!-- GROUP -->
                                <div id="tab-group" class="tab-panel hidden">
                                    <div class="flex items-start justify-between gap-4 mb-5">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900">Add Menu Group</h3>
                                            <p class="text-sm text-gray-500 mt-1">A group holds multiple items (e.g., “Products”, “Categories”).</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-green-50 text-green-700 px-3 py-1 text-xs font-semibold">
                                            Level 2
                                        </span>
                                    </div>

                                    <form id="addGroupForm" class="space-y-5">
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                            <div class="sm:col-span-6">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Parent</label>
                                                <select name="parent_id" id="parentSelect"
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                                                    <option value="">-- No parent --</option>
                                                </select>
                                                <p class="text-xs text-gray-500 mt-2">Attach group under a parent menu.</p>
                                            </div>

                                            <div class="sm:col-span-6">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Position</label>
                                                <input type="number" name="position" value="1" min="1"
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                                                <p class="text-xs text-gray-500 mt-2">Order inside parent.</p>
                                            </div>

                                            <div class="sm:col-span-12">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Group Title</label>
                                                <input type="text" name="group_title" required
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                                                    placeholder="e.g., Products">
                                            </div>

                                            <div class="sm:col-span-12">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Link URL (Optional)</label>
                                                <input type="text" name="link_url"
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                                                    placeholder="/products.php">
                                                <p class="text-xs text-gray-500 mt-2">If group title should be clickable, set a URL.</p>
                                            </div>
                                        </div>

                                        <button type="submit"
                                            class="w-full success-bg text-white font-semibold py-3.5 rounded-2xl hover:opacity-95 transition flex items-center justify-center gap-2 shadow-sm">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                            Save Group
                                        </button>
                                    </form>
                                </div>

                                <!-- ITEM -->
                                <div id="tab-item" class="tab-panel hidden">
                                    <div class="flex items-start justify-between gap-4 mb-5">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900">Add Menu Item</h3>
                                            <p class="text-sm text-gray-500 mt-1">Final link inside a group (e.g., “All Shoes”, “New Arrivals”).</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-purple-50 text-purple-700 px-3 py-1 text-xs font-semibold">
                                            Level 3
                                        </span>
                                    </div>

                                    <form id="addItemForm" class="space-y-5">
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                            <div class="sm:col-span-7">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Group</label>
                                                <select name="group_id" id="groupSelect" required
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                                                    <option value="">-- Select Group --</option>
                                                </select>
                                                <p class="text-xs text-gray-500 mt-2">Pick where this link belongs.</p>
                                            </div>

                                            <div class="sm:col-span-5">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Position</label>
                                                <input type="number" name="position" value="1" min="1"
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                                                <p class="text-xs text-gray-500 mt-2">Order inside group.</p>
                                            </div>

                                            <div class="sm:col-span-12">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Item Title</label>
                                                <input type="text" name="item_title" required
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                                                    placeholder="e.g., New Arrivals">
                                            </div>

                                            <div class="sm:col-span-12">
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Link URL</label>
                                                <input type="text" name="link_url" required
                                                    class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"
                                                    placeholder="/products.php?filter=new">
                                            </div>
                                        </div>

                                        <button type="submit"
                                            class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-3.5 rounded-2xl hover:opacity-95 transition flex items-center justify-center gap-2 shadow-sm">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                            Save Item
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Live Preview -->
                    <aside class="xl:col-span-5">
                        <div class="rounded-3xl border bg-white shadow-soft overflow-hidden">
                            <div class="p-5 border-b bg-white/70 backdrop-blur">
                                <h3 class="text-lg font-extrabold text-gray-900 flex items-center gap-2">
                                    <i class="fa-solid fa-eye text-gray-700"></i>
                                    Live Preview
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">This preview updates as you type.</p>
                            </div>

                            <div class="p-5">
                                <div class="rounded-2xl border border-gray-200 bg-gray-50/50 p-4">
                                    <div class="text-xs text-gray-500 mb-2">Example Navbar</div>

                                    <div class="space-y-3 text-sm">
                                        <div class="flex items-center justify-between">
                                            <div class="font-semibold text-gray-900" id="pvParent">Parent: —</div>
                                            <span class="text-xs px-2 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold">L1</span>
                                        </div>

                                        <div class="pl-4 border-l border-gray-200 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <div class="font-semibold text-gray-900" id="pvGroup">Group: —</div>
                                                <span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 font-semibold">L2</span>
                                            </div>

                                            <div class="pl-4 border-l border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div class="font-semibold text-gray-900" id="pvItem">Item: —</div>
                                                    <span class="text-xs px-2 py-1 rounded-full bg-purple-50 text-purple-700 font-semibold">L3</span>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1" id="pvUrl">URL: —</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="rounded-2xl border p-4">
                                        <div class="text-xs text-gray-500">Tip</div>
                                        <div class="text-sm font-semibold text-gray-900 mt-1">Use positions</div>
                                        <p class="text-xs text-gray-500 mt-1">1,2,3… keeps your menu sorted.</p>
                                    </div>
                                    <div class="rounded-2xl border p-4">
                                        <div class="text-xs text-gray-500">Tip</div>
                                        <div class="text-sm font-semibold text-gray-900 mt-1">Group first</div>
                                        <p class="text-xs text-gray-500 mt-1">Create group before items.</p>
                                    </div>
                                    <div class="rounded-2xl border p-4">
                                        <div class="text-xs text-gray-500">Tip</div>
                                        <div class="text-sm font-semibold text-gray-900 mt-1">URL check</div>
                                        <p class="text-xs text-gray-500 mt-1">Start with “/” for internal routes.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <!-- ===============================
     Current Menu Structure (BETTER + EASY VIEW)
================================ -->
            <div class="rounded-3xl border bg-white shadow-soft overflow-hidden">
                <!-- Header -->
                <div class="p-6 border-b border-gray-200/80 bg-white/70 backdrop-blur flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-indigo-600 text-white shadow">
                                <i class="fas fa-sitemap"></i>
                            </span>
                            Current Menu Structure
                        </h2>
                        <p class="text-gray-500 mt-1 text-sm">
                            Clean hierarchical view — search, expand/collapse, and quick actions.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="expandAllBtn"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border hover:bg-gray-50 transition text-sm font-semibold">
                            <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                            Expand
                        </button>
                        <button type="button" id="collapseAllBtn"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border hover:bg-gray-50 transition text-sm font-semibold">
                            <i class="fa-solid fa-down-left-and-up-right-to-center"></i>
                            Collapse
                        </button>
                        <button type="button" onclick="window.location.reload()"
                            class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border hover:bg-gray-50 transition"
                            title="Refresh">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="p-5 sm:p-6 border-b border-gray-200/80">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                        <!-- Search -->
                        <div class="lg:col-span-6">
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                                <input id="menuSearch" type="text"
                                    class="w-full pl-10 pr-10 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                    placeholder="Search parent, group, item... (e.g., Products, Home, Shoes)">
                                <button type="button" id="menuSearchClear"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 hidden">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Quick chips -->
                        <div class="lg:col-span-6 flex flex-wrap gap-2 lg:justify-end">
                            <span class="inline-flex items-center gap-2 px-3 py-2 rounded-2xl bg-blue-50 text-blue-700 text-xs font-semibold">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span> Parent
                            </span>
                            <span class="inline-flex items-center gap-2 px-3 py-2 rounded-2xl bg-green-50 text-green-700 text-xs font-semibold">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span> Group
                            </span>
                            <span class="inline-flex items-center gap-2 px-3 py-2 rounded-2xl bg-purple-50 text-purple-700 text-xs font-semibold">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span> Item
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-5 sm:p-6">
                    <!-- Scroll wrapper -->
                    <div class="max-h-[560px] overflow-y-auto pr-2">
                        <!-- IMPORTANT:
           menu.js should render inside this container.
           Each parent/group/item should be output as <details class="menu-node"> ... </details>
      -->
                        <div id="menuStructure" class="space-y-4">
                            <!-- Empty State -->
                            <div class="text-center py-12 text-gray-400">
                                <div class="mx-auto w-14 h-14 rounded-3xl bg-gray-100 flex items-center justify-center mb-3">
                                    <i class="fas fa-folder-open text-2xl"></i>
                                </div>
                                <p class="text-sm font-semibold">No menu items yet</p>
                                <p class="text-xs text-gray-400 mt-1">Create Parent → Group → Item from the form above.</p>
                            </div>
                        </div>

                        <!-- No results -->
                        <div id="menuNoResults" class="hidden text-center py-10 text-gray-400">
                            <div class="mx-auto w-14 h-14 rounded-3xl bg-gray-100 flex items-center justify-center mb-3">
                                <i class="fa-solid fa-filter-circle-xmark text-2xl"></i>
                            </div>
                            <p class="text-sm font-semibold">No results</p>
                            <p class="text-xs text-gray-400 mt-1">Try another keyword.</p>
                        </div>
                    </div>
                </div>
            </div>
            <template id="menuNodeTemplate">
                <!-- Example structure menu.js can clone -->
                <details class="menu-node group rounded-3xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <summary class="cursor-pointer select-none p-4 sm:p-5 flex items-start justify-between gap-4 hover:bg-gray-50/80 transition">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-blue-50 text-blue-700 border border-blue-100">
                                <i class="fa-solid fa-layer-group"></i>
                            </span>

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-extrabold text-gray-900 leading-tight">
                                        Parent Title
                                    </h4>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-[11px] font-semibold">
                                        #1
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-gray-500">
                                    <span class="inline-flex items-center gap-1">
                                        <i class="fa-solid fa-diagram-project"></i> 3 groups
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <i class="fa-solid fa-link"></i> 12 items
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right actions -->
                        <div class="flex items-center gap-2">
                            <button type="button"
                                class="px-3 py-2 rounded-2xl border hover:bg-white transition text-xs font-semibold"
                                data-action="edit-parent">
                                <i class="fa-solid fa-pen mr-1"></i> Edit
                            </button>
                            <button type="button"
                                class="px-3 py-2 rounded-2xl border border-red-200 text-red-600 hover:bg-red-50 transition text-xs font-semibold"
                                data-action="delete-parent">
                                <i class="fa-solid fa-trash mr-1"></i> Delete
                            </button>
                            <span class="ml-1 text-gray-400 group-open:rotate-180 transition">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </div>
                    </summary>

                    <!-- Children container -->
                    <div class="p-4 sm:p-5 pt-0">
                        <div class="mt-4 pl-4 border-l border-gray-200 space-y-3">
                            <!-- group nodes go here -->
                        </div>
                    </div>
                </details>
            </template>
        </div>
    </main>
    <script src="../../../assets/Js/menu.js"></script>
</body>

</html>