<?php
require_once __DIR__ . '/../../../config/conn.php';
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

/* ================= FLASH MESSAGE ================= */
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$error = '';

/* ================= FILTERS ================= */
$search      = trim($_GET['search'] ?? '');
$category_id = $_GET['category_id'] ?? '';
$status      = strtolower($_GET['status'] ?? '');
$date_from   = $_GET['date_from'] ?? '';
$date_to     = $_GET['date_to'] ?? '';
$sort        = $_GET['sort'] ?? 'newest';

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

/* ================= DATA ================= */
$products   = [];
$categories = [];
$stats = [
    'total'       => 0,
    'active'      => 0,
    'inactive'    => 0,
    'total_stock' => 0,
];

try {
    /* ================= CATEGORIES ================= */
    $categories = $pdo
        ->query("SELECT category_id, category_name FROM categories ORDER BY category_name")
        ->fetchAll(PDO::FETCH_ASSOC);

    /* ================= WHERE ================= */
    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    if ($category_id !== '') {
        $where[] = "p.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    if (in_array($status, ['active', 'inactive'], true)) {
        $where[] = "p.status = :status";
        $params[':status'] = $status;
    }

    if ($date_from) {
        $where[] = "p.created_at >= :date_from";
        $params[':date_from'] = $date_from . ' 00:00:00';
    }

    if ($date_to) {
        $where[] = "p.created_at <= :date_to";
        $params[':date_to'] = $date_to . ' 23:59:59';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    /* ================= SORT ================= */
    $orderBy = match ($sort) {
        'oldest'     => 'p.created_at ASC',
        'price_high' => 'p.price DESC',
        'price_low'  => 'p.price ASC',
        default      => 'p.created_at DESC',
    };

    /* ================= COUNT (FILTERED) ================= */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
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
        LEFT JOIN categories c ON p.category_id = c.category_id
        $whereSql
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ================= STATS (GLOBAL) ================= */
    $stats = $pdo->query("
        SELECT
            COUNT(*) total,
            SUM(status = 'active') active,
            SUM(status = 'inactive') inactive,
            COALESCE(SUM(stock),0) total_stock
        FROM products
    ")->fetch(PDO::FETCH_ASSOC);

    $stats = array_map('intval', $stats);
} catch (PDOException $e) {
    $error = 'Database error';
    error_log('[products] ' . $e->getMessage());
}

/* ================= STATUS COUNTS ================= */
$statusCounts = [
    'all'      => (int)($stats['total'] ?? 0),
    'active'   => (int)($stats['active'] ?? 0),
    'inactive' => (int)($stats['inactive'] ?? 0),
];

/* ================= SAFETY ================= */
$products      = $products ?? [];
$categories    = $categories ?? [];
$totalProducts = $totalProducts ?? 0;
$totalPages    = $totalPages ?? 1;
