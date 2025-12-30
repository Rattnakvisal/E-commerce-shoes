<?php
require_once __DIR__ . '/../../../config/conn.php';
require_once __DIR__ . '/user_api.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
            }
        }

        .card-hover:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }

        .role-admin {
            background-color: #dc2626;
            color: white;
        }

        .role-staff {
            background-color: #2563eb;
            color: white;
        }

        .role-customer {
            background-color: #059669;
            color: white;
        }

        .table-row:hover {
            background-color: #f9fafb;
        }

        .checkbox-cell {
            width: 40px;
        }

        .role-cell {
            width: 120px;
        }

        .action-cell {
            width: 150px;
        }

        .avatar-cell {
            width: 60px;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
        }

        .swal2-container {
            z-index: 99999 !important;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Top Navbar -->
    <?php require_once __DIR__ . '/../../include/navbar.php'; ?>
    <!-- Main Content -->
    <main class="md:ml-64 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">

            <!-- Page Header -->
            <div class="mb-6 animate-fade-in">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <!-- Title -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Users Management
                        </h1>
                        <p class="text-gray-600 mt-1">
                            Manage user accounts, roles, and permissions
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">

                        <button
                            id="openAddUserBtn"
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            <i class="fas fa-user-plus mr-2"></i>
                            Add User
                        </button>

                        <button
                            onclick="refreshData()"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                            <i class="fas fa-sync-alt"></i>
                        </button>

                    </div>
                </div>
            </div>


            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6 animate-fade-in">
                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Users</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($totalUsers); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Admin Users -->
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Administrators</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($adminUsers); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-crown text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Staff Users -->
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Staff Members</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($staffUsers); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-tie text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Customer Users -->
                <div class="bg-white rounded-xl shadow p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Customers</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($customerUsers); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow mb-6 animate-fade-in">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Filter Users
                    </h3>

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                        <!-- Search -->
                        <form method="GET" id="searchForm" class="flex-1 max-w-md">
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </span>
                                <input
                                    type="text"
                                    name="search"
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Search by name or email..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </form>

                        <!-- Filters -->
                        <div class="flex flex-wrap items-center gap-3">

                            <!-- Role -->
                            <select id="roleFilter"
                                class="px-3 py-2 border border-gray-300 rounded-lg
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Roles</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Customer</option>
                            </select>

                            <!-- Sort -->
                            <select id="sortFilter"
                                class="px-3 py-2 border border-gray-300 rounded-lg
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
                                <option value="email" <?= $sort === 'email' ? 'selected' : '' ?>>Email</option>
                            </select>

                            <!-- Actions -->
                            <button
                                id="applyFiltersBtn"
                                type="button"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg
                           hover:bg-indigo-700 transition">
                                Apply
                            </button>

                            <a
                                href="user.php"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg
                           hover:bg-gray-200 transition">
                                Clear
                            </a>
                        </div>
                    </div>
                </div>


                <!-- Users Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="checkbox-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </th>
                                <th class="avatar-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    User
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="role-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Joined
                                </th>
                                <th class="action-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                            <p class="text-lg font-medium text-gray-900">No users found</p>
                                            <p class="text-gray-500 mt-1">Try adjusting your search or filters</p>
                                            <a href="user.php" class="mt-4 text-indigo-600 hover:text-indigo-800">
                                                <i class="fas fa-redo mr-1"></i>
                                                Reset filters
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $name = $user['NAME'] ?? $user['name'] ?? 'Unknown';
                                    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=6366f1&color=fff';
                                    $roleVal = $user['ROLE'] ?? $user['role'] ?? 'customer';
                                    $roleClass = '';
                                    switch ($roleVal) {
                                        case 'admin':
                                            $roleClass = 'role-admin';
                                            break;
                                        case 'staff':
                                            $roleClass = 'role-staff';
                                            break;
                                        default:
                                            $roleClass = 'role-customer';
                                            break;
                                    }
                                    $createdAt = $user['created_at'] ?? 'N/A';
                                    $joinDate = $createdAt !== 'N/A' ? date('M d, Y', strtotime($createdAt)) : 'N/A';
                                    ?>
                                    <tr class="table-row hover:bg-gray-50 transition-colors" data-id="<?php echo $user['user_id']; ?>">
                                        <td class="checkbox-cell px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox"
                                                value="<?php echo $user['user_id']; ?>"
                                                class="user-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="avatar-cell px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full"
                                                        src="<?php echo htmlspecialchars($avatar); ?>"
                                                        alt="<?php echo htmlspecialchars($name); ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($name); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        ID: <?php echo $user['user_id']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="role-cell px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $roleClass; ?>">
                                                <?php echo ucfirst($roleVal); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $joinDate; ?>
                                        </td>
                                        <td class="action-cell px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-3">
                                                <!-- Edit -->
                                                <button type="button"
                                                    onclick="editUser(<?php echo $user['user_id']; ?>)"
                                                    class="text-indigo-600 p-2 hover:text-indigo-900 transition"
                                                    title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <!-- Quick Role Change -->
                                                <button type="button"
                                                    onclick="quickChangeRole(<?php echo $user['user_id']; ?>, '<?php echo $name; ?>')"
                                                    class="text-yellow-600 p-2 hover:text-yellow-900 transition"
                                                    title="Change Role">
                                                    <i class="fas fa-user-tag"></i>
                                                </button>

                                                <!-- Delete -->
                                                <button type="button"
                                                    onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo addslashes($name); ?>')"
                                                    class="text-red-600 p-2 hover:text-red-900 transition"
                                                    title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
                <div id="usersPagination" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                            <span class="font-medium"><?php echo min($offset + $limit, $totalResults); ?></span> of
                            <span class="font-medium"><?php echo $totalResults; ?></span> users
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>"
                                    data-page="<?php echo $page - 1; ?>"
                                    class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition pagination-link">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>"
                                    data-page="<?php echo $i; ?>"
                                    class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $i === $page ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-700 bg-white hover:bg-gray-50'; ?> transition pagination-link">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>"
                                    data-page="<?php echo $page + 1; ?>"
                                    class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition pagination-link">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </main>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div class="absolute inset-0 bg-black opacity-50 modal-overlay"></div>
        <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-lg z-10">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 id="modalTitle" class="text-lg font-semibold">Add User</h3>
                <button id="closeModal" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <form id="userForm" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="user_id" id="formUserId" value="">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" id="formName" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" id="formEmail" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div id="passwordField">
                    <label class="block text-sm font-medium text-gray-700 mb-1" id="passwordLabel">Password *</label>
                    <input type="password" name="password" id="formPassword"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-xs text-gray-500 mt-1" id="passwordHint">Minimum 6 characters</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select name="role" id="formRole" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="customer">Customer</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-2 pt-4">
                    <button type="button" id="cancelBtn"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        <span id="submitText">Add User</span>
                        <i id="loadingSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inline JavaScript -->
    <script src="../../../assets/Js/user.js"></script>
</body>

</html>