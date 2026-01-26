<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   SESSION & AUTH
===================================================== */
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) die('Database connection missing.');

/* =====================================================
   INPUT HELPERS
===================================================== */
function s(string $key, string $default = ''): string
{
    return trim((string)($_GET[$key] ?? $default));
}
function sl(string $key, string $default = ''): string
{
    return strtolower(s($key, $default));
}
function allow(string $value, array $allowed): string
{
    return in_array($value, $allowed, true) ? $value : '';
}

/* =====================================================
   FILTERS
===================================================== */
$filters = [
    'status'      => allow(sl('status'),  ['pending', 'processing', 'completed', 'cancelled', '']),
    'payment'     => allow(sl('payment'), ['pending', 'paid', 'failed', 'refunded', 'unpaid', '']),
    'type'        => sl('type'),
    'method_code' => sl('method'), // ?method=aba
    'date_from'   => s('date_from'),
    'date_to'     => s('date_to'),
    'search'      => s('search'),
    'sort'        => allow(sl('sort', 'newest'), ['newest', 'oldest', 'total_asc', 'total_desc']),
];

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

/* =====================================================
   WHERE (use named params)
===================================================== */
$where  = [];
$params = [];

if ($filters['status'] !== '') {
    $where[] = 'o.order_status = :status';
    $params[':status'] = $filters['status'];
}

if ($filters['payment'] !== '') {
    $where[] = 'o.payment_status = :pay';
    $params[':pay'] = $filters['payment'];
}

if ($filters['type'] !== '') {
    $where[] = 'LOWER(o.order_type) = :type';
    $params[':type'] = $filters['type'];
}

if ($filters['date_from'] !== '') {
    $where[] = 'o.created_at >= :df';
    $params[':df'] = $filters['date_from'] . ' 00:00:00';
}

if ($filters['date_to'] !== '') {
    $where[] = 'o.created_at <= :dt';
    $params[':dt'] = $filters['date_to'] . ' 23:59:59';
}

if ($filters['search'] !== '') {
    $where[] = '(CAST(o.order_id AS CHAR) LIKE :q OR COALESCE(u.name,"") LIKE :q OR COALESCE(u.email,"") LIKE :q)';
    $params[':q'] = '%' . $filters['search'] . '%';
}

// method filter needs join (derived latest payment method)
$needMethodJoin = ($filters['method_code'] !== '');
if ($needMethodJoin) {
    $where[] = 'LOWER(latest_pm.method_code) = :m';
    $params[':m'] = $filters['method_code'];
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* =====================================================
   SORT
===================================================== */
$orderBy = match ($filters['sort']) {
    'oldest'     => 'o.created_at ASC',
    'total_asc'  => 'o.total ASC',
    'total_desc' => 'o.total DESC',
    default      => 'o.created_at DESC',
};

/* =====================================================
   GLOBAL STATS (not filtered)
===================================================== */
$stats = [
    'total_orders'   => 0,
    'pending_orders' => 0,
    'today_orders'   => 0,
    'today_revenue'  => 0.0,
    'total_revenue'  => 0.0,
    'pending_count'  => 0, // for template compatibility
];

try {
    $row = $pdo->query("
        SELECT
            COUNT(*) AS total_orders,
            SUM(CASE WHEN order_status='pending' THEN 1 ELSE 0 END) AS pending_orders
        FROM orders
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    $stats['total_orders']   = (int)($row['total_orders'] ?? 0);
    $stats['pending_orders'] = (int)($row['pending_orders'] ?? 0);
    $stats['pending_count']  = $stats['pending_orders'];

    $row = $pdo->query("
        SELECT
            COUNT(*) AS today_orders,
            COALESCE(SUM(total),0) AS today_revenue
        FROM orders
        WHERE DATE(created_at) = CURDATE()
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    $stats['today_orders']  = (int)($row['today_orders'] ?? 0);
    $stats['today_revenue'] = (float)($row['today_revenue'] ?? 0);

    $row = $pdo->query("
        SELECT COALESCE(SUM(total),0) AS total_revenue
        FROM orders
        WHERE payment_status='paid'
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    $stats['total_revenue'] = (float)($row['total_revenue'] ?? 0);
} catch (Throwable $e) {
    error_log('[orders_process stats] ' . $e->getMessage());
}

/* =====================================================
   TAB COUNTS (not filtered)
===================================================== */
$statusCounts = [
    'all'        => (int)($stats['total_orders'] ?? 0),
    'pending'    => 0,
    'processing' => 0,
    'completed'  => 0,
    'cancelled'  => 0,
];

try {
    $rows = $pdo->query("
        SELECT LOWER(order_status) AS st, COUNT(*) AS cnt
        FROM orders
        GROUP BY LOWER(order_status)
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $k = (string)($r['st'] ?? '');
        if ($k !== '' && array_key_exists($k, $statusCounts)) {
            $statusCounts[$k] = (int)($r['cnt'] ?? 0);
        }
    }
} catch (Throwable $e) {
    error_log('[orders_process statusCounts] ' . $e->getMessage());
}

$filteredTotal = 0;
$orders = [];

$joinLatestPayment = "
    LEFT JOIN (
        SELECT p.order_id, p.payment_id, p.amount, p.payment_date, p.payment_method_id
        FROM payments p
        JOIN (
            SELECT order_id, MAX(payment_date) AS max_date
            FROM payments
            GROUP BY order_id
        ) x ON x.order_id = p.order_id AND x.max_date = p.payment_date
    ) lp ON lp.order_id = o.order_id
    LEFT JOIN payment_methods latest_pm ON latest_pm.method_id = lp.payment_method_id
";

$countSql = "
    SELECT COUNT(*) 
    FROM orders o
    LEFT JOIN users u ON u.user_id = o.user_id
    " . ($needMethodJoin ? $joinLatestPayment : '') . "
    $whereSql
";

$listSql = "
    SELECT
        o.order_id,
        o.total,
        o.order_status,
        o.payment_status,
        o.order_type,
        o.created_at,

        u.user_id AS user_id,
        COALESCE(u.name, u.email, 'Guest') AS customer_name,
        u.email AS customer_email,

        lp.payment_id,
        lp.amount AS paid_amount,
        lp.payment_date,
        latest_pm.method_code AS payment_method_code,
        latest_pm.method_name AS payment_method_name,

        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
    FROM orders o
    LEFT JOIN users u ON u.user_id = o.user_id
    $joinLatestPayment
    $whereSql
    ORDER BY $orderBy
    LIMIT :limit OFFSET :offset
";

try {
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $filteredTotal = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare($listSql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('[orders_process list/count] ' . $e->getMessage());
    $filteredTotal = 0;
    $orders = [];
}

$totalPages = max(1, (int)ceil($filteredTotal / $perPage));

/* =====================================================
   PAYMENT METHODS (for dropdown)
===================================================== */
$paymentMethodsList = [];
try {
    $paymentMethodsList = $pdo->query("
        SELECT method_id, method_code, method_name
        FROM payment_methods
        WHERE is_active = 1
        ORDER BY method_name
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $paymentMethodsList = [];
}

/* =====================================================
   TEMPLATE VARS
===================================================== */
$totalOrders  = (int)($stats['total_orders'] ?? 0);
$todayOrders  = (int)($stats['today_orders'] ?? 0);
$totalRevenue = (float)($stats['total_revenue'] ?? 0);
$todayRevenue = (float)($stats['today_revenue'] ?? 0);

// Ensure required keys exist
$statusCounts['all']        = $statusCounts['all'] ?? $totalOrders;
$statusCounts['pending']    = $statusCounts['pending'] ?? 0;
$statusCounts['processing'] = $statusCounts['processing'] ?? 0;
$statusCounts['completed']  = $statusCounts['completed'] ?? 0;
$statusCounts['cancelled']  = $statusCounts['cancelled'] ?? 0;
