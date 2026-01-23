<?php
require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

/* =====================================================
   DATE RANGE FILTER
===================================================== */
$dateRange = $_GET['range'] ?? '7days';
$startDate = $_GET['start'] ?? null;
$endDate   = $_GET['end'] ?? null;

$dateSql    = '';
$dateParams = [];

switch ($dateRange) {
    case 'today':
        $dateSql = "DATE(created_at) = CURDATE()";
        break;
    case 'yesterday':
        $dateSql = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case '30days':
        $dateSql = "created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    case '90days':
        $dateSql = "created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        break;
    case 'custom':
        if ($startDate && $endDate) {
            $dateSql = "DATE(created_at) BETWEEN :start AND :end";
            $dateParams = [
                ':start' => $startDate,
                ':end'   => $endDate,
            ];
        }
        break;
    default: // 7days
        $dateSql = "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
}

/* =====================================================
   NORMALIZE RANGE TO START/END DATETIMES.
===================================================== */
$startDt = null;
$endDt = null;
if ($dateRange === 'custom' && $startDate && $endDate) {
    $startDt = $startDate . ' 00:00:00';
    $endDt   = $endDate   . ' 23:59:59';
} else {
    switch ($dateRange) {
        case 'today':
            $startDt = date('Y-m-d') . ' 00:00:00';
            $endDt   = date('Y-m-d') . ' 23:59:59';
            break;
        case 'yesterday':
            $startDt = date('Y-m-d', strtotime('-1 day')) . ' 00:00:00';
            $endDt   = date('Y-m-d', strtotime('-1 day')) . ' 23:59:59';
            break;
        case '30days':
            $startDt = date('Y-m-d', strtotime('-29 days')) . ' 00:00:00';
            $endDt   = date('Y-m-d') . ' 23:59:59';
            break;
        case '90days':
            $startDt = date('Y-m-d', strtotime('-89 days')) . ' 00:00:00';
            $endDt   = date('Y-m-d') . ' 23:59:59';
            break;
        default: // 7days
            $startDt = date('Y-m-d', strtotime('-6 days')) . ' 00:00:00';
            $endDt   = date('Y-m-d') . ' 23:59:59';
            break;
    }
}

/* =====================================================
   SAFE DEFAULT TOTALS
===================================================== */
$totals = [
    'products'            => 0,
    'products_active'     => 0,
    'products_inactive'   => 0,
    'products_low_stock'  => 0,
    'featured'            => 0,
    'featured_active'     => 0,
    'users'               => 0,
    'users_today'         => 0,
    'orders'              => 0,
    'revenue'             => 0.0,
    'revenue_today'       => 0.0,
    'revenue_yesterday'   => 0.0,
    'revenue_change'      => 0.0,
    'avg_order_value'     => 0.0,
    'conversion_rate'     => 0.0,
];

/* =====================================================
   DATA HOLDERS
===================================================== */
$productsByCategory = [];
$ordersByStatus     = [];
$revenueByMonth     = [];
$topProducts        = [];
$topCustomers       = [];
$ordersLast7        = [];
$hourlyOrders       = [];
$paymentMethods     = [];
$locationStats      = [];

try {
    /* ================= PRODUCTS ================= */
    $totals['products'] = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totals['products_active'] = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    $totals['products_inactive'] = max(0, $totals['products'] - $totals['products_active']);

    try {
        $totals['products_low_stock'] = (int)$pdo
            ->query("SELECT COUNT(*) FROM products WHERE quantity <= 10 AND quantity > 0")
            ->fetchColumn();
    } catch (Throwable) {
    }

    /* ================= FEATURED ================= */
    $f = $pdo->query("
        SELECT COUNT(*) total, SUM(is_active = 1) active 
        FROM featured_items
    ")->fetch(PDO::FETCH_ASSOC);

    $totals['featured'] = (int)($f['total'] ?? 0);
    $totals['featured_active'] = (int)($f['active'] ?? 0);

    /* ================= USERS ================= */
    $totals['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totals['users_today'] = (int)$pdo
        ->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")
        ->fetchColumn();

    /* ================= ORDERS & REVENUE (respecting selected range) ================= */
    // Orders count in selected range
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN :start AND :end");
    $stmt->execute([':start' => $startDt, ':end' => $endDt]);
    $totals['orders'] = (int)$stmt->fetchColumn();

    // Revenue in selected range (only paid)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid' AND created_at BETWEEN :start AND :end");
    $stmt->execute([':start' => $startDt, ':end' => $endDt]);
    $totals['revenue'] = (float)$stmt->fetchColumn();

    // Today's / Yesterday's revenue remain based on calendar days
    $totals['revenue_today'] = (float)$pdo
        ->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid' AND DATE(created_at)=CURDATE()")
        ->fetchColumn();

    $totals['revenue_yesterday'] = (float)$pdo
        ->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid' AND DATE(created_at)=DATE_SUB(CURDATE(),INTERVAL 1 DAY)")
        ->fetchColumn();

    if ($totals['revenue_yesterday'] > 0) {
        $totals['revenue_change'] =
            (($totals['revenue_today'] - $totals['revenue_yesterday']) / $totals['revenue_yesterday']) * 100;
    }

    $totals['avg_order_value'] =
        $totals['orders'] > 0 ? $totals['revenue'] / $totals['orders'] : 0;

    /* ================= ORDERS BY STATUS ================= */
    $ordersByStatus = $pdo
        ->query("SELECT order_status, COUNT(*) count FROM orders GROUP BY order_status")
        ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ordersByStatus as $row) {
        $key = 'orders_' . strtolower(str_replace(' ', '_', $row['order_status']));
        $totals[$key] = (int)$row['count'];
    }

    /* ================= TOP PRODUCTS ================= */
    $topProducts = $pdo->query("
        SELECT p.name, SUM(oi.quantity) total_sold, SUM(oi.quantity * oi.price) revenue
        FROM order_items oi
        JOIN products p ON p.product_id = oi.product_id
        GROUP BY p.product_id
        ORDER BY total_sold DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    /* ================= TOP CUSTOMERS ================= */
    $topCustomers = $pdo->query("
        SELECT u.email, COUNT(o.order_id) orders_count, SUM(o.total) total_spent
        FROM orders o
        JOIN users u ON u.user_id = o.user_id
        GROUP BY o.user_id
        ORDER BY total_spent DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    /* ================= REVENUE BY MONTH ================= */
    $revenueByMonth = $pdo->query("
        SELECT DATE_FORMAT(created_at,'%Y-%m') month,
               COUNT(*) orders,
               SUM(total) revenue
        FROM orders
        WHERE payment_status='paid'
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month
    ")->fetchAll(PDO::FETCH_ASSOC);

    /* ================= PRODUCTS BY CATEGORY ================= */
    try {
        $stmt = $pdo->query(
            "SELECT c.category_name AS category, COUNT(p.product_id) AS cnt
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.category_id
            GROUP BY c.category_id
            ORDER BY cnt DESC
            LIMIT 10"
        );
        $productsByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $productsByCategory = [];
    }

    /* ================= PAYMENT METHODS (respecting selected range) ================= */
    try {
        $pmStmt = $pdo->prepare(
            "SELECT COALESCE(payment_method, 'Unknown') AS method,
                   COUNT(*) AS cnt,
                   COALESCE(SUM(total),0) AS revenue
            FROM orders
            WHERE payment_status = 'paid' AND created_at BETWEEN :start AND :end
            GROUP BY method
            ORDER BY cnt DESC"
        );
        $pmStmt->execute([':start' => $startDt, ':end' => $endDt]);
        $paymentMethods = $pmStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $paymentMethods = [];
    }

    /* ================= LAST 7 DAYS ================= */
    $rows = $pdo->query("
        SELECT DATE(created_at) d, COUNT(*) cnt, SUM(total) revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY d
    ")->fetchAll(PDO::FETCH_ASSOC);
    /* ================= ORDERS FOR SELECTED RANGE (grouped by day) ================= */
    $rowsStmt = $pdo->prepare("SELECT DATE(created_at) d, COUNT(*) cnt, COALESCE(SUM(total),0) revenue
            FROM orders
            WHERE created_at BETWEEN :start AND :end
            GROUP BY DATE(created_at)");
    $rowsStmt->execute([':start' => $startDt, ':end' => $endDt]);
    $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    // Build series from startDt to endDt (inclusive)
    $startDateObj = new DateTime(substr($startDt, 0, 10));
    $endDateObj = new DateTime(substr($endDt, 0, 10));
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($startDateObj, $interval, $endDateObj->add($interval));
    foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');
        $map[$d] = ['cnt' => 0, 'revenue' => 0];
    }

    foreach ($rows as $r) {
        $d = $r['d'];
        if (isset($map[$d])) {
            $map[$d] = ['cnt' => (int)$r['cnt'], 'revenue' => (float)$r['revenue']];
        }
    }

    foreach ($map as $date => $v) {
        $ordersLast7[] = ['date' => $date, 'count' => $v['cnt'], 'revenue' => $v['revenue']];
    }

    /* ================= CONVERSION ================= */
    $totals['conversion_rate'] =
        $totals['users'] > 0 ? ($totals['orders'] / $totals['users']) * 100 : 0;
} catch (PDOException $e) {
    error_log('[Dashboard Analytics] ' . $e->getMessage());
}
// =====================================================
// Map commonly used view variables for analytics.php
// =====================================================

// Products
$totalProducts    = $totals['products'] ?? 0;
$activeProducts   = $totals['products_active'] ?? 0;
$inactiveProducts = max(0, $totalProducts - $activeProducts);

// Percentages
$activePercent    = $totalProducts > 0
    ? ($activeProducts / $totalProducts) * 100
    : 0;

$inactivePercent  = $totalProducts > 0
    ? ($inactiveProducts / $totalProducts) * 100
    : 0;

// Inventory alerts
$lowStockCount    = $totals['products_low_stock'] ?? 0;

// Featured items
$featuredCount    = $totals['featured_active']
    ?? ($totals['featured'] ?? 0);

// Revenue
$todaysRevenue    = $totals['revenue_today'] ?? 0.0;

// Pending orders
$pendingOrders = $totals['orders_pending']
    ?? ($ordersByStatus['pending'] ?? 0);

$pendingOrders = 0;
if (isset($totals['orders_pending'])) {
    $pendingOrders = (int)$totals['orders_pending'];
} else {
    foreach ($ordersByStatus as $s) {
        if (strtolower(trim($s['order_status'] ?? '')) === 'pending') {
            $pendingOrders = (int)($s['count'] ?? 0);
            break;
        }
    }
}
/* =====================================================
   PAYMENT GATEWAYS ANALYTICS (LAST 30 DAYS)
===================================================== */

$paymentGateways = [
    'paypal' => [
        'name' => 'PayPal',
        'description' => 'Big Brands',
        'icon' => 'fab fa-paypal',
        'color' => 'blue',
        'amount' => 0
    ],
    'wallet' => [
        'name' => 'Wallet',
        'description' => 'All payments',
        'icon' => 'fas fa-wallet',
        'color' => 'green',
        'amount' => 0
    ],
    'card' => [
        'name' => 'Credit Card',
        'description' => 'All payments',
        'icon' => 'fas fa-credit-card',
        'color' => 'purple',
        'amount' => 0
    ],
    'cash' => [
        'name' => 'Cash',
        'description' => 'In-store payments',
        'icon' => 'fas fa-money-bill-wave',
        'color' => 'emerald',
        'amount' => 0
    ]
];

/* =====================================================
   TOTAL AMOUNT PER PAYMENT METHOD
===================================================== */
$sql = "
    SELECT payment_method, SUM(amount) AS total
    FROM payments
    WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY payment_method
";
$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $method = strtolower($row['payment_method']);
    if (isset($paymentGateways[$method])) {
        $paymentGateways[$method]['amount'] = (float)$row['total'];
    }
}

/* =====================================================
   RECENT PAYMENTS BY METHOD
===================================================== */
$paymentsByMethod = [];

$sql = "
    SELECT p.payment_method, p.amount, p.payment_date, p.order_id, u.email
    FROM payments p
    LEFT JOIN orders o ON o.order_id = p.order_id
    LEFT JOIN users u ON u.user_id = o.user_id
    ORDER BY p.payment_date DESC
";
$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $method = strtolower($row['payment_method']);
    $paymentsByMethod[$method][] = $row;
}
