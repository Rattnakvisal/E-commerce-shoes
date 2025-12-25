<?php
require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();
ini_set('display_errors', 0);

// Authentication check
if (empty($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Authorization check
$currentRole = $_SESSION['role'] ?? null;
if ($currentRole !== 'admin') {
    if ($currentRole === 'staff') {
        header('Location: ../../pos/staff_dashboard.php');
        exit;
    }
    header('Location: ../../view/index.php');
    exit;
}

// Admin session data
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'Administrator';
$admin_avatar = $_SESSION['admin_avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($admin_name) . '&background=6366f1&color=fff';

// Get user counts
function getUserCount($pdo, $role = null)
{
    $sql = "SELECT COUNT(*) FROM users";
    if ($role) {
        $sql .= " WHERE ROLE = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$role]);
        return (int) $stmt->fetchColumn();
    }
    $stmt = $pdo->query($sql);
    return (int) $stmt->fetchColumn();
}

$totalUsers = getUserCount($pdo);
$adminUsers = getUserCount($pdo, 'admin');
$staffUsers = getUserCount($pdo, 'staff');
$customerUsers = getUserCount($pdo, 'customer');

// API Request Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    handleApiRequest($pdo);
    exit;
}

// GET API for fetching single user
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user') {
    handleGetUserRequest($pdo);
    exit;
}

// Main page logic
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

[$users, $totalResults, $totalPages] = getUsers($pdo, $search, $role, $sort, $page);

/* =====================================================
   API FUNCTIONS
===================================================== */

/**
 * Handle POST API requests
 */
function handleApiRequest($pdo)
{
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];
        $response = match ($action) {
            'delete' => handleDelete($pdo),
            'update_role' => handleUpdateRole($pdo),
            'create', 'update' => handleCreateUpdate($pdo),
            default => ['success' => false, 'message' => 'Invalid action']
        };

        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle GET user request
 */
function handleGetUserRequest($pdo)
{
    header('Content-Type: application/json');

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user id']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT user_id, NAME, email, ROLE, created_at FROM users WHERE user_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'user_id' => $user['user_id'],
                    'name' => $user['NAME'],
                    'email' => $user['email'],
                    'role' => $user['ROLE'],
                    'created_at' => $user['created_at']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle user deletion
 */
function handleDelete($pdo)
{
    $userId = (int)($_POST['user_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return ['success' => true, 'message' => 'User deleted successfully'];
}

/**
 * Handle role update
 */
function handleUpdateRole($pdo)
{
    $userId = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';

    if (!in_array($role, ['admin', 'staff', 'customer'])) {
        return ['success' => false, 'message' => 'Invalid role'];
    }

    $stmt = $pdo->prepare("UPDATE users SET ROLE = ? WHERE user_id = ?");
    $stmt->execute([$role, $userId]);
    return ['success' => true, 'message' => 'User role updated successfully'];
}

/**
 * Handle user creation/update
 */
function handleCreateUpdate($pdo)
{
    $action = $_POST['action'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    // Validation
    if (empty($name) || empty($email)) {
        return ['success' => false, 'message' => 'Name and email are required'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }

    if ($action === 'create' && (empty($password) || strlen($password) < 6)) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }

    if (!in_array($role, ['admin', 'staff', 'customer'])) {
        $role = 'customer';
    }

    // Check email uniqueness
    if (!isEmailUnique($pdo, $email, $userId, $action)) {
        return ['success' => false, 'message' => 'Email already exists'];
    }

    // Create or update user
    if ($action === 'create') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (NAME, email, PASSWORD, ROLE) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $role]);
        $message = 'User created successfully';
    } else {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET NAME = ?, email = ?, PASSWORD = ?, ROLE = ? WHERE user_id = ?');
            $stmt->execute([$name, $email, $hash, $role, $userId]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET NAME = ?, email = ?, ROLE = ? WHERE user_id = ?');
            $stmt->execute([$name, $email, $role, $userId]);
        }
        $message = 'User updated successfully';
    }

    return ['success' => true, 'message' => $message];
}

/**
 * Check if email is unique
 */
function isEmailUnique($pdo, $email, $userId, $action)
{
    if ($action === 'create') {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
    } else {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1');
        $stmt->execute([$email, $userId]);
    }
    return !$stmt->fetch();
}

/* =====================================================
   DATA FUNCTIONS
===================================================== */

/**
 * Get paginated users with filters
 */
function getUsers($pdo, $search, $role, $sort, $page, $limit = 10)
{
    // Build base query
    $query = "SELECT * FROM users WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (NAME LIKE ? OR email LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm];
    }

    if (!empty($role)) {
        $query .= " AND ROLE = ?";
        $params[] = $role;
    }

    // Get total count
    $countQuery = preg_replace('/^SELECT\s+\*/i', 'SELECT COUNT(*)', $query);
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalResults = $countStmt->fetchColumn();
    $totalPages = ceil($totalResults / $limit);

    // Apply sorting
    $query .= match ($sort) {
        'oldest' => " ORDER BY created_at ASC",
        'name' => " ORDER BY NAME ASC",
        'email' => " ORDER BY email ASC",
        default => " ORDER BY created_at DESC"
    };

    // Apply pagination
    $offset = ($page - 1) * $limit;
    $query .= " LIMIT $limit OFFSET $offset";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [$users, $totalResults, $totalPages];
}
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
                <div class="flex flex-col md:flex-row md:items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Users Management</h1>
                        <p class="text-gray-600 mt-1">Manage user accounts, roles, and permissions</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <button id="openAddUserBtn" type="button"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-300">
                            <i class="fas fa-user-plus mr-2"></i>
                            Add New User
                        </button>

                        <button onclick="refreshData()" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-300">
                            <i class="fas fa-sync-alt mr-2"></i>
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

            <!-- Filters and Actions -->
            <div class="bg-white rounded-xl shadow mb-6 animate-fade-in">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between space-y-4 lg:space-y-0">
                        <!-- Search -->
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <form method="GET" action="" id="searchForm">
                                    <input type="text"
                                        name="search"
                                        value="<?php echo htmlspecialchars($search); ?>"
                                        class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Search users by name or email...">
                                </form>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-wrap gap-3">
                            <div class="flex items-center space-x-3">
                                <!-- Role Filter -->
                                <div>
                                    <select name="role" id="roleFilter"
                                        class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">All Roles</option>
                                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="staff" <?php echo $role === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="customer" <?php echo $role === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    </select>
                                </div>

                                <!-- Sort -->
                                <div>
                                    <select name="sort" id="sortFilter"
                                        class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>By Name</option>
                                        <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>By Email</option>
                                    </select>
                                </div>
                                <!-- Apply / Clear Buttons -->
                                <div class="flex items-center space-x-2">
                                    <button id="applyFiltersBtn" type="button" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Apply</button>
                                    <a href="user.php" id="clearFiltersBtn" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">Clear</a>
                                </div>
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

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                                    <span class="font-medium"><?php echo min($offset + $limit, $totalResults); ?></span> of
                                    <span class="font-medium"><?php echo $totalResults; ?></span> users
                                </div>
                                <div class="flex space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&sort=<?php echo urlencode($sort); ?>"
                                            class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                                            Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&sort=<?php echo urlencode($sort); ?>"
                                            class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $i === $page ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-700 bg-white hover:bg-gray-50'; ?> transition">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&sort=<?php echo urlencode($sort); ?>"
                                            class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
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