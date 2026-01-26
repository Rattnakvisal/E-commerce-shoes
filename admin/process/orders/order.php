<?php
require_once __DIR__ . '/process.php';
$queryBase = $_GET ?? [];
unset($queryBase['status'], $queryBase['page']);

$currentStatus = (string)($filters['status'] ?? '');

$tabs = [
    [
        'label'      => 'All Orders',
        'status'     => '',
        'countKey'   => 'all',
        'pill'       => 'bg-gray-100 text-gray-600',
        'activeText' => 'text-indigo-600',
    ],
    [
        'label'      => 'Completed',
        'status'     => 'completed',
        'countKey'   => 'completed',
        'pill'       => 'bg-green-100 text-green-700',
        'activeText' => 'text-green-600',
    ],
    [
        'label'      => 'Pending',
        'status'     => 'pending',
        'countKey'   => 'pending',
        'pill'       => 'bg-yellow-100 text-yellow-700',
        'activeText' => 'text-yellow-600',
    ],
    [
        'label'      => 'Cancelled',
        'status'     => 'cancelled',
        'countKey'   => 'cancelled',
        'pill'       => 'bg-red-100 text-red-700',
        'activeText' => 'text-red-600',
    ],
];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Orders Management</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../../assets/Css/reports.css">
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <!-- Main Content -->
    <main class="md:ml-64 min-h-screen animate-fade-in">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="mb-8 fade-in-up">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">Orders <span class="gradient-text font-extrabold">Management</span></h1>
                        </div>
                        <p class="text-gray-600 ml-1">Manage and track all orders in your store.</p>
                    </div>
                </div>
            </div>
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 mt-6 fade-in-up">

                <!-- Total Orders -->
                <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden group hover:shadow-glow-blue">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Total Orders</h3>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format((int)$totalOrders) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-shopping-cart text-lg"></i>
                        </div>
                    </div>

                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>All time</span>
                            <span class="font-semibold">100%</span>
                        </div>
                    </div>
                </div>

                <!-- Today's Orders -->
                <div class="stat-card bg-gradient-to-br from-white to-green-50/50 rounded-2xl p-6 shadow-soft-xl border border-green-100/50 relative overflow-hidden group hover:shadow-glow-green">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Today’s Orders</h3>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format((int)$todayOrders) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-calendar-day text-lg"></i>
                        </div>
                    </div>

                    <?php
                    $todayRate = ($todayOrders && $totalOrders)
                        ? min(($todayOrders / max($totalOrders, 1)) * 100, 100)
                        : 0;
                    ?>

                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Today’s revenue</span>
                            <span class="<?= $todayOrders > 0 ? 'text-green-600 font-semibold' : '' ?>">
                                $<?= number_format((float)$todayRevenue, 2) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="stat-card bg-gradient-to-br from-white to-purple-50/50 rounded-2xl p-6 shadow-soft-xl border border-purple-100/50 relative overflow-hidden group hover:shadow-glow-purple">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Total Revenue</h3>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                $<?= number_format((float)$totalRevenue, 2) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-dollar-sign text-lg"></i>
                        </div>
                    </div>

                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Paid orders only</span>
                            <span class="font-semibold">$<?= number_format((float)$totalRevenue, 0) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="stat-card bg-gradient-to-br from-white to-amber-50/50 rounded-2xl p-6 shadow-soft-xl border border-amber-100/50 relative overflow-hidden group hover:shadow-glow-amber">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-amber-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Pending Orders</h3>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format((int)($stats['pending_count'] ?? 0)) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-clock text-lg"></i>
                        </div>
                    </div>

                    <?php
                    $pendingRate = ($stats['pending_count'] ?? 0) && $totalOrders
                        ? min((($stats['pending_count'] ?? 0) / max($totalOrders, 1)) * 100, 100)
                        : 0;
                    ?>

                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Needs attention</span>
                            <span class="<?= ($stats['pending_count'] ?? 0) > 0 ? 'text-amber-600 font-semibold' : '' ?>">
                                <?= number_format($pendingRate, 0) ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white">
                <div class="border-b border-gray-200">
                    <nav class="flex gap-6 px-6 py-4 overflow-x-auto">

                        <?php foreach ($tabs as $t): ?>
                            <?php
                            $isActive = ($t['status'] === $currentStatus);

                            $href = '?' . http_build_query(array_merge(
                                $queryBase,
                                ['status' => $t['status']]
                            ));

                            $linkClass = $isActive
                                ? "{$t['activeText']} border-b-2 border-indigo-600"
                                : "text-gray-500 hover:text-gray-700
                                border-b-2 border-transparent
                                transition-all duration-200";

                            $count = (int)($statusCounts[$t['countKey']] ?? 0);
                            ?>

                            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                                class="flex items-center gap-2 pb-2 text-sm font-medium <?= $linkClass ?>">
                                <?= htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') ?>

                                <span class="px-2 py-0.5 rounded-full text-xs <?= $t['pill'] ?>">
                                    <?= $count ?>
                                </span>
                            </a>

                        <?php endforeach; ?>

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
                                Clear
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Apply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- TABLE -->
            <div class="overflow-x-auto">
                <table class="divide-y divide-gray-200 min-w-[820px] table-auto text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="p-12 text-center text-gray-500">No orders found</td>
                            </tr>
                            <?php else: foreach ($orders as $o): ?>
                                <?php
                                $orderId = (int)($o['order_id'] ?? 0);

                                $statusRaw  = (string)($o['order_status'] ?? '');
                                $paymentRaw = (string)($o['payment_status'] ?? '');

                                $status  = strtolower(trim($statusRaw));
                                $payment = strtolower(trim($paymentRaw));

                                // SAFE CLASS (no spaces/special chars)
                                $statusSlug  = preg_replace('/[^a-z0-9]+/i', '-', $status);
                                $statusSlug  = trim((string)$statusSlug, '-');

                                $paymentSlug = preg_replace('/[^a-z0-9]+/i', '-', $payment);
                                $paymentSlug = trim((string)$paymentSlug, '-');

                                // Tailwind badge colors (order status)
                                $orderBadgeClass = match ($status) {
                                    'completed'  => 'bg-green-100 text-green-700',
                                    'pending'    => 'bg-yellow-100 text-yellow-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'cancelled'  => 'bg-red-100 text-red-700',
                                    default      => 'bg-gray-100 text-gray-700',
                                };

                                // Tailwind badge colors (payment status)
                                $payBadgeClass = match ($payment) {
                                    'paid'      => 'bg-green-100 text-green-700',
                                    'unpaid'    => 'bg-yellow-100 text-yellow-700',
                                    'pending'   => 'bg-yellow-100 text-yellow-700',
                                    'refunded'  => 'bg-red-100 text-red-700',
                                    default     => 'bg-gray-100 text-gray-700',
                                };
                                ?>

                                <tr data-row="<?= $orderId ?>" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        #<?= str_pad((string)$orderId, 6, '0', STR_PAD_LEFT) ?><br>
                                        <span class="text-xs text-gray-500">
                                            <?= !empty($o['created_at']) ? date('M j, Y', strtotime((string)$o['created_at'])) : '' ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-medium">
                                            <?= htmlspecialchars((string)($o['customer_name'] ?? 'Guest'), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            ID: <?= (int)($o['user_id'] ?? 0) ?>
                                            <?= !empty($o['customer_email']) ? ' • ' . htmlspecialchars((string)$o['customer_email'], ENT_QUOTES, 'UTF-8') : '' ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 font-semibold">
                                        $<?= number_format((float)($o['total'] ?? 0), 2) ?>
                                    </td>

                                    <!-- ✅ ORDER STATUS BADGE (FIXED) -->
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $orderBadgeClass ?>">
                                            <?= htmlspecialchars($statusRaw !== '' ? ucfirst($statusRaw) : '—', ENT_QUOTES, 'UTF-8') ?>
                                        </span>

                                        <!-- If you MUST keep your CSS status-badge system, use this instead:
                        <span class="status-badge status-<?= htmlspecialchars($statusSlug, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars(ucfirst($statusRaw), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        -->
                                    </td>

                                    <!-- ✅ PAYMENT STATUS BADGE (IMPROVED) -->
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $payBadgeClass ?>">
                                            <?= htmlspecialchars($paymentRaw !== '' ? ucfirst($paymentRaw) : '—', ENT_QUOTES, 'UTF-8') ?>
                                        </div>

                                        <?php if ($payment === 'paid'): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                $<?= number_format((float)($o['paid_amount'] ?? 0), 2) ?>
                                                <?php if (!empty($o['payment_date'])): ?>
                                                    • <?= date('M j, Y H:i', strtotime((string)$o['payment_date'])) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?php if (!empty($o['payment_method_name'])): ?>
                                            <div class="font-medium">
                                                <?= htmlspecialchars((string)$o['payment_method_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?= htmlspecialchars(strtoupper((string)($o['payment_method_code'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex gap-2 flex-wrap">
                                            <button type="button"
                                                class="btn-view px-3 py-2 bg-indigo-50 text-indigo-700 rounded"
                                                data-action="view"
                                                data-id="<?= $orderId ?>">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </button>

                                            <?php if ($payment !== 'refunded'): ?>
                                                <button type="button"
                                                    class="btn-payment px-3 py-2 bg-blue-50 text-blue-700 rounded"
                                                    data-action="payment"
                                                    data-id="<?= $orderId ?>"
                                                    data-payment="<?= htmlspecialchars($payment, ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-credit-card mr-1"></i> Payment
                                                </button>
                                            <?php endif; ?>

                                            <?php if (in_array($status, ['pending', 'processing'], true) && $payment !== 'paid'): ?>
                                                <button type="button"
                                                    class="btn-edit px-3 py-2 bg-yellow-50 text-yellow-700 rounded"
                                                    data-action="edit"
                                                    data-id="<?= $orderId ?>"
                                                    data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-edit mr-1"></i> Edit
                                                </button>

                                            <?php elseif (in_array($status, ['pending', 'processing'], true) && $payment === 'paid'): ?>
                                                <button type="button"
                                                    class="btn-complete px-3 py-2 bg-green-50 text-green-700 rounded"
                                                    data-action="complete"
                                                    data-id="<?= $orderId ?>">
                                                    <i class="fas fa-check mr-1"></i> Complete
                                                </button>

                                                <button type="button"
                                                    class="btn-refund px-3 py-2 bg-red-50 text-red-700 rounded"
                                                    data-action="refund"
                                                    data-id="<?= $orderId ?>">
                                                    <i class="fas fa-undo mr-1"></i> Refund
                                                </button>

                                            <?php else: ?>
                                                <span class="text-xs text-gray-400 italic px-3 py-2">Locked</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
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
    </main>

    <script src="../../../assets/Js/orders.js"></script>
    <script src="../../../assets/js/reports.js"></script>
</body>

</html>