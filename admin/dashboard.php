<?php
require_once __DIR__ . '/../config/conn.php';

/* =====================================================
   SESSION & AUTH
===================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../auth/login.php');
    exit;
}

/* =====================================================
   ADMIN INFO (SAFE DEFAULTS)
===================================================== */
$admin_name   = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin';
$admin_role   = $_SESSION['role'] ?? 'Administrator';
$admin_avatar = $_SESSION['avatar']
    ?? 'https://ui-avatars.com/api/?name=' . urlencode($admin_name) . '&background=6366f1&color=fff';

/* =====================================================
   DEFAULT VALUES (AVOID WARNINGS)
===================================================== */
$total_users       = 0;
$total_orders      = 0;
$revenue           = 0.0;
$conversion_rate   = 0.0;

$recent_orders     = [];
$topProducts       = [];
$lowStockProducts  = [];
$ordersByStatus    = [
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0,
];
$revenueLast7      = []; // array of ['date' => 'YYYY-MM-DD','total' => float]
$recent_users      = [];

/* =====================================================
   FETCH DASHBOARD DATA
===================================================== */
try {

    /* ---------- USERS COUNT ---------- */
    $total_users = (int)$pdo
        ->query("SELECT COUNT(*) FROM users")
        ->fetchColumn();

    /* ---------- ORDERS COUNT ---------- */
    $total_orders = (int)$pdo
        ->query("SELECT COUNT(*) FROM orders")
        ->fetchColumn();

    /* ---------- TOTAL REVENUE ---------- */
    $revenue = (float)$pdo
        ->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid'")
        ->fetchColumn();

    /* ---------- CONVERSION RATE ---------- */
    $conversion_rate = $total_users > 0
        ? round(($total_orders / $total_users) * 100, 2)
        : 0;

    /* ---------- TOP PRODUCTS ---------- */
    $stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.name,
            p.price,
            p.stock,
            COALESCE(SUM(oi.quantity), 0) AS total_quantity
        FROM products p
        LEFT JOIN order_items oi ON oi.product_id = p.product_id
        GROUP BY p.product_id
        ORDER BY total_quantity DESC
        LIMIT 5
    ");
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- LOW STOCK PRODUCTS ---------- */
    $stmt = $pdo->query("
        SELECT 
            product_id,
            name,
            stock
        FROM products
                WHERE stock IS NOT NULL
                    AND stock <= 10
        ORDER BY stock ASC
        LIMIT 5
    ");
    $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- RECENT ORDERS ---------- */
    $stmt = $pdo->query("
        SELECT 
            o.order_id AS id,
            o.total,
            o.order_status AS status,
            o.payment_status,
            o.created_at,
            COALESCE(u.name, u.email, 'Guest') AS customer
        FROM orders o
        LEFT JOIN users u ON u.user_id = o.user_id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- ORDERS BY STATUS ---------- */
    $stmt = $pdo->query(
        "SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $key = strtolower($r['order_status']);
        if (isset($ordersByStatus[$key])) {
            $ordersByStatus[$key] = (int)$r['cnt'];
        }
    }

    /* ---------- REVENUE LAST 7 DAYS ---------- */
    $stmt = $pdo->query(
        "SELECT DATE(created_at) as day, COALESCE(SUM(total),0) as total FROM orders WHERE payment_status = 'paid' GROUP BY DATE(created_at) ORDER BY DATE(created_at) DESC LIMIT 7"
    );
    $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    foreach ($rows as $r) {
        $revenueLast7[] = [
            'date' => $r['day'],
            'total' => (float)$r['total']
        ];
    }

    /* ---------- RECENT USERS ---------- */
    $stmt = $pdo->query("SELECT user_id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Admin Dashboard] ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/Css/dasboard.css">
    <link rel="stylesheet" href="../assets/Css/reports.css">
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/include/navbar.php'; ?>
    <div class="md:ml-64 min-h-screen">
        <main class="pt-6 md:pt-16 p-4 sm:p-6 lg:p-8 page-transition bg-gray-50 min-h-screen animate-fade-in">
            <!-- Page Header -->
            <div class="mb-6 animate-fade-in">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <!-- Welcome Text -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Welcome back, <?= htmlspecialchars(explode(' ', $admin_name)[0]) ?>!
                        </h1>
                        <p class="text-gray-600 mt-1">
                            Here's what's happening with your store today.
                        </p>
                    </div>
                </div>
            </div>
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in-up">
                <!-- Total Users -->
                <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden group hover:shadow-glow-blue">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Total Users</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format($total_users) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>

                    <div class="mt-4 relative z-10">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                            <div>
                                <?= $total_users > 0 ? 'Calculated from DB' : 'No data' ?>
                            </div>
                            <div>100%</div>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-blue-500 w-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="stat-card bg-gradient-to-br from-white to-green-50/50 rounded-2xl p-6 shadow-soft-xl border border-green-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Total Orders</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format($total_orders) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-shopping-cart text-xl"></i>
                        </div>
                    </div>

                    <div class="mt-4 relative z-10">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                            <div>From last week</div>
                            <div class="text-green-600">+8.3%</div>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-green-500" style="width: 8.3%"></div>
                        </div>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="stat-card bg-gradient-to-br from-white to-purple-50/50 rounded-2xl p-6 shadow-soft-xl border border-purple-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Revenue</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                $<?= number_format($revenue, 2) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                    </div>

                    <div class="mt-4 relative z-10">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                            <div>From last month</div>
                            <div class="text-red-600">-3.2%</div>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-purple-500" style="width: 96.8%"></div>
                        </div>
                    </div>
                </div>

                <!-- Conversion Rate -->
                <div class="stat-card bg-gradient-to-br from-white to-yellow-50/50 rounded-2xl p-6 shadow-soft-xl border border-yellow-100/50 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-yellow-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <div>
                            <p class="text-sm text-gray-500">Conversion Rate</p>
                            <p class="text-2xl font-bold mt-2 text-gray-900">
                                <?= number_format($conversion_rate, 2) ?>%
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-3 rounded-xl shadow-md">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                    </div>

                    <div class="mt-4 relative z-10">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                            <div>From last week</div>
                            <div class="text-green-600">+1.1%</div>
                        </div>
                        <div class="w-full bg-gray-200/50 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-yellow-500" style="width: <?= min(max($conversion_rate, 0), 100) ?>%"></div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- Orders Status & Revenue -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-2xl shadow-md lg:col-span-1">
                    <h3 class="font-semibold text-lg mb-3">Orders by Status</h3>
                    <ul class="space-y-3 text-sm text-gray-700">
                        <li class="flex justify-between"><span>Pending</span><strong><?= number_format($ordersByStatus['pending']) ?></strong></li>
                        <li class="flex justify-between"><span>Processing</span><strong><?= number_format($ordersByStatus['processing']) ?></strong></li>
                        <li class="flex justify-between"><span>Completed</span><strong><?= number_format($ordersByStatus['completed']) ?></strong></li>
                        <li class="flex justify-between"><span>Cancelled</span><strong><?= number_format($ordersByStatus['cancelled']) ?></strong></li>
                    </ul>
                    <div class="mt-4 h-36">
                        <canvas id="ordersStatusChart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-md lg:col-span-2">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-semibold text-lg">Revenue (Last 7 Days)</h3>
                        <span class="text-sm text-gray-400">Paid orders only</span>
                    </div>
                    <div class="h-56">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Users & Recent Orders (2-column grid) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Recent Users -->
                <div class="bg-white p-6 rounded-2xl shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-lg">Recent Users</h3>
                        <a href="users/users.php" class="text-sm text-indigo-600 hover:underline">View all users</a>
                    </div>
                    <ul class="divide-y">
                        <?php if (empty($recent_users)): ?>
                            <li class="py-3 text-gray-500">No recent users</li>
                            <?php else: foreach ($recent_users as $u): ?>
                                <li class="py-3 flex justify-between items-center">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($u['name'] ?: $u['email']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></p>
                                    </div>
                                    <div class="text-xs text-gray-400"><?= htmlspecialchars(date('Y-m-d', strtotime($u['created_at']))) ?></div>
                                </li>
                        <?php endforeach;
                        endif; ?>
                    </ul>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-xl shadow-soft p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                        <a href="orders.php" class="text-sm text-indigo-600 hover:underline">View all orders</a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-sm table-auto">
                            <thead class="text-gray-500 text-left">
                                <tr>
                                    <th class="py-2">Order</th>
                                    <th class="py-2">Customer</th>
                                    <th class="py-2">Total</th>
                                    <th class="py-2">Payment</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2">Date</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr class="border-t">
                                        <td class="py-3">#<?= htmlspecialchars($order['id']) ?></td>
                                        <td class="py-3"><?= htmlspecialchars($order['customer']) ?></td>
                                        <td class="py-3">$<?= number_format($order['total'], 2) ?></td>
                                        <td class="py-3"><?= htmlspecialchars($order['payment_status']) ?></td>
                                        <td class="py-3"><?= htmlspecialchars($order['status']) ?></td>
                                        <td class="py-3"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($order['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="7" class="py-4 text-center text-gray-500">No recent orders</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= TOP & LOW STOCK ================= -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <!-- ================= TOP PRODUCTS ================= -->
                <div class="bg-white p-6 rounded-2xl shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Top Products</h3>
                        <span class="text-xs text-gray-400">Units Sold</span>
                    </div>

                    <div class="h-56 mb-6">
                        <canvas id="topProductsChart"></canvas>
                    </div>

                    <ul class="space-y-3 max-h-44 overflow-y-auto pr-2">
                        <?php if (!$topProducts): ?>
                            <li class="text-gray-500 text-sm">No data available.</li>
                            <?php else: foreach ($topProducts as $p): ?>
                                <li class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-sm"><?= htmlspecialchars($p['name']) ?></p>
                                        <p class="text-xs text-gray-500">Sold: <?= (int)$p['total_quantity'] ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-sm">$<?= number_format($p['price'], 2) ?></p>
                                        <p class="text-xs text-gray-400">Stock: <?= (int)$p['stock'] ?></p>
                                    </div>
                                </li>
                        <?php endforeach;
                        endif; ?>
                    </ul>
                </div>
                <!-- ================= LOW STOCK PRODUCTS ================= -->
                <div class="bg-white p-6 rounded-2xl shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg text-red-600">Low Stock Products</h3>
                        <span class="text-xs text-red-400">Critical</span>
                    </div>

                    <div class="h-56 mb-6">
                        <canvas id="lowStockChart"></canvas>
                    </div>

                    <ul class="space-y-3 max-h-44 overflow-y-auto pr-2">
                        <?php if (!$lowStockProducts): ?>
                            <li class="text-gray-500 text-sm">No low stock items.</li>
                            <?php else: foreach ($lowStockProducts as $p): ?>
                                <li class="flex justify-between items-center">
                                    <span class="text-sm"><?= htmlspecialchars($p['name']) ?></span>
                                    <span class="text-red-600 font-bold"><?= (int)$p['stock'] ?></span>
                                </li>
                        <?php endforeach;
                        endif; ?>
                    </ul>
                </div>
            </section>

        </main>
    </div>

    <script src="/assets/Js/notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            /* ================= TOP PRODUCTS ================= */
            const topLabels = <?= json_encode(array_column($topProducts, 'name')) ?> || [];
            const topData = <?= json_encode(array_map('intval', array_column($topProducts, 'total_quantity'))) ?> || [];

            if (topLabels.length) {
                const ctx = document.getElementById('topProductsChart').getContext('2d');

                const blueGradient = ctx.createLinearGradient(0, 0, 400, 0);
                blueGradient.addColorStop(0, 'rgba(59,130,246,0.9)');
                blueGradient.addColorStop(1, 'rgba(99,102,241,0.9)');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: topLabels,
                        datasets: [{
                            data: topData,
                            backgroundColor: blueGradient,
                            borderRadius: 10,
                            barThickness: 14
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true
                            },
                            y: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
            /* ================= LOW STOCK ================= */
            const lowLabels = <?= json_encode(array_column($lowStockProducts, 'name')) ?> || [];
            const lowData = <?= json_encode(array_map('intval', array_column($lowStockProducts, 'stock'))) ?> || [];

            if (lowLabels.length) {
                const ctx = document.getElementById('lowStockChart').getContext('2d');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: lowLabels,
                        datasets: [{
                            data: lowData,
                            backgroundColor: 'rgba(239,68,68,0.85)',
                            borderRadius: 10,
                            barThickness: 14
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ` Stock: ${ctx.raw}`
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true
                            },
                            y: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
        /* ================= REVENUE & ORDERS STATUS CHARTS ================= */
        document.addEventListener('DOMContentLoaded', () => {
            /* Revenue Last 7 Days */
            const revenueLabels = <?= json_encode(array_column($revenueLast7, 'date')) ?> || [];
            const revenueData = <?= json_encode(array_map(function ($r) {
                                    return (float)$r['total'];
                                }, $revenueLast7)) ?> || [];

            if (revenueLabels.length) {
                const ctx = document.getElementById('revenueChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: revenueLabels,
                        datasets: [{
                            data: revenueData,
                            borderColor: 'rgba(99,102,241,0.9)',
                            backgroundColor: 'rgba(99,102,241,0.12)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            /* Orders Status - simple horizontal bar */
            const statusLabels = ['Pending', 'Processing', 'Completed', 'Cancelled'];
            const statusData = [
                <?= (int)$ordersByStatus['pending'] ?>,
                <?= (int)$ordersByStatus['processing'] ?>,
                <?= (int)$ordersByStatus['completed'] ?>,
                <?= (int)$ordersByStatus['cancelled'] ?>
            ];
            if (document.getElementById('ordersStatusChart')) {
                const ctx2 = document.getElementById('ordersStatusChart').getContext('2d');
                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusData,
                            backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444']
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true
                            },
                            y: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
        /* ================================
           ORDER DETAIL MODAL
        ================================ */
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.view-order').forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const order = JSON.parse(btn.getAttribute('data-order'));
                        const html = `
                            <div class="text-left">
                                <p><strong>Order:</strong> #${order.id}</p>
                                <p><strong>Customer:</strong> ${order.customer}</p>
                                <p><strong>Total:</strong> $${parseFloat(order.total).toFixed(2)}</p>
                                <p><strong>Payment:</strong> ${order.payment_status}</p>
                                <p><strong>Status:</strong> ${order.status}</p>
                                <p><strong>Date:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                            </div>
                        `;
                        Swal.fire({
                            title: `Order #${order.id}`,
                            html,
                            width: 600,
                            confirmButtonText: 'Close'
                        });
                    } catch (e) {
                        Swal.fire('Error', 'Unable to parse order details', 'error');
                    }
                });
            });
        });
    </script>
</body>

</html>