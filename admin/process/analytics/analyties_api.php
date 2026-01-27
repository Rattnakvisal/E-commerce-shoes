<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   BOOTSTRAP
===================================================== */
$pdo ??= $conn ?? null;
if (!$pdo instanceof PDO) {
    die('Database connection missing.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/Log/login.php');
    exit;
}

/* =====================================================
   HELPERS
===================================================== */
function resolveDateRange(string $range, ?string $start, ?string $end): array
{
    $range = strtolower(trim($range));
    $today = date('Y-m-d');

    if ($range === 'custom' && $start && $end) {
        return [$start . ' 00:00:00', $end . ' 23:59:59'];
    }

    return match ($range) {
        'today'      => [$today . ' 00:00:00', $today . ' 23:59:59'],
        'yesterday'  => [date('Y-m-d', strtotime('-1 day')) . ' 00:00:00', date('Y-m-d', strtotime('-1 day')) . ' 23:59:59'],
        '30days'     => [date('Y-m-d', strtotime('-29 days')) . ' 00:00:00', $today . ' 23:59:59'],
        '90days'     => [date('Y-m-d', strtotime('-89 days')) . ' 00:00:00', $today . ' 23:59:59'],
        default      => [date('Y-m-d', strtotime('-6 days')) . ' 00:00:00', $today . ' 23:59:59'], // 7days
    };
}

function fetchScalar(PDO $pdo, string $sql, array $params = [], $default = 0)
{
    try {
        if ($params) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $val = $stmt->fetchColumn();
        } else {
            $val = $pdo->query($sql)->fetchColumn();
        }
        return $val ?? $default;
    } catch (Throwable) {
        return $default;
    }
}

function fetchAllSafe(PDO $pdo, string $sql, array $params = []): array
{
    try {
        if ($params) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }
}

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/* =====================================================
   INPUTS
===================================================== */
$dateRange = (string)($_GET['range'] ?? '7days');
$startDate = $_GET['start'] ?? null; // Y-m-d
$endDate   = $_GET['end'] ?? null;   // Y-m-d

[$startDt, $endDt] = resolveDateRange($dateRange, $startDate, $endDate);

/* =====================================================
   DEFAULT TOTALS / HOLDERS
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
    'transactions'        => 0,
    'transactions_today'  => 0,
];

$productsByCategory = [];
$ordersByStatus     = [];
$revenueByMonth     = [];
$topProducts        = [];
$topCustomers       = [];
$ordersSeries       = [];   // was $ordersLast7
$hourlyOrders       = [];
$paymentMethods     = [];
$paymentGateways    = [];
$paymentsByMethod   = [];
$recentPayments     = [];
$locationStats      = [];

/* =====================================================
   GATEWAY JSON ENDPOINT (EARLY EXIT)
===================================================== */
try {
    if (!empty($_GET['gateway'])) {
        $gw    = strtolower(trim((string)$_GET['gateway']));
        $limit = isset($_GET['limit']) ? max(1, min(1000, (int)$_GET['limit'])) : 200;

        $sql = "
            SELECT
                p.payment_id,
                p.order_id,
                p.amount,
                p.payment_date,
                COALESCE(pm.method_code,'unknown') AS method_code,
                COALESCE(pm.method_name,'Unknown') AS method_name,
                u.user_id,
                u.email
            FROM payments p
            LEFT JOIN payment_methods pm ON pm.method_id = p.payment_method_id
            LEFT JOIN orders o ON o.order_id = p.order_id
            LEFT JOIN users u ON u.user_id = o.user_id
            WHERE LOWER(COALESCE(pm.method_code,'unknown')) = :method_code
            ORDER BY p.payment_date DESC
            LIMIT {$limit}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':method_code' => $gw]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        jsonResponse([
            'status'  => 'ok',
            'gateway' => $gw,
            'count'   => count($rows),
            'data'    => $rows,
        ]);
    }
} catch (Throwable $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}

/* =====================================================
   ANALYTICS
===================================================== */
try {
    /* ================= PRODUCTS ================= */
    $totals['products'] = (int) fetchScalar($pdo, "SELECT COUNT(*) FROM products", [], 0);
    $totals['products_active'] = (int) fetchScalar($pdo, "SELECT COUNT(*) FROM products WHERE status = 'active'", [], 0);
    $totals['products_inactive'] = max(0, $totals['products'] - $totals['products_active']);
    $totals['products_low_stock'] = (int) fetchScalar(
        $pdo,
        "SELECT COUNT(*) FROM products WHERE quantity <= 10 AND quantity > 0",
        [],
        0
    );

    /* ================= FEATURED ================= */
    $f = fetchAllSafe($pdo, "
        SELECT COUNT(*) total, SUM(is_active = 1) active
        FROM featured_items
    ");
    $f0 = $f[0] ?? [];
    $totals['featured'] = (int)($f0['total'] ?? 0);
    $totals['featured_active'] = (int)($f0['active'] ?? 0);

    /* ================= USERS ================= */
    $totals['users'] = (int) fetchScalar($pdo, "SELECT COUNT(*) FROM users", [], 0);
    $totals['users_today'] = (int) fetchScalar($pdo, "SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()", [], 0);

    /* ================= ORDERS (RANGE) ================= */
    $totals['orders'] = (int) fetchScalar(
        $pdo,
        "SELECT COUNT(*) FROM orders WHERE created_at BETWEEN :start AND :end",
        [':start' => $startDt, ':end' => $endDt],
        0
    );

    /* ================= REVENUE (FROM PAYMENTS, RANGE) ================= */
    $totals['revenue'] = (float) fetchScalar(
        $pdo,
        "SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.payment_date BETWEEN :start AND :end",
        [':start' => $startDt, ':end' => $endDt],
        0.0
    );

    $totals['revenue_today'] = (float) fetchScalar(
        $pdo,
        "SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE DATE(p.payment_date) = CURDATE()",
        [],
        0.0
    );

    $totals['revenue_yesterday'] = (float) fetchScalar(
        $pdo,
        "SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE DATE(p.payment_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
        [],
        0.0
    );

    $y = $totals['revenue_yesterday'];
    $t = $totals['revenue_today'];
    $totals['revenue_change'] = ($y > 0) ? (($t - $y) / $y) * 100 : 0.0;

    $totals['avg_order_value'] = ($totals['orders'] > 0) ? ($totals['revenue'] / $totals['orders']) : 0.0;

    /* ================= ORDERS BY STATUS ================= */
    $ordersByStatus = fetchAllSafe($pdo, "SELECT order_status, COUNT(*) count FROM orders GROUP BY order_status");
    foreach ($ordersByStatus as $row) {
        $key = 'orders_' . strtolower(str_replace(' ', '_', (string)($row['order_status'] ?? 'unknown')));
        $totals[$key] = (int)($row['count'] ?? 0);
    }

    /* ================= TOP PRODUCTS ================= */
    $topProducts = fetchAllSafe($pdo, "
        SELECT p.name, SUM(oi.quantity) total_sold, SUM(oi.quantity * oi.price) revenue
        FROM order_items oi
        JOIN products p ON p.product_id = oi.product_id
        GROUP BY p.product_id
        ORDER BY total_sold DESC
        LIMIT 5
    ");

    /* ================= TOP CUSTOMERS ================= */
    $topCustomers = fetchAllSafe($pdo, "
        SELECT u.email, COUNT(o.order_id) orders_count, SUM(o.total) total_spent
        FROM orders o
        JOIN users u ON u.user_id = o.user_id
        GROUP BY o.user_id
        ORDER BY total_spent DESC
        LIMIT 5
    ");

    /* ================= REVENUE BY MONTH (LAST 6 MONTHS) ================= */
    $revenueByMonth = fetchAllSafe($pdo, "
        SELECT DATE_FORMAT(p.payment_date,'%Y-%m') month,
               COUNT(*) payments,
               COALESCE(SUM(p.amount),0) revenue
        FROM payments p
        WHERE p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month
    ");

    /* ================= PRODUCTS BY CATEGORY ================= */
    $productsByCategory = fetchAllSafe($pdo, "
        SELECT c.category_name AS category, COUNT(p.product_id) AS cnt
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.category_id
        GROUP BY c.category_id
        ORDER BY cnt DESC
        LIMIT 10
    ");

    /* ================= PAYMENTS: METHODS (RANGE) ================= */
    $paymentMethods = fetchAllSafe($pdo, "
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
    ", [':start' => $startDt, ':end' => $endDt]);

    /* ================= PAYMENT GATEWAYS TOTALS (LAST 30 DAYS) ================= */
    $rows = fetchAllSafe($pdo, "
        SELECT
            pm.method_code,
            pm.method_name,
            COUNT(p.payment_id) AS cnt,
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
    foreach ($rows as $row) {
        $code = strtolower((string)($row['method_code'] ?? 'unknown'));

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
            'name'        => (string)($row['method_name'] ?? 'Unknown'),
            'description' => 'Last 30 days',
            'icon'        => $icon,
            'color'       => $color,
            'amount'      => (float)($row['total'] ?? 0),
            'count'       => (int)($row['cnt'] ?? 0),
        ];
    }

    /* ================= RECENT PAYMENTS + GROUP ================= */
    $recentPayments = fetchAllSafe($pdo, "
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

    $paymentsByMethod = [];
    foreach ($recentPayments as $row) {
        $code = strtolower((string)($row['method_code'] ?? 'unknown'));
        $paymentsByMethod[$code][] = $row;
    }

    /* ================= ORDERS SERIES (RANGE, GROUP BY DAY) ================= */
    $rows = fetchAllSafe($pdo, "
        SELECT DATE(o.created_at) d, COUNT(*) cnt, COALESCE(SUM(o.total),0) revenue
        FROM orders o
        WHERE o.created_at BETWEEN :start AND :end
        GROUP BY DATE(o.created_at)
        ORDER BY d
    ", [':start' => $startDt, ':end' => $endDt]);

    $map = [];
    $startDateObj = new DateTime(substr($startDt, 0, 10));
    $endDateObj   = new DateTime(substr($endDt, 0, 10));
    $period = new DatePeriod($startDateObj, new DateInterval('P1D'), (clone $endDateObj)->add(new DateInterval('P1D')));

    foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');
        $map[$d] = ['cnt' => 0, 'revenue' => 0.0];
    }

    foreach ($rows as $r) {
        $d = (string)($r['d'] ?? '');
        if ($d !== '' && isset($map[$d])) {
            $map[$d] = ['cnt' => (int)($r['cnt'] ?? 0), 'revenue' => (float)($r['revenue'] ?? 0)];
        }
    }

    $ordersSeries = [];
    foreach ($map as $date => $v) {
        $ordersSeries[] = ['date' => $date, 'count' => $v['cnt'], 'revenue' => $v['revenue']];
    }

    /* ================= CONVERSION ================= */
    $totals['conversion_rate'] = ($totals['users'] > 0) ? ($totals['orders'] / $totals['users']) * 100 : 0.0;
} catch (Throwable $e) {
    error_log('[Dashboard Analytics] ' . $e->getMessage());
}

/* =====================================================
   VIEW VARIABLES
===================================================== */
// Products
$totalProducts    = (int)($totals['products'] ?? 0);
$activeProducts   = (int)($totals['products_active'] ?? 0);
$inactiveProducts = max(0, $totalProducts - $activeProducts);

// Percentages
$activePercent   = $totalProducts > 0 ? ($activeProducts / $totalProducts) * 100 : 0;
$inactivePercent = $totalProducts > 0 ? ($inactiveProducts / $totalProducts) * 100 : 0;

// Inventory alerts
$lowStockCount = (int)($totals['products_low_stock'] ?? 0);

// Featured items (active preferred)
$featuredCount = (int)($totals['featured_active'] ?? ($totals['featured'] ?? 0));

// Revenue
$todaysRevenue = (float)($totals['revenue_today'] ?? 0.0);

// Recent transactions for UI
$recentTransactions = array_slice($recentPayments ?? [], 0, 10);

// Pending orders (if not in totals, infer from ordersByStatus)
$pendingOrders = (int)($totals['orders_pending'] ?? 0);
if ($pendingOrders === 0 && $ordersByStatus) {
    foreach ($ordersByStatus as $s) {
        if (strtolower(trim((string)($s['order_status'] ?? ''))) === 'pending') {
            $pendingOrders = (int)($s['count'] ?? 0);
            break;
        }
    }
}

// For your JS charts keep same variable name if needed:
$ordersLast7 = $ordersSeries; // compatibility
