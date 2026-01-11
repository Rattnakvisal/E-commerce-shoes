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
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.0"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/Css/dasboard.css">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

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
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
                        <p class="text-gray-600 mt-1">Comprehensive overview of store performance and metrics</p>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card bg-white rounded-xl p-6 shadow-sm border-l-4 border-indigo-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Revenue</p>
                                <p class="text-2xl font-bold mt-2">$<?= number_format($totals['revenue'], 2) ?></p>
                            </div>
                            <div class="bg-indigo-100 p-3 rounded-lg">
                                <i class="fas fa-dollar-sign text-indigo-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm">
                            <span class="<?= $totals['revenue_change'] >= 0 ? 'positive-change' : 'negative-change' ?>">
                                <i class="fas <?= $totals['revenue_change'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?> mr-1"></i>
                                <?= number_format(abs($totals['revenue_change']), 1) ?>%
                            </span>
                            <span class="text-gray-500 ml-2">vs yesterday</span>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-xl p-6 shadow-sm border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Orders</p>
                                <p class="text-2xl font-bold mt-2"><?= number_format($totals['orders']) ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-lg">
                                <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Avg. Order Value: $<?= number_format($totals['avg_order_value'], 2) ?></p>
                    </div>

                    <div class="stat-card bg-white rounded-xl p-6 shadow-sm border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Customers</p>
                                <p class="text-2xl font-bold mt-2"><?= number_format($totals['users']) ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-lg">
                                <i class="fas fa-users text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">New today: <?= number_format($totals['users_today']) ?></p>
                    </div>

                    <div class="stat-card bg-white rounded-xl p-6 shadow-sm border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Conversion Rate</p>
                                <p class="text-2xl font-bold mt-2"><?= number_format($totals['conversion_rate'], 1) ?>%</p>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-lg">
                                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Orders per customer</p>
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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Inventory Status -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Inventory Status</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Total Products</span>
                                    <span><?= number_format($totals['products']) ?></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?= min(100, ($totals['products_active'] / max(1, $totals['products'])) * 100) ?>%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Active: <?= number_format($totals['products_active']) ?> | Inactive: <?= number_format($totals['products_inactive']) ?>
                                </div>
                            </div>

                            <?php if ($totals['products_low_stock'] > 0): ?>
                                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                        <span class="text-sm font-medium">Low Stock Alert</span>
                                    </div>
                                    <p class="text-xs text-yellow-600 mt-1">
                                        <?= $totals['products_low_stock'] ?> product(s) have low stock (≤ 10 units)
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Payment Methods</h3>
                        <div class="space-y-3">
                            <?php if (!empty($paymentMethods)): ?>
                                <?php foreach ($paymentMethods as $method): ?>
                                    <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                                        <span class="text-sm"><?= htmlspecialchars($method['payment_method'] ?? 'Unknown') ?></span>
                                        <div class="text-right">
                                            <div class="font-medium"><?= number_format($method['count'] ?? 0) ?> orders</div>
                                            <div class="text-xs text-gray-500">$<?= number_format($method['amount'] ?? 0, 2) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-sm text-gray-500">No payment method data available</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold mb-4">Quick Stats</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Featured Items</span>
                                <span class="font-medium"><?= number_format($totals['featured']) ?>
                                    <span class="text-xs text-green-600 ml-1">
                                        (<?= number_format($totals['featured_active']) ?> active)
                                    </span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Today's Revenue</span>
                                <span class="font-medium text-green-600">$<?= number_format($totals['revenue_today'], 2) ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Pending Orders</span>
                                <span class="font-medium"><?= number_format($totals['orders_pending'] ?? 0) ?></span>
                            </div>
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
    <script>
        const ordersData = <?= json_encode($ordersLast7) ?>;
        const catData = <?= json_encode($productsByCategory) ?>;
        const revenueByMonth = <?= json_encode($revenueByMonth) ?>;
        const ordersByStatus = <?= json_encode($ordersByStatus) ?>;
        const paymentMethods = <?= json_encode($paymentMethods) ?>;
        const hourlyOrders = <?= json_encode($hourlyOrders) ?>;

        // Orders chart
        (function() {
            const labels = ordersData.map(r => r.date);
            const counts = ordersData.map(r => r.count);
            const revenue = ordersData.map(r => r.revenue);

            const ctx = document.getElementById('ordersChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Orders',
                            data: counts,
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79,70,229,0.08)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Revenue ($)',
                            data: revenue,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.08)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Orders'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        })();

        // Revenue by month chart
        (function() {
            if (!revenueByMonth || revenueByMonth.length === 0) return;

            const labels = revenueByMonth.map(r => r.month);
            const revenue = revenueByMonth.map(r => parseFloat(r.revenue));
            const orders = revenueByMonth.map(r => parseInt(r.orders));

            const ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Revenue ($)',
                            data: revenue,
                            backgroundColor: '#10b981',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders',
                            data: orders,
                            type: 'line',
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79,70,229,0.1)',
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Orders'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        })();

        // Category chart
        (function() {
            if (!catData || catData.length === 0) return;
            const labels = catData.map(r => r.category || 'Uncategorized');
            const values = catData.map(r => parseInt(r.cnt, 10));
            const ctx = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Products',
                        data: values,
                        backgroundColor: [
                            '#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                            '#06b6d4', '#84cc16', '#f97316', '#6366f1', '#ec4899'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        })();

        // Orders by status chart
        (function() {
            if (!ordersByStatus || ordersByStatus.length === 0) return;

            const labels = ordersByStatus.map(r => r.order_status || 'Unknown');
            const values = ordersByStatus.map(r => parseInt(r.count, 10));

            const ctx = document.getElementById('statusChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Orders',
                        data: values,
                        backgroundColor: [
                            '#f59e0b', // Pending
                            '#3b82f6', // Processing
                            '#10b981', // Completed
                            '#ef4444', // Cancelled
                            '#8b5cf6' // Other
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        })();
        // Auto-refresh every 5 minutes
        setTimeout(() => {
            window.location.reload();
        }, 300000); // 5 minutes
    </script>
</body>

</html>