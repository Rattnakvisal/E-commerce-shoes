<?php
require_once __DIR__ . '/../../../config/conn.php';
require_once __DIR__ . '/analyties_api.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Analytics Dashboard - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.3.0"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/Css/reports.css">
    <style>
        .positive-change {
            color: #10b981;
        }

        .negative-change {
            color: #ef4444;
        }

        .analytics-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

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
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../include/navbar.php'; ?>

    <div class="md:ml-64 min-h-screen">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-6 animate-fade-in">
                <!-- Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Title -->
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">Analytics <span class="gradient-text font-extrabold">Dashboard</span></h1>
                        </div>
                        <p class="text-gray-600 ml-1">Comprehensive overview of store performance and metrics</p>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="flex items-center gap-3">
                        <form method="GET" class="flex items-center gap-2 flex-wrap">
                            <!-- Range Select -->
                            <select
                                name="range"
                                onchange="this.form.submit()"
                                class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="today" <?= $dateRange === 'today' ? 'selected' : '' ?>>Today</option>
                                <option value="yesterday" <?= $dateRange === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                                <option value="7days" <?= $dateRange === '7days' ? 'selected' : '' ?>>Last 7 days</option>
                                <option value="30days" <?= $dateRange === '30days' ? 'selected' : '' ?>>Last 30 days</option>
                                <option value="90days" <?= $dateRange === '90days' ? 'selected' : '' ?>>Last 90 days</option>
                                <option value="custom" <?= $dateRange === 'custom' ? 'selected' : '' ?>>Custom range</option>
                            </select>

                            <!-- Custom Range -->
                            <?php if ($dateRange === 'custom'): ?>
                                <input
                                    type="date"
                                    name="start"
                                    value="<?= htmlspecialchars($startDate) ?>"
                                    class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                                    required>

                                <span class="text-sm text-gray-500">to</span>

                                <input
                                    type="date"
                                    name="end"
                                    value="<?= htmlspecialchars($endDate) ?>"
                                    class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                                    required>

                                <button
                                    type="submit"
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
                                    Apply
                                </button>
                            <?php endif; ?>

                        </form>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in-up">

                    <!-- Total Revenue -->
                    <div class="stat-card bg-gradient-to-br from-white to-indigo-50/50 rounded-2xl p-6 shadow-soft-xl border border-indigo-100/50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-indigo-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <p class="text-sm text-gray-500">Total Revenue</p>
                                <p class="text-2xl font-bold mt-2 text-gray-900">
                                    $<?= number_format($totals['revenue'], 2) ?>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-dollar-sign text-xl"></i>
                            </div>
                        </div>

                        <?php
                        $revChange = $totals['revenue_change'] ?? 0;
                        $revPercent = min(abs($revChange), 100);
                        ?>

                        <div class="mt-4 relative z-10">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <div class="<?= $revChange >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    <i class="fas <?= $revChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?> mr-1"></i>
                                    <?= number_format(abs($revChange), 1) ?>%
                                </div>
                                <div>vs yesterday</div>
                            </div>
                            <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                                <div class="h-2 <?= $revChange >= 0 ? 'bg-green-500' : 'bg-red-500' ?>"
                                    style="width: <?= $revPercent ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Orders -->
                    <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <p class="text-sm text-gray-500">Total Orders</p>
                                <p class="text-2xl font-bold mt-2 text-gray-900">
                                    <?= number_format($totals['orders']) ?>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-shopping-cart text-xl"></i>
                            </div>
                        </div>

                        <div class="mt-4 relative z-10">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <div>Avg. Order Value</div>
                                <div>$<?= number_format($totals['avg_order_value'], 2) ?></div>
                            </div>
                            <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                                <div class="h-2 bg-blue-500 w-full"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Customers -->
                    <div class="stat-card bg-gradient-to-br from-white to-green-50/50 rounded-2xl p-6 shadow-soft-xl border border-green-100/50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <p class="text-sm text-gray-500">Total Customers</p>
                                <p class="text-2xl font-bold mt-2 text-gray-900">
                                    <?= number_format($totals['users']) ?>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>

                        <div class="mt-4 relative z-10">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <div>New today</div>
                                <div><?= number_format($totals['users_today']) ?></div>
                            </div>
                            <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                                <div class="h-2 bg-green-500 w-full"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Conversion Rate -->
                    <div class="stat-card bg-gradient-to-br from-white to-purple-50/50 rounded-2xl p-6 shadow-soft-xl border border-purple-100/50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <p class="text-sm text-gray-500">Conversion Rate</p>
                                <p class="text-2xl font-bold mt-2 text-gray-900">
                                    <?= number_format($totals['conversion_rate'], 1) ?>%
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                        </div>

                        <div class="mt-4 relative z-10">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <div>Orders per customer</div>
                                <div><?= number_format($totals['conversion_rate'], 1) ?>%</div>
                            </div>
                            <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                                <div class="h-2 bg-purple-500"
                                    style="width: <?= min(max($totals['conversion_rate'], 0), 100) ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Orders Chart -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Orders Trend</h3>
                            <span class="text-sm text-gray-500">Last 7 days</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="ordersChart"></canvas>
                        </div>
                    </div>

                    <!-- Revenue by Month -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Revenue by Month</h3>
                            <span class="text-sm text-gray-500">Last 6 months</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <!-- Products by Category -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Products by Category</h3>
                            <span class="text-sm text-gray-500">Top 10 categories</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                        <?php if (empty($productsByCategory)): ?>
                            <p class="text-sm text-gray-500 mt-3">No category data available.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Orders by Status -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Orders by Status</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Detailed Stats Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Top Products -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Top Selling Products</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2 px-4">Product</th>
                                        <th class="text-left py-2 px-4">Units Sold</th>
                                        <th class="text-left py-2 px-4">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($topProducts)): ?>
                                        <?php foreach ($topProducts as $product): ?>
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="py-3 px-4">
                                                    <?php $pid = $product['product_id'] ?? null; ?>
                                                    <?php if ($pid): ?>
                                                        <a href="/E-commerce-shoes/admin/process/products/products.php?search=<?= urlencode($product['name'] ?? '') ?>" class="text-indigo-600 hover:underline"><?= htmlspecialchars($product['name'] ?? 'N/A') ?></a>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($product['name'] ?? 'N/A') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 px-4"><?= number_format($product['total_sold'] ?? 0) ?></td>
                                                <td class="py-3 px-4">$<?= number_format($product['revenue'] ?? 0, 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="py-4 px-4 text-center text-gray-500">
                                                No product sales data available
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Customers -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Top Customers</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2 px-4">Customer</th>
                                        <th class="text-left py-2 px-4">Orders</th>
                                        <th class="text-left py-2 px-4">Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($topCustomers)): ?>
                                        <?php foreach ($topCustomers as $customer): ?>
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="py-3 px-4"><?= htmlspecialchars($customer['username'] ?? $customer['email'] ?? 'N/A') ?></td>
                                                <td class="py-3 px-4"><?= number_format($customer['orders_count'] ?? 0) ?></td>
                                                <td class="py-3 px-4">$<?= number_format($customer['total_spent'] ?? 0, 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="py-4 px-4 text-center text-gray-500">
                                                No customer data available
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Additional Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 md:auto-rows-fr items-stretch">

                    <!-- LEFT COLUMN: 2 stacked cards -->
                    <div class="grid grid-cols-1 gap-6 md:col-span-1 md:row-span-2 h-full">

                        <!-- Inventory Status -->
                        <div class="bg-white rounded-xl shadow-lg p-6 h-full">
                            <div class="flex items-center mb-4">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-boxes text-blue-600"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Inventory Status</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-gray-600">Total Products</span>
                                        <span class="font-semibold"><?= number_format($totalProducts) ?></span>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 flex overflow-hidden">
                                        <div class="bg-green-500 h-2.5" style="width: <?= (int)$activePercent ?>%"></div>
                                        <div class="bg-gray-400 h-2.5" style="width: <?= (int)$inactivePercent ?>%"></div>
                                    </div>

                                    <!-- Legend -->
                                    <div class="text-xs text-gray-500 mt-2">
                                        <span class="text-green-600 font-medium">
                                            <?= number_format($activeProducts) ?> active
                                        </span>
                                        •
                                        <span class="text-gray-600">
                                            <?= number_format($inactiveProducts) ?> inactive
                                        </span>
                                    </div>
                                </div>

                                <?php if ((int)($lowStockCount ?? 0) > 0): ?>
                                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-2">
                                                <i class="fas fa-exclamation-triangle text-yellow-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <span class="text-sm font-semibold text-yellow-800">Low Stock Alert</span>
                                                <p class="text-xs text-yellow-600 mt-1">
                                                    <?= number_format((int)($lowStockCount ?? 0)) ?>
                                                    product<?= ((int)($lowStockCount ?? 0) > 1) ? 's' : '' ?> below threshold
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="pt-3 border-t border-gray-100">
                                    <a href="../view/inventory.php"
                                        class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center">
                                        View Inventory
                                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="bg-white rounded-xl shadow-lg p-6 h-full">
                            <div class="flex items-center mb-4">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-chart-bar text-purple-600"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Quick Stats</h3>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-star mr-2"></i>
                                        <span class="text-sm text-gray-700">Featured Items</span>
                                    </div>
                                    <span class="font-semibold text-gray-900">
                                        <?= number_format((int)($featuredCount ?? 0)) ?>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-money-bill-wave mr-2"></i>
                                        <span class="text-sm text-gray-700">Today's Revenue</span>
                                    </div>
                                    <span class="font-bold text-green-600">
                                        $<?= number_format((float)($todaysRevenue ?? 0), 2) ?>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                        <span class="text-sm text-gray-700">Pending Orders</span>
                                    </div>
                                    <span class="font-bold text-gray-900">
                                        <?= number_format((int)($pendingOrders ?? 0)) ?>
                                    </span>
                                </div>

                                <div class="pt-3 border-t border-gray-100">
                                    <div class="text-xs text-gray-500">
                                        Last updated: <?= date('h:i A') ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT BIG CARD: spans 2 columns + full height -->
                    <div class="bg-white rounded-xl shadow-lg p-6 md:col-span-2 md:row-span-2 h-full">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-credit-card text-indigo-600"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Payment Gateways</h3>
                            </div>
                            <a href="../view/transactions.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                View all
                                <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>

                        <div class="space-y-4">
                            <?php foreach (($paymentGateways ?? []) as $key => $gateway):
                                $colorMap = [
                                    'blue' => ['bg' => 'from-blue-50 to-blue-100', 'border' => 'border-blue-200', 'icon-bg' => 'bg-blue-200', 'icon-text' => 'text-blue-700'],
                                    'red' => ['bg' => 'from-red-50 to-red-100', 'border' => 'border-red-200', 'icon-bg' => 'bg-red-200', 'icon-text' => 'text-red-700'],
                                    'green' => ['bg' => 'from-green-50 to-green-100', 'border' => 'border-green-200', 'icon-bg' => 'bg-green-200', 'icon-text' => 'text-green-700'],
                                    'purple' => ['bg' => 'from-purple-50 to-purple-100', 'border' => 'border-purple-200', 'icon-bg' => 'bg-purple-200', 'icon-text' => 'text-purple-700'],
                                    'amber' => ['bg' => 'from-amber-50 to-amber-100', 'border' => 'border-amber-200', 'icon-bg' => 'bg-amber-200', 'icon-text' => 'text-amber-700'],
                                    'cyan' => ['bg' => 'from-cyan-50 to-cyan-100', 'border' => 'border-cyan-200', 'icon-bg' => 'bg-cyan-200', 'icon-text' => 'text-cyan-700'],
                                    'emerald' => ['bg' => 'from-emerald-50 to-emerald-100', 'border' => 'border-emerald-200', 'icon-bg' => 'bg-emerald-200', 'icon-text' => 'text-emerald-700'],
                                ];

                                $colorKey = strtolower((string)($gateway['color'] ?? 'blue'));
                                $colorConfig = $colorMap[$colorKey] ?? $colorMap['blue'];

                                $icon = (string)($gateway['icon'] ?? 'fas fa-money-check-alt');
                                $name = (string)($gateway['name'] ?? 'Unknown');
                                $desc = (string)($gateway['description'] ?? '');
                                $amount = (float)($gateway['amount'] ?? 0);
                                $count = (int)($gateway['count'] ?? 0);
                                $recent = (array)($gateway['recent'] ?? []);
                            ?>
                                <div class="p-4 bg-gradient-to-r <?= $colorConfig['bg'] ?> border <?= $colorConfig['border'] ?> rounded-lg hover:shadow transition duration-300">
                                    <div class="flex justify-between items-center gap-4">
                                        <div class="flex items-center min-w-0">
                                            <div class="w-10 h-10 flex items-center justify-center <?= $colorConfig['icon-bg'] ?> rounded-lg mr-3 shrink-0">
                                                <i class="<?= $icon ?> <?= $colorConfig['icon-text'] ?>"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 truncate"><?= $name ?></p>
                                                <p class="text-xs text-gray-600 truncate"><?= $desc ?></p>
                                            </div>
                                        </div>

                                        <div class="text-right shrink-0">
                                            <p class="text-xl font-bold text-gray-900">
                                                $<?= number_format($amount, 2) ?>
                                            </p>
                                            <p class="text-xs text-gray-600">
                                                <?= number_format($count) ?> transaction<?= ($count !== 1) ? 's' : '' ?>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if (!empty($recent)): ?>
                                        <details class="mt-3">
                                            <summary class="cursor-pointer text-sm text-gray-700 font-medium">
                                                Recent payments
                                                <span class="text-xs bg-white px-2 py-1 rounded-full ml-2">
                                                    <?= count($recent) ?>
                                                </span>
                                            </summary>

                                            <div class="mt-3 space-y-2">
                                                <?php foreach (array_slice($recent, 0, 3) as $payment):
                                                    $orderId = $payment['order_id'] ?? '';
                                                    $payDate = $payment['payment_date'] ?? '';
                                                    $payAmount = (float)($payment['amount'] ?? 0);
                                                    $email = (string)($payment['email'] ?? 'Guest');
                                                ?>
                                                    <div class="flex items-center justify-between p-2 bg-white/50 rounded">
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-800">#<?= htmlspecialchars((string)$orderId) ?></p>
                                                            <p class="text-xs text-gray-600 mt-0.5">
                                                                <?= $payDate ? date('h:i A', strtotime((string)$payDate)) : '-' ?>
                                                            </p>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="font-bold text-gray-900">
                                                                $<?= number_format($payAmount, 2) ?>
                                                            </p>
                                                            <p class="text-xs text-gray-500">
                                                                <?= htmlspecialchars(mb_substr($email, 0, 15)) ?>...
                                                            </p>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>


                <!-- Location Stats (if available) -->
                <?php if (!empty($locationStats)): ?>
                    <div class="bg-white rounded-xl p-6 shadow-sm mb-8">
                        <h3 class="text-lg font-semibold mb-4">Top Locations by Revenue</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2 px-4">Location</th>
                                        <th class="text-left py-2 px-4">Customers</th>
                                        <th class="text-left py-2 px-4">Orders</th>
                                        <th class="text-left py-2 px-4">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($locationStats as $location): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <?= htmlspecialchars($location['city'] ?? 'Unknown') ?>,
                                                <?= htmlspecialchars($location['country'] ?? 'Unknown') ?>
                                            </td>
                                            <td class="py-3 px-4"><?= number_format($location['customers'] ?? 0) ?></td>
                                            <td class="py-3 px-4"><?= number_format($location['orders'] ?? 0) ?></td>
                                            <td class="py-3 px-4">$<?= number_format($location['revenue'] ?? 0, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../../../assets/Js/reports.js"></script>
    <script>
        /* ================================
                DATA (from PHP)
        ================================= */
        const ordersData = <?= json_encode($ordersLast7, JSON_UNESCAPED_UNICODE) ?> || [];
        const catData = <?= json_encode($productsByCategory, JSON_UNESCAPED_UNICODE) ?> || [];
        const revenueByMonth = <?= json_encode($revenueByMonth, JSON_UNESCAPED_UNICODE) ?> || [];
        const ordersByStatus = <?= json_encode($ordersByStatus, JSON_UNESCAPED_UNICODE) ?> || [];
        const paymentMethods = <?= json_encode($paymentMethods, JSON_UNESCAPED_UNICODE) ?> || [];
        const hourlyOrders = <?= json_encode($hourlyOrders, JSON_UNESCAPED_UNICODE) ?> || [];

        /* ================================
           HELPERS
        ================================= */
        const toInt = (v, fallback = 0) => {
            const n = parseInt(v, 10);
            return Number.isFinite(n) ? n : fallback;
        };

        const toFloat = (v, fallback = 0) => {
            const n = parseFloat(v);
            return Number.isFinite(n) ? n : fallback;
        };

        function getCtx(id) {
            const el = document.getElementById(id);
            return el ? el.getContext("2d") : null;
        }

        function destroyChart(id) {
            try {
                const chart = Chart.getChart?.(id);
                if (chart) chart.destroy();
            } catch (_) {}
        }

        function createChart(id, config) {
            const ctx = getCtx(id);
            if (!ctx) return null;
            destroyChart(id);
            return new Chart(ctx, config);
        }

        /* ================================
           CHARTS
        ================================= */

        // 1) Orders (last 7 days): line + line (two y axes)
        (function renderOrdersChart() {
            if (!Array.isArray(ordersData) || ordersData.length === 0) return;

            const labels = ordersData.map(r => r.date ?? "");
            const counts = ordersData.map(r => toInt(r.count));
            const revenue = ordersData.map(r => toFloat(r.revenue));

            createChart("ordersChart", {
                type: "line",
                data: {
                    labels,
                    datasets: [{
                            label: "Orders",
                            data: counts,
                            borderColor: "#4f46e5",
                            backgroundColor: "rgba(79,70,229,0.08)",
                            fill: true,
                            tension: 0.3,
                            yAxisID: "y",
                        },
                        {
                            label: "Revenue ($)",
                            data: revenue,
                            borderColor: "#10b981",
                            backgroundColor: "rgba(16,185,129,0.08)",
                            fill: true,
                            tension: 0.3,
                            yAxisID: "y1",
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: "index",
                        intersect: false
                    },
                    scales: {
                        y: {
                            type: "linear",
                            position: "left",
                            title: {
                                display: true,
                                text: "Orders"
                            },
                        },
                        y1: {
                            type: "linear",
                            position: "right",
                            title: {
                                display: true,
                                text: "Revenue ($)"
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                        },
                    },
                },
            });
        })();

        // 2) Revenue by month: bar + line (two y axes)
        (function renderRevenueChart() {
            if (!Array.isArray(revenueByMonth) || revenueByMonth.length === 0) return;

            const labels = revenueByMonth.map(r => r.month ?? "");
            const revenue = revenueByMonth.map(r => toFloat(r.revenue));
            const orders = revenueByMonth.map(r => toInt(r.orders));

            createChart("revenueChart", {
                type: "bar",
                data: {
                    labels,
                    datasets: [{
                            label: "Revenue ($)",
                            data: revenue,
                            backgroundColor: "#10b981",
                            yAxisID: "y",
                        },
                        {
                            label: "Orders",
                            data: orders,
                            type: "line",
                            borderColor: "#4f46e5",
                            backgroundColor: "rgba(79,70,229,0.1)",
                            fill: false,
                            tension: 0.3,
                            yAxisID: "y1",
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: "linear",
                            position: "left",
                            title: {
                                display: true,
                                text: "Revenue ($)"
                            },
                        },
                        y1: {
                            type: "linear",
                            position: "right",
                            title: {
                                display: true,
                                text: "Orders"
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                        },
                    },
                },
            });
        })();

        // 3) Products by category: doughnut
        (function renderCategoryChart() {
            if (!Array.isArray(catData) || catData.length === 0) return;

            const labels = catData.map(r => r.category || "Uncategorized");
            const values = catData.map(r => toInt(r.cnt));

            createChart("categoryChart", {
                type: "doughnut",
                data: {
                    labels,
                    datasets: [{
                        label: "Products",
                        data: values,
                        backgroundColor: [
                            "#4f46e5", "#10b981", "#f59e0b", "#ef4444", "#8b5cf6",
                            "#06b6d4", "#84cc16", "#f97316", "#6366f1", "#ec4899",
                        ],
                    }, ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "right"
                        }
                    },
                },
            });
        })();

        // 4) Orders by status: pie
        (function renderStatusChart() {
            if (!Array.isArray(ordersByStatus) || ordersByStatus.length === 0) return;

            const labels = ordersByStatus.map(r => r.order_status || "Unknown");
            const values = ordersByStatus.map(r => toInt(r.count));

            createChart("statusChart", {
                type: "pie",
                data: {
                    labels,
                    datasets: [{
                        label: "Orders",
                        data: values,
                        backgroundColor: ["#f59e0b", "#3b82f6", "#10b981", "#ef4444", "#8b5cf6"],
                    }, ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "right"
                        }
                    },
                },
            });
        })();

        /* ================================
           AUTO REFRESH (optional)
        ================================= */
        const AUTO_REFRESH = true;
        const REFRESH_MS = 5 * 60 * 1000;

        if (AUTO_REFRESH) {
            setTimeout(() => window.location.reload(), REFRESH_MS);
        }
    </script>
</body>

</html>