<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   SESSION & AUTH
===================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    empty($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)
) {
    header('Location: ../auth/login.php');
    exit;
}

/* =====================================================
   FILTER INPUTS
===================================================== */
$filters = [
    'status'     => $_GET['status'] ?? '',
    'payment'    => $_GET['payment'] ?? '',
    'type'       => $_GET['type'] ?? '',
    'date_from'  => $_GET['date_from'] ?? '',
    'date_to'    => $_GET['date_to'] ?? '',
    'search'     => trim($_GET['search'] ?? ''),
    'sort'       => $_GET['sort'] ?? 'newest',
];

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

/* =====================================================
   BUILD QUERY
===================================================== */
$where = [];
$params = [];

if ($filters['status']) {
    $where[] = 'o.order_status = ?';
    $params[] = $filters['status'];
}
if ($filters['payment']) {
    $where[] = 'o.payment_status = ?';
    $params[] = $filters['payment'];
}
if ($filters['type']) {
    $where[] = 'o.order_type = ?';
    $params[] = $filters['type'];
}
if ($filters['date_from']) {
    $where[] = 'DATE(o.created_at) >= ?';
    $params[] = $filters['date_from'];
}
if ($filters['date_to']) {
    $where[] = 'DATE(o.created_at) <= ?';
    $params[] = $filters['date_to'];
}
if ($filters['search']) {
    $where[] = '(o.order_id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
    $term = '%' . $filters['search'] . '%';
    array_push($params, $term, $term, $term);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderBy = match ($filters['sort']) {
    'oldest'      => 'o.created_at ASC',
    'total_asc'   => 'o.total ASC',
    'total_desc'  => 'o.total DESC',
    default       => 'o.created_at DESC',
};

/* =====================================================
   FETCH STATS
===================================================== */
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(o.order_status='pending') pending
    FROM orders o
");
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalOrders = (int)$stats['total'];

$stats['pending_count'] = (int)($stats['pending'] ?? 0);

// Today's orders and revenue
$todayStmt = $pdo->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS revenue FROM orders WHERE DATE(created_at) = CURDATE()");
$todayStmt->execute();
$today = $todayStmt->fetch(PDO::FETCH_ASSOC);
$todayOrders = (int)($today['cnt'] ?? 0);
$todayRevenue = (float)($today['revenue'] ?? 0);

// Total revenue from paid orders
$revStmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) AS total_revenue FROM orders WHERE payment_status = 'paid'");
$revStmt->execute();
$rev = $revStmt->fetch(PDO::FETCH_ASSOC);
$totalRevenue = (float)($rev['total_revenue'] ?? 0.0);

// Status counts for filter tabs
$statusCounts = [
    'all' => $totalOrders,
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
];
$scStmt = $pdo->prepare("SELECT order_status, COUNT(*) AS cnt FROM orders GROUP BY order_status");
$scStmt->execute();
foreach ($scStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $k = $r['order_status'] ?? '';
    if ($k && array_key_exists($k, $statusCounts)) {
        $statusCounts[$k] = (int)$r['cnt'];
    }
}

/* =====================================================
   FETCH ORDERS
===================================================== */
$listStmt = $pdo->prepare("
    SELECT 
        o.order_id,
        o.total,
        o.order_status,
        o.payment_status,
        o.order_type,
        o.created_at,
        COALESCE(u.name, u.email, 'Guest') customer_name,
        u.email customer_email,
        (
            SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.order_id
        ) item_count
    FROM orders o
    LEFT JOIN users u ON u.user_id=o.user_id
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$listStmt->execute($params);
$orders = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = (int)ceil($totalOrders / $perPage);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Orders Management</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <main class="md:ml-64 p-4 md:p-6">
        <!-- Page Header -->
        <div class="mb-6 animate-fade-in">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Orders Management</h1>
                <p class="text-gray-600 mt-1">
                    Manage and track all customer orders
                </p>
            </div>
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Orders</p>
                            <p class="text-2xl font-bold mt-1"><?= number_format($totalOrders) ?></p>
                            <p class="text-xs text-gray-500 mt-1">All time</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-shopping-cart text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Today's Orders</p>
                            <p class="text-2xl font-bold mt-1"><?= number_format($todayOrders) ?></p>
                            <p class="text-xs <?= $todayOrders > 0 ? 'text-green-600' : 'text-gray-500' ?> mt-1">
                                $<?= number_format($todayRevenue, 2) ?> revenue
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-calendar-day text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Revenue</p>
                            <p class="text-2xl font-bold mt-1">$<?= number_format($totalRevenue, 2) ?></p>
                            <p class="text-xs text-gray-500 mt-1">Paid orders only</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Pending Orders</p>
                            <p class="text-2xl font-bold mt-1"><?= number_format($stats['pending_count'] ?? 0) ?></p>
                            <p class="text-xs <?= ($stats['pending_count'] ?? 0) > 0 ? 'text-yellow-600' : 'text-gray-500' ?> mt-1">
                                Needs attention
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="bg-white rounded-xl shadow-sm mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex overflow-x-auto">
                        <a href="?status="
                            class="filter-tab px-6 py-4 text-sm font-medium <?= empty($filters['status']) ? 'active' : '' ?>">
                            All Orders <span class="ml-2 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?= $statusCounts['all'] ?></span>
                        </a>
                        <a href="?status=completed"
                            class="filter-tab px-6 py-4 text-sm font-medium <?= $filters['status'] === 'completed' ? 'active' : '' ?>">
                            Completed <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs"><?= $statusCounts['completed'] ?></span>
                        </a>
                        <a href="?status=pending"
                            class="filter-tab px-6 py-4 text-sm font-medium <?= $filters['status'] === 'pending' ? 'active' : '' ?>">
                            Pending <span class="ml-2 bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs"><?= $statusCounts['pending'] ?></span>
                        </a>
                    </nav>
                </div>

                <!-- Filter Controls -->
                <div class="p-4 border-b border-gray-200">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text"
                                name="search"
                                value="<?= htmlspecialchars($filters['search']) ?>"
                                placeholder="Order ID, Name, Email..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date"
                                name="date_from"
                                value="<?= htmlspecialchars($filters['date_from']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date"
                                name="date_to"
                                value="<?= htmlspecialchars($filters['date_to']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <!-- Payment Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment</label>
                            <select name="payment" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">All Payment</option>
                                <option value="paid" <?= $filters['payment'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="pending" <?= $filters['payment'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="failed" <?= $filters['payment'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                <option value="refunded" <?= $filters['payment'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                            </select>
                        </div>

                        <!-- Order Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Order Type</label>
                            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">All Types</option>
                                <option value="delivery" <?= $filters['type'] === 'delivery' ? 'selected' : '' ?>>Delivery</option>
                                <option value="pickup" <?= $filters['type'] === 'pickup' ? 'selected' : '' ?>>Pickup</option>
                                <option value="dine-in" <?= $filters['type'] === 'dine-in' ? 'selected' : '' ?>>Dine-in</option>
                            </select>
                        </div>

                        <!-- Sort -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                <option value="total_desc" <?= $filters['sort'] === 'total_desc' ? 'selected' : '' ?>>Amount (High to Low)</option>
                                <option value="total_asc" <?= $filters['sort'] === 'total_asc' ? 'selected' : '' ?>>Amount (Low to High)</option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="md:col-span-3 lg:col-span-6 flex justify-end gap-2">
                            <button type="reset"
                                onclick="window.location.href='order.php'"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                Clear Filters
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                <i class="fas fa-filter mr-2"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- TABLE -->
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">Order</th>
                            <th class="px-6 py-3 text-left">Customer</th>
                            <th class="px-6 py-3 text-left">Total</th>
                            <th class="px-6 py-3 text-left">Status</th>
                            <th class="px-6 py-3 text-left">Payment</th>
                            <th class="px-6 py-3 text-left">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <?php if (!$orders): ?>
                            <tr>
                                <td colspan="6" class="p-12 text-center text-gray-500">
                                    No orders found
                                </td>
                            </tr>
                            <?php else: foreach ($orders as $o): ?>
                                <tr data-row="<?= $o['order_id'] ?>" class="hover:bg-gray-50">

                                    <td class="px-6 py-4">
                                        #<?= str_pad((string)$o['order_id'], 6, '0', STR_PAD_LEFT) ?><br>
                                        <span class="text-xs text-gray-500"><?= date('M j, Y', strtotime($o['created_at'])) ?></span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?= htmlspecialchars($o['customer_name']) ?><br>
                                        <span class="text-xs text-gray-500"><?= htmlspecialchars($o['customer_email']) ?></span>
                                    </td>

                                    <td class="px-6 py-4 font-semibold">
                                        $<?= number_format((float)$o['total'], 2) ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="status-badge status-<?= $o['order_status'] ?>">
                                            <?= ucfirst($o['order_status']) ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?= ucfirst($o['payment_status']) ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">

                                            <button
                                                class="btn-view px-3 py-2 bg-indigo-50 text-indigo-700 rounded"
                                                data-id="<?= $o['order_id'] ?>">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </button>

                                            <?php if (!in_array($o['order_status'], ['completed'], true)): ?>
                                                <button
                                                    class="btn-complete px-3 py-2 bg-green-50 text-green-700 rounded"
                                                    data-id="<?= $o['order_id'] ?>">
                                                    Complete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div id="usersPagination" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                            <span class="font-medium"><?php echo min($offset + $perPage, $totalOrders); ?></span> of
                            <span class="font-medium"><?php echo $totalOrders; ?></span> orders
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
    </main>

    <script src="../../../assets/Js/orders.js"></script>
</body>

</html>