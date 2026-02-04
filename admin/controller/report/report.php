<?php
require_once __DIR__ . '/report_api.php';

if (!isset($exportTypes) || !is_array($exportTypes)) {
    $exportTypes = [
        'orders' => [
            'icon' => 'fa-shopping-cart',
            'color' => 'from-blue-500 to-blue-600',
            'formats' => ['csv', 'pdf', 'excel', 'json']
        ],
        'products' => [
            'icon' => 'fa-box',
            'color' => 'from-green-500 to-green-600',
            'formats' => ['csv', 'pdf', 'excel']
        ],
        'order_items' => [
            'icon' => 'fa-list',
            'color' => 'from-purple-500 to-purple-600',
            'formats' => ['csv', 'pdf']
        ],
        'customers' => [
            'icon' => 'fa-users',
            'color' => 'from-amber-500 to-amber-600',
            'formats' => ['csv', 'excel']
        ],
        'revenue' => [
            'icon' => 'fa-chart-line',
            'color' => 'from-emerald-500 to-emerald-600',
            'formats' => ['csv', 'pdf', 'excel']
        ],
        'analytics' => [
            'icon' => 'fa-chart-pie',
            'color' => 'from-pink-500 to-pink-600',
            'formats' => ['pdf', 'excel']
        ],
    ];
}

if (!isset($formatColors) || !is_array($formatColors)) {
    $formatColors = [
        'csv' => 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100',
        'excel' => 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100',
        'pdf' => 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100',
        'json' => 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100',
    ];
}
if (!isset($formatIcons) || !is_array($formatIcons)) {
    $formatIcons = [
        'csv' => 'fa-file-csv',
        'excel' => 'fa-file-excel',
        'pdf' => 'fa-file-pdf',
        'json' => 'fa-file-code',
    ];
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../../../assets/Css/reports.css">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <!-- Chart.js for simple charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gradient-to-br from-gray-50 via-white to-indigo-50/30 min-h-screen">

    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <div class="md:ml-64 min-h-screen animate-fade-in">
        <main class="pt-6 md:pt-16 p-4 sm:p-6 lg:p-8 page-transition bg-transparent min-h-screen">
            <!-- ===============================
                Reports Header
            ================================ -->
            <div class="mb-8">
                <div class="relative rounded-3xl border bg-white shadow-soft p-6 sm:p-8">
                    <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-black/[0.04] via-transparent to-black/[0.06] pointer-events-none"></div>
                    <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

                        <!-- Left -->
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-black text-white shadow">
                                    <i class="fas fa-chart-line"></i>
                                </span>

                                <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                                    Dashboard <span class="gradient-text">Reports</span>
                                </h1>
                            </div>

                            <p class="text-gray-600 text-sm sm:text-base max-w-2xl">
                                Welcome back, <span class="font-semibold text-gray-900">Admin</span>! Here's what's happening with your store today.
                            </p>

                            <!-- Meta -->
                            <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-500">
                                <span class="flex items-center gap-2">
                                    <i class="fa-regular fa-calendar"></i>
                                    <?= date('l, F j, Y') ?>
                                </span>

                                <span class="flex items-center gap-2">
                                    <i class="fa-regular fa-clock"></i>
                                    <span id="liveTime"></span>
                                </span>

                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Reports Ready
                                </span>
                            </div>
                        </div>

                        <!-- Right: Actions -->
                        <div class="flex flex-wrap items-center gap-3">

                            <!-- Dropdown Wrapper (relative) -->
                            <div class="relative" id="exportWrap">

                                <button
                                    id="exportDropdownBtn"
                                    type="button"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    class="inline-flex items-center gap-2 rounded-2xl bg-black text-white px-5 py-3 text-sm font-semibold shadow hover:opacity-90 transition group">
                                    <i class="fas fa-file-export"></i>
                                    Export Reports
                                    <i id="exportChevron" class="fas fa-chevron-down text-xs opacity-80 transition-transform duration-200"></i>
                                </button>

                                <!-- Dropdown (NOT clipped anymore) -->
                                <div
                                    id="exportDropdown"
                                    class="absolute right-0 mt-3 w-[min(22rem,calc(100vw-2rem))] bg-white rounded-2xl shadow-2xl border border-gray-100 hidden z-[999] overflow-hidden">

                                    <!-- Header -->
                                    <div class="p-4 border-b border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-black text-white flex items-center justify-center shadow">
                                                <i class="fas fa-download"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-extrabold text-gray-900">Export Reports</h3>
                                                <p class="text-xs text-gray-500">Select dataset and format</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Body -->
                                    <div class="p-4 max-h-[420px] overflow-y-auto space-y-4">
                                        <?php foreach ($exportTypes as $type => $data): ?>
                                            <div>
                                                <div class="flex items-center gap-2 mb-2">
                                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br <?= $data['color'] ?> flex items-center justify-center">
                                                        <i class="fas <?= $data['icon'] ?> text-white text-xs"></i>
                                                    </div>
                                                    <span class="font-semibold text-gray-800 capitalize">
                                                        <?= str_replace('_', ' ', $type) ?>
                                                    </span>
                                                    <span class="ml-auto text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                                        <?= count($data['formats']) ?> formats
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-2 gap-2">
                                                    <?php foreach ($data['formats'] as $format): ?>
                                                        <?php
                                                        $colorClass = $formatColors[$format] ?? 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100';
                                                        $iconClass  = $formatIcons[$format] ?? 'fa-file';
                                                        ?>
                                                        <a
                                                            href="./export_api.php?type=<?= $type ?>&format=<?= $format ?>"
                                                            target="_blank"
                                                            class="flex items-center justify-between p-3 rounded-xl border <?= $colorClass ?> transition group hover:shadow-sm">
                                                            <div class="flex items-center gap-2">
                                                                <i class="fas <?= $iconClass ?> text-base"></i>
                                                                <span class="font-semibold text-sm"><?= strtoupper($format) ?></span>
                                                            </div>
                                                            <i class="fas fa-arrow-down text-xs opacity-0 group-hover:opacity-100 transition"></i>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Footer -->
                                    <div class="px-4 py-3 bg-gray-50 flex items-center justify-between text-sm">
                                        <span class="text-gray-600 flex items-center gap-2">
                                            <i class="fas fa-info-circle"></i>
                                            Files download instantly
                                        </span>
                                        <button type="button" class="text-gray-900 hover:opacity-80 font-semibold flex items-center gap-2">
                                            <i class="fas fa-history"></i>
                                            Export History
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Refresh -->
                            <button
                                type="button"
                                onclick="window.location.reload()"
                                class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border hover:bg-gray-50 transition"
                                title="Refresh">
                                <i class="fa-solid fa-rotate"></i>
                            </button>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in-up">
                <!-- Total Orders -->
                <div class="stat-card bg-gradient-to-br from-white to-emerald-50/50 rounded-2xl p-6 shadow-soft-xl border border-emerald-100/50 relative overflow-hidden group hover:shadow-glow-green">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Total Orders</h3>
                            <div class="flex items-baseline mt-2">
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($summary['orders_count']); ?></p>
                                <div class="ml-2">
                                    <?php
                                    $lastWeekOrders = $summary['orders_count'] * 0.92;
                                    $percentage = $summary['orders_count'] > 0 ?
                                        (($summary['orders_count'] - $lastWeekOrders) / $lastWeekOrders * 100) : 0;
                                    $trend = $percentage >= 0 ? 'up' : 'down';
                                    $trendColor = $trend === 'up' ? 'green' : 'red';
                                    ?>
                                    <span class="inline-flex items-center text-<?php echo $trendColor; ?>-600 bg-<?php echo $trendColor; ?>-100/80 px-2 py-1 rounded-full text-xs font-medium border border-<?php echo $trendColor; ?>-200">
                                        <i class="fas fa-arrow-<?php echo $trend; ?> mr-1 text-xs"></i>
                                        <?php echo number_format(abs($percentage), 1); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-shopping-cart text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Completed</span>
                            <span class="font-semibold"><?php echo number_format(($statusSummaryMap['completed'] ?? 0) / max($summary['orders_count'], 1) * 100, 0); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full report-progress bg-gradient-to-r from-emerald-400 to-emerald-600"
                                style="--target-width: <?php echo ($statusSummaryMap['completed'] ?? 0) / max($summary['orders_count'], 1) * 100; ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="stat-card bg-gradient-to-br from-white to-amber-50/50 rounded-2xl p-6 shadow-soft-xl border border-amber-100/50 relative overflow-hidden group hover:shadow-glow">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-amber-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Revenue</h3>
                            <div class="flex items-baseline mt-2">
                                <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($summary['total_sales'], 2); ?></p>
                                <div class="ml-2">
                                    <?php
                                    $lastMonthRevenue = $summary['total_sales'] * 1.032;
                                    $revPercentage = $summary['total_sales'] > 0 ?
                                        (($summary['total_sales'] - $lastMonthRevenue) / $lastMonthRevenue * 100) : 0;
                                    $revTrend = $revPercentage >= 0 ? 'up' : 'down';
                                    $revTrendColor = $revTrend === 'up' ? 'green' : 'red';
                                    ?>
                                    <span class="inline-flex items-center text-<?php echo $revTrendColor; ?>-600 bg-<?php echo $revTrendColor; ?>-100/80 px-2 py-1 rounded-full text-xs font-medium border border-<?php echo $revTrendColor; ?>-200">
                                        <i class="fas fa-arrow-<?php echo $revTrend; ?> mr-1 text-xs"></i>
                                        <?php echo number_format(abs($revPercentage), 1); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-dollar-sign text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Target</span>
                            <span class="font-semibold">$<?php echo number_format($summary['total_sales'] * 1.2, 0); ?></span>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full report-progress bg-gradient-to-r from-amber-400 to-amber-600"
                                style="--target-width: <?php echo min(($summary['total_sales'] / ($summary['total_sales'] * 1.2)) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden group hover:shadow-glow-blue">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Registered Users</h3>
                            <div class="flex items-baseline mt-2">
                                <p class="text-2xl font-bold text-gray-900 glow-text"><?php echo number_format($total_users ?? 0); ?></p>
                                <div class="ml-2">
                                    <span class="inline-flex items-center text-green-600 bg-green-100/80 px-2 py-1 rounded-full text-xs font-medium border border-green-200">
                                        <i class="fas fa-user-plus mr-1 text-xs"></i>
                                        <?php echo $new_users > 0 ? number_format(($new_users / max($total_users, 1)) * 100, 1) . '%' : '0%'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>New (range)</span>
                            <span class="font-semibold"><?php echo number_format($new_users ?? 0); ?></span>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full report-progress bg-gradient-to-r from-blue-500 to-indigo-500"
                                style="--target-width: <?php echo ($new_users && $total_users) ? min(($new_users / max($total_users, 1)) * 100, 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
                <!-- Top Customer -->
                <div class="stat-card bg-gradient-to-br from-white to-purple-50/50 rounded-2xl p-6 shadow-soft-xl border border-purple-100/50 relative overflow-hidden group hover:shadow-glow-purple">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Top Customer</h3>
                            <div class="flex items-baseline mt-2">
                                <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($top_customer['user_id'] ?? '—'); ?></p>
                                <div class="ml-2">
                                    <span class="text-sm text-gray-500">$<?php echo number_format($top_customer['total_spent'] ?? 0, 2); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-crown text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Orders in range</span>
                            <span class="font-semibold"><?php echo isset($top_customer['total_spent']) && $top_customer['total_spent'] > 0 ? 'Yes' : 'No'; ?></span>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full report-progress bg-gradient-to-r from-amber-400 to-amber-600"
                                style="--target-width: <?php echo isset($top_customer['total_spent']) ? min(($top_customer['total_spent'] / max($summary['total_sales'], 1)) * 100, 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAGE WRAPPER (Premium Layout) -->
            <div class="min-h-screen">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 lg:gap-8">
                    <!-- =========================
                    LEFT COLUMN (Orders + Status)
                    ========================= -->
                    <div class="xl:col-span-7 flex flex-col gap-6 lg:gap-8">

                        <!-- ===== Card: Recent Orders ===== -->
                        <section class="group rounded-3xl bg-white/75 backdrop-blur-xl ring-1 ring-black/5 shadow-[0_25px_70px_-35px_rgba(0,0,0,0.35)]
                        overflow-hidden transition-all duration-300 hover:shadow-[0_35px_95px_-45px_rgba(0,0,0,0.45)]">

                            <!-- Header -->
                            <div class="sticky top-0 z-10 bg-white/70 backdrop-blur-xl border-b border-slate-200/70">
                                <div class="p-6 flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Recent Orders</h2>
                                        <p class="text-sm text-slate-500 mt-1">Latest 5 transactions with live updates</p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <!-- Live Badge -->
                                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold text-white
                                bg-gradient-to-r from-indigo-500 to-violet-500 shadow-sm ring-1 ring-white/20">
                                            <span class="relative flex h-2 w-2">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white/70 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                                            </span>
                                            Live
                                        </span>

                                        <!-- Action Button -->
                                        <button class="p-2 rounded-2xl hover:bg-slate-100/70 active:scale-95 transition"
                                            title="More">
                                            <i class="fas fa-ellipsis-v text-slate-400"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Chart -->
                            <div class="p-6 pt-5">
                                <div class="rounded-3xl bg-gradient-to-b from-white to-slate-50 ring-1 ring-slate-200/70 p-4 shadow-sm">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Orders Trend</p>
                                        <span class="text-xs px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 ring-1 ring-slate-200/70">
                                            Updated just now
                                        </span>
                                    </div>
                                    <div class="h-44">
                                        <canvas id="ordersChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Orders list (soft scroll) -->
                            <div class="divide-y divide-slate-200/70 max-h-[420px] overflow-y-auto">
                                <?php if (empty($recentOrders)): ?>
                                    <div class="p-10 text-center">
                                        <div class="mx-auto mb-4 w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center ring-1 ring-slate-200/70">
                                            <i class="fas fa-shopping-cart text-2xl text-slate-400"></i>
                                        </div>
                                        <p class="text-slate-600 font-bold">No recent orders</p>
                                        <p class="text-sm mt-2 text-slate-400">Orders will appear here as they come in</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <?php
                                        $st = $order['status'] ?? '';

                                        if ($st === 'completed') {
                                            $stClass = 'bg-gradient-to-r from-emerald-500 to-green-500 text-white';
                                            $stIcon  = 'fa-check-circle';
                                        } elseif ($st === 'pending') {
                                            $stClass = 'bg-gradient-to-r from-amber-400 to-yellow-500 text-white';
                                            $stIcon  = 'fa-clock';
                                        } elseif ($st === 'cancelled') {
                                            $stClass = 'bg-gradient-to-r from-rose-500 to-red-500 text-white';
                                            $stIcon  = 'fa-times-circle';
                                        } else {
                                            $stClass = 'bg-gradient-to-r from-indigo-500 to-violet-500 text-white';
                                            $stIcon  = 'fa-circle';
                                        }

                                        $initial = strtoupper(substr($order['customer_name'] ?? 'C', 0, 1));
                                        ?>

                                        <div class="p-5 hover:bg-slate-50/70 transition cursor-pointer group recent-order-row"
                                            data-order-id="<?= (int)$order['order_id']; ?>">

                                            <div class="flex items-center justify-between gap-4">
                                                <div class="flex items-center gap-4 min-w-0">
                                                    <!-- Avatar -->
                                                    <div class="w-11 h-11 rounded-2xl grid place-items-center text-white font-extrabold
                                            bg-gradient-to-br from-indigo-500 to-violet-600 shadow-sm ring-4 ring-indigo-500/10
                                            group-hover:ring-indigo-500/20 transition shrink-0">
                                                        <?= $initial; ?>
                                                    </div>

                                                    <!-- Text -->
                                                    <div class="min-w-0">
                                                        <p class="font-extrabold text-slate-900 truncate">
                                                            <?= htmlspecialchars($order['customer_name'] ?? ''); ?>
                                                        </p>

                                                        <div class="flex items-center gap-2 mt-2 flex-wrap">
                                                            <span class="text-xs text-slate-500 font-semibold">
                                                                #<?= (int)$order['order_id']; ?>
                                                            </span>
                                                            <span class="text-[11px] px-2 py-1 rounded-full bg-slate-100 text-slate-600 ring-1 ring-slate-200/70">
                                                                <?= isset($order['created_at']) ? date('M d', strtotime($order['created_at'])) : '—'; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Amount + Status -->
                                                <div class="text-right shrink-0">
                                                    <p class="font-extrabold text-slate-900 text-2xl tracking-tight">
                                                        $<?= number_format((float)$order['total'], 2); ?>
                                                    </p>

                                                    <span class="mt-2 inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold shadow-sm ring-1 ring-white/20 <?= $stClass; ?>">
                                                        <i class="fas <?= $stIcon; ?> text-[11px]"></i>
                                                        <?= htmlspecialchars($order['status'] ?? ''); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Footer -->
                            <div class="p-5 border-t border-slate-200/70 bg-white/60">
                                <div class="flex items-center justify-between gap-3">
                                    <a href="../analytics/analytics.php"
                                        class="text-sm font-bold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-2">
                                        View detailed analytics
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>

                                    <div class="flex items-center gap-2">
                                        <button class="p-2 rounded-2xl hover:bg-slate-100/70 active:scale-95 transition" title="Download">
                                            <i class="fas fa-download text-slate-500"></i>
                                        </button>
                                        <button class="p-2 rounded-2xl hover:bg-slate-100/70 active:scale-95 transition" title="Share">
                                            <i class="fas fa-share-alt text-slate-500"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- ===== Card: Order Status ===== -->
                        <section class="rounded-3xl bg-white/75 backdrop-blur-xl ring-1 ring-black/5 shadow-[0_25px_70px_-35px_rgba(0,0,0,0.35)]
                p-6 hover:shadow-[0_35px_95px_-45px_rgba(0,0,0,0.45)] transition-all duration-300">

                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">Order Status</h3>
                                    <p class="text-sm text-slate-500 mt-1">Distribution by status</p>
                                </div>
                                <span class="text-xs px-3 py-1 rounded-full bg-slate-100 text-slate-600 ring-1 ring-slate-200/70 font-semibold">
                                    Total: <?= (int)($summary['orders_count'] ?? 0); ?>
                                </span>
                            </div>

                            <div class="space-y-5">
                                <?php foreach ($statusSummary as $status):
                                    $percentage = ((int)$status['count'] / max((int)($summary['orders_count'] ?? 0), 1)) * 100;
                                    $statusKey = $status['status'] ?? '';

                                    if ($statusKey === 'completed') $bar = 'bg-gradient-to-r from-emerald-400 to-green-500';
                                    elseif ($statusKey === 'pending') $bar = 'bg-gradient-to-r from-amber-400 to-yellow-500';
                                    elseif ($statusKey === 'cancelled') $bar = 'bg-gradient-to-r from-rose-400 to-red-500';
                                    else $bar = 'bg-gradient-to-r from-sky-400 to-blue-500';
                                ?>
                                    <div class="rounded-3xl p-4 bg-white/60 ring-1 ring-slate-200/70">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-slate-700 font-bold">
                                                <?= htmlspecialchars($status['status'] ?? '') ?>
                                            </span>
                                            <span class="font-extrabold text-slate-900">
                                                <?= (int)$status['count']; ?>
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <div class="flex-1">
                                                <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                                    <div class="h-2 rounded-full <?= $bar; ?> transition-all duration-700"
                                                        style="width: <?= max(0, min(100, $percentage)); ?>%"></div>
                                                </div>
                                            </div>
                                            <span class="text-sm font-extrabold text-slate-700 w-14 text-right">
                                                <?= number_format($percentage, 1); ?>%
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                    </div>

                    <!-- =========================
                    RIGHT COLUMN (Top Products + Shipping)
                    ========================= -->
                    <div class="xl:col-span-5 flex flex-col gap-6 lg:gap-8">
                        <!-- ===== Top Products Card ===== -->
                        <section class="rounded-3xl bg-white/75 backdrop-blur-xl ring-1 ring-black/5
                        shadow-[0_25px_70px_-35px_rgba(0,0,0,0.35)]
                        overflow-hidden transition-all duration-300 hover:shadow-[0_35px_95px_-45px_rgba(0,0,0,0.45)]">

                            <!-- Header -->
                            <div class="p-6 border-b border-slate-200/70 flex justify-between items-start gap-4">
                                <div>
                                    <h2 class="text-2xl font-extrabold text-slate-900">Top Products</h2>
                                    <p class="text-sm text-slate-500 mt-1">Best sellers by revenue</p>
                                </div>

                                <select id="topProductsRange"
                                    class="text-sm rounded-2xl border-slate-200 bg-white/80 px-3 py-2
                                    focus:ring-4 focus:ring-indigo-500/15 transition">
                                    <option value="all" selected>All Time</option>
                                </select>
                            </div>

                            <!-- Chart -->
                            <div class="p-6">
                                <div class="mb-6 rounded-3xl bg-white ring-1 ring-slate-200/70 p-4">
                                    <div class="flex justify-between mb-3">
                                        <p class="text-xs font-bold text-slate-500 uppercase">Revenue Distribution</p>
                                        <span class="text-xs px-2.5 py-1 rounded-full bg-slate-100">
                                            Top <?= count($best ?? []) ?>
                                        </span>
                                    </div>

                                    <div class="h-56">
                                        <canvas id="topProductsChart"></canvas>
                                    </div>
                                </div>

                                <!-- Products List -->
                                <?php if (empty($best)): ?>
                                    <div class="text-center py-10">
                                        <p class="text-slate-500 font-medium">No product data available</p>
                                    </div>
                                <?php else: ?>
                                    <div
                                        class="space-y-5 max-h-[420px] overflow-y-auto pr-2
               scrollbar-thin scrollbar-thumb-slate-300
               hover:scrollbar-thumb-slate-400 scrollbar-track-transparent">

                                        <?php foreach ($best as $i => $p):
                                            $percent = ($p['revenue'] / $totalSales) * 100;
                                        ?>
                                            <div class="rounded-2xl p-4 bg-white ring-1 ring-slate-200/70">

                                                <div class="flex justify-between items-start gap-4">
                                                    <div>
                                                        <p class="font-semibold text-slate-900">
                                                            <?= htmlspecialchars($p['product_name']) ?>
                                                        </p>
                                                        <p class="text-xs text-slate-500 mt-1">
                                                            <?= number_format($p['qty_sold']) ?> sold
                                                        </p>
                                                    </div>

                                                    <div class="text-right">
                                                        <p class="font-extrabold text-slate-900">
                                                            $<?= number_format($p['revenue'], 2) ?>
                                                        </p>
                                                        <p class="text-xs text-slate-500">
                                                            <?= number_format($percent, 1) ?>%
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Progress -->
                                                <div class="mt-3 h-2 bg-slate-200 rounded-full overflow-hidden">
                                                    <div
                                                        class="h-2 bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-500"
                                                        style="width: <?= min(100, $percent) ?>%">
                                                    </div>
                                                </div>

                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </section>

                        <!-- ===== Card: Shipping & Locations ===== -->
                        <section class="rounded-3xl bg-white/75 backdrop-blur-xl ring-1 ring-black/5 shadow-[0_25px_70px_-35px_rgba(0,0,0,0.35)]
                        overflow-hidden hover:shadow-[0_35px_95px_-45px_rgba(0,0,0,0.45)] transition-all duration-300">

                            <div class="p-6 border-b border-slate-200/70">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Shipping & Locations</h2>
                                        <p class="text-sm text-slate-500 mt-1">Recent shipments and customer locations</p>
                                    </div>

                                    <a href="?page=shipping"
                                        class="text-sm font-bold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-2">
                                        View all
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="p-6">
                                <?php if (empty($shippingDetails)): ?>
                                    <div class="text-center py-8">
                                        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4 ring-1 ring-slate-200/70">
                                            <i class="fas fa-truck text-slate-400 text-2xl"></i>
                                        </div>
                                        <p class="text-slate-500 font-medium">No shipping data available</p>
                                        <p class="text-sm mt-2 text-slate-400">Shipments will appear here when available</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4 max-h-72 overflow-y-auto">
                                        <?php foreach ($shippingDetails as $ship):
                                            $lat = $ship['lat'] ?? null;
                                            $lng = $ship['lng'] ?? null;
                                            $mapUrl = ($lat && $lng) ? "https://www.google.com/maps?q={$lat},{$lng}" : null;
                                        ?>
                                            <div class="p-3 rounded-lg bg-white/60 ring-1 ring-slate-200/70 flex items-start justify-between">
                                                <div>
                                                    <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($ship['user'] ?? 'Unknown'); ?></p>
                                                    <p class="text-sm text-slate-500">
                                                        <?php echo htmlspecialchars($ship['address'] ?? ''); ?><?php echo !empty($ship['city']) ? ', ' . htmlspecialchars($ship['city']) : ''; ?><?php echo !empty($ship['country']) ? ', ' . htmlspecialchars($ship['country']) : ''; ?>
                                                    </p>
                                                    <p class="text-xs text-slate-400 mt-1">Shipped: <?php echo isset($ship['shipped_at']) ? date('M d, Y', strtotime($ship['shipped_at'])) : '—'; ?></p>
                                                </div>

                                                <div class="text-right flex flex-col items-end gap-2">
                                                    <?php if ($mapUrl): ?>
                                                        <div class="flex items-center gap-2">
                                                            <a href="<?php echo $mapUrl; ?>" target="_blank" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-2">Map <i class="fas fa-map-marker-alt text-[10px]"></i></a>
                                                            <button type="button" class="share-btn text-xs px-2 py-1 rounded-lg border text-slate-600 hover:bg-slate-100" data-address="<?php echo htmlspecialchars($ship['address'] ?? '', ENT_QUOTES); ?>" data-map="<?php echo $mapUrl; ?>">Share</button>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs text-slate-500">No coords</span>
                                                            <button type="button" class="share-btn text-xs px-2 py-1 rounded-lg border text-slate-600 hover:bg-slate-100" data-address="<?php echo htmlspecialchars($ship['address'] ?? '', ENT_QUOTES); ?>" data-map="">Share</button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../../../assets/Js/reports.js"></script>
    <script src="../../../assets/Js/exports.js"></script>
    <script src="../../../assets/Js/share.js"></script>

</body>

</html>