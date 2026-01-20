<?php
require_once __DIR__ . '/../../../config/conn.php';
require_once __DIR__ . '/process.php';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../../assets/Css/users.css">
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="bg-gray-50">

    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <div class="md:ml-64">
        <main class="p-4 sm:p-6 lg:p-8 min-h-screen animate-fade-in">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title -->
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">Users<span class="gradient-text font-extrabold">Management</span></h1>
                        </div>
                        <p class="text-gray-600 ml-1">Manage user accounts, roles, and permissions</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button onclick="showAddUserModal()"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <i class="fas fa-user-plus mr-2"></i> Add User
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
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-blue-500" style="width: <?= round(($stats['active_count'] ?? 0) / max($totalUsers, 1) * 100, 1) ?>%"></div>
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
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-green-500" style="width: <?= $totalUsers ? round($todayUsers / max($totalUsers, 1) * 100, 1) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>

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
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-emerald-500" style="width: <?= round(($stats['active_count'] ?? 0) / max($totalUsers, 1) * 100, 1) ?>%"></div>
                        </div>
                    </div>
                </div>

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
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-red-500" style="width: <?= round((($stats['inactive_count'] ?? $statusCounts['inactive'] ?? 0) / max($totalUsers, 1)) * 100, 1) ?>%"></div>
                        </div>
                    </div>
                </div>

            </div>

            <?php
            $queryBase = $_GET;
            unset($queryBase['status'], $queryBase['page']);
            ?>

            <!-- Filter Tabs -->
            <div class="bg-white rounded-xl shadow-sm mb-6">

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex gap-6 px-6 py-4 overflow-x-auto">

                        <!-- All Users -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['status' => '', 'role' => ''])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
               <?= (!$filters['status'] && !$filters['role'])
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            All Users
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600">
                                <?= $statusCounts['all'] ?>
                            </span>
                        </a>

                        <!-- Active -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['status' => 'active', 'role' => ''])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
               <?= ($filters['status'] === 'active')
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            Active
                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                <?= $statusCounts['active'] ?>
                            </span>
                        </a>

                        <!-- Inactive -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['status' => 'inactive', 'role' => ''])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
               <?= ($filters['status'] === 'inactive')
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            Inactive
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                <?= $statusCounts['inactive'] ?>
                            </span>
                        </a>

                        <!-- Role: Admin -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['role' => 'admin', 'status' => ''])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
               <?= ($filters['role'] === 'admin')
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            Admin
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                <?= $roleCounts['admin'] ?>
                            </span>
                        </a>

                        <!-- Role: Staff -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['role' => 'staff', 'status' => ''])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
               <?= ($filters['role'] === 'staff')
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            Staff
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                <?= $roleCounts['staff'] ?>
                            </span>
                        </a>

                        <!-- Role: Customer -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['role' => 'customer', 'status' => ''])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
               <?= ($filters['role'] === 'customer')
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            Customers
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                <?= $roleCounts['customer'] ?>
                            </span>
                        </a>

                    </nav>
                </div>

                <!-- Filter Form -->
                <div class="p-4">
                    <form method="GET"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">

                        <!-- Preserve tab filters -->
                        <input type="hidden" name="status" value="<?= htmlspecialchars($filters['status']) ?>">
                        <input type="hidden" name="role" value="<?= htmlspecialchars($filters['role']) ?>">

                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input type="text"
                                    name="search"
                                    value="<?= htmlspecialchars($filters['search']) ?>"
                                    placeholder="Name, Email, Phone..."
                                    class="w-full pl-10 pr-3 py-2 rounded-lg border border-gray-300
                                  focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            </div>
                        </div>

                        <!-- Role Select -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select name="role"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                <option value="">All Roles</option>
                                <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="staff" <?= $filters['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="customer" <?= $filters['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                            </select>
                        </div>

                        <!-- Sort -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select name="sort"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                <option value="name_asc" <?= $filters['sort'] === 'name_asc' ? 'selected' : '' ?>>Name (A–Z)</option>
                                <option value="name_desc" <?= $filters['sort'] === 'name_desc' ? 'selected' : '' ?>>Name (Z–A)</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="lg:col-span-2 flex gap-2 justify-end">
                            <a href="users.php"
                                class="px-4 py-2 rounded-lg border border-gray-300
                          text-gray-700 bg-white hover:bg-gray-50 transition">
                                Clear
                            </a>

                            <button type="submit"
                                class="px-4 py-2 rounded-lg bg-indigo-600 text-white
                               hover:bg-indigo-700 transition inline-flex items-center">
                                <i class="fas fa-filter mr-2"></i> Apply
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
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

                                    <tr class="hover:bg-gray-50 transition" data-user-id="<?= $id ?>">

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
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($name) ?></div>
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
    </div>
    </main>
    </div>
    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Add New User</h3>
                    <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <form id="addUserForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                <input type="text" name="name" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                <input type="email" name="email" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" name="phone"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                                <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="customer">Customer</option>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                                <input type="password" name="password" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                                <input type="password" name="confirm_password" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="pt-4 flex justify-end gap-2">
                            <button type="button" onclick="closeAddUserModal()"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Edit User</h3>
                    <button onclick="closeEditUserModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <form id="editUserForm" class="space-y-4">
                        <input type="hidden" name="user_id" id="edit_user_id">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                <input id="edit_name" name="name" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                <input id="edit_email" type="email" name="email" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input id="edit_phone" name="phone"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                                <select id="edit_role" name="role"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="customer">Customer</option>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    New Password (optional)
                                </label>
                                <input id="edit_password" type="password" name="password"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="edit_status" name="status"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-4 flex justify-end gap-2">
                            <button type="button" onclick="closeEditUserModal()"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="../../../assets/Js/users.js"></script>
</body>

</html>