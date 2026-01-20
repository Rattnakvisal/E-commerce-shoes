<?php
require_once __DIR__ . '/report_api.php';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js for simple charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gradient-to-br from-gray-50 via-white to-indigo-50/30 min-h-screen">

    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <div class="md:ml-64 min-h-screen">
        <main class="pt-6 md:pt-16 p-4 sm:p-6 lg:p-8 page-transition bg-transparent min-h-screen">
            <!-- Header with Breadcrumb -->
            <div class="mb-8 fade-in-up">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">
                                Dashboard <span class="gradient-text font-extrabold">Reports</span>
                            </h1>
                        </div>
                        <p class="text-gray-600 ml-1">Welcome back, <span class="font-semibold text-gray-900">Admin</span>! Here's what's happening with your store today.</p>
                    </div>

                    <!-- Export Section with Dropdown -->
                    <div class="relative fade-in-down">
                        <div class="flex items-center gap-3">
                            <button id="exportDropdownBtn" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl shadow-md hover:shadow-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 group">
                                <i class="fas fa-file-export text-sm"></i>
                                <span class="font-semibold">Export Reports</span>
                                <i class="fas fa-chevron-down text-xs transition-transform duration-300 group-hover:rotate-180"></i>
                            </button>
                        </div>

                        <!-- Export Dropdown Menu -->
                        <div id="exportDropdown" class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-2xl border border-gray-100 hidden z-50 overflow-hidden glass-effect">
                            <div class="p-4 border-b border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center">
                                        <i class="fas fa-download text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900">Export Reports</h3>
                                        <p class="text-xs text-gray-500">Select data and format</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 max-h-96 overflow-y-auto">
                                <!-- Export Categories -->
                                <?php
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
                                    ]
                                ];
                                ?>

                                <?php foreach ($exportTypes as $type => $data): ?>
                                    <div class="mb-4 last:mb-0">
                                        <div class="flex items-center gap-2 mb-2 px-2">
                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br <?php echo $data['color']; ?> flex items-center justify-center">
                                                <i class="fas <?php echo $data['icon']; ?> text-white text-xs"></i>
                                            </div>
                                            <span class="font-semibold text-gray-800 capitalize"><?php echo str_replace('_', ' ', $type); ?></span>
                                            <span class="ml-auto text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                                                <?php echo count($data['formats']); ?> formats
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 px-2">
                                            <?php foreach ($data['formats'] as $format): ?>
                                                <?php
                                                $formatColors = [
                                                    'csv' => 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100',
                                                    'pdf' => 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100',
                                                    'excel' => 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100',
                                                    'json' => 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                                                ];
                                                $formatIcons = [
                                                    'csv' => 'fa-file-csv',
                                                    'pdf' => 'fa-file-pdf',
                                                    'excel' => 'fa-file-excel',
                                                    'json' => 'fa-file-code'
                                                ];
                                                $colorClass = $formatColors[$format] ?? 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100';
                                                $iconClass = $formatIcons[$format] ?? 'fa-file';
                                                ?>
                                                <a href="./export_api.php?type=<?php echo $type; ?>&format=<?php echo $format; ?>"
                                                    target="_blank"
                                                    class="export-item flex items-center justify-between p-3 rounded-lg border <?php echo $colorClass; ?> transition-all duration-300 group relative overflow-hidden"
                                                    data-type="<?php echo $type; ?>"
                                                    data-format="<?php echo $format; ?>">
                                                    <div class="flex items-center gap-2">
                                                        <i class="fas <?php echo $iconClass; ?> text-lg"></i>
                                                        <span class="font-medium text-sm"><?php echo strtoupper($format); ?></span>
                                                    </div>
                                                    <i class="fas fa-download text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-300"></i>

                                                    <!-- Progress bar -->
                                                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-current opacity-20 download-progress"></div>

                                                    <!-- Format badge -->
                                                    <span class="absolute -top-2 -right-2 text-xs font-bold px-2 py-1 rounded-full bg-white border shadow-sm capitalize">
                                                        <?php echo $format; ?>
                                                    </span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Files download instantly
                                    </span>
                                    <button class="text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1">
                                        <i class="fas fa-history"></i>
                                        Export History
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in-up">
                <!-- Total Users -->
                <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden group hover:shadow-glow-blue">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Total Users</h3>
                            <div class="flex items-baseline mt-2">
                                <p class="text-2xl font-bold text-gray-900 glow-text"><?php echo number_format($summary['unique_customers']); ?></p>
                                <div class="ml-2">
                                    <span class="inline-flex items-center text-green-600 bg-green-100/80 px-2 py-1 rounded-full text-xs font-medium border border-green-200">
                                        <i class="fas fa-user-check mr-1 text-xs"></i>
                                        <?php echo number_format(($summary['unique_customers'] / max($total_users, 1)) * 100, 1); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-users text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Of Total Users</span>
                            <span class="font-semibold"><?php echo number_format(($summary['unique_customers'] / max($total_users, 1)) * 100, 1); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full report-progress bg-gradient-to-r from-blue-500 to-indigo-500"
                                style="--target-width: <?php echo ($summary['unique_customers'] / max($total_users, 1)) * 100; ?>%"></div>
                        </div>
                    </div>
                </div>

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
                            <i class="fas fa-shopping-cart text-lg"></i>
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
                            <i class="fas fa-dollar-sign text-lg"></i>
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

                <!-- Conversion Rate -->
                <div class="stat-card bg-gradient-to-br from-indigo-600 to-purple-600 text-white rounded-2xl p-6 shadow-soft-xl border border-indigo-500/30 relative overflow-hidden group hover:shadow-glow-purple">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-white/90 tracking-wider mb-1">Conversion Rate</h3>
                            <div class="flex items-baseline mt-2">
                                <?php
                                $visitors = max($summary['unique_customers'] * 5, 1);
                                $conversionRate = ($summary['orders_count'] / $visitors) * 100;
                                ?>
                                <p class="text-2xl font-bold text-white"><?php echo number_format($conversionRate, 2); ?>%</p>
                                <div class="ml-2">
                                    <span class="inline-flex items-center bg-white/20 text-white px-2 py-1 rounded-full text-xs font-medium border border-white/30">
                                        <i class="fas fa-arrow-up mr-1 text-xs"></i>
                                        <?php echo number_format($conversionRate / 2, 1); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/20 p-3 rounded-xl shadow-inner">
                            <i class="fas fa-chart-line text-lg text-white"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-white/80 mb-2">
                            <span>Overall Rate</span>
                            <span class="font-semibold"><?php echo number_format($conversionRate, 2); ?>%</span>
                        </div>
                        <div class="w-full bg-white/30 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full report-progress bg-gradient-to-r from-white to-white/80"
                                style="--target-width: <?php echo min(($conversionRate / 3.5) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Users Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in-up">
                <!-- Registered Users -->
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
                            <i class="fas fa-users text-lg"></i>
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

                <!-- Active Customers -->
                <div class="stat-card bg-gradient-to-br from-white to-green-50/50 rounded-2xl p-6 shadow-soft-xl border border-green-100/50 relative overflow-hidden group hover:shadow-glow-green">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Active Customers</h3>
                            <div class="flex items-baseline mt-2">
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($summary['unique_customers'] ?? 0); ?></p>
                                <div class="ml-2">
                                    <span class="inline-flex items-center text-green-600 bg-green-100/80 px-2 py-1 rounded-full text-xs font-medium border border-green-200">
                                        <i class="fas fa-chart-line mr-1 text-xs"></i>
                                        <?php echo number_format(($summary['unique_customers'] ?? 0) / max($total_users, 1) * 100, 1); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-user-check text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Percent of users</span>
                            <span class="font-semibold"><?php echo number_format(($summary['unique_customers'] ?? 0) / max($total_users, 1) * 100, 1); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full report-progress bg-gradient-to-r from-green-400 to-green-600"
                                style="--target-width: <?php echo ($summary['unique_customers'] ?? 0) / max($total_users, 1) * 100; ?>%"></div>
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
                                <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($top_customer['NAME'] ?? '—'); ?></p>
                                <div class="ml-2">
                                    <span class="text-sm text-gray-500">$<?php echo number_format($top_customer['total_spent'] ?? 0, 2); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-crown text-lg"></i>
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

                <!-- Customers Growth -->
                <div class="stat-card bg-gradient-to-br from-white to-pink-50/50 rounded-2xl p-6 shadow-soft-xl border border-pink-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-pink-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 tracking-wider mb-1">Customer Growth</h3>
                            <div class="flex items-baseline mt-2">
                                <?php $growth = ($new_users / max(($total_users - $new_users), 1)) * 100; ?>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($growth, 1); ?>%</p>
                                <div class="ml-2">
                                    <span class="inline-flex items-center text-pink-600 bg-pink-100/80 px-2 py-1 rounded-full text-xs font-medium border border-pink-200">
                                        <i class="fas fa-arrow-up mr-1 text-xs"></i>
                                        <?php echo number_format($growth / 3, 1); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-pink-500 to-pink-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-seedling text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-4 relative z-10">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>New vs existing</span>
                            <span class="font-semibold"><?php echo number_format($new_users ?? 0); ?></span>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full report-progress bg-gradient-to-r from-pink-400 to-pink-600"
                                style="--target-width: <?php echo ($total_users > 0) ? min(($new_users / $total_users) * 100, 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAGE WRAPPER -->
            <div class="min-h-screen">
                <!-- MAIN GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

                    <!-- LEFT COLUMN (STACK) -->
                    <div class="flex flex-col gap-8">

                        <!-- Recent Orders -->
                        <div class="rounded-2xl bg-white/80 backdrop-blur-xl ring-1 ring-black/5 shadow-[0_20px_45px_-20px_rgba(0,0,0,0.25)] overflow-hidden hover:shadow-[0_30px_70px_-25px_rgba(0,0,0,0.35)] transition-all duration-300">
                            <div class="p-6 border-b border-slate-200/70">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-lg font-semibold text-slate-900 tracking-tight">Recent Orders</h2>
                                        <p class="text-sm text-slate-500 mt-1">Latest 5 transactions</p>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold text-white
                           bg-gradient-to-r from-indigo-500 to-violet-500 shadow-sm ring-1 ring-white/20">
                                            <span class="relative flex h-2 w-2">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white/70 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                                            </span>
                                            Live
                                        </span>

                                        <button class="p-2 rounded-xl hover:bg-slate-100/70 active:scale-95 transition">
                                            <i class="fas fa-ellipsis-v text-slate-400"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="mb-6 rounded-2xl bg-gradient-to-b from-white to-slate-50 ring-1 ring-slate-200/70 p-4 shadow-sm">
                                    <div class="h-40">
                                        <canvas id="ordersChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Orders list -->
                            <div class="divide-y divide-slate-200/70 max-h-96 overflow-y-auto">
                                <?php if (empty($recentOrders)): ?>
                                    <div class="p-10 text-center">
                                        <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
                                            <i class="fas fa-shopping-cart text-2xl text-slate-400"></i>
                                        </div>
                                        <p class="text-slate-500 font-medium">No recent orders</p>
                                        <p class="text-sm mt-2 text-slate-400">Orders will appear here as they come in</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $index => $order): ?>
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

                                        <div class="p-4 hover:bg-slate-50/70 transition group recent-order-row cursor-pointer"
                                            data-order-id="<?php echo (int)$order['order_id']; ?>">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div class="mr-4 w-11 h-11 rounded-full grid place-items-center text-white font-bold
                                bg-gradient-to-br from-indigo-500 to-violet-600 shadow-sm ring-4 ring-indigo-500/10
                                group-hover:ring-indigo-500/20 transition">
                                                        <?php echo $initial; ?>
                                                    </div>

                                                    <div>
                                                        <p class="font-semibold text-slate-900 leading-tight">
                                                            <?php echo htmlspecialchars($order['customer_name'] ?? ''); ?>
                                                        </p>

                                                        <div class="flex items-center gap-2 mt-2">
                                                            <span class="text-xs text-slate-500">#<?php echo $order['order_id']; ?></span>
                                                            <span class="text-[11px] px-2 py-1 rounded-full bg-slate-100 text-slate-600 ring-1 ring-slate-200/70">
                                                                <?php echo date('M d', strtotime($order['created_at'])); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="text-right">
                                                    <p class="font-bold text-slate-900 text-lg tracking-tight">
                                                        $<?php echo number_format($order['total'], 2); ?>
                                                    </p>

                                                    <span class="mt-2 inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold shadow-sm ring-1 ring-white/20 <?php echo $stClass; ?>">
                                                        <i class="fas <?php echo $stIcon; ?> text-[11px]"></i>
                                                        <?php echo htmlspecialchars($order['status'] ?? ''); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="p-4 border-slate-200/70">
                                <div class="flex items-center justify-between">
                                    <a href="?report_type=products"
                                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-2">
                                        View detailed analytics
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>

                                    <div class="flex items-center space-x-2">
                                        <button class="p-2 rounded-xl hover:bg-slate-100/70 active:scale-95 transition" title="Download">
                                            <i class="fas fa-download text-slate-500"></i>
                                        </button>
                                        <button class="p-2 rounded-xl hover:bg-slate-100/70 active:scale-95 transition" title="Share">
                                            <i class="fas fa-share-alt text-slate-500"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Status (CARD 2) -->
                        <div class="rounded-2xl bg-white/80 backdrop-blur-xl ring-1 ring-black/5 shadow-[0_20px_45px_-20px_rgba(0,0,0,0.25)] p-6 hover:shadow-[0_30px_70px_-25px_rgba(0,0,0,0.35)] transition-all duration-300">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-slate-900 tracking-tight">Order Status</h3>
                                <span class="text-xs text-slate-500">Total: <?php echo (int)($summary['orders_count'] ?? 0); ?></span>
                            </div>

                            <div class="space-y-5">
                                <?php foreach ($statusSummary as $status):
                                    $percentage = ($status['count'] / max($summary['orders_count'], 1)) * 100;
                                    $statusKey = $status['status'] ?? '';
                                    if ($statusKey === 'completed') {
                                        $bar = 'bg-gradient-to-r from-emerald-400 to-green-500';
                                    } elseif ($statusKey === 'pending') {
                                        $bar = 'bg-gradient-to-r from-amber-400 to-yellow-500';
                                    } elseif ($statusKey === 'cancelled') {
                                        $bar = 'bg-gradient-to-r from-rose-400 to-red-500';
                                    } else {
                                        $bar = 'bg-gradient-to-r from-sky-400 to-blue-500';
                                    }
                                ?>
                                    <div class="rounded-2xl p-4 bg-white/60 ring-1 ring-slate-200/70">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-slate-700 font-semibold">
                                                <?php echo htmlspecialchars($status['status'] ?? ''); ?>
                                            </span>
                                            <span class="font-extrabold text-slate-900">
                                                <?php echo (int)$status['count']; ?>
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <div class="flex-1">
                                                <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                                    <div class="h-2 rounded-full <?php echo $bar; ?> transition-all duration-700"
                                                        style="width: <?php echo max(0, min(100, $percentage)); ?>%"></div>
                                                </div>
                                            </div>

                                            <span class="text-sm font-bold text-slate-700 w-14 text-right">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Top Products -->
                    <div class="rounded-2xl bg-white/80 backdrop-blur-xl ring-1 ring-black/5 shadow-[0_20px_45px_-20px_rgba(0,0,0,0.25)] overflow-hidden hover:shadow-[0_30px_70px_-25px_rgba(0,0,0,0.35)] transition-all duration-300">
                        <div class="p-6 border-b border-slate-200/70">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-900 tracking-tight">Top Products</h2>
                                    <p class="text-sm text-slate-500 mt-1">Best sellers by revenue</p>
                                </div>

                                <select class="text-sm rounded-xl border-slate-200 bg-white/80 backdrop-blur px-3 py-2
                focus:outline-none focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-300 transition">
                                    <option>This Week</option>
                                    <option>This Month</option>
                                    <option selected>All Time</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-6">
                            <?php if (empty($best)): ?>
                                <div class="text-center py-10">
                                    <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4 ring-1 ring-slate-200/70">
                                        <i class="fas fa-box text-slate-400 text-2xl"></i>
                                    </div>
                                    <p class="text-slate-500 font-medium">No product data available</p>

                                    <a href="?page=products" class="mt-5 inline-flex items-center px-4 py-2.5 rounded-xl text-white font-semibold
                    bg-gradient-to-r from-indigo-500 to-violet-500 shadow-sm ring-1 ring-white/20
                    hover:brightness-105 active:scale-95 transition">
                                        <i class="fas fa-plus mr-2"></i>
                                        Add Products
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="space-y-6">
                                    <?php foreach ($best as $index => $product):
                                        $totalSales = max((float)($summary['total_sales'] ?? 0), 1);
                                        $revenue    = (float)($product['revenue'] ?? 0);
                                        $qtySold    = (int)($product['qty_sold'] ?? 0);
                                        $percentage = ($revenue / $totalSales) * 100;

                                        // Rank bar color
                                        $rankBar = 'bg-slate-200';
                                        if ($index === 0) $rankBar = 'bg-gradient-to-b from-yellow-400 to-amber-500';
                                        if ($index === 1) $rankBar = 'bg-gradient-to-b from-slate-300 to-slate-400';
                                        if ($index === 2) $rankBar = 'bg-gradient-to-b from-amber-700 to-yellow-700';

                                        // Rank badge color
                                        $rankBadge = 'bg-slate-100 text-slate-700 ring-slate-200/70';
                                        if ($index === 0) $rankBadge = 'bg-yellow-50 text-yellow-800 ring-yellow-200/70';
                                        if ($index === 1) $rankBadge = 'bg-slate-100 text-slate-700 ring-slate-200/70';
                                        if ($index === 2) $rankBadge = 'bg-amber-50 text-amber-800 ring-amber-200/70';
                                    ?>
                                        <div class="group relative rounded-2xl p-4 ring-1 ring-slate-200/70 bg-white/60 hover:bg-white transition">

                                            <!-- Left rank accent bar (THINNER) -->
                                            <div class="absolute left-0 top-4 bottom-4 w-1 rounded-full <?php echo $rankBar; ?>"></div>

                                            <div class="flex items-start justify-between pl-4 gap-4">
                                                <div class="flex items-start gap-4">
                                                    <!-- Rank box -->
                                                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-slate-100 to-white
                                    ring-1 ring-slate-200/70 shadow-sm grid place-items-center
                                    group-hover:scale-[1.03] transition">
                                                        <span class="font-extrabold text-slate-700"><?php echo $index + 1; ?></span>
                                                    </div>

                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <p class="font-semibold text-slate-900 leading-tight truncate max-w-[260px]">
                                                                <?php echo htmlspecialchars($product['product_name'] ?? ''); ?>
                                                            </p>

                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 <?php echo $rankBadge; ?>">
                                                                <i class="fas fa-crown mr-1 text-[10px] opacity-80"></i>
                                                                Rank <?php echo $index + 1; ?>
                                                            </span>
                                                        </div>

                                                        <div class="flex items-center gap-2 mt-2 flex-wrap">
                                                            <span class="text-xs text-slate-500">
                                                                <i class="fas fa-shopping-bag mr-1"></i>
                                                                <?php echo number_format($qtySold); ?> sold
                                                            </span>

                                                            <span class="text-xs px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                                                                $<?php echo number_format($revenue, 0); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="text-right shrink-0">
                                                    <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </div>
                                                    <div class="text-xs text-slate-500 mt-1">of total revenue</div>
                                                </div>
                                            </div>

                                            <!-- Progress bar -->
                                            <div class="mt-4 pl-4">
                                                <div class="h-2 w-full rounded-full bg-slate-200/80 overflow-hidden">
                                                    <div class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-700"
                                                        style="width: <?php echo max(0, min(100, $percentage)); ?>%">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="mt-4 pl-4 flex items-center justify-between">
                                                <span class="text-xs text-slate-400">
                                                    Updated: <span class="time-display">--:--</span>
                                                </span>

                                                <a href="?page=products&product=<?php echo urlencode((string)($product['product_id'] ?? '')); ?>"
                                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-2">
                                                    View
                                                    <i class="fas fa-arrow-right text-[10px]"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-4 border-t border-slate-200/70">
                            <div class="flex items-center justify-between">
                                <a href="?report_type=products"
                                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-2">
                                    View detailed analytics
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>

                                <div class="flex items-center space-x-2">
                                    <button class="p-2 rounded-xl hover:bg-slate-100/70 active:scale-95 transition" title="Download">
                                        <i class="fas fa-download text-slate-500"></i>
                                    </button>
                                    <button class="p-2 rounded-xl hover:bg-slate-100/70 active:scale-95 transition" title="Share">
                                        <i class="fas fa-share-alt text-slate-500"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Enhanced Scripts -->
    <script src="../../../assets/Js/reports.js"></script>
    <script>
        // Export dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const exportDropdownBtn = document.getElementById('exportDropdownBtn');
            const exportDropdown = document.getElementById('exportDropdown');

            if (exportDropdownBtn && exportDropdown) {
                exportDropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    exportDropdown.classList.toggle('hidden');
                    exportDropdown.classList.toggle('animate-fade-in-down');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!exportDropdown.contains(e.target) && !exportDropdownBtn.contains(e.target)) {
                        exportDropdown.classList.add('hidden');
                    }
                });
            }

            // Export item click handlers
            const exportItems = document.querySelectorAll('.export-item');
            exportItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    const type = this.getAttribute('data-type');
                    const format = this.getAttribute('data-format');

                    // Add downloading animation
                    this.classList.add('downloading');

                    // Remove animation after delay
                    setTimeout(() => {
                        this.classList.remove('downloading');
                    }, 1500);

                    // Track export event (you can add analytics here)
                    console.log(`Exporting ${type} as ${format}`);
                });
            });

            // Quick export buttons
            const quickExportBtns = document.querySelectorAll('.export-quick-btn');
            quickExportBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const type = this.getAttribute('data-type');
                    const format = this.getAttribute('data-format');

                    // Add pulse animation
                    this.classList.add('animate-pulse');
                    setTimeout(() => {
                        this.classList.remove('animate-pulse');
                    }, 1000);
                });
            });

            // Progress bar animations
            const progressBars = document.querySelectorAll('.report-progress');
            progressBars.forEach(bar => {
                const width = bar.style.getPropertyValue('--target-width') || '0%';
                bar.style.width = width;
            });
        });
    </script>

</body>

</html>