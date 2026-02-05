<?php
require_once __DIR__ . '/data.php';

/** Helpers */
if (!function_exists('e')) {
    function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

$uri = $_SERVER['REQUEST_URI'] ?? '';
function isActive(string $needle, string $uri): bool
{
    return strpos($uri, $needle) !== false;
}
function navClass(bool $active): string
{
    return $active
        ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100'
        : 'text-gray-700 hover:bg-gray-100';
}
?>
<style>
    .hide-scrollbar::-webkit-scrollbar {
        width: 0;
        height: 0
    }

    .sidebar-transition {
        transition: transform .25s ease
    }

    .dropdown-transition {
        transition: opacity .18s ease, transform .18s ease
    }
</style>

<!-- Mobile overlay -->
<div id="mobileOverlay" class="fixed inset-0 bg-black/40 z-40 hidden"></div>

<!-- ========== SIDEBAR (Desktop) ========== -->
<aside class="hidden md:flex fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 z-30 flex-col">
    <!-- Brand -->
    <div class="px-4 py-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <img
                src="https://i.pinimg.com/736x/04/11/26/04112661e97e3ccba6176d69c49ba8a5.jpg"
                class="w-11 h-11 rounded-xl object-cover border"
                alt="Brand" />
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 leading-5 truncate">My Brand</p>
                <p class="text-xs text-gray-500 truncate">Admin Panel</p>
            </div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto hide-scrollbar">
        <!-- Dashboard -->
        <?php if ($currentRole === 'admin'): ?>
            <a href="/E-commerce-shoes/admin/controller/dashboard/dashboard.php"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/dashboard/dashboard.php', $uri)) ?>">
                <i class="fas fa-home w-5 text-center text-gray-500"></i> Dashboard
            </a>
        <?php else: ?>
            <a href="/E-commerce-shoes/pos/staff_dashboard.php"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/pos/staff_dashboard.php', $uri)) ?>">
                <i class="fas fa-home w-5 text-center text-gray-500"></i> Staff Dashboard
            </a>
        <?php endif; ?>

        <?php if ($currentRole === 'admin'): ?>
            <a href="/E-commerce-shoes/admin/controller/users/users.php"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/users/users.php', $uri)) ?>">
                <i class="fas fa-users w-5 text-center text-gray-500"></i> Users
            </a>
        <?php endif; ?>

        <a href="/E-commerce-shoes/admin/controller/navbar/menu.php"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/navbar/menu.php', $uri)) ?>">
            <i class="fas fa-bars w-5 text-center text-gray-500"></i> Navbar
        </a>

        <!-- Section: E-commerce -->
        <div class="pt-3">
            <p class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">E-commerce</p>

            <div class="mt-2 space-y-1">
                <a href="/E-commerce-shoes/admin/controller/products/products.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/products/products.php', $uri)) ?>">
                    <i class="fas fa-shopping-bag w-5 text-center text-gray-500"></i> Products
                </a>

                <!-- Item dropdown -->
                <?php
                $itemOpen = isActive('/admin/controller/featured/', $uri)
                    || isActive('/admin/controller/category/', $uri)
                    || isActive('/admin/controller/slides/', $uri);
                ?>
                <div class="rounded-xl">
                    <button
                        type="button"
                        class="js-accordion w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100"
                        aria-expanded="<?= $itemOpen ? 'true' : 'false' ?>">
                        <i class="fa-solid fa-folder-open w-5 text-center text-gray-500"></i>
                        Item
                        <span class="ml-auto">
                            <i class="fa-solid fa-chevron-down text-xs transition-transform <?= $itemOpen ? 'rotate-180' : '' ?>"></i>
                        </span>
                    </button>

                    <div class="js-accordion-panel mt-1 ml-8 space-y-1 <?= $itemOpen ? '' : 'hidden' ?>">
                        <a href="/E-commerce-shoes/admin/controller/featured/featured.php"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm <?= navClass(isActive('/admin/controller/featured/featured.php', $uri)) ?>">
                            <i class="fa-solid fa-star w-5 text-center text-gray-500"></i> Featured
                        </a>
                        <a href="/E-commerce-shoes/admin/controller/category/category.php"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm <?= navClass(isActive('/admin/controller/category/category.php', $uri)) ?>">
                            <i class="fa-solid fa-layer-group w-5 text-center text-gray-500"></i> Categories
                        </a>
                        <a href="/E-commerce-shoes/admin/controller/slides/slides.php"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm <?= navClass(isActive('/admin/controller/slides/slides.php', $uri)) ?>">
                            <i class="fa-solid fa-sliders w-5 text-center text-gray-500"></i> Slides
                        </a>
                    </div>
                </div>

                <a href="/E-commerce-shoes/admin/controller/orders/order.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/orders/order.php', $uri)) ?>">
                    <i class="fas fa-shopping-cart w-5 text-center text-gray-500"></i> Orders
                </a>
            </div>
        </div>

        <?php if ($currentRole === 'admin'): ?>
            <div class="pt-4">
                <p class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Analytics</p>
                <div class="mt-2 space-y-1">
                    <a href="/E-commerce-shoes/admin/controller/analytics/analytics.php"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/analytics/analytics.php', $uri)) ?>">
                        <i class="fas fa-chart-bar w-5 text-center text-gray-500"></i> Analytics
                    </a>
                    <a href="/E-commerce-shoes/admin/controller/report/report.php"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/report/report.php', $uri)) ?>">
                        <i class="fas fa-chart-pie w-5 text-center text-gray-500"></i> Reports
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="pt-4">
            <p class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Settings</p>
            <div class="mt-2 space-y-1">
                <a href="/E-commerce-shoes/admin/controller/setting/setting.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/setting/setting.php', $uri)) ?>">
                    <i class="fas fa-cog w-5 text-center text-gray-500"></i> Settings
                </a>
                <a href="/E-commerce-shoes/admin/logs.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/logs.php', $uri)) ?>">
                    <i class="fas fa-clipboard-list w-5 text-center text-gray-500"></i> Activity Logs
                </a>
            </div>
        </div>
    </nav>
</aside>

<!-- ========== MAIN ========== -->
<div class="md:ml-64 mt-3">
    <!-- Topbar -->
    <header class="sticky top-0 z-20 bg-white/95 backdrop-blur border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="h-16 flex items-center justify-between gap-4">

                <!-- Left -->
                <div class="flex items-center gap-3">
                    <button id="mobileMenuButton" class="md:hidden p-2 rounded-xl text-gray-600 hover:bg-gray-100">
                        <i class="fas fa-bars text-lg"></i>
                    </button>

                    <!-- Search (desktop) -->
                    <div class="hidden md:block relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text"
                            class="pl-9 pr-4 py-2 w-72 lg:w-96 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500"
                            placeholder="Search..." />
                    </div>
                </div>

                <!-- Right -->
                <div class="flex items-center gap-2">
                    <button id="mobileSearchButton" class="md:hidden p-2 rounded-xl text-gray-600 hover:bg-gray-100">
                        <i class="fas fa-search"></i>
                    </button>

                    <!-- NOTIFICATIONS -->
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
                            class="hidden absolute left-1/2 -translate-x-1/2 mt-3 w-[340px]
						bg-white rounded-2xl shadow-[0_25px_80px_-30px_rgba(0,0,0,0.55)]
						border border-gray-200 z-50 overflow-hidden"
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
                                                class="notif-item block px-4 py-3 transition hover:bg-gray-50 <?= $isRead ? '' : 'bg-indigo-50/60' ?>"
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
                                                class="notif-clear absolute top-3 right-3 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition text-sm"
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

                    <div class="relative js-dropdown">
                        <!-- Button -->
                        <button
                            type="button"
                            class="js-dropdown-btn relative p-2 rounded-full text-gray-600 hover:bg-gray-100 transition
           focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            aria-haspopup="true"
                            aria-expanded="false">
                            <i class="fas fa-envelope text-base"></i>

                            <?php if ($messagesCount > 0): ?>
                                <span class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1.5 rounded-full
                   bg-indigo-500 text-white text-[11px] font-semibold flex items-center justify-center">
                                    <?= $messagesCount > 99 ? '99+' : (int)$messagesCount; ?>
                                </span>
                            <?php endif; ?>
                        </button>

                        <!-- Dropdown Menu -->
                        <div class="js-dropdown-menu hidden absolute left-1/2 -translate-x-1/2 mt-3 w-[300px] max-w-[80vw] bg-white rounded-2xl shadow-[0_25px_80px_-30px_rgba(0,0,0,0.55)] border border-gray-200 z-50 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700">Messages</span>
                                <div class="flex items-center gap-3">
                                    <button id="msgMarkAllReadBtn" type="button"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                        Mark all read
                                    </button>
                                    <button id="msgClearAllBtn" type="button"
                                        class="text-xs font-medium text-red-600 hover:text-red-800">
                                        Clear all
                                    </button>
                                </div>
                            </div>

                            <div id="messagesList" class="max-h-[360px] overflow-y-auto divide-y divide-gray-100">
                                <?php if (empty($contactMessages)): ?>
                                    <p class="text-center text-sm text-gray-500 py-6">No messages</p>
                                <?php else: ?>
                                    <?php foreach ($contactMessages as $m): ?>
                                        <?php
                                        $name = (string)($m['NAME'] ?? '');
                                        $initials = rawurlencode($name);
                                        $avatar = "https://ui-avatars.com/api/?name={$initials}&background=6b21a8&color=fff";
                                        $isUnread = (int)($m['is_read'] ?? 0) === 0;
                                        $mid = (int)($m['message_id'] ?? 0);
                                        ?>
                                        <div class="relative group">
                                            <a href="#"
                                                data-id="<?= $mid ?>"
                                                class="msg-item flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition <?= $isUnread ? 'bg-indigo-50' : '' ?>">
                                                <img src="<?= e($avatar) ?>" class="w-9 h-9 rounded-full border" alt="<?= e($name) ?>">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-sm font-medium text-gray-900 truncate"><?= e($name) ?></p>
                                                        <span class="text-xs text-gray-400 whitespace-nowrap">
                                                            <?= !empty($m['created_at']) ? date('d M Y H:i', strtotime((string)$m['created_at'])) : '' ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mt-1 truncate"><?= e((string)($m['message'] ?? '')) ?></p>
                                                </div>
                                            </a>

                                            <?php if ($isUnread): ?>
                                                <span class="absolute top-4 left-3 w-2.5 h-2.5 bg-indigo-500 rounded-full"></span>
                                            <?php endif; ?>

                                            <button type="button" data-id="<?= $mid ?>"
                                                class="msg-clear absolute top-3 right-3 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition">
                                                &times;
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                                <a href="/E-commerce-shoes/admin/pages/messages.php"
                                    class="block text-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                    View all messages
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="h-6 w-px bg-gray-200 mx-1"></div>

                    <!-- Admin -->
                    <div class="relative js-dropdown">
                        <button class="js-dropdown-btn flex items-center gap-3 p-1.5 rounded-xl hover:bg-gray-100">
                            <img src="<?= e($admin_avatar) ?>" class="w-9 h-9 rounded-full border" alt="Admin">
                            <div class="hidden md:block text-left leading-4">
                                <p class="text-sm font-semibold text-gray-900"><?= e($admin_name) ?></p>
                                <p class="text-xs text-gray-500"><?= e($admin_role) ?></p>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs hidden md:block"></i>
                        </button>

                        <div class="js-dropdown-menu hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                            <div class="p-4 border-b bg-gray-50">
                                <p class="text-sm font-semibold text-gray-900"><?= e($admin_name) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= e($admin_role) ?></p>
                            </div>
                            <div class="p-2">
                                <a href="/E-commerce-shoes/admin/profile.php" class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100 rounded-xl">
                                    <i class="fas fa-user w-5 text-center text-gray-500"></i> My Profile
                                </a>
                                <a href="/E-commerce-shoes/admin/activity.php" class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100 rounded-xl">
                                    <i class="fas fa-chart-line w-5 text-center text-gray-500"></i> Activity
                                </a>
                            </div>
                            <div class="p-2 border-t">
                                <a href="/E-commerce-shoes/auth/Log/logout.php"
                                    onclick="return confirm('Are you sure you want to logout?');"
                                    class="flex items-center gap-3 px-3 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-xl">
                                    <i class="fas fa-sign-out-alt w-5 text-center text-red-500"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Mobile search -->
            <div id="mobileSearchBar" class="md:hidden pb-4 hidden">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text"
                        class="pl-9 pr-4 py-2 w-full border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500"
                        placeholder="Search..." />
                </div>
            </div>
        </div>
    </header>
</div>

<!-- ========== MOBILE SIDEBAR ========== -->
<aside id="mobileSidebar"
    class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 z-50 md:hidden
         transform -translate-x-full sidebar-transition flex flex-col">

    <div class="px-4 py-4 border-b border-gray-200 flex items-center gap-3">
        <img src="<?= e($admin_avatar) ?>" class="w-10 h-10 rounded-full border" alt="Admin">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900 truncate"><?= e($admin_name) ?></p>
            <p class="text-xs text-gray-500 truncate"><?= e($admin_role) ?></p>
        </div>
        <button id="closeMobileMenu" class="p-2 rounded-xl text-gray-600 hover:bg-gray-100">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- For mobile, reuse same links quickly (keep it simple) -->
    <div class="px-3 py-4 overflow-y-auto hide-scrollbar">
        <!-- Dashboard -->
        <?php if ($currentRole === 'admin'): ?>
            <a href="/E-commerce-shoes/admin/controller/dashboard/dashboard.php"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/dashboard/dashboard.php', $uri)) ?>">
                <i class="fas fa-home w-5 text-center text-gray-500"></i> Dashboard
            </a>
        <?php else: ?>
            <a href="/E-commerce-shoes/pos/staff_dashboard.php"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/pos/staff_dashboard.php', $uri)) ?>">
                <i class="fas fa-home w-5 text-center text-gray-500"></i> Staff Dashboard
            </a>
        <?php endif; ?>

        <?php if ($currentRole === 'admin'): ?>
            <a href="/E-commerce-shoes/admin/controller/users/users.php"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/users/users.php', $uri)) ?>">
                <i class="fas fa-users w-5 text-center text-gray-500"></i> Users
            </a>
        <?php endif; ?>

        <a href="/E-commerce-shoes/admin/controller/navbar/menu.php"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/navbar/menu.php', $uri)) ?>">
            <i class="fas fa-bars w-5 text-center text-gray-500"></i> Navbar
        </a>

        <!-- Section: E-commerce -->
        <div class="pt-3">
            <p class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">E-commerce</p>

            <div class="mt-2 space-y-1">
                <a href="/E-commerce-shoes/admin/controller/products/products.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/products/products.php', $uri)) ?>">
                    <i class="fas fa-shopping-bag w-5 text-center text-gray-500"></i> Products
                </a>

                <!-- Item dropdown -->
                <?php
                $itemOpen = isActive('/admin/controller/featured/', $uri)
                    || isActive('/admin/controller/category/', $uri)
                    || isActive('/admin/controller/slides/', $uri);
                ?>
                <div class="rounded-xl">
                    <button
                        type="button"
                        class="js-accordion w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100"
                        aria-expanded="<?= $itemOpen ? 'true' : 'false' ?>">
                        <i class="fa-solid fa-folder-open w-5 text-center text-gray-500"></i>
                        Item
                        <span class="ml-auto">
                            <i class="fa-solid fa-chevron-down text-xs transition-transform <?= $itemOpen ? 'rotate-180' : '' ?>"></i>
                        </span>
                    </button>

                    <div class="js-accordion-panel mt-1 ml-8 space-y-1 <?= $itemOpen ? '' : 'hidden' ?>">
                        <a href="/E-commerce-shoes/admin/controller/featured/featured.php"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm <?= navClass(isActive('/admin/controller/featured/featured.php', $uri)) ?>">
                            <i class="fa-solid fa-star w-5 text-center text-gray-500"></i> Featured
                        </a>
                        <a href="/E-commerce-shoes/admin/controller/category/category.php"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm <?= navClass(isActive('/admin/controller/category/category.php', $uri)) ?>">
                            <i class="fa-solid fa-layer-group w-5 text-center text-gray-500"></i> Categories
                        </a>
                        <a href="/E-commerce-shoes/admin/controller/slides/slides.php"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm <?= navClass(isActive('/admin/controller/slides/slides.php', $uri)) ?>">
                            <i class="fa-solid fa-sliders w-5 text-center text-gray-500"></i> Slides
                        </a>
                    </div>
                </div>

                <a href="/E-commerce-shoes/admin/controller/orders/order.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/orders/order.php', $uri)) ?>">
                    <i class="fas fa-shopping-cart w-5 text-center text-gray-500"></i> Orders
                </a>
            </div>
        </div>

        <?php if ($currentRole === 'admin'): ?>
            <div class="pt-4">
                <p class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Analytics</p>
                <div class="mt-2 space-y-1">
                    <a href="/E-commerce-shoes/admin/controller/analytics/analytics.php"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/analytics/analytics.php', $uri)) ?>">
                        <i class="fas fa-chart-bar w-5 text-center text-gray-500"></i> Analytics
                    </a>
                    <a href="/E-commerce-shoes/admin/controller/report/report.php"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/report/report.php', $uri)) ?>">
                        <i class="fas fa-chart-pie w-5 text-center text-gray-500"></i> Reports
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="pt-4">
            <p class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Settings</p>
            <div class="mt-2 space-y-1">
                <a href="/E-commerce-shoes/admin/controller/setting/setting.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/controller/setting/setting.php', $uri)) ?>">
                    <i class="fas fa-cog w-5 text-center text-gray-500"></i> Settings
                </a>
                <a href="/E-commerce-shoes/admin/logs.php"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?= navClass(isActive('/admin/logs.php', $uri)) ?>">
                    <i class="fas fa-clipboard-list w-5 text-center text-gray-500"></i> Activity Logs
                </a>
            </div>
        </div>
    </div>
</aside>
<!-- Keep your existing scripts -->
<script src="/E-commerce-shoes/assets/Js/nav.js"></script>
<?php if (strpos($_SERVER['REQUEST_URI'], '/admin/controller/slides/slides.php') !== false): ?>
    <script src="/E-commerce-shoes/admin/controller/slides/media_choice.js"></script>
    <script src="/E-commerce-shoes/admin/controller/slides/media_preview.js"></script>
<?php endif; ?>
<script src="/E-commerce-shoes/assets/Js/notifications.js"></script>
<script src="/E-commerce-shoes/assets/Js/message.js"></script>