<?php
require_once __DIR__ . '/../config/conn.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login
if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Only allow staff or admin to view this page
$role = $_SESSION['role'] ?? null;
if ($role !== 'staff' && $role !== 'admin') {
    header('Location: ../view/index.php');
    exit;
}

$staff_name = $_SESSION['name'] ?? $_SESSION['admin_name'] ?? 'Staff';
$staff_id = $_SESSION['user_id'] ?? null;

// Simple stats (example queries)
try {
    // Get total POS orders
    $totalPosOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_type = 'pos'")->fetchColumn();

    // Today's orders
    $today = date('Y-m-d');
    $todaysOrdersStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ? AND order_type = 'pos'");
    $todaysOrdersStmt->execute([$today]);
    $todaysOrders = (int)$todaysOrdersStmt->fetchColumn();

    // Today's revenue
    $todayRevenueStmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM orders 
        WHERE DATE(created_at) = ? 
        AND order_type = 'pos' 
        AND status = 'completed'
    ");
    $todayRevenueStmt->execute([$today]);
    $todaysRevenue = (float)$todayRevenueStmt->fetchColumn();

    // Staff's today orders
    $staffTodayOrdersStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM orders 
        WHERE DATE(created_at) = ? 
        AND order_type = 'pos' 
        AND staff_id = ?
    ");
    $staffTodayOrdersStmt->execute([$today, $staff_id]);
    $staffTodaysOrders = (int)$staffTodayOrdersStmt->fetchColumn();

    // Recent orders
    $recentOrdersStmt = $pdo->prepare("
        SELECT o.*, c.name as customer_name 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        WHERE o.order_type = 'pos' 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recentOrdersStmt->execute();
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Popular products
    $popularProductsStmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, 
               COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.order_type = 'pos' 
        AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY p.id, p.name, p.price
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $popularProductsStmt->execute();
    $popularProducts = $popularProductsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $totalPosOrders = 0;
    $todaysOrders = 0;
    $todaysRevenue = 0;
    $staffTodaysOrders = 0;
    $recentOrders = [];
    $popularProducts = [];
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Staff Dashboard - POS Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .quick-action-btn {
            transition: all 0.2s ease;
        }

        .quick-action-btn:hover {
            transform: scale(1.05);
        }

        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <body class="bg-gray-50 min-h-screen">
        <!-- Include Admin Navbar -->
        <?php require_once __DIR__ . '/../admin/include/navbar.php'; ?>
        <main class="md:ml-64 min-h-screen animate-fade-in">
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="mb-6 ">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-800">Staff Dashboard</h1>
                        <p class="text-gray-600 mt-2">Welcome back, <?php echo htmlspecialchars($staff_name); ?>! Here's your POS management overview.</p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- Today's Orders -->
                        <div class="stat-card bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Today's Orders</p>
                                    <p class="text-2xl font-bold text-gray-800"><?php echo $todaysOrders; ?></p>
                                </div>
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <i class="fas fa-shopping-cart text-blue-500 text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Your orders today:</span> <?php echo $staffTodaysOrders; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Today's Revenue -->
                        <div class="stat-card bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Today's Revenue</p>
                                    <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($todaysRevenue, 2); ?></p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full">
                                    <i class="fas fa-dollar-sign text-green-500 text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-calendar-day text-green-500 mr-1"></i>
                                    <?php echo date('F j, Y'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Total POS Orders -->
                        <div class="stat-card bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Total POS Orders</p>
                                    <p class="text-2xl font-bold text-gray-800"><?php echo $totalPosOrders; ?></p>
                                </div>
                                <div class="bg-purple-100 p-3 rounded-full">
                                    <i class="fas fa-cash-register text-purple-500 text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-history text-purple-500 mr-1"></i>
                                    All-time POS orders
                                </p>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="stat-card bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Quick Actions</p>
                                    <p class="text-lg font-semibold text-gray-800">Manage POS</p>
                                </div>
                                <div class="bg-orange-100 p-3 rounded-full">
                                    <i class="fas fa-bolt text-orange-500 text-xl"></i>
                                </div>
                            </div>
                            <div class="mt-4 space-y-2">
                                <a href="../pos/new-order.php" class="block text-center bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition quick-action-btn">
                                    <i class="fas fa-plus mr-2"></i>New Order
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column: Recent Orders & Actions -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Recent Orders -->
                            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <h2 class="text-xl font-semibold text-gray-800">Recent Orders</h2>
                                        <a href="../pos/orders.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                            View All <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (count($recentOrders) > 0): ?>
                                                <?php foreach ($recentOrders as $order): ?>
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                            <?php echo !empty($order['customer_name']) ? htmlspecialchars($order['customer_name']) : 'Walk-in Customer'; ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                            <?php echo date('M j, g:i A', strtotime($order['created_at'])); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                            $<?php echo number_format($order['total_amount'], 2); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="order-status status-<?php echo $order['status']; ?>">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                            <a href="../pos/view-order.php?id=<?php echo $order['id']; ?>" class="text-blue-500 hover:text-blue-700 mr-3">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($order['status'] == 'pending' || $order['status'] == 'processing'): ?>
                                                                <a href="../pos/edit-order.php?id=<?php echo $order['id']; ?>" class="text-green-500 hover:text-green-700">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                                        <i class="fas fa-clipboard-list text-3xl mb-2 block"></i>
                                                        <p>No recent orders found.</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Quick Actions Panel
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2 class="text-xl font-semibold text-gray-800 mb-6">Quick Actions</h2>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <a href="../pos/new-order.php" class="quick-action-btn flex flex-col items-center justify-center bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl p-5 transition">
                                        <div class="bg-blue-500 p-3 rounded-full mb-3">
                                            <i class="fas fa-plus text-white text-xl"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">New Order</span>
                                    </a>

                                    <a href="../pos/orders.php" class="quick-action-btn flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl p-5 transition">
                                        <div class="bg-green-500 p-3 rounded-full mb-3">
                                            <i class="fas fa-list text-white text-xl"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">View Orders</span>
                                    </a>

                                    <a href="../products/" class="quick-action-btn flex flex-col items-center justify-center bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl p-5 transition">
                                        <div class="bg-purple-500 p-3 rounded-full mb-3">
                                            <i class="fas fa-boxes text-white text-xl"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">Products</span>
                                    </a>

                                    <a href="../customers/" class="quick-action-btn flex flex-col items-center justify-center bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 rounded-xl p-5 transition">
                                        <div class="bg-yellow-500 p-3 rounded-full mb-3">
                                            <i class="fas fa-users text-white text-xl"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">Customers</span>
                                    </a>

                                    <a href="../pos/scan-barcode.php" class="quick-action-btn flex flex-col items-center justify-center bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl p-5 transition">
                                        <div class="bg-red-500 p-3 rounded-full mb-3">
                                            <i class="fas fa-barcode text-white text-xl"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">Scan Barcode</span>
                                    </a>

                                    <a href="../reports/daily-sales.php" class="quick-action-btn flex flex-col items-center justify-center bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl p-5 transition">
                                        <div class="bg-indigo-500 p-3 rounded-full mb-3">
                                            <i class="fas fa-chart-bar text-white text-xl"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">Daily Report</span>
                                    </a>
                                </div>
                            </div> -->
                        </div>

                        <!-- Right Column: Popular Products & Activity -->
                        <div class="space-y-8">
                            <!-- Popular Products -->
                            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-xl font-semibold text-gray-800">Popular Products</h2>
                                    <p class="text-sm text-gray-600 mt-1">Top selling items this week</p>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <?php if (count($popularProducts) > 0): ?>
                                        <?php foreach ($popularProducts as $index => $product): ?>
                                            <div class="px-6 py-4 hover:bg-gray-50 flex items-center">
                                                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                                    <i class="fas fa-box text-blue-500"></i>
                                                </div>
                                                <div class="flex-grow">
                                                    <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($product['name']); ?></h3>
                                                    <p class="text-sm text-gray-600">Sold: <?php echo $product['total_sold']; ?> units</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="font-semibold text-gray-900">$<?php echo number_format($product['price'], 2); ?></p>
                                                    <?php if ($index < 3): ?>
                                                        <span class="inline-block w-6 h-6 bg-yellow-500 text-white text-xs font-bold rounded-full flex items-center justify-center mt-1">
                                                            <?php echo $index + 1; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-chart-line text-3xl mb-2 block"></i>
                                            <p>No sales data available.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                                    <a href="../products/" class="text-blue-500 hover:text-blue-700 text-sm font-medium inline-flex items-center">
                                        View All Products <i class="fas fa-arrow-right ml-2"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Daily Target Progress -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2 class="text-xl font-semibold text-gray-800 mb-4">Daily Target</h2>
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Today's Progress</span>
                                        <span>
                                            <?php
                                            $dailyTarget = 50; // Example daily target
                                            $progress = min(100, ($todaysOrders / $dailyTarget) * 100);
                                            echo round($progress, 1);
                                            ?>%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-green-500 h-3 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-bullseye text-green-500 mr-2"></i>
                                    <?php echo $todaysOrders; ?> of <?php echo $dailyTarget; ?> orders completed
                                </p>
                            </div>

                            <!-- System Status -->
                            <div class="bg-white rounded-xl shadow-md p-6">
                                <h2 class="text-xl font-semibold text-gray-800 mb-4">System Status</h2>
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                        <span class="text-gray-700">POS System</span>
                                        <span class="ml-auto text-sm text-green-600 font-medium">Online</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                        <span class="text-gray-700">Printer Connection</span>
                                        <span class="ml-auto text-sm text-green-600 font-medium">Connected</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                        <span class="text-gray-700">Database</span>
                                        <span class="ml-auto text-sm text-green-600 font-medium">Active</span>
                                    </div>
                                    <div class="pt-4 border-t border-gray-100">
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                            Last updated: <?php echo date('g:i A'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <script>
            // Simple greeting based on time of day
            document.addEventListener('DOMContentLoaded', function() {
                const hour = new Date().getHours();
                let greeting = "Good ";

                if (hour < 12) greeting += "Morning";
                else if (hour < 18) greeting += "Afternoon";
                else greeting += "Evening";

                // Update welcome message if needed
                const welcomePara = document.querySelector('.text-gray-600.mt-2');
                if (welcomePara) {
                    welcomePara.innerHTML = `${greeting}, <?php echo htmlspecialchars($staff_name); ?>! Here's your POS management overview.`;
                }

                // Add click animations to quick action buttons
                const quickActionBtns = document.querySelectorAll('.quick-action-btn');
                quickActionBtns.forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        // Add a ripple effect
                        const ripple = document.createElement('span');
                        const rect = this.getBoundingClientRect();
                        const size = Math.max(rect.width, rect.height);
                        const x = e.clientX - rect.left - size / 2;
                        const y = e.clientY - rect.top - size / 2;

                        ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(255, 255, 255, 0.7);
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        width: ${size}px;
                        height: ${size}px;
                        top: ${y}px;
                        left: ${x}px;
                    `;

                        this.style.position = 'relative';
                        this.style.overflow = 'hidden';
                        this.appendChild(ripple);

                        setTimeout(() => {
                            ripple.remove();
                        }, 600);
                    });
                });
            });

            // Add CSS for ripple animation
            const style = document.createElement('style');
            style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
            document.head.appendChild(style);
        </script>
    </body>

</html>