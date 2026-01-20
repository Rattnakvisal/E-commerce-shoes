<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   SESSION & AUTH
===================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    empty($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)
) {
    header('Location: ../auth/login.php');
    exit;
}

$status  = strtolower($o['order_status'] ?? '');
$payment = strtolower($o['payment_status'] ?? '');
/* =====================================================
   FILTER INPUTS
===================================================== */
$filters = [
    'status'     => $_GET['status'] ?? '',
    'payment'    => $_GET['payment'] ?? '',
    'type'       => $_GET['type'] ?? '',
    'date_from'  => $_GET['date_from'] ?? '',
    'date_to'    => $_GET['date_to'] ?? '',
    'search'     => trim($_GET['search'] ?? ''),
    'sort'       => $_GET['sort'] ?? 'newest',
];

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

/* =====================================================
   BUILD QUERY
===================================================== */
$where = [];
$params = [];

if ($filters['status']) {
    $where[] = 'o.order_status = ?';
    $params[] = $filters['status'];
}
if ($filters['payment']) {
    $where[] = 'o.payment_status = ?';
    $params[] = $filters['payment'];
}
if ($filters['type']) {
    $where[] = 'o.order_type = ?';
    $params[] = $filters['type'];
}
if ($filters['date_from']) {
    $where[] = 'DATE(o.created_at) >= ?';
    $params[] = $filters['date_from'];
}
if ($filters['date_to']) {
    $where[] = 'DATE(o.created_at) <= ?';
    $params[] = $filters['date_to'];
}
if ($filters['search']) {
    $where[] = '(o.order_id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
    $term = '%' . $filters['search'] . '%';
    array_push($params, $term, $term, $term);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderBy = match ($filters['sort']) {
    'oldest'      => 'o.created_at ASC',
    'total_asc'   => 'o.total ASC',
    'total_desc'  => 'o.total DESC',
    default       => 'o.created_at DESC',
};

/* =====================================================
   FETCH STATS
===================================================== */
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(o.order_status='pending') pending
    FROM orders o
");
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalOrders = (int)$stats['total'];

$stats['pending_count'] = (int)($stats['pending'] ?? 0);

// Today's orders and revenue
$todayStmt = $pdo->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS revenue FROM orders WHERE DATE(created_at) = CURDATE()");
$todayStmt->execute();
$today = $todayStmt->fetch(PDO::FETCH_ASSOC);
$todayOrders = (int)($today['cnt'] ?? 0);
$todayRevenue = (float)($today['revenue'] ?? 0);

// Total revenue from paid orders
$revStmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) AS total_revenue FROM orders WHERE payment_status = 'paid'");
$revStmt->execute();
$rev = $revStmt->fetch(PDO::FETCH_ASSOC);
$totalRevenue = (float)($rev['total_revenue'] ?? 0.0);

// Status counts for filter tabs
$statusCounts = [
    'all' => $totalOrders,
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0,
];
$scStmt = $pdo->prepare("SELECT order_status, COUNT(*) AS cnt FROM orders GROUP BY order_status");
$scStmt->execute();
foreach ($scStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $k = $r['order_status'] ?? '';
    if ($k && array_key_exists($k, $statusCounts)) {
        $statusCounts[$k] = (int)$r['cnt'];
    }
}

/* =====================================================
   FETCH ORDERS
===================================================== */
$listStmt = $pdo->prepare("
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
        (
            SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.order_id
        ) item_count
    FROM orders o
    LEFT JOIN users u ON u.user_id=o.user_id
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$listStmt->execute($params);
$orders = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = (int)ceil($totalOrders / $perPage);
