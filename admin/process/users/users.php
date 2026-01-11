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
    <!-- Custom JS -->
    <link rel="stylesheet" href="../../../assets/Css/same.css">

</head>

<body class="bg-gray-50">

    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <div class="md:ml-64">
        <main class="p-4 sm:p-6 lg:p-8 min-h-screen animate-fade-in">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Users Management</h1>
                        <p class="text-gray-600 mt-1">
                            Manage user accounts, roles, and permissions
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button onclick="showAddUserModal()"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <i class="fas fa-user-plus mr-2"></i> Add User
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-fade-in">

                <!-- TOTAL USERS -->
                <div class="stat-card bg-white rounded-xl p-6 shadow-sm border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Users</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format($totalUsers) ?>
                            </p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">All accounts</p>
                </div>

                <!-- TODAY'S NEW USERS -->
                <div class="stat-card bg-white rounded-xl p-6 shadow-sm border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Today's New Users</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format($todayUsers) ?>
                            </p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-user-clock text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs mt-4 <?= $todayUsers > 0 ? 'text-green-600' : 'text-gray-500' ?>">
                        <?= $todayUsers > 0 ? 'Active growth' : 'No new users' ?>
                    </p>
                </div>

                <!-- ACTIVE USERS -->
                <div class="stat-card bg-white rounded-xl p-6 shadow-sm border-l-4 border-emerald-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Active Users</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format($stats['active_count'] ?? 0) ?>
                            </p>
                        </div>
                        <div class="bg-emerald-100 p-3 rounded-lg">
                            <i class="fas fa-user-check text-emerald-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-emerald-600 mt-4">
                        <?= round(($stats['active_count'] ?? 0) / max($totalUsers, 1) * 100, 1) ?>% of total
                    </p>
                </div>

                <!-- ADMINS / STAFF -->
                <div class="stat-card bg-white rounded-xl p-6 shadow-sm border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Admins</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format($stats['admin_count'] ?? 0) ?>
                            </p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-shield-alt text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-purple-600 mt-4">
                        <?= number_format($stats['staff_count'] ?? 0) ?> staff members
                    </p>
                </div>

            </div>

            <?php
            $queryBase = $_GET;
            unset($queryBase['status'], $queryBase['page']);
            ?>

            <!-- Filter Tabs -->
            <div class="bg-white rounded-xl shadow-sm mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex gap-6 px-6 py-4 overflow-x-auto">

                        <!-- ALL USERS -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['status' => ''])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
              <?= empty($filters['status'])
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            All Users
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600">
                                <?= $statusCounts['all'] ?>
                            </span>
                        </a>

                        <!-- ACTIVE -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['status' => 'active'])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
              <?= $filters['status'] === 'active'
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            Active
                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                <?= $statusCounts['active'] ?>
                            </span>
                        </a>

                        <!-- INACTIVE -->
                        <a href="?<?= http_build_query(array_merge($queryBase, ['status' => 'inactive'])) ?>"
                            class="flex items-center gap-2 text-sm font-medium
              <?= $filters['status'] === 'inactive'
                    ? 'text-indigo-600 border-b-2 border-indigo-600'
                    : 'text-gray-500 hover:text-gray-700' ?>">
                            Inactive
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                <?= $statusCounts['inactive'] ?>
                            </span>
                        </a>
                    </nav>
                </div>

                <!-- Filter Controls -->
                <div class="p-4 border-b border-gray-200">
                    <!-- Filter Controls -->
                    <div class="p-4">
                        <form method="GET"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">

                            <!-- Search -->
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Search
                                </label>
                                <div class="relative">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    <input
                                        type="text"
                                        name="search"
                                        value="<?= htmlspecialchars($filters['search']) ?>"
                                        placeholder="Name, Email, Phone..."
                                        class="w-full pl-10 pr-3 py-2 rounded-lg border border-gray-300
                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                           transition">
                                </div>
                            </div>

                            <!-- Role Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Role
                                </label>
                                <select
                                    name="role"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300
                       focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                       transition">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="staff" <?= $filters['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="customer" <?= $filters['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                </select>
                            </div>

                            <!-- Sort -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Sort By
                                </label>
                                <select
                                    name="sort"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300
                       focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                       transition">
                                    <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                    <option value="name_asc" <?= $filters['sort'] === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                                    <option value="name_desc" <?= $filters['sort'] === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                                    <option value="email_asc" <?= $filters['sort'] === 'email_asc' ? 'selected' : '' ?>>Email (A-Z)</option>
                                    <option value="email_desc" <?= $filters['sort'] === 'email_desc' ? 'selected' : '' ?>>Email (Z-A)</option>
                                </select>
                            </div>

                            <!-- Buttons -->
                            <div class="lg:col-span-2 flex gap-2 justify-end">
                                <button
                                    type="reset"
                                    onclick="window.location.href='users.php'"
                                    class="px-4 py-2 rounded-lg border border-gray-300
                       text-gray-700 bg-white hover:bg-gray-50
                       transition active:scale-95">
                                    Clear
                                </button>

                                <button
                                    type="submit"
                                    class="px-4 py-2 rounded-lg bg-indigo-600 text-white
                       hover:bg-indigo-700 transition
                       active:scale-95 inline-flex items-center">
                                    <i class="fas fa-filter mr-2"></i>
                                    Apply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <h3 class="empty-state-title">No users found</h3>
                                            <p class="empty-state-description">Try adjusting your filters</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50 transition" data-user-id="<?= $user['user_id'] ?>">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <?php $avatar = $user['avatar_url'] ?? null; ?>
                                                <?php if (!empty($avatar)): ?>
                                                    <img src="<?= htmlspecialchars($avatar) ?>"
                                                        alt="<?= htmlspecialchars($user['name'] ?? '') ?>"
                                                        class="w-10 h-10 rounded-full mr-3">
                                                <?php else: ?>
                                                    <div class="w-10 h-10 avatar-placeholder mr-3">
                                                        <?= strtoupper(substr($user['name'] ?? '', 0, 2)) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($user['name'] ?? '') ?></div>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        ID: #<?= str_pad($user['user_id'], 6, '0', STR_PAD_LEFT) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                                                <?php if ($user['phone']): ?>
                                                    <div class="text-sm text-gray-500">
                                                        <i class="fas fa-phone mr-1 text-xs"></i>
                                                        <?= htmlspecialchars($user['phone'] ?? '') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="role-badge <?= 'role-' . htmlspecialchars($user['role'] ?? '') ?>">
                                                <?= ucfirst(htmlspecialchars($user['role'] ?? '')) ?>
                                            </span>
                                            <?php $orderCount = (int)($user['order_count'] ?? 0); ?>
                                            <?php if ($orderCount > 0): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <?= $orderCount ?> orders
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-6 py-4">
                                            <?php
                                            // Normalize status (NULL / invalid → active)
                                            $status = in_array($user['status'] ?? '', ['active', 'inactive'], true)
                                                ? $user['status']
                                                : 'active';
                                            ?>
                                            <span class="status-badge status-<?= $status ?>">
                                                <?= ucfirst($status) ?>
                                            </span>

                                            <?php $totalSpent = (float)($user['total_spent'] ?? 0); ?>
                                            <?php if ($totalSpent > 0): ?>
                                                <div class="text-xs text-green-600 mt-1">
                                                    $<?= number_format($totalSpent, 2) ?> spent
                                                </div>
                                            <?php endif; ?>
                                        </td>

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

                                        <td class="px-6 py-4">
                                            <div class="flex flex-col sm:flex-row gap-2">
                                                <button onclick="editUser(<?= $user['user_id'] ?>)"
                                                    class="inline-flex items-center px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 text-sm hover-lift">
                                                    <i class="fas fa-edit mr-2"></i> Edit
                                                </button>

                                                <?php if (($user['role'] ?? '') !== 'admin' && $user['user_id'] !=
                                                    ($_SESSION['user_id'] ?? 0)
                                                ): ?>
                                                    <button onclick="deleteUser(<?= $user['user_id'] ?>)"
                                                        class="inline-flex items-center px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 text-sm hover-lift">
                                                        <i class="fas fa-trash mr-2"></i> Delete
                                                    </button>
                                                <?php endif; ?>

                                                <?php $uStatus = $user['status'] ?? ''; ?>
                                                <?php if ($uStatus === 'active'): ?>
                                                    <button onclick="toggleUserStatus(<?= $user['user_id'] ?>, 'deactivate')"
                                                        class="inline-flex items-center px-3 py-2 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 text-sm hover-lift">
                                                        <i class="fas fa-user-slash mr-2"></i> Deactivate
                                                    </button>
                                                <?php elseif ($uStatus === 'inactive'): ?>
                                                    <button onclick="toggleUserStatus(<?= $user['user_id'] ?>, 'activate')"
                                                        class="inline-flex items-center px-3 py-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 text-sm hover-lift">
                                                        <i class="fas fa-user-check mr-2"></i> Activate
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?= min($offset + 1, $totalUsers) ?></span> to
                                <span class="font-medium"><?= min($offset + $perPage, $totalUsers) ?></span> of
                                <span class="font-medium"><?= $totalUsers ?></span> users
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

    <script>

    </script>

</body>

</html>