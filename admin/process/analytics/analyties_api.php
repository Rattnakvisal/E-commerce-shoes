<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

$pdo ??= $conn ?? null;
if (!$pdo instanceof PDO) {
    die('Database connection missing.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

/* =====================================================
   DATE RANGE (startDt / endDt)
===================================================== */
$dateRange = $_GET['range'] ?? '7days';
$startDate = $_GET['start'] ?? null; // Y-m-d
$endDate   = $_GET['end'] ?? null;   // Y-m-d

$startDt = null;
$endDt   = null;

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
$ordersLast7        = []; // actually "orders series for selected range"
$hourlyOrders       = []; // (optional)
$paymentMethods     = []; // selected range (count + revenue)
$paymentGateways    = []; // last 30 days totals
$paymentsByMethod   = []; // recent payments grouped
$recentPayments     = []; // flat list
$locationStats      = []; // (optional)

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
        $totals['products_low_stock'] = 0;
    }

    /* ================= FEATURED ================= */
    try {
        $f = $pdo->query("
            SELECT COUNT(*) total, SUM(is_active = 1) active
            FROM featured_items
        ")->fetch(PDO::FETCH_ASSOC);

        $totals['featured'] = (int)($f['total'] ?? 0);
        $totals['featured_active'] = (int)($f['active'] ?? 0);
    } catch (Throwable) {
        $totals['featured'] = 0;
        $totals['featured_active'] = 0;
    }

    /* ================= USERS ================= */
    $totals['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totals['users_today'] = (int)$pdo
        ->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")
        ->fetchColumn();

    /* ================= ORDERS (SELECTED RANGE) ================= */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN :start AND :end");
    $stmt->execute([':start' => $startDt, ':end' => $endDt]);
    $totals['orders'] = (int)$stmt->fetchColumn();

    /* ================= REVENUE (BEST PRACTICE: FROM PAYMENTS) =================
       If you want revenue from orders.total instead, tell me and I’ll switch it.
    */
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(p.amount), 0)
        FROM payments p
        WHERE p.payment_date BETWEEN :start AND :end
    ");
    $stmt->execute([':start' => $startDt, ':end' => $endDt]);
    $totals['revenue'] = (float)$stmt->fetchColumn();

    // Today revenue (calendar day)
    $totals['revenue_today'] = (float)$pdo
        ->query("
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            WHERE DATE(p.payment_date) = CURDATE()
        ")
        ->fetchColumn();

    // Yesterday revenue (calendar day)
    $totals['revenue_yesterday'] = (float)$pdo
        ->query("
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            WHERE DATE(p.payment_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ")
        ->fetchColumn();

    if ($totals['revenue_yesterday'] > 0) {
        $totals['revenue_change'] =
            (($totals['revenue_today'] - $totals['revenue_yesterday']) / $totals['revenue_yesterday']) * 100;
    } else {
        $totals['revenue_change'] = 0.0;
    }

    $totals['avg_order_value'] = $totals['orders'] > 0 ? ($totals['revenue'] / $totals['orders']) : 0.0;

    /* ================= ORDERS BY STATUS ================= */
    try {
        $ordersByStatus = $pdo
            ->query("SELECT order_status, COUNT(*) count FROM orders GROUP BY order_status")
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ordersByStatus as $row) {
            $key = 'orders_' . strtolower(str_replace(' ', '_', (string)$row['order_status']));
            $totals[$key] = (int)$row['count'];
        }
    } catch (Throwable) {
        $ordersByStatus = [];
    }

    /* ================= TOP PRODUCTS ================= */
    try {
        $topProducts = $pdo->query("
            SELECT p.name, SUM(oi.quantity) total_sold, SUM(oi.quantity * oi.price) revenue
            FROM order_items oi
            JOIN products p ON p.product_id = oi.product_id
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        $topProducts = [];
    }

    /* ================= TOP CUSTOMERS ================= */
    try {
        // Note: uses orders.total; if your revenue is payment-based, this is still fine as "order total spent".
        $topCustomers = $pdo->query("
            SELECT u.email, COUNT(o.order_id) orders_count, SUM(o.total) total_spent
            FROM orders o
            JOIN users u ON u.user_id = o.user_id
            GROUP BY o.user_id
            ORDER BY total_spent DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        $topCustomers = [];
    }

    /* ================= REVENUE BY MONTH (FROM PAYMENTS) ================= */
    try {
        $revenueByMonth = $pdo->query("
            SELECT DATE_FORMAT(p.payment_date,'%Y-%m') month,
                   COUNT(*) payments,
                   COALESCE(SUM(p.amount),0) revenue
            FROM payments p
            WHERE p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY month
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        $revenueByMonth = [];
    }

    /* ================= PRODUCTS BY CATEGORY ================= */
    try {
        $productsByCategory = $pdo->query("
            SELECT c.category_name AS category, COUNT(p.product_id) AS cnt
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.category_id
            GROUP BY c.category_id
            ORDER BY cnt DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        $productsByCategory = [];
    }

    /* =====================================================
       PAYMENTS ANALYTICS (NEW DESIGN)
       - payments.payment_method_id
       - payment_methods.method_id, method_code, method_name
    ===================================================== */

    // 1) Payment methods summary for selected range (count + revenue)
    try {
        $pmStmt = $pdo->prepare("
            SELECT
                COALESCE(pm.method_name, 'Unknown') AS method,
                COALESCE(pm.method_code, 'unknown') AS method_code,
                COUNT(*) AS cnt,
                COALESCE(SUM(p.amount), 0) AS revenue
            FROM payments p
            LEFT JOIN payment_methods pm ON pm.method_id = p.payment_method_id
            WHERE p.payment_date BETWEEN :start AND :end
            GROUP BY pm.method_id, pm.method_name, pm.method_code
            ORDER BY cnt DESC
        ");
        $pmStmt->execute([':start' => $startDt, ':end' => $endDt]);
        $paymentMethods = $pmStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        $paymentMethods = [];
    }

    // 2) Payment gateways totals (last 30 days) - dynamic from payment_methods
    try {
        $gwStmt = $pdo->query("
            SELECT
                pm.method_code,
                pm.method_name,
                COALESCE(SUM(p.amount), 0) AS total
            FROM payment_methods pm
            LEFT JOIN payments p
                ON p.payment_method_id = pm.method_id
               AND p.payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            WHERE pm.is_active = 1
            GROUP BY pm.method_id, pm.method_code, pm.method_name
            ORDER BY total DESC
        ");

        $paymentGateways = [];
        while ($row = $gwStmt->fetch(PDO::FETCH_ASSOC)) {
            $code = strtolower((string)$row['method_code']);

            // Optional UI customization
            $icon  = 'fas fa-money-check-alt';
            $color = 'blue';
            if ($code === 'aba') {
                $icon = 'fas fa-university';
                $color = 'red';
            }
            if ($code === 'acleda') {
                $icon = 'fas fa-university';
                $color = 'green';
            }
            if ($code === 'wing') {
                $icon = 'fas fa-wallet';
                $color = 'purple';
            }
            if ($code === 'chipmong') {
                $icon = 'fas fa-university';
                $color = 'amber';
            }
            if ($code === 'bakong') {
                $icon = 'fas fa-qrcode';
                $color = 'cyan';
            }

            $paymentGateways[$code] = [
                'name'        => (string)$row['method_name'],
                'description' => 'Last 30 days',
                'icon'        => $icon,
                'color'       => $color,
                'amount'      => (float)$row['total'],
            ];
        }
    } catch (Throwable) {
        $paymentGateways = [];
    }

    // 3) Recent payments list + grouped by method_code
    try {
        $rpStmt = $pdo->query("
            SELECT
                p.payment_id,
                p.order_id,
                p.amount,
                p.payment_date,
                COALESCE(pm.method_code, 'unknown') AS method_code,
                COALESCE(pm.method_name, 'Unknown') AS method_name,
                u.user_id,
                u.email
            FROM payments p
            LEFT JOIN payment_methods pm ON pm.method_id = p.payment_method_id
            LEFT JOIN orders o ON o.order_id = p.order_id
            LEFT JOIN users u ON u.user_id = o.user_id
            ORDER BY p.payment_date DESC
            LIMIT 200
        ");
        $recentPayments = $rpStmt->fetchAll(PDO::FETCH_ASSOC);

        $paymentsByMethod = [];
        foreach ($recentPayments as $row) {
            $code = strtolower((string)($row['method_code'] ?? 'unknown'));
            $paymentsByMethod[$code][] = $row;
        }
    } catch (Throwable) {
        $recentPayments = [];
        $paymentsByMethod = [];
    }

    /* ================= ORDERS SERIES (SELECTED RANGE, GROUP BY DAY) ================= */
    $rowsStmt = $pdo->prepare("
        SELECT DATE(o.created_at) d, COUNT(*) cnt, COALESCE(SUM(o.total),0) revenue
        FROM orders o
        WHERE o.created_at BETWEEN :start AND :end
        GROUP BY DATE(o.created_at)
        ORDER BY d
    ");
    $rowsStmt->execute([':start' => $startDt, ':end' => $endDt]);
    $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Build a full date map from startDt to endDt inclusive
    $map = [];
    $startDateObj = new DateTime(substr($startDt, 0, 10));
    $endDateObj   = new DateTime(substr($endDt, 0, 10));
    $interval     = new DateInterval('P1D');
    $period       = new DatePeriod($startDateObj, $interval, (clone $endDateObj)->add($interval));

    foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');
        $map[$d] = ['cnt' => 0, 'revenue' => 0.0];
    }

    foreach ($rows as $r) {
        $d = (string)$r['d'];
        if (isset($map[$d])) {
            $map[$d] = ['cnt' => (int)$r['cnt'], 'revenue' => (float)$r['revenue']];
        }
    }

    $ordersLast7 = [];
    foreach ($map as $date => $v) {
        $ordersLast7[] = ['date' => $date, 'count' => $v['cnt'], 'revenue' => $v['revenue']];
    }

    /* ================= CONVERSION ================= */
    $totals['conversion_rate'] = $totals['users'] > 0 ? ($totals['orders'] / $totals['users']) * 100 : 0.0;
} catch (Throwable $e) {
    error_log('[Dashboard Analytics] ' . $e->getMessage());
}

/* =====================================================
   VIEW VARIABLES (like your old mapping)
===================================================== */

// Products
$totalProducts    = $totals['products'] ?? 0;
$activeProducts   = $totals['products_active'] ?? 0;
$inactiveProducts = max(0, $totalProducts - $activeProducts);

// Percentages
$activePercent   = $totalProducts > 0 ? ($activeProducts / $totalProducts) * 100 : 0;
$inactivePercent = $totalProducts > 0 ? ($inactiveProducts / $totalProducts) * 100 : 0;

// Inventory alerts
$lowStockCount = $totals['products_low_stock'] ?? 0;

// Featured items
$featuredCount = $totals['featured_active'] ?? ($totals['featured'] ?? 0);

// Revenue
$todaysRevenue = $totals['revenue_today'] ?? 0.0;

// Pending orders
$pendingOrders = 0;
if (isset($totals['orders_pending'])) {
    $pendingOrders = (int)$totals['orders_pending'];
} else {
    foreach ($ordersByStatus as $s) {
        if (strtolower(trim((string)($s['order_status'] ?? ''))) === 'pending') {
            $pendingOrders = (int)($s['count'] ?? 0);
            break;
        }
    }
}
