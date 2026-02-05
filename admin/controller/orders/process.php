<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    die('Database connection missing.');
}

/* ================= AUTH ================= */
$userId = (int)($_SESSION['user_id'] ?? 0);
$role   = (string)($_SESSION['role'] ?? '');

if ($userId <= 0 || !in_array($role, ['admin', 'staff'], true)) {
    header('Location: /E-commerce-shoes/admin/Log/login.php');
    exit;
}

/* ================= HELPERS ================= */
function s(string $k, string $d = ''): string
{
    return trim((string)($_GET[$k] ?? $d));
}
function sl(string $k, string $d = ''): string
{
    return strtolower(s($k, $d));
}
function allow(string $v, array $allowed): string
{
    return in_array($v, $allowed, true) ? $v : '';
}

/* ================= FILTERS ================= */
$filters = [
    'status'    => allow(sl('status'),  ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', '']),
    'payment'   => allow(sl('payment'), ['paid', 'unpaid', 'refunded', 'pending', 'failed', '']),
    'type'      => allow(sl('type'),    ['pos', 'online', '']),
    'search'    => s('search'),
    'date_from' => s('date_from'),
    'date_to'   => s('date_to'),
    'sort'      => allow(sl('sort', 'newest'), ['newest', 'oldest', 'total_asc', 'total_desc']),
];

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

/* ================= WHERE ================= */
$where = [];
$params = [];

if ($filters['status'] !== '') {
    $where[] = 'o.order_status = :st';
    $params[':st'] = $filters['status'];
}
if ($filters['payment'] !== '') {
    $where[] = 'o.payment_status = :ps';
    $params[':ps'] = $filters['payment'];
}
if ($filters['type'] !== '') {
    $where[] = 'o.order_type = :tp';
    $params[':tp'] = $filters['type'];
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

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$orderBy = match ($filters['sort']) {
    'oldest'     => 'o.created_at ASC',
    'total_asc'  => 'o.total ASC',
    'total_desc' => 'o.total DESC',
    default      => 'o.created_at DESC',
};

/* ================= STATS ================= */
$stats = [
    'total_orders' => 0,
    'pending_count' => 0,
    'today_orders' => 0,
    'today_revenue' => 0.0,
    'total_revenue' => 0.0,
];

try {
    $row = $pdo->query("SELECT COUNT(*) total_orders, SUM(order_status='pending') pending_count FROM orders")
        ->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['total_orders'] = (int)($row['total_orders'] ?? 0);
    $stats['pending_count'] = (int)($row['pending_count'] ?? 0);

    $row = $pdo->query("
        SELECT COUNT(*) today_orders, COALESCE(SUM(total),0) today_revenue
        FROM orders
        WHERE DATE(created_at)=CURDATE()
    ")->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['today_orders'] = (int)($row['today_orders'] ?? 0);
    $stats['today_revenue'] = (float)($row['today_revenue'] ?? 0);

    $row = $pdo->query("SELECT COALESCE(SUM(total),0) total_revenue FROM orders WHERE payment_status='paid'")
        ->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['total_revenue'] = (float)($row['total_revenue'] ?? 0);
} catch (Throwable $e) {
    error_log('[process_orders stats] ' . $e->getMessage());
}

/* ================= TAB COUNTS ================= */
$statusCounts = [
    'all'        => (int)$stats['total_orders'],
    'pending'    => 0,
    'processing' => 0,
    'shipped'    => 0,
    'delivered'  => 0,
    'completed'  => 0,
    'cancelled'  => 0,
];

try {
    $rows = $pdo->query("SELECT LOWER(order_status) st, COUNT(*) cnt FROM orders GROUP BY LOWER(order_status)")
        ->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as $r) {
        $k = (string)($r['st'] ?? '');
        if ($k !== '' && array_key_exists($k, $statusCounts)) {
            $statusCounts[$k] = (int)($r['cnt'] ?? 0);
        }
    }
} catch (Throwable $e) {
    error_log('[process_orders tabcounts] ' . $e->getMessage());
}

/* ================= QUERY (latest payment method) ================= */
$joinLatestPayment = "
LEFT JOIN (
  SELECT p1.*
  FROM payments p1
  JOIN (
    SELECT order_id, MAX(payment_date) max_date
    FROM payments
    GROUP BY order_id
  ) x ON x.order_id = p1.order_id AND x.max_date = p1.payment_date
) lp ON lp.order_id = o.order_id
LEFT JOIN payment_methods pm ON pm.method_id = lp.payment_method_id
";

/* count */
$countSql = "
SELECT COUNT(*)
FROM orders o
LEFT JOIN users u ON u.user_id = o.user_id
$joinLatestPayment
$whereSql
";

/* list */
$listSql = "
SELECT
  o.order_id, o.user_id, o.total, o.order_status, o.payment_status, o.order_type, o.created_at,
  COALESCE(u.name, u.email, 'Guest') customer_name,
  u.email customer_email,
  lp.amount paid_amount,
  lp.payment_date,
  pm.method_code payment_method_code,
  pm.method_name payment_method_name
FROM orders o
LEFT JOIN users u ON u.user_id = o.user_id
$joinLatestPayment
$whereSql
ORDER BY $orderBy
LIMIT :limit OFFSET :offset
";

$filteredTotal = 0;
$orders = [];

try {
    $st = $pdo->prepare($countSql);
    $st->execute($params);
    $filteredTotal = (int)$st->fetchColumn();

    $st = $pdo->prepare($listSql);
    foreach ($params as $k => $v) $st->bindValue($k, $v);
    $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $st->bindValue(':offset', $offset, PDO::PARAM_INT);
    $st->execute();
    $orders = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('[process_orders list] ' . $e->getMessage());
}

$totalPages = max(1, (int)ceil($filteredTotal / $perPage));

$totalOrders  = (int)$stats['total_orders'];
$todayOrders  = (int)$stats['today_orders'];
$totalRevenue = (float)$stats['total_revenue'];
$todayRevenue = (float)$stats['today_revenue'];
