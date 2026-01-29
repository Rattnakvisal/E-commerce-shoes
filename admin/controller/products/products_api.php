<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';
$pdo = $pdo ?? ($conn ?? null);

if (!$pdo instanceof PDO) {
    die('Database connection missing.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow admin + staff
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ../auth/Log/login.php');
    exit;
}

/* ================= FLASH ================= */
$message = (string)($_SESSION['message'] ?? '');
unset($_SESSION['message']);

$error = '';

/* ================= FILTERS ================= */
$search      = trim((string)($_GET['search'] ?? ''));
$category_id = (string)($_GET['category_id'] ?? '');
$status      = strtolower(trim((string)($_GET['status'] ?? '')));
$brand       = trim((string)($_GET['brand'] ?? ''));
$date_from   = (string)($_GET['date_from'] ?? '');
$date_to     = (string)($_GET['date_to'] ?? '');
$sort        = (string)($_GET['sort'] ?? 'newest');

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

/* ================= SORT (SAFE) ================= */
$sortMap = [
    'newest'     => 'p.created_at DESC',
    'oldest'     => 'p.created_at ASC',
    'price_high' => 'p.price DESC',
    'price_low'  => 'p.price ASC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['newest'];

/* ================= DATA ================= */
$products      = [];
$categories    = [];
$totalProducts = 0;
$totalPages    = 1;

try {
    /* ================= CATEGORIES ================= */
    $categories = $pdo->query("
        SELECT category_id, category_name
        FROM categories
        ORDER BY category_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    /* ================= OPTIONAL: BACKWARD COMPAT =================*/
    if ($brand === '' && $status !== '' && !in_array($status, ['active', 'inactive'], true)) {
        $brand = (string)($_GET['status'] ?? '');
        $status = '';
    }

    /* ================= WHERE ================= */
    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(p.name LIKE :s OR p.description LIKE :s)";
        $params[':s'] = "%{$search}%";
    }

    if ($category_id !== '') {
        $where[] = "p.category_id = :cat";
        $params[':cat'] = (int)$category_id;
    }

    if (in_array($status, ['active', 'inactive'], true)) {
        $where[] = "p.status = :st";
        $params[':st'] = $status;
    }

    if ($brand !== '') {
        $where[] = "c.category_name = :brand";
        $params[':brand'] = $brand;
    }

    if ($date_from !== '') {
        $where[] = "p.created_at >= :df";
        $params[':df'] = $date_from . ' 00:00:00';
    }

    if ($date_to !== '') {
        $where[] = "p.created_at <= :dt";
        $params[':dt'] = $date_to . ' 23:59:59';
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    /* ================= COUNT ================= */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        $whereSql
    ");
    $stmt->execute($params);
    $totalProducts = (int)$stmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalProducts / $limit));

    /* ================= PRODUCTS ================= */
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.category_name,
            p.image_url
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        $whereSql
        ORDER BY $orderBy
        LIMIT :lim OFFSET :off
    ");

    // bind normal params
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    // bind limit/offset safely
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize expected keys (optional, only if your DB columns are inconsistent)
    foreach ($products as &$p) {
        $p['name']        = (string)($p['name'] ?? $p['NAME'] ?? '');
        $p['description'] = (string)($p['description'] ?? $p['DESCRIPTION'] ?? '');
        $p['sku']         = (string)($p['sku'] ?? $p['SKU'] ?? '');
        $p['price']       = (float)($p['price'] ?? $p['PRICE'] ?? 0);
        $p['cost']        = $p['cost'] ?? $p['COST'] ?? null;
        $p['stock']       = (int)($p['stock'] ?? $p['STOCK'] ?? 0);
        $p['status']      = (string)($p['status'] ?? $p['STATUS'] ?? 'inactive');
        $p['image_url']   = $p['image_url'] ?? $p['IMAGE_URL'] ?? null;
        $p['category_name'] = $p['category_name'] ?? $p['CATEGORY_NAME'] ?? null;
    }
    unset($p);

    /* ================= STATS (GLOBAL + BRAND COUNTS) ================= */
    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN p.status='active' THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN p.status='inactive' THEN 1 ELSE 0 END) AS inactive,
            SUM(CASE WHEN p.stock=0 THEN 1 ELSE 0 END) AS out_of_stock,
            SUM(CASE WHEN c.category_name='Nike' THEN 1 ELSE 0 END) AS Nike,
            SUM(CASE WHEN c.category_name='Adidas' THEN 1 ELSE 0 END) AS Adidas,
            SUM(CASE WHEN c.category_name='New Balance' THEN 1 ELSE 0 END) AS `New Balance`,
            SUM(CASE WHEN c.category_name='Other' THEN 1 ELSE 0 END) AS Other,
            COALESCE(SUM(p.stock),0) AS total_stock
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $error = 'Database error';
    error_log('[products.php] ' . $e->getMessage());
    $stats = [];
}

/* ================= STATUS COUNTS ================= */
$statusCounts = [
    'all'         => (int)($stats['total'] ?? 0),
    'active'      => (int)($stats['active'] ?? 0),
    'inactive'    => (int)($stats['inactive'] ?? 0),
    'out_of_stock' => (int)($stats['out_of_stock'] ?? 0),
    'Nike'        => (int)($stats['Nike'] ?? 0),
    'Adidas'      => (int)($stats['Adidas'] ?? 0),
    'New Balance' => (int)($stats['New Balance'] ?? 0),
    'Other'       => (int)($stats['Other'] ?? 0),
];

/* ================= SAFETY ================= */
$products   = $products ?: [];
$categories = $categories ?: [];
