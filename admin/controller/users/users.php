<?php
require_once __DIR__ . '/../../../config/conn.php';
require_once __DIR__ . '/process.php';

$queryBase = $_GET ?? [];
unset($queryBase['status'], $queryBase['role'], $queryBase['page']);

$currentStatus = (string)($filters['status'] ?? '');
$currentRole   = (string)($filters['role'] ?? '');

$tabs = [
    [
        'label' => 'All Users',
        'status' => '',
        'role' => '',
        'count' => (int)($statusCounts['all'] ?? 0),
        'pill' => 'bg-gray-100 text-gray-600',
        'activeText' => 'text-indigo-600',
    ],
    [
        'label' => 'Active',
        'status' => 'active',
        'role' => '',
        'count' => (int)($statusCounts['active'] ?? 0),
        'pill' => 'bg-green-100 text-green-700',
        'activeText' => 'text-green-600',
        'hideWhenZero' => true,
    ],
    [
        'label' => 'Inactive',
        'status' => 'inactive',
        'role' => '',
        'count' => (int)($statusCounts['inactive'] ?? 0),
        'pill' => 'bg-yellow-100 text-yellow-700',
        'activeText' => 'text-yellow-700',
        'hideWhenZero' => true,
    ],
    [
        'label' => 'Admin',
        'status' => '',
        'role' => 'admin',
        'count' => (int)($roleCounts['admin'] ?? 0),
        'pill' => 'bg-purple-100 text-purple-700',
        'activeText' => 'text-purple-700',
    ],
    [
        'label' => 'Staff',
        'status' => '',
        'role' => 'staff',
        'count' => (int)($roleCounts['staff'] ?? 0),
        'pill' => 'bg-blue-100 text-blue-700',
        'activeText' => 'text-blue-700',
    ],
    [
        'label' => 'Customers',
        'status' => '',
        'role' => 'customer',
        'count' => (int)($roleCounts['customer'] ?? 0),
        'pill' => 'bg-amber-100 text-amber-700',
        'activeText' => 'text-amber-700',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin Panel</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../../assets/Css/users.css">
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="bg-gray-50">

    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <div class="md:ml-64">
        <main class="p-4 sm:p-6 lg:p-8 min-h-screen animate-fade-in">
            <!-- ===============================
                Users Management Header
            ================================ -->
            <div>
                <div
                    class="relative mb-6 overflow-hidden rounded-3xl border bg-white shadow-soft p-6 sm:p-8">

                    <!-- Subtle Background Accent -->
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-black/[0.04] via-transparent to-black/[0.06] pointer-events-none"></div>

                    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">

                        <!-- Left: Title -->
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span
                                    class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-black text-white shadow">
                                    <i class="fas fa-users"></i>
                                </span>

                                <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                                    Users
                                    <span class="gradient-text ml-2">Management</span>
                                </h1>
                            </div>

                            <p class="text-gray-600 text-sm sm:text-base max-w-xl">
                                Manage user accounts, roles, permissions, and access levels across your platform.
                            </p>

                            <!-- Meta -->
                            <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-500">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-elevator"></i>
                                    Role-based Access
                                </span>

                                <span
                                    class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    System Secure
                                </span>
                            </div>
                        </div>

                        <!-- Right: Actions -->
                        <div class="flex flex-wrap items-center gap-3">

                            <!-- Add User -->
                            <button
                                onclick="openAddUserModal()"
                                class="inline-flex items-center gap-2 rounded-2xl bg-black text-white px-5 py-3 text-sm font-semibold shadow hover:opacity-90 transition">
                                <i class="fas fa-user-plus"></i>
                                Add User
                            </button>

                            <!-- Refresh -->
                            <button
                                onclick="window.location.reload()"
                                class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border hover:bg-gray-50 transition"
                                title="Refresh">
                                <i class="fa-solid fa-rotate"></i>
                            </button>

                        </div>
                    </div>
                </div>


                <!-- Quick Stats (reports-style) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in-up">

                    <!-- Total Users -->
                    <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <p class="text-sm text-gray-500">Total Users</p>
                                <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format($totalUsers) ?></p>
                            </div>
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 relative z-10">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <div>
                                    <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                        <?= $totalUsers > 0 ? '+' . round(($todayUsers / max($totalUsers, 1)) * 100, 1) . '%' : '0%' ?>
                                    </span>
                                </div>
                                <div class="text-gray-500">Of Total Users</div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's New Users -->
                    <div class="stat-card bg-gradient-to-br from-white to-green-50/50 rounded-2xl p-6 shadow-soft-xl border border-green-100/50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <p class="text-sm text-gray-500">Today's New Users</p>
                                <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format($todayUsers) ?></p>
                            </div>
                            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-user-clock text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 relative z-10">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <div>
                                    <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700"><?= $todayUsers > 0 ? '+' . number_format($todayUsers) : '0' ?></span>
                                </div>
                                <div class="text-gray-500"><?= $todayUsers > 0 ? 'Active growth' : 'No new users' ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if (($stats['active_count'] ?? 0) > 0): ?>
                        <!-- Active Users -->
                        <div class="stat-card bg-gradient-to-br from-white to-emerald-50/50 rounded-2xl p-6 shadow-soft-xl border border-emerald-100/50 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                            <div class="flex items-center justify-between mb-4 relative z-10">
                                <div>
                                    <p class="text-sm text-gray-500">Active Users</p>
                                    <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format($stats['active_count'] ?? 0) ?></p>
                                </div>
                                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-3 rounded-xl shadow-md">
                                    <i class="fas fa-user-check text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4 relative z-10">
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                    <div class="text-gray-500">Percent of users</div>
                                    <div class="text-gray-500"><?= round(($stats['active_count'] ?? 0) / max($totalUsers, 1) * 100, 1) ?>%</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ((($stats['inactive_count'] ?? 0) + ($statusCounts['inactive'] ?? 0)) > 0): ?>
                        <!-- Inactive Users -->
                        <div class="stat-card bg-gradient-to-br from-white to-red-50/50 rounded-2xl p-6 shadow-soft-xl border border-red-100/50 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 w-20 h-20 bg-red-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                            <div class="flex items-center justify-between mb-4 relative z-10">
                                <div>
                                    <p class="text-sm text-gray-500">Inactive Users</p>
                                    <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format($stats['inactive_count'] ?? $statusCounts['inactive'] ?? 0) ?></p>
                                </div>
                                <div class="bg-gradient-to-br from-red-500 to-red-600 text-white p-3 rounded-xl shadow-md">
                                    <i class="fas fa-pause-circle text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4 relative z-10">
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                    <div class="text-gray-500"><?= ($stats['inactive_count'] ?? $statusCounts['inactive'] ?? 0) > 0 ? 'Hidden / inactive accounts' : 'All active' ?></div>
                                    <div class="text-gray-500"><?= round((($stats['inactive_count'] ?? $statusCounts['inactive'] ?? 0) / max($totalUsers, 1)) * 100, 1) ?>%</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <?php
                $queryBase = $_GET;
                unset($queryBase['status'], $queryBase['page']);
                ?>

                <!-- Filter Tabs -->
                <?php
                $queryBase = $_GET ?? [];
                unset($queryBase['status'], $queryBase['role'], $queryBase['page']);

                $currentStatus = (string)($filters['status'] ?? '');
                $currentRole   = (string)($filters['role'] ?? '');
                ?>

                <div class="bg-white border-b border-gray-200">
                    <nav class="flex gap-6 px-6 py-4 overflow-x-auto">
                        <?php foreach ($tabs as $t): ?>
                            <?php
                            $isActive =
                                ($t['status'] === $currentStatus) &&
                                ($t['role'] === $currentRole);

                            $href = '?' . http_build_query(array_merge(
                                $queryBase,
                                ['status' => $t['status'], 'role' => $t['role']]
                            ));

                            $linkClass = $isActive
                                ? "{$t['activeText']} border-b-2 border-indigo-600"
                                : "text-gray-500 hover:text-gray-700
                   border-b-2 border-transparent
                   transition-all duration-200";

                            $count = (int)($t['count'] ?? 0);
                            if (!empty($t['hideWhenZero']) && $count <= 0) {
                                continue;
                            }
                            ?>

                            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                                class="flex items-center gap-2 pb-2 text-sm font-medium <?= $linkClass ?>">
                                <?= htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') ?>

                                <span class="px-2 py-0.5 text-xs rounded-full <?= $t['pill'] ?>">
                                    <?= $count ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- User Filters -->
                <form method="GET" class="bg-white rounded-xl shadow mb-8 p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 items-end">

                        <!-- Preserve tabs -->
                        <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="role" value="<?= htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8') ?>">

                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Search</label>
                            <input type="text"
                                name="search"
                                value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Name, Email, Phone..."
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- From -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">From Date</label>
                            <input type="date"
                                name="date_from"
                                value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full px-3 py-2 border rounded-lg">
                        </div>

                        <!-- To -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">To Date</label>
                            <input type="date"
                                name="date_to"
                                value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full px-3 py-2 border rounded-lg">
                        </div>

                        <!-- Role -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Role</label>
                            <select name="role" class="w-full px-3 py-2 border rounded-lg">
                                <option value="">All Roles</option>
                                <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="staff" <?= $currentRole === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="customer" <?= $currentRole === 'customer' ? 'selected' : '' ?>>Customer</option>
                            </select>
                        </div>

                        <!-- Sort -->
                        <select name="sort" class="w-full px-3 py-2 border rounded-lg">
                            <option value="newest" <?= ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="name_asc" <?= ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>Name (A–Z)</option>
                            <option value="name_desc" <?= ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' ?>>Name (Z–A)</option>
                        </select>

                        <!-- Actions -->
                        <div class="flex gap-2 justify-end lg:col-span-6">
                            <a href="users.php"
                                class="inline-flex items-center gap-2 rounded-2xl text-black px-5 py-3 text-sm font-semibold shadow hover:opacity-90 transition">
                                Clear
                            </a>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-2xl bg-black text-white px-5 py-3 text-sm font-semibold shadow hover:opacity-90 transition">
                                Apply
                            </button>
                        </div>

                    </div>
                </form>

                <!-- Users Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden table-no-hover">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <div class="empty-state">
                                                <div class="empty-state-icon"><i class="fas fa-users"></i></div>
                                                <h3 class="empty-state-title">No users found</h3>
                                                <p class="empty-state-description">Try adjusting your filters</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>

                                        <?php
                                        // ---------- Normalize ----------
                                        $id    = (int) $user['user_id'];
                                        $name  = trim((string) ($user['name'] ?? ''));
                                        $email = (string) ($user['email'] ?? '');
                                        $role  = strtolower((string) ($user['role'] ?? ''));
                                        $phone = (string) ($user['phone'] ?? '');

                                        $avatar = $user['avatar_url'] ?? null;
                                        $initials = strtoupper(substr($name !== '' ? $name : 'U', 0, 2));

                                        $rawStatus = strtolower(trim((string) ($user['status'] ?? '')));
                                        $status = match (true) {
                                            in_array($rawStatus, ['1', 'true', 'yes', 'y', 'active', 'enabled', 'enable'], true) => 'active',
                                            in_array($rawStatus, ['0', 'false', 'no', 'n', 'inactive', 'disabled', 'disable'], true) => 'inactive',
                                            default => 'active',
                                        };

                                        $canDelete = $role !== 'admin' && $id !== ($_SESSION['user_id'] ?? 0);
                                        $canToggle = $id !== ($_SESSION['user_id'] ?? 0);
                                        ?>

                                        <tr class="hover:bg-gray-50 transition <?= $status === 'inactive' ? 'opacity-70 bg-gray-50' : '' ?>" data-user-id="<?= $id ?>" data-user-status="<?= $status ?>">

                                            <!-- User -->
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <?php if (!empty($avatar)): ?>
                                                        <img src="<?= htmlspecialchars($avatar) ?>"
                                                            alt="<?= htmlspecialchars($name) ?>"
                                                            class="w-10 h-10 rounded-full mr-3">
                                                    <?php else: ?>
                                                        <div class="w-10 h-10 avatar-placeholder mr-3">
                                                            <?= htmlspecialchars($initials) ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div>
                                                        <div class="font-medium text-gray-900 <?= $status === 'inactive' ? 'line-through text-gray-500' : '' ?>"><?= htmlspecialchars($name) ?></div>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            ID: #<?= str_pad((string)$id, 6, '0', STR_PAD_LEFT) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Email -->
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($email) ?></div>
                                                <?php if ($phone !== ''): ?>
                                                    <div class="text-sm text-gray-500">
                                                        <i class="fas fa-phone mr-1 text-xs"></i>
                                                        <?= htmlspecialchars($phone) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Role -->
                                            <td class="px-6 py-4">
                                                <span class="role-badge role-<?= htmlspecialchars($role) ?>">
                                                    <?= ucfirst($role) ?>
                                                </span>
                                            </td>

                                            <!-- Status -->
                                            <td class="px-6 py-4">
                                                <span class="status-badge status-<?= $status ?>">
                                                    <?= ucfirst($status) ?>
                                                </span>

                                                <?php if (!empty($user['total_spent'])): ?>
                                                    <div class="text-xs text-green-600 mt-1">
                                                        $<?= number_format((float)$user['total_spent'], 2) ?> spent
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Activity -->
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    Joined <?= !empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : '-' ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?= !empty($user['last_login'])
                                                        ? 'Last login: ' . date('M j, g:i A', strtotime($user['last_login']))
                                                        : 'Never logged in' ?>
                                                </div>
                                            </td>

                                            <!-- Actions -->
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col sm:flex-row gap-2">

                                                    <button onclick="editUser(<?= $id ?>)"
                                                        class="inline-flex items-center px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 text-sm hover-lift">
                                                        <i class="fas fa-edit mr-2"></i> Edit
                                                    </button>

                                                    <?php if ($canDelete): ?>
                                                        <button onclick="deleteUser(<?= $id ?>)"
                                                            class="inline-flex items-center px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 text-sm hover-lift">
                                                            <i class="fas fa-trash mr-2"></i> Delete
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($canToggle): ?>
                                                        <?php if ($status === 'active'): ?>
                                                            <button onclick="toggleUserStatus(<?= $id ?>, 'deactivate')"
                                                                class="inline-flex items-center px-3 py-2 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 text-sm hover-lift">
                                                                <i class="fas fa-user-slash mr-2"></i> Deactivate
                                                            </button>
                                                        <?php else: ?>
                                                            <button onclick="toggleUserStatus(<?= $id ?>, 'activate')"
                                                                class="inline-flex items-center px-3 py-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 text-sm hover-lift">
                                                                <i class="fas fa-user-check mr-2"></i> Activate
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>

                                                </div>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?= min($offset + 1, $filteredTotal) ?></span> to
                                <span class="font-medium"><?= min($offset + $perPage, $filteredTotal) ?></span> of
                                <span class="font-medium"><?= $filteredTotal ?></span> users
                            </div>

                            <div class="flex items-center space-x-2">
                                <?php if ($filters['page'] > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $filters['page'] - 1])) ?>"
                                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                                        <i class="fas fa-chevron-left mr-1"></i> Previous
                                    </a>
                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $filters['page'] - 2);
                                $endPage = min($totalPages, $filters['page'] + 2);
                                for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"
                                        class="px-3 py-2 text-sm rounded-lg <?= $i == $filters['page'] ? 'pagination-active' : 'border border-gray-300 hover:bg-gray-50' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($filters['page'] < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $filters['page'] + 1])) ?>"
                                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
                                        Next <i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
        </main>
    </div>
    <!-- Add/Edit User Modal (Converted from Product Modal style) -->
    <div id="userModal" class="fixed inset-0 hidden z-50 p-4 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50 modal-overlay"></div>

        <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl z-10 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                <h3 id="userModalTitle" class="text-lg font-semibold">Add User</h3>
                <button id="closeUserModal" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="userForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="userFormAction" value="add">
                <input type="hidden" name="user_id" id="userId" value="">
                <input type="hidden" name="old_avatar" id="oldAvatar" value="">

                <!-- Left column: fields -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="name" id="userName" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" id="userEmail" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" id="userPhone"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                            <select name="role" id="userRole" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="customer">Customer</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select name="status" id="userStatus" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Password area (Add required, Edit optional) -->
                    <div id="passwordWrap" class="space-y-4 pt-2 border-t">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span id="passwordLabel">Password *</span>
                            </label>
                            <input type="password" name="password" id="userPassword"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Enter password">
                            <p id="passwordHint" class="text-xs text-gray-400 mt-1 hidden">
                                Leave blank to keep current password.
                            </p>
                        </div>

                        <div id="confirmWrap">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                            <input type="password" name="confirm_password" id="userConfirmPassword"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Right column: avatar upload + preview -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Profile Image</label>

                    <div id="userUploadBox" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition">
                        <input type="file" name="avatar" id="userAvatar" accept="image/*" class="hidden">

                        <label for="userAvatar" class="cursor-pointer">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 mb-3 flex items-center justify-center bg-indigo-50 rounded-full">
                                    <i class="fas fa-cloud-upload-alt text-indigo-600 text-xl"></i>
                                </div>
                                <p class="text-sm font-medium text-gray-700 mb-1">Upload Image</p>
                                <p class="text-xs text-gray-500">Click to browse or drag and drop</p>
                                <p class="text-xs text-gray-400 mt-2">PNG, JPG up to 2MB</p>
                            </div>
                        </label>
                    </div>

                    <!-- Preview -->
                    <div id="userImagePreview" class="mt-4 hidden">
                        <div class="relative">
                            <img id="userPreviewImage" class="w-full h-64 object-cover rounded-lg border-2 border-gray-300" alt="Preview">
                            <button type="button" id="removeUserImage"
                                class="absolute top-3 right-3 bg-white/90 hover:bg-white border rounded-lg px-3 py-1 text-sm text-rose-600">
                                <i class="fas fa-trash mr-1"></i> Remove
                            </button>
                        </div>
                    </div>

                    <small class="text-gray-500">Max size: 2MB. Supported formats: JPG, PNG, GIF</small>

                    <!-- Buttons -->
                    <div class="mt-6 md:mt-12 flex justify-end gap-2">
                        <button type="button" id="cancelUserBtn"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" id="saveUserBtn"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition font-medium flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            <span id="saveUserText">Add User</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <script src="../../../assets/Js/users.js"></script>
</body>

</html>