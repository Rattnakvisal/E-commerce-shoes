<?php
require_once __DIR__ . '/../config/conn.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
    header('Location: ../auth/login.php');
    exit;
}

$admin_name = $admin_name ?? ($_SESSION['name'] ?? ($_SESSION['email'] ?? 'Admin'));
$admin_role = $admin_role ?? ($_SESSION['role'] ?? 'Administrator');
$admin_avatar = $admin_avatar ?? ('https://ui-avatars.com/api/?name=' . urlencode($admin_name) . '&background=6366f1&color=fff');

// Fetch dashboard metrics from database
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $total_orders = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid'");
    $revenue = (float)$stmt->fetchColumn();

    $conversion_rate = $total_users > 0 ? ($total_orders / $total_users) * 100 : 0;
} catch (PDOException $e) {
    $total_users = 0;
    $total_orders = 0;
    $revenue = 0.0;
    $conversion_rate = 0.0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }

        .page-transition {
            transition: opacity 0.3s ease-in-out;
        }

        .dropdown-transition {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .shadow-soft {
            box-shadow: 0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .hover-lift:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }

        .active-menu-item {
            position: relative;
        }

        .active-menu-item::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: #6366f1;
            border-radius: 3px 0 0 3px;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/include/navbar.php'; ?>
    <div class="md:ml-64 min-h-screen">
        <main class="pt-6 md:pt-16 p-4 sm:p-6 lg:p-8 page-transition bg-gray-50 min-h-screen">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">
                    Welcome back, <?php echo htmlspecialchars(explode(' ', $admin_name)[0]); ?>!
                </h1>
                <p class="text-gray-600 mt-1">
                    Here's what's happening with your store today.
                </p>
            </div>
            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow-soft p-6 flex items-center justify-between">
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
                <div class="bg-white rounded-xl shadow-soft p-6 flex items-center justify-between">
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
                <div class="bg-white rounded-xl shadow-soft p-6 flex items-center justify-between">
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
                <div class="bg-white rounded-xl shadow-soft p-6 flex items-center justify-between">
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
        </main>
    </div>
    <script src="../assets/Js/nav.js"></script>
</body>

</html>