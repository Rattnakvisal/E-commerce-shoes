<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentRole = $_SESSION['role'] ?? null;
$admin_name = $_SESSION['admin_name'] ?? $_SESSION['name'] ?? 'Admin';
$admin_role = ucfirst($_SESSION['role'] ?? 'Admin');
// Admin avatar: use session value when available, otherwise generate a placeholder avatar
$admin_avatar = $_SESSION['admin_avatar'] ?? $_SESSION['avatar'] ?? '';
if (empty($admin_avatar)) {
    $initials = rawurlencode($admin_name);
    $admin_avatar = "https://ui-avatars.com/api/?name={$initials}&background=ffffff&color=111827&rounded=true&size=128";
}
?>
<div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>
<!-- Sidebar for Desktop -->
<div class="hidden md:flex flex-col fixed top-0 left-0 h-full w-64 bg-white border-r border-gray-200 sidebar-transition z-30">
    <!-- Admin Info -->
    <div class="px-4 py-2 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <!-- Logo -->
            <img src="https://i.pinimg.com/736x/04/11/26/04112661e97e3ccba6176d69c49ba8a5.jpg"
                alt="Logo"
                class="w-12 h-12 rounded-lg object-contain bg-white p-1">

            <!-- Text -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">
                <h2 class="text-xl font-bold">My Brand</h2>
                </p>
            </div>

        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto hide-scrollbar">
        <!-- Dashboard -->
        <?php if ($currentRole === 'admin'): ?>
            <a href="/Pos-system_drink/admin/dashboard.php"
                class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift active-menu-item">
                <i class="fas fa-home mr-3 text-gray-500 w-5 text-center"></i>
                Dashboard
                <span class="ml-auto bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full">5</span>
            </a>
        <?php elseif ($currentRole === 'staff'): ?>
            <a href="/Pos-system_drink/pos/staff_dashboard.php"
                class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                <i class="fas fa-home mr-3 text-gray-500 w-5 text-center"></i>
                Staff Dashboard
            </a>
        <?php endif; ?>

        <!-- Users (admin only) -->
        <?php if ($currentRole === 'admin'): ?>
            <a href="/Pos-system_drink/admin/process/user/user.php"
                class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                <i class="fas fa-users mr-3 text-gray-500 w-5 text-center"></i>
                Users
            </a>
        <?php endif; ?>

        <!-- Navbar Manager -->
        <a href="/Pos-system_drink/admin/process/navbar/menu.php"
            class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
            <i class="fas fa-bars mr-3 text-gray-600 w-5 text-center"></i>
            Navbar Manager
        </a>
        <!-- E-commerce -->
        <div class="pt-2">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">E-commerce</p>
            <div class="mt-2 space-y-1">
                <a href="/Pos-system_drink/admin/process/products/products.php"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                    <i class="fas fa-shopping-bag mr-3 text-gray-500 w-5 text-center"></i>
                    Products
                </a>
                <!-- Item Dropdown -->
                <div class="relative">
                    <button onclick="toggleItemDropdown(this)"
                        class="mobile-nav-item w-full flex items-center px-3 py-3 text-sm font-medium rounded-lg
               text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fa-solid fa-folder-open mr-3 text-gray-500 w-5 text-center"></i>
                        Item
                        <span class="ml-auto flex items-center gap-2">
                            <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full">24</span>
                            <i class="itemChevron fa-solid fa-chevron-down text-xs transition-transform"></i>
                        </span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div class="itemDropdown hidden mt-1 ml-8 space-y-1">

                        <a href="/Pos-system_drink/admin/products.php"
                            class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                            Featured
                        </a>

                        <a href="/Pos-system_drink/admin/process/category/category.php"
                            class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                            Categories
                        </a>

                        <a href="/Pos-system_drink/admin/process/slides/slides.php"
                            class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                            Slide
                        </a>
                    </div>
                </div>
                <a href="/Pos-system_drink/admin/orders.php"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                    <i class="fas fa-shopping-cart mr-3 text-gray-500 w-5 text-center"></i>
                    Orders
                    <span class="ml-auto bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full">3</span>
                </a>
            </div>
        </div>
        <!-- Analytics Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Analytics</p>
            <div class="mt-2 space-y-1">
                <a href="/Pos-system_drink/admin/analytics.php"
                    class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                    <i class="fas fa-chart-bar mr-3 text-gray-500 w-5 text-center"></i>
                    Analytics
                </a>

                <a href="/Pos-system_drink/admin/reports.php"
                    class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                    <i class="fas fa-chart-pie mr-3 text-gray-500 w-5 text-center"></i>
                    Reports
                </a>

                <a href="/Pos-system_drink/admin/insights.php"
                    class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                    <i class="fas fa-chart-line mr-3 text-gray-500 w-5 text-center"></i>
                    Insights
                </a>
            </div>
        </div>

        <!-- Settings -->
        <div class="pt-2">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Settings</p>
            <div class="mt-2 space-y-1">
                <a href="/Pos-system_drink/admin/settings.php"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                    <i class="fas fa-cog mr-3 text-gray-500 w-5 text-center"></i>
                    Settings
                </a>
                <a href="/Pos-system_drink/admin/logs.php"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                    <i class="fas fa-clipboard-list mr-3 text-gray-500 w-5 text-center"></i>
                    Activity Logs
                </a>
                <a href="/Pos-system_drink/admin/security.php"
                    class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                    <i class="fas fa-shield-alt mr-3 text-gray-500 w-5 text-center"></i>
                    Security
                </a>
            </div>
        </div>
    </nav>
</div>

<!-- Main Content Area -->
<div class="md:ml-64">
    <!-- Top Navbar -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuButton"
                        class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100">
                        <i class="fas fa-bars text-lg"></i>
                    </button>

                    <!-- Search -->
                    <div class="relative hidden md:block">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text"
                            class="pl-10 pr-4 py-2 w-64 lg:w-96 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Search...">
                    </div>
                </div>

                <!-- Right: Notifications & Admin Dropdown -->
                <div class="flex items-center space-x-3">
                    <!-- Search Mobile -->
                    <button id="mobileSearchButton"
                        class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100">
                        <i class="fas fa-search"></i>
                    </button>

                    <!-- Notifications -->
                    <div class="relative">
                        <button id="notificationsButton"
                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 relative">
                            <i class="fas fa-bell"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
                        </button>

                        <!-- Notifications Dropdown -->
                        <div id="notificationsDropdown"
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 hidden dropdown-transition z-50">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-800">Notifications</h3>
                                    <button class="text-xs text-indigo-600 hover:text-indigo-800">Mark all as read</button>
                                </div>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <!-- Notification Items -->
                                <a href="#" class="flex items-start px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user-plus text-blue-600 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900">New user registered</p>
                                        <p class="text-xs text-gray-500 mt-1">John Doe just signed up</p>
                                        <p class="text-xs text-gray-400 mt-1">2 minutes ago</p>
                                    </div>
                                </a>

                                <a href="#" class="flex items-start px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-shopping-cart text-green-600 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900">New order received</p>
                                        <p class="text-xs text-gray-500 mt-1">Order #ORD-78945</p>
                                        <p class="text-xs text-gray-400 mt-1">1 hour ago</p>
                                    </div>
                                </a>

                                <a href="#" class="flex items-start px-4 py-3 hover:bg-gray-50">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-exclamation-triangle text-yellow-600 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900">System Alert</p>
                                        <p class="text-xs text-gray-500 mt-1">High server load detected</p>
                                        <p class="text-xs text-gray-400 mt-1">3 hours ago</p>
                                    </div>
                                </a>
                            </div>
                            <div class="p-3 border-t border-gray-200">
                                <a href="#" class="block text-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                    View all notifications
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="relative">
                        <button id="messagesButton"
                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 relative">
                            <i class="fas fa-envelope"></i>
                            <span class="absolute -top-1 -right-1 bg-indigo-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">5</span>
                        </button>

                        <!-- Messages Dropdown -->
                        <div id="messagesDropdown"
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 hidden dropdown-transition z-50">
                            <div class="p-4 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-800">Messages</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <!-- Message Items -->
                                <a href="#" class="flex items-start px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                    <img src="https://ui-avatars.com/api/?name=Sarah+Smith&background=10b981&color=fff"
                                        alt="Sarah"
                                        class="w-8 h-8 rounded-full">
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-gray-900">Sarah Smith</p>
                                            <span class="text-xs text-gray-400">10:42 AM</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1 truncate">Can we schedule a meeting for tomorrow?</p>
                                    </div>
                                </a>

                                <a href="#" class="flex items-start px-4 py-3 hover:bg-gray-50">
                                    <img src="https://ui-avatars.com/api/?name=David+Wilson&background=6366f1&color=fff"
                                        alt="David"
                                        class="w-8 h-8 rounded-full">
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-gray-900">David Wilson</p>
                                            <span class="text-xs text-gray-400">Yesterday</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1 truncate">The report is ready for review</p>
                                    </div>
                                </a>
                            </div>
                            <div class="p-3 border-t border-gray-200">
                                <a href="#" class="block text-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                    View all messages
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- Separator -->
                    <div class="h-6 w-px bg-gray-300"></div>

                    <!-- Admin Dropdown -->
                    <div class="relative">
                        <button id="adminDropdownButton"
                            class="flex items-center space-x-3 p-1 rounded-lg hover:bg-gray-100">
                            <img src="<?php echo htmlspecialchars($admin_avatar); ?>"
                                alt="Admin"
                                class="w-8 h-8 rounded-full border border-gray-300">
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($admin_name); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_role); ?></p>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs hidden md:block"></i>
                        </button>

                        <!-- Admin Dropdown Menu -->
                        <div id="adminDropdownMenu"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden dropdown-transition z-50">
                            <div class="p-4 border-b border-gray-200">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($admin_name); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($admin_role); ?></p>
                            </div>
                            <div class="p-2">
                                <a href="/Pos-system_drink/admin/profile.php"
                                    class="flex items-center px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                                    <i class="fas fa-user mr-3 text-gray-500 w-5 text-center"></i>
                                    My Profile
                                </a>
                                <a href="/Pos-system_drink/admin/activity.php"
                                    class="flex items-center px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                                    <i class="fas fa-chart-line mr-3 text-gray-500 w-5 text-center"></i>
                                    Activity
                                </a>
                            </div>
                            <div class="p-2 border-t border-gray-200">
                                <a href="/Pos-system_drink/auth/logout.php"
                                    onclick="return confirm('Are you sure you want to logout?');"
                                    class="flex items-center px-3 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                                    <i class="fas fa-sign-out-alt mr-3 text-red-500 w-5 text-center"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Search Bar -->
            <div id="mobileSearchBar" class="md:hidden py-3 hidden">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text"
                        class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Search...">
                </div>
            </div>
        </div>
    </header>
</div>

<!-- Mobile Sidebar -->
<div id="mobileSidebar"
    class="fixed top-0 left-0 h-full w-64 bg-white border-r border-gray-200 transform -translate-x-full sidebar-transition z-50 md:hidden">
    <!-- Mobile Admin Info -->
    <div class="px-4 py-5 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <img src="<?php echo htmlspecialchars($admin_avatar); ?>"
                alt="Admin"
                class="w-10 h-10 rounded-full border-2 border-indigo-100">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($admin_name); ?></p>
                <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($admin_role); ?></p>
            </div>
            <button id="closeMobileMenu" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 touch-feedback">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div class="px-3 py-4 overflow-y-auto h-[calc(100%-8rem)] hide-scrollbar">
        <nav class="space-y-1">
            <!-- Dashboard -->
            <a href="/Pos-system_drink/admin/dashboard.php"
                class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 active-menu-item touch-feedback">
                <i class="fas fa-home mr-3 text-gray-500 w-5 text-center"></i>
                Dashboard
                <span class="ml-auto bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">5</span>
            </a>

            <!-- Users -->
            <a href="/Pos-system_drink/admin/process/user/user.php"
                class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                <i class="fas fa-users mr-3 text-gray-500 w-5 text-center"></i>
                Users
            </a>

            <!-- Navbar Manager -->
            <a href="/Pos-system_drink/admin/process/navbar/menu.php"
                class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                <i class="fas fa-bars mr-3 text-gray-600 w-5 text-center"></i>
                Navbar Manager
            </a>

            <!-- E-commerce Section -->
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">E-commerce</p>
                <div class="mt-2 space-y-1">
                    <a href="/Pos-system_drink/admin/process/products/products.php" class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-shopping-bag mr-3 text-gray-500 w-5 text-center"></i>
                        Products
                    </a>

                    <!-- Item Dropdown -->
                    <div class="relative">
                        <button onclick="toggleItemDropdown(this)"
                            class="mobile-nav-item w-full flex items-center px-3 py-3 text-sm font-medium rounded-lg
                                    text-gray-700 hover:bg-gray-100 touch-feedback">
                            <i class="fa-solid fa-folder-open mr-3 text-gray-500 w-5 text-center"></i>
                            Item
                            <span class="ml-auto flex items-center gap-2">
                                <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full">24</span>
                                <i class="itemChevron fa-solid fa-chevron-down text-xs transition-transform"></i>
                            </span>
                        </button>
                        <!-- Dropdown Menu -->
                        <div class="itemDropdown hidden mt-1 ml-8 space-y-1">

                            <a href="/Pos-system_drink/admin/products.php"
                                class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                                Featured
                            </a>

                            <a href="/Pos-system_drink/admin/process/category/category.php"
                                class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                                Categories
                            </a>

                            <a href="/Pos-system_drink/admin/process/slides/slides.php"
                                class="block px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                                Slide
                            </a>
                        </div>
                    </div>

                    <a href="/Pos-system_drink/admin/orders.php" class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-shopping-cart mr-3 text-gray-500 w-5 text-center"></i>
                        Orders
                    </a>
                </div>
            </div>

            <!-- Analytics Section -->
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Analytics</p>
                <div class="mt-2 space-y-1">
                    <a href="/Pos-system_drink/admin/analytics.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-chart-bar mr-3 text-gray-500 w-5 text-center"></i>
                        Analytics
                    </a>

                    <a href="/Pos-system_drink/admin/reports.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-chart-pie mr-3 text-gray-500 w-5 text-center"></i>
                        Reports
                    </a>

                    <a href="/Pos-system_drink/admin/insights.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-chart-line mr-3 text-gray-500 w-5 text-center"></i>
                        Insights
                    </a>
                </div>
            </div>

            <!-- Settings Section -->
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Settings</p>
                <div class="mt-2 space-y-1">
                    <a href="/Pos-system_drink/admin/settings.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-cog mr-3 text-gray-500 w-5 text-center"></i>
                        Settings
                    </a>

                    <a href="/Pos-system_drink/admin/logs.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-clipboard-list mr-3 text-gray-500 w-5 text-center"></i>
                        Activity Logs
                    </a>

                    <a href="/Pos-system_drink/admin/security.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-shield-alt mr-3 text-gray-500 w-5 text-center"></i>
                        Security
                    </a>
                </div>
            </div>
        </nav>
    </div>
</div>
<script>
    function toggleItemDropdown(btn) {
        if (!btn) return;
        const parent = btn.closest('.relative');
        if (!parent) return;
        const dropdown = parent.querySelector('.itemDropdown');
        const chevron = parent.querySelector('.itemChevron');

        if (dropdown) dropdown.classList.toggle('hidden');
        if (chevron) chevron.classList.toggle('rotate-180');
    }
</script>
<script src="/Pos-system_drink/assets/Js/nav.js"></script>