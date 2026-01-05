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
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/include/navbar.php'; ?>
    <div class="md:ml-64 min-h-screen">
        <main class="pt-6 md:pt-16 p-4 sm:p-6 lg:p-8 page-transition bg-gray-50 min-h-screen">
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
            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Users -->
                <div class="stat-card bg-white rounded-xl shadow-soft p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            <?php echo number_format($total_users); ?>
                        </p>
                        <p class="text-xs mt-1 text-green-600 flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <?php echo $total_users > 0 ? 'Calculated from DB' : 'No data'; ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="stat-card bg-white rounded-xl shadow-soft p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Orders</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            <?php echo number_format($total_orders); ?>
                        </p>
                        <p class="text-xs mt-1 text-green-600 flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i>
                            8.3% from last week
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="stat-card bg-white rounded-xl shadow-soft p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            $<?php echo number_format($revenue, 2); ?>
                        </p>
                        <p class="text-xs mt-1 text-red-600 flex items-center">
                            <i class="fas fa-arrow-down mr-1"></i>
                            3.2% from last month
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                    </div>
                </div>

                <!-- Conversion Rate -->
                <div class="stat-card bg-white rounded-xl shadow-soft p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Conversion Rate</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            <?php echo number_format($conversion_rate, 2); ?>%
                        </p>
                        <p class="text-xs mt-1 text-green-600 flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i>
                            1.1% from last week
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
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
            <!-- Recent Orders -->
            <div class="mt-6">
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