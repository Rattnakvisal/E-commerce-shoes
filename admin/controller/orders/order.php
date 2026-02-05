<?php

declare(strict_types=1);

require_once __DIR__ . '/process.php'; // MUST define: $filters, $orders, $statusCounts, $stats, $page, $perPage, $offset, $totalPages, $totalOrders, $todayOrders, $totalRevenue, $todayRevenue

// ---------- safe helpers ----------
if (!function_exists('e')) {
    function e(mixed $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

// keep query string when switching tabs/pages
$queryBase = $_GET ?? [];
unset($queryBase['status'], $queryBase['page']);

$currentStatus = (string)($filters['status'] ?? '');

$tabs = [
    ['label' => 'All Orders',  'status' => '',           'countKey' => 'all',        'pill' => 'bg-gray-100 text-gray-600',    'activeText' => 'text-indigo-600'],
    ['label' => 'Completed',   'status' => 'completed',  'countKey' => 'completed',  'pill' => 'bg-emerald-100 text-emerald-700', 'activeText' => 'text-emerald-600'],
    ['label' => 'Processing',  'status' => 'processing', 'countKey' => 'processing', 'pill' => 'bg-sky-100 text-sky-700',      'activeText' => 'text-sky-600'],
    ['label' => 'Shipped',     'status' => 'shipped',    'countKey' => 'shipped',    'pill' => 'bg-amber-100 text-amber-700',  'activeText' => 'text-amber-600'],
    ['label' => 'Delivered',   'status' => 'delivered',  'countKey' => 'delivered',  'pill' => 'bg-indigo-100 text-indigo-700', 'activeText' => 'text-indigo-600'],
    ['label' => 'Cancelled',   'status' => 'cancelled',  'countKey' => 'cancelled',  'pill' => 'bg-rose-100 text-rose-700',    'activeText' => 'text-rose-600'],
];

// UI: badge classes
function orderBadgeClass(string $status): string
{
    $s = strtolower(trim($status));
    return match ($s) {
        'completed'  => 'bg-emerald-100 text-emerald-700',
        'pending'    => 'bg-amber-100 text-amber-700',
        'processing' => 'bg-slate-100 text-slate-700',
        'shipped'    => 'bg-amber-100 text-amber-700',
        'delivered'  => 'bg-indigo-100 text-indigo-700',
        'cancelled'  => 'bg-rose-100 text-rose-700',
        default      => 'bg-gray-100 text-gray-700',
    };
}

function payBadgeClass(string $payment): string
{
    $p = strtolower(trim($payment));
    return match ($p) {
        'paid'     => 'bg-emerald-100 text-emerald-700',
        'unpaid'   => 'bg-amber-100 text-amber-700',
        'pending'  => 'bg-amber-100 text-amber-700',
        'failed'   => 'bg-rose-100 text-rose-700',
        'refunded' => 'bg-purple-100 text-purple-700',
        default    => 'bg-gray-100 text-gray-700',
    };
}

// Action buttons: step flow
function nextStepButton(string $status, string $payment, int $orderId): string
{
    $s = strtolower(trim($status));
    $p = strtolower(trim($payment));

    $locked = in_array($s, ['completed', 'cancelled'], true);
    if ($locked) return '<span class="text-xs text-gray-400 italic px-3 py-2">Locked</span>';

    // only paid can complete
    if ($s === 'pending') {
        return '
          <button type="button"
            class="px-3 py-2 bg-sky-50 text-sky-700 rounded"
            data-action="mark-processing"
            data-id="' . $orderId . '"
            data-status="pending"
            data-payment="' . e($p) . '">
            <i class="fas fa-gear mr-1"></i> Start Processing
          </button>';
    }

    if ($s === 'processing') {
        return '
          <button type="button"
            class="px-3 py-2 bg-indigo-50 text-indigo-700 rounded"
                        data-action="mark-shipped"
                        data-id="' . $orderId . '"
                        data-status="processing"
                        data-payment="' . e($p) . '">
                        <i class="fas fa-box-open mr-1"></i> Mark Shipped
          </button>';
    }

    if ($s === 'shipped') {
        return '
                    <button type="button"
                        class="px-3 py-2 bg-indigo-50 text-indigo-700 rounded"
                        data-action="mark-delivered"
                        data-id="' . $orderId . '"
                        data-status="shipped"
                        data-payment="' . e($p) . '">
                        <i class="fas fa-truck mr-1"></i> Mark Delivered
                    </button>';
    }

    if ($s === 'delivered') {
        if ($p !== 'paid') {
            return '
              <span class="text-xs text-amber-700 bg-amber-50 border border-amber-100 px-3 py-2 rounded">
                Must be <b>PAID</b> to complete
              </span>';
        }

        return '
          <button type="button"
            class="px-3 py-2 bg-emerald-50 text-emerald-700 rounded"
            data-action="complete"
            data-id="' . $orderId . '"
            data-status="delivered"
            data-payment="paid">
            <i class="fas fa-check mr-1"></i> Mark Complete
          </button>';
    }

    // fallback: manual edit
    return '
      <button type="button"
        class="btn-edit px-3 py-2 bg-amber-50 text-amber-700 rounded"
        data-action="edit"
        data-id="' . $orderId . '"
        data-status="' . e($s) . '"
        data-payment="' . e($p) . '">
        <i class="fas fa-edit mr-1"></i> Edit
      </button>';
}

function pageHref(int $page, array $queryBase): string
{
    return '?' . http_build_query(array_merge($queryBase, ['page' => $page]));
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Orders Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../assets/css/products.css">
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <main class="md:ml-64 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">

            <!-- Header -->
            <div class="mb-8">
                <div class="relative rounded-3xl border bg-white shadow-soft p-6 sm:p-8">
                    <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-black/[0.04] via-transparent to-black/[0.06] pointer-events-none"></div>

                    <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-black text-white shadow">
                                    <i class="fas fa-bag-shopping"></i>
                                </span>
                                <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                                    Orders <span class="gradient-text ml-2">Management</span>
                                </h1>
                            </div>
                            <p class="text-gray-600 text-sm sm:text-base max-w-2xl">
                                Manage, monitor, and track all orders in your store with real-time status control.
                            </p>

                            <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-500">
                                <span class="flex items-center gap-2">
                                    <i class="fa-regular fa-truck"></i> Shipping & Delivery
                                </span>
                                <span class="flex items-center gap-2">
                                    <i class="fa-regular fa-credit-card"></i> Payments Tracking
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Orders Live
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button"
                                onclick="window.location.reload()"
                                class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border hover:bg-gray-50 transition"
                                title="Refresh">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 mt-6">
                <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Total Orders</h3>
                            <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format((int)$totalOrders) ?></p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-shopping-cart text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>All time</span><span class="font-semibold">100%</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-gradient-to-br from-white to-emerald-50/50 rounded-2xl p-6 shadow-soft-xl border border-emerald-100/50 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Today’s Orders</h3>
                            <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format((int)$todayOrders) ?></p>
                        </div>
                        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-calendar-day text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Today’s revenue</span>
                            <span class="<?= $todayOrders > 0 ? 'text-emerald-600 font-semibold' : '' ?>">
                                $<?= number_format((float)$todayRevenue, 2) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-gradient-to-br from-white to-purple-50/50 rounded-2xl p-6 shadow-soft-xl border border-purple-100/50 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Total Revenue</h3>
                            <p class="text-2xl font-bold mt-2 text-gray-900">$<?= number_format((float)$totalRevenue, 2) ?></p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-dollar-sign text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Paid orders only</span><span class="font-semibold">$<?= number_format((float)$totalRevenue, 0) ?></span>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-gradient-to-br from-white to-amber-50/50 rounded-2xl p-6 shadow-soft-xl border border-amber-100/50 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-amber-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Pending Orders</h3>
                            <p class="text-2xl font-bold mt-2 text-gray-900"><?= number_format((int)($stats['pending_count'] ?? 0)) ?></p>
                        </div>
                        <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-clock text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs + Filters + Table -->
            <section class="bg-white rounded-3xl border shadow-soft overflow-hidden">

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="flex gap-6 px-6 py-4 overflow-x-auto">
                        <?php foreach ($tabs as $t): ?>
                            <?php
                            $isActive = ($t['status'] === $currentStatus);
                            $href = '?' . http_build_query(array_merge($queryBase, ['status' => $t['status']]));
                            $linkClass = $isActive
                                ? "{$t['activeText']} border-b-2 border-indigo-600"
                                : "text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-all duration-200";
                            $count = (int)($statusCounts[$t['countKey']] ?? 0);
                            ?>
                            <a href="<?= e($href) ?>" class="flex items-center gap-2 pb-2 text-sm font-medium <?= e($linkClass) ?>">
                                <?= e($t['label']) ?>
                                <span class="px-2 py-0.5 rounded-full text-xs <?= e($t['pill']) ?>"><?= $count ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Filters -->
                <div class="p-4 border-b border-gray-200">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>"
                                placeholder="Order ID, Name, Email..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment</label>
                            <select name="payment" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">All Payment</option>
                                <option value="paid" <?= ($filters['payment'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="unpaid" <?= ($filters['payment'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                <option value="pending" <?= ($filters['payment'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="failed" <?= ($filters['payment'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                                <option value="refunded" <?= ($filters['payment'] ?? '') === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Order Type</label>
                            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">All Types</option>
                                <option value="online" <?= ($filters['type'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
                                <option value="pos" <?= ($filters['type'] ?? '') === 'pos' ? 'selected' : '' ?>>POS</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="newest" <?= ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                <option value="oldest" <?= ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                <option value="total_desc" <?= ($filters['sort'] ?? '') === 'total_desc' ? 'selected' : '' ?>>Amount (High to Low)</option>
                                <option value="total_asc" <?= ($filters['sort'] ?? '') === 'total_asc' ? 'selected' : '' ?>>Amount (Low to High)</option>
                            </select>
                        </div>

                        <div class="md:col-span-3 lg:col-span-6 flex justify-end gap-2">
                            <button type="button"
                                onclick="window.location.href='order.php'"
                                class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white text-gray-900 px-5 py-3 text-sm font-semibold hover:bg-gray-50 transition">
                                Clear
                            </button>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-2xl bg-black text-white px-5 py-3 text-sm font-semibold hover:opacity-90 transition">
                                Apply
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Table -->
            <div class="overflow-x-auto mt-6">
                <table class="min-w-full divide-y divide-gray-200">
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

                                $statusLabel  = $statusRaw !== '' ? ucwords(strtolower($statusRaw)) : '—';
                                $paymentLabel = $paymentRaw !== '' ? ucwords(strtolower($paymentRaw)) : '—';

                                $isLocked = in_array($status, ['completed', 'cancelled'], true);
                                $canRefund = (!$isLocked && $payment === 'paid');
                                ?>
                                <tr class="hover:bg-gray-50"
                                    data-row="<?= $orderId ?>">
                                    <td class="px-6 py-4">
                                        #<?= str_pad((string)$orderId, 6, '0', STR_PAD_LEFT) ?><br>
                                        <span class="text-xs text-gray-500">
                                            <?= !empty($o['created_at']) ? date('M j, Y', strtotime((string)$o['created_at'])) : '' ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-medium"><?= e($o['customer_name'] ?? 'Guest') ?></div>
                                        <div class="text-xs text-gray-500">
                                            ID: <?= (int)($o['user_id'] ?? 0) ?>
                                            <?php if (!empty($o['customer_email'])): ?>
                                                • <?= e($o['customer_email']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 font-semibold">
                                        $<?= number_format((float)($o['total'] ?? 0), 2) ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= e(orderBadgeClass($status)) ?>">
                                            <?= e($statusLabel) ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= e(payBadgeClass($payment)) ?>">
                                            <?= e($paymentLabel) ?>
                                        </div>

                                        <?php
                                        $paidAmount  = $o['paid_amount'] ?? null;
                                        $paymentDate = $o['payment_date'] ?? null;
                                        ?>
                                        <?php if ($payment === 'paid' && ($paidAmount !== null || !empty($paymentDate))): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <?php if ($paidAmount !== null): ?>
                                                    $<?= number_format((float)$paidAmount, 2) ?>
                                                <?php endif; ?>
                                                <?php if (!empty($paymentDate)): ?>
                                                    <?= $paidAmount !== null ? ' • ' : '' ?>
                                                    <?= date('M j, Y H:i', strtotime((string)$paymentDate)) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?php if (!empty($o['payment_method_name'])): ?>
                                            <div class="font-medium"><?= e($o['payment_method_name']) ?></div>
                                            <div class="text-xs text-gray-500"><?= e(strtoupper((string)($o['payment_method_code'] ?? ''))) ?></div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex gap-2 flex-wrap">
                                            <button type="button"
                                                class="px-3 py-2 bg-indigo-50 text-indigo-700 rounded"
                                                data-action="view"
                                                data-id="<?= $orderId ?>">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </button>

                                            <?php if (!$isLocked && $payment !== 'refunded'): ?>
                                                <button type="button"
                                                    class="px-3 py-2 bg-blue-50 text-blue-700 rounded"
                                                    data-action="payment"
                                                    data-id="<?= $orderId ?>"
                                                    data-payment="<?= e($payment) ?>">
                                                    <i class="fas fa-credit-card mr-1"></i> Payment
                                                </button>
                                            <?php endif; ?>

                                            <!-- STEP FLOW BUTTON (pending->processing->delivered->completed) -->
                                            <?= nextStepButton($status, $payment, $orderId) ?>

                                            <?php if ($canRefund): ?>
                                                <button type="button"
                                                    class="px-3 py-2 bg-rose-50 text-rose-700 rounded"
                                                    data-action="refund"
                                                    data-id="<?= $orderId ?>">
                                                    <i class="fas fa-undo mr-1"></i> Refund
                                                </button>
                                            <?php endif; ?>

                                            <?php if (!$isLocked): ?>
                                                <button type="button"
                                                    class="px-3 py-2 bg-gray-100 text-gray-800 rounded"
                                                    data-action="cancel"
                                                    data-id="<?= $orderId ?>"
                                                    data-status="<?= e($status) ?>"
                                                    data-payment="<?= e($payment) ?>">
                                                    <i class="fas fa-ban mr-1"></i> Cancel
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

            <!-- Pagination (FIXED: keeps filters, uses filtered total) -->
            <?php if (($totalPages ?? 1) > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium"><?= (int)$offset + 1 ?></span>
                            to
                            <span class="font-medium"><?= (int)min($offset + $perPage, ($filteredTotal ?? $totalOrders)) ?></span>
                            of
                            <span class="font-medium"><?= (int)($filteredTotal ?? $totalOrders) ?></span>
                            orders
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <?php if ($page > 1): ?>
                                <a href="<?= e(pageHref($page - 1, array_merge($queryBase, $_GET))) ?>"
                                    class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="<?= e(pageHref($i, array_merge($queryBase, $_GET))) ?>"
                                    class="px-3 py-2 border text-sm font-medium rounded-md transition
                                   <?= $i === $page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="<?= e(pageHref($page + 1, array_merge($queryBase, $_GET))) ?>"
                                    class="px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            </section>
        </div>
    </main>

    <!-- IMPORTANT: only include Tailwind once -->
    <script src="../../../assets/Js/orders.js"></script>
    <script>
        async function updateOrderStatus(orderId, toStatus) {
            const endpoint = "order_update_status.php";

            let note = "";
            if (toStatus === "cancelled") {
                const res = await Swal.fire({
                    title: "Cancel Order?",
                    input: "text",
                    inputLabel: "Reason (required)",
                    inputPlaceholder: "Example: customer requested cancel",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Cancel",
                    preConfirm: (val) => {
                        if (!val || !val.trim()) {
                            Swal.showValidationMessage("Reason is required");
                            return false;
                        }
                        return val.trim();
                    }
                });
                if (!res.isConfirmed) return;
                note = res.value;
            } else {
                const res = await Swal.fire({
                    title: "Confirm Status Change",
                    text: `Change order #${orderId} to "${toStatus}"?`,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Update"
                });
                if (!res.isConfirmed) return;
            }

            try {
                const r = await fetch(endpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    credentials: "same-origin",
                    body: JSON.stringify({
                        order_id: orderId,
                        to_status: toStatus,
                        note
                    })
                });

                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) throw new Error(data.error || ("HTTP " + r.status));

                await Swal.fire({
                    icon: "success",
                    title: "Updated",
                    text: data.message
                });

                // simplest: reload page
                location.reload();

                // OR: update UI row dynamically (if you want, tell me your row HTML and I update it)
            } catch (err) {
                Swal.fire({
                    icon: "error",
                    title: "Failed",
                    text: String(err.message || err)
                });
            }
        }
    </script>

</body>

</html>