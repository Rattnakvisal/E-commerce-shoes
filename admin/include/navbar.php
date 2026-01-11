<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/conn.php';

/*
|--------------------------------------------------------------------------
| Session Data
|--------------------------------------------------------------------------
*/
$userId      = $_SESSION['user_id'] ?? null;
$role        = $_SESSION['role'] ?? 'admin';
$adminName   = $_SESSION['admin_name'] ?? $_SESSION['name'] ?? 'Admin';
$adminRole   = ucfirst($role);
$adminAvatar = $_SESSION['admin_avatar'] ?? $_SESSION['avatar'] ?? '';

if ($adminAvatar === '') {
    $initials    = rawurlencode($adminName);
    $adminAvatar = "https://ui-avatars.com/api/?name={$initials}&background=ffffff&color=111827&rounded=true&size=128";
}

$currentRole = $role;
$admin_name  = $adminName;
$admin_role  = $adminRole;
$admin_avatar = $adminAvatar;

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/
$unreadCount = 0;
$notifications = [];
try {
    $unreadStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM notifications
         WHERE is_read = 0
           AND (user_id = :uid OR user_id IS NULL)"
    );
    $unreadStmt->execute(['uid' => $userId]);
    $unreadCount = (int) $unreadStmt->fetchColumn();

    $listStmt = $pdo->prepare(
        "SELECT notification_id, title, message, is_read, created_at
         FROM notifications
         WHERE (user_id = :uid OR user_id IS NULL)
         ORDER BY created_at DESC
         LIMIT 10"
    );
    $listStmt->execute(['uid' => $userId]);
    $notifications = $listStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Navbar] Notifications query failed: ' . $e->getMessage());
    $unreadCount = 0;
    $notifications = [];
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
            <a href="/E-commerce-shoes/admin/dashboard.php"
                class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift active-menu-item">
                <i class="fas fa-home mr-3 text-gray-500 w-5 text-center"></i>
                Dashboard
            </a>
        <?php elseif ($currentRole === 'staff'): ?>
            <a href="/E-commerce-shoes/pos/staff_dashboard.php"
                class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                <i class="fas fa-home mr-3 text-gray-500 w-5 text-center"></i>
                Staff Dashboard
            </a>
        <?php endif; ?>

        <!-- Users (admin only) -->
        <?php if ($currentRole === 'admin'): ?>
            <a href="/E-commerce-shoes/admin/process/users/users.php"
                class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                <i class="fas fa-users mr-3 text-gray-500 w-5 text-center"></i>
                Users
            </a>
        <?php endif; ?>

        <!-- Navbar Manager -->
        <a href="/E-commerce-shoes/admin/process/navbar/menu.php"
            class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
            <i class="fas fa-bars mr-3 text-gray-600 w-5 text-center"></i>
            Navbar Manager
        </a>
        <!-- E-commerce -->
        <div class="pt-2">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">E-commerce</p>
            <div class="mt-2 space-y-1">
                <a href="/E-commerce-shoes/admin/process/products/products.php"
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
                            <i class="itemChevron fa-solid fa-chevron-down text-xs transition-transform"></i>
                        </span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div class="itemDropdown hidden mt-1 ml-8 space-y-1">

                        <a href="/E-commerce-shoes/admin/process/featured/featured.php"
                            class="flex items-center px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                            <i class="fa-solid fa-star mr-3 text-gray-500 w-5 text-center"></i>
                            Featured
                        </a>

                        <a href="/E-commerce-shoes/admin/process/category/category.php"
                            class="flex items-center px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                            <i class="fa-solid fa-layer-group mr-3 text-gray-500 w-5 text-center"></i>
                            Categories
                        </a>

                        <a href="/E-commerce-shoes/admin/process/slides/slides.php"
                            class="flex items-center px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                            <i class="fa-solid fa-sliders mr-3 text-gray-500 w-5 text-center"></i>
                            Slides
                        </a>
                    </div>
                </div>
                <a href="/E-commerce-shoes/admin/process/orders/order.php"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                    <i class="fas fa-shopping-cart mr-3 text-gray-500 w-5 text-center"></i>
                    Orders
                </a>
            </div>
        </div>
        <!-- Analytics Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Analytics</p>
            <div class="mt-2 space-y-1">
                <a href="/E-commerce-shoes/admin/process/analytics/analytics.php"
                    class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                    <i class="fas fa-chart-bar mr-3 text-gray-500 w-5 text-center"></i>
                    Analytics
                </a>

                <a href="/E-commerce-shoes/admin/reports.php"
                    class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                    <i class="fas fa-chart-pie mr-3 text-gray-500 w-5 text-center"></i>
                    Reports
                </a>

                <a href="/E-commerce-shoes/admin/insights.php"
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
                <a href="/E-commerce-shoes/admin/settings.php"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                    <i class="fas fa-cog mr-3 text-gray-500 w-5 text-center"></i>
                    Settings
                </a>
                <a href="/E-commerce-shoes/admin/logs.php"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                    <i class="fas fa-clipboard-list mr-3 text-gray-500 w-5 text-center"></i>
                    Activity Logs
                </a>
                <a href="/E-commerce-shoes/admin/security.php"
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

                            <?php if ($unreadCount > 0): ?>
                                <span
                                    class="badge-count absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                    <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                                </span>
                            <?php else: ?>
                                <span class="badge-count absolute -top-1 -right-1 hidden"></span>
                            <?php endif; ?>
                        </button>

                        <div id="notificationsDropdown"
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50">

                            <!-- Header -->
                            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800">Notifications</h3>

                                <div class="flex items-center gap-3">
                                    <button id="markAllReadBtn"
                                        class="text-xs text-indigo-600 hover:text-indigo-800">
                                        Mark all as read
                                    </button>
                                    <button id="clearAllNotifsBtn"
                                        class="text-xs text-red-600 hover:text-red-800">
                                        Clear all
                                    </button>
                                </div>
                            </div>

                            <!-- Notifications List -->
                            <div id="notificationsList"
                                class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                                <?php if (empty($notifications)): ?>
                                    <p class="text-center text-sm text-gray-500 py-6">
                                        No notifications
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($notifications as $n): ?>
                                        <div class="notif-row relative">
                                            <a href="#"
                                                data-id="<?= (int)$n['notification_id'] ?>"
                                                class="notif-item block px-4 py-3 hover:bg-gray-50
                                                <?= $n['is_read'] == 0 ? 'bg-indigo-50' : '' ?>">
                                                <div class="flex justify-between items-start">
                                                    <p class="text-sm font-medium text-gray-800">
                                                        <?= htmlspecialchars($n['title']) ?>
                                                    </p>
                                                    <span class="text-xs text-gray-400 whitespace-nowrap">
                                                        <?= date('d M Y H:i', strtotime($n['created_at'])) ?>
                                                    </span>
                                                </div>

                                                <p class="text-xs text-gray-600 mt-1 truncate">
                                                    <?= htmlspecialchars($n['message']) ?>
                                                </p>
                                            </a>
                                            <?php if ($n['is_read'] == 0): ?>
                                                <span class="absolute top-4 left-3 w-2.5 h-2.5 bg-indigo-500 rounded-full"></span>
                                            <?php endif; ?>
                                            <button type="button"
                                                data-id="<?= (int)$n['notification_id'] ?>"
                                                class="notif-clear absolute top-3 right-3 text-gray-400 hover:text-red-500 text-sm"
                                                aria-label="Delete notification">
                                                &times;
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 border-t border-gray-200">
                                <a id="viewAllNotifications"
                                    href="/notifications.php"
                                    class="block text-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
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
                                <a href="/E-commerce-shoes/admin/profile.php"
                                    class="flex items-center px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                                    <i class="fas fa-user mr-3 text-gray-500 w-5 text-center"></i>
                                    My Profile
                                </a>
                                <a href="/E-commerce-shoes/admin/activity.php"
                                    class="flex items-center px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                                    <i class="fas fa-chart-line mr-3 text-gray-500 w-5 text-center"></i>
                                    Activity
                                </a>
                            </div>
                            <div class="p-2 border-t border-gray-200">
                                <a href="/E-commerce-shoes/auth/logout.php"
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
            <a href="/E-commerce-shoes/admin/dashboard.php"
                class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 active-menu-item touch-feedback">
                <i class="fas fa-home mr-3 text-gray-500 w-5 text-center"></i>
                Dashboard
                <span class="ml-auto bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">5</span>
            </a>

            <!-- Users -->
            <a href="/E-commerce-shoes/admin/process/users/users.php"
                class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                <i class="fas fa-users mr-3 text-gray-500 w-5 text-center"></i>
                Users
            </a>

            <!-- Navbar Manager -->
            <a href="/E-commerce-shoes/admin/process/navbar/menu.php"
                class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 hover-lift">
                <i class="fas fa-bars mr-3 text-gray-600 w-5 text-center"></i>
                Navbar Manager
            </a>

            <!-- E-commerce Section -->
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">E-commerce</p>
                <div class="mt-2 space-y-1">
                    <a href="/E-commerce-shoes/admin/process/products/products.php" class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
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

                            <a href="/E-commerce-shoes/admin/products.php"
                                class="flex items-center px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                                <i class="fa-solid fa-star mr-3 text-gray-500 w-5 text-center"></i>
                                Featured
                            </a>

                            <a href="/E-commerce-shoes/admin/process/category/category.php"
                                class="flex items-center px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                                <i class="fa-solid fa-layer-group mr-3 text-gray-500 w-5 text-center"></i>
                                Categories
                            </a>

                            <a href="/E-commerce-shoes/admin/process/slides/slides.php"
                                class="flex items-center px-3 py-2 text-sm rounded-lg text-gray-600 hover:bg-gray-100">
                                <i class="fa-solid fa-sliders mr-3 text-gray-500 w-5 text-center"></i>
                                Slides
                            </a>
                        </div>
                    </div>
                    <a href="/E-commerce-shoes/admin/process/orders/order.php" class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-shopping-cart mr-3 text-gray-500 w-5 text-center"></i>
                        Orders
                    </a>
                </div>
            </div>

            <!-- Analytics Section -->
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Analytics</p>
                <div class="mt-2 space-y-1">
                    <a href="/E-commerce-shoes/admin/process/analytics/analytics.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-chart-bar mr-3 text-gray-500 w-5 text-center"></i>
                        Analytics
                    </a>

                    <a href="/E-commerce-shoes/admin/reports.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-chart-pie mr-3 text-gray-500 w-5 text-center"></i>
                        Reports
                    </a>

                    <a href="/E-commerce-shoes/admin/insights.php"
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
                    <a href="/E-commerce-shoes/admin/settings.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-cog mr-3 text-gray-500 w-5 text-center"></i>
                        Settings
                    </a>

                    <a href="/E-commerce-shoes/admin/logs.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-clipboard-list mr-3 text-gray-500 w-5 text-center"></i>
                        Activity Logs
                    </a>

                    <a href="/E-commerce-shoes/admin/security.php"
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
<script src="/E-commerce-shoes/assets/Js/nav.js"></script>
<?php if (strpos($_SERVER['REQUEST_URI'], '/admin/process/slides/slides.php') !== false): ?>
    <script src="/E-commerce-shoes/admin/process/slides/media_choice.js"></script>
    <script src="/E-commerce-shoes/admin/process/slides/media_preview.js"></script>
<?php endif; ?>
<script src="/E-commerce-shoes/assets/Js/notifications.js"></script>