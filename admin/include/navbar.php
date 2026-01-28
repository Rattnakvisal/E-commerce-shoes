<?php
require_once __DIR__ . '/data.php';
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
        <?php if ($currentRole === 'admin'): ?>
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Analytics</p>
                <div class="mt-2 space-y-1">

                    <a href="/E-commerce-shoes/admin/process/analytics/analytics.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-chart-bar mr-3 text-gray-500 w-5 text-center"></i>
                        Analytics
                    </a>


                    <a href="/E-commerce-shoes/admin/process/report/report.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-chart-pie mr-3 text-gray-500 w-5 text-center"></i>
                        Reports
                    </a>
                </div>
            </div>
        <?php endif; ?>
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

                    <!-- NOTIFICATIONS (ADMIN) -->
                    <div class="relative" id="notifWrap">

                        <!-- Bell Button -->
                        <button
                            id="notificationsButton"
                            type="button"
                            class="relative p-2 rounded-full text-gray-600 hover:bg-gray-100 transition focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            aria-haspopup="true"
                            aria-expanded="false">
                            <i class="fas fa-bell text-lg"></i>

                            <span
                                id="notificationBadge"
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-semibold
             rounded-full min-w-[18px] h-[18px] px-1 flex items-center justify-center
             <?= ((int)$unreadCount > 0) ? '' : 'hidden' ?>">
                                <?= ((int)$unreadCount > 99) ? '99+' : (int)$unreadCount ?>
                            </span>
                        </button>

                        <!-- Dropdown -->
                        <div
                            id="notificationsDropdown"
                            class="hidden absolute right-0 mt-3 w-96 max-w-[90vw]
           bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-50"
                            role="menu">
                            <!-- Header -->
                            <div class="flex items-center justify-between px-4 py-3 border-b bg-gray-50">
                                <h3 class="text-sm font-semibold text-gray-800">Notifications</h3>

                                <div class="flex items-center gap-3">
                                    <button id="markAllReadBtn" type="button" class="text-xs text-indigo-600 hover:underline">
                                        Mark all read
                                    </button>
                                    <button id="clearAllNotifsBtn" type="button" class="text-xs text-red-600 hover:underline">
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <!-- List -->
                            <div id="notificationsList" class="max-h-[360px] overflow-y-auto divide-y divide-gray-100">
                                <?php if (empty($notifications)): ?>
                                    <div class="py-10 text-center text-sm text-gray-500">No notifications</div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $n): ?>
                                        <?php
                                        $id = (int)($n['notification_id'] ?? 0);
                                        $title = htmlspecialchars((string)($n['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                                        $msg = htmlspecialchars((string)($n['message'] ?? ''), ENT_QUOTES, 'UTF-8');
                                        $isRead = (int)($n['is_read'] ?? 0) === 1;
                                        $date = !empty($n['created_at']) ? date('d M H:i', strtotime((string)$n['created_at'])) : '';
                                        ?>
                                        <div class="relative group notif-row">
                                            <!-- Clickable row -->
                                            <a
                                                href="#"
                                                data-id="<?= $id ?>"
                                                class="notif-item block px-4 py-3 transition hover:bg-gray-50
                     <?= $isRead ? '' : 'bg-indigo-50/60' ?>"
                                                role="menuitem">
                                                <div class="flex justify-between items-start gap-2">
                                                    <p class="text-sm font-medium text-gray-900 line-clamp-1"><?= $title ?></p>
                                                    <span class="text-xs text-gray-400 whitespace-nowrap"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                                <p class="mt-1 text-xs text-gray-600 line-clamp-2"><?= $msg ?></p>
                                            </a>

                                            <!-- Unread dot -->
                                            <?php if (!$isRead): ?>
                                                <span class="absolute top-4 left-2 w-2 h-2 bg-indigo-500 rounded-full"></span>
                                            <?php endif; ?>

                                            <!-- Delete (hover reveal) -->
                                            <button
                                                type="button"
                                                data-id="<?= $id ?>"
                                                class="notif-clear absolute top-3 right-3 opacity-0 group-hover:opacity-100
                     text-gray-400 hover:text-red-500 transition text-sm"
                                                aria-label="Delete notification"
                                                title="Delete">
                                                &times;
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Footer -->
                            <div class="border-t px-4 py-3 bg-gray-50">
                                <a
                                    id="viewAllNotifications"
                                    href="/E-commerce-shoes/admin/notifications.php"
                                    class="block text-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                    View all notifications
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- Messages -->
                    <div class="relative">
                        <button id="messagesButton" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 relative">
                            <i class="fas fa-envelope"></i>

                            <?php if ($messagesCount > 0): ?>
                                <span id="msgBadge"
                                    class="absolute -top-1 -right-1 bg-indigo-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                    <?= $messagesCount > 99 ? '99+' : $messagesCount; ?>
                                </span>
                            <?php else: ?>
                                <span id="msgBadge" class="absolute -top-1 -right-1 hidden"></span>
                            <?php endif; ?>
                        </button>

                        <!-- Messages Dropdown -->
                        <div id="messagesDropdown"
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 hidden dropdown-transition z-50">

                            <!-- Header -->
                            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800">Messages</h3>

                                <!-- Optional actions like notifications -->
                                <div class="flex items-center gap-3">
                                    <button id="msgMarkAllReadBtn" class="text-xs text-indigo-600 hover:text-indigo-800">
                                        Mark all as read
                                    </button>
                                    <button id="msgClearAllBtn" class="text-xs text-red-600 hover:text-red-800">
                                        Clear all
                                    </button>
                                </div>
                            </div>

                            <!-- List -->
                            <div id="messagesList" class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                                <?php if (empty($contactMessages)): ?>
                                    <p class="text-center text-sm text-gray-500 py-6">No messages</p>
                                <?php else: ?>
                                    <?php foreach ($contactMessages as $m): ?>
                                        <?php
                                        $name = (string)($m['NAME'] ?? '');
                                        $initials = rawurlencode($name);
                                        $avatar = "https://ui-avatars.com/api/?name={$initials}&background=6b21a8&color=fff";
                                        $isUnread = (int)($m['is_read'] ?? 0) === 0;
                                        ?>

                                        <div class="msg-row relative">
                                            <a href="#"
                                                data-id="<?= (int)$m['message_id']; ?>"
                                                class="msg-item flex items-start px-4 py-3 hover:bg-gray-50 <?= $isUnread ? 'bg-indigo-50' : '' ?>">

                                                <img src="<?= $avatar; ?>"
                                                    alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="w-8 h-8 rounded-full">

                                                <div class="ml-3 flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <p class="text-sm font-medium text-gray-900 truncate">
                                                            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                                                        </p>
                                                        <span class="text-xs text-gray-400 whitespace-nowrap">
                                                            <?= date('d M Y H:i', strtotime((string)$m['created_at'])); ?>
                                                        </span>
                                                    </div>

                                                    <p class="text-xs text-gray-500 mt-1 truncate">
                                                        <?= htmlspecialchars((string)$m['message'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </p>
                                                </div>
                                            </a>

                                            <?php if ($isUnread): ?>
                                                <span class="absolute top-4 left-3 w-2.5 h-2.5 bg-indigo-500 rounded-full"></span>
                                            <?php endif; ?>

                                            <!-- delete button (optional) -->
                                            <button type="button"
                                                data-id="<?= (int)$m['message_id']; ?>"
                                                class="msg-clear absolute top-3 right-3 text-gray-400 hover:text-red-500 text-sm"
                                                aria-label="Delete message">
                                                &times;
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 border-t border-gray-200">
                                <a id="viewAllMessages"
                                    href="/notifications.php"
                                    class="block text-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
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
                                <a href="/E-commerce-shoes/auth/Log/logout.php"
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

                    <a href="/E-commerce-shoes/admin/report/report.php"
                        class="mobile-nav-item flex items-center px-3 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-100 touch-feedback">
                        <i class="fas fa-chart-pie mr-3 text-gray-500 w-5 text-center"></i>
                        Reports
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
<script src="/E-commerce-shoes/assets/Js/message.js"></script>