<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   DB + SESSION + AUTH
===================================================== */
$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection missing.');
}

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id']) || (string)($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/Log/login.php');
    exit;
}

/* =====================================================
   ADMIN INFO
===================================================== */
$adminName      = (string)($_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin');
$adminFirstName = htmlspecialchars(strtok(trim($adminName), ' '), ENT_QUOTES, 'UTF-8');

/* =====================================================
   COLUMN CACHE
===================================================== */
$__colCache = [];

function userColExists(string $col): bool
{
    global $pdo, $__colCache;

    if (isset($__colCache[$col])) return $__colCache[$col];

    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `users` LIKE ?");
        $st->execute([$col]);
        return $__colCache[$col] = (bool)$st->fetch();
    } catch (Throwable) {
        return $__colCache[$col] = false;
    }
}

/* =====================================================
   INPUT HELPERS
===================================================== */
function safeEnum(?string $value, array $allowed): string
{
    $v = strtolower(trim((string)$value));
    return in_array($v, $allowed, true) ? $v : '';
}

function safeDateYmd(?string $value): string
{
    $v = trim((string)$value);
    if ($v === '') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $v);
    return ($dt && $dt->format('Y-m-d') === $v) ? $v : '';
}

function safeSort(?string $value): string
{
    $v = (string)$value;
    $allowed = ['newest', 'oldest', 'name_asc', 'name_desc', 'email_asc', 'email_desc'];
    return in_array($v, $allowed, true) ? $v : 'newest';
}

/* =====================================================
   FLAGS
===================================================== */
$hasStatus    = userColExists('status');
$hasAvatar    = userColExists('avatar_url');
$hasLastLogin = userColExists('last_login');
$hasPhone     = userColExists('phone');

/* =====================================================
   FILTERS (STRICT)
===================================================== */
$filters = [
    'status'    => $hasStatus ? safeEnum($_GET['status'] ?? '', ['active', 'inactive']) : '',
    'role'      => safeEnum($_GET['role'] ?? '', ['admin', 'staff', 'customer']),
    'date_from' => safeDateYmd($_GET['date_from'] ?? ''),
    'date_to'   => safeDateYmd($_GET['date_to'] ?? ''),
    'search'    => trim((string)($_GET['search'] ?? '')),
    'sort'      => safeSort($_GET['sort'] ?? 'newest'),
    'page'      => max(1, (int)($_GET['page'] ?? 1)),
];

$perPage = 15;
$offset  = ($filters['page'] - 1) * $perPage;

/* =====================================================
   WHERE BUILDER
===================================================== */
$where = [];
$params = [];

if ($filters['status'] !== '') {
    $where[] = "u.`status` = ?";
    $params[] = $filters['status'];
}

if ($filters['role'] !== '') {
    $where[]  = "u.`role` = ?";
    $params[] = $filters['role'];
}

if ($filters['date_from'] !== '') {
    $where[]  = "DATE(u.`created_at`) >= ?";
    $params[] = $filters['date_from'];
}

if ($filters['date_to'] !== '') {
    $where[]  = "DATE(u.`created_at`) <= ?";
    $params[] = $filters['date_to'];
}

if ($filters['search'] !== '') {
    $like = '%' . $filters['search'] . '%';

    $searchCols = [
        "u.`name` LIKE ?",
        "u.`email` LIKE ?",
        "CAST(u.`user_id` AS CHAR) LIKE ?",
    ];

    if ($hasPhone) $searchCols[] = "u.`phone` LIKE ?";

    $where[] = '(' . implode(' OR ', $searchCols) . ')';

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    if ($hasPhone) $params[] = $like;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* =====================================================
   ORDER BY (SAFE)
===================================================== */
$orderBy = match ($filters['sort']) {
    'oldest'     => 'u.`created_at` ASC',
    'name_asc'   => 'u.`name` ASC',
    'name_desc'  => 'u.`name` DESC',
    'email_asc'  => 'u.`email` ASC',
    'email_desc' => 'u.`email` DESC',
    default      => 'u.`created_at` DESC',
};

/* =====================================================
   FILTERED COUNT (pagination)
===================================================== */
$filteredTotal = 0;
try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM `users` u $whereSql");
    $st->execute($params);
    $filteredTotal = (int)$st->fetchColumn();
} catch (PDOException $e) {
    error_log('[users_count] ' . $e->getMessage());
}

/* =====================================================
   GLOBAL STATS (no filters)
===================================================== */
$stats = [
    'total_count'    => 0,
    'active_count'   => 0,
    'inactive_count' => 0,
    'admin_count'    => 0,
    'staff_count'    => 0,
    'customer_count' => 0,
];

try {
    $parts = [
        "COUNT(*) AS total_count",
        "SUM(u.`role` = 'admin') AS admin_count",
        "SUM(u.`role` = 'staff') AS staff_count",
        "SUM(u.`role` = 'customer') AS customer_count",
    ];

    if ($hasStatus) {
        $parts[] = "SUM(u.`status` = 'active') AS active_count";
        $parts[] = "SUM(u.`status` = 'inactive') AS inactive_count";
    } else {
        $parts[] = "0 AS active_count";
        $parts[] = "0 AS inactive_count";
    }

    $row = $pdo->query("SELECT " . implode(', ', $parts) . " FROM `users` u")->fetch(PDO::FETCH_ASSOC);
    if ($row) $stats = array_merge($stats, $row);
} catch (PDOException $e) {
    error_log('[users_stats] ' . $e->getMessage());
}

foreach ($stats as $k => $v) $stats[$k] = (int)$v;
$totalUsers = $stats['total_count'];

/* =====================================================
   USERS LIST
===================================================== */
$select = [
    "u.`user_id`",
    "u.`name`",
    "u.`email`",
    "u.`role`",
    "u.`created_at`",
];

if ($hasPhone)     $select[] = "u.`phone`";
if ($hasStatus)    $select[] = "u.`status`";
if ($hasAvatar)    $select[] = "u.`avatar_url`";
if ($hasLastLogin) $select[] = "u.`last_login`";

$users = [];
try {
    $sql = "
        SELECT " . implode(', ', $select) . "
        FROM `users` u
        $whereSql
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ";

    $st = $pdo->prepare($sql);

    // bind filter params
    $i = 1;
    foreach ($params as $p) {
        $st->bindValue($i++, $p);
    }

    $st->bindValue($i++, $perPage, PDO::PARAM_INT);
    $st->bindValue($i++, $offset, PDO::PARAM_INT);

    $st->execute();
    $users = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('[users_list] ' . $e->getMessage());
}

/* =====================================================
   PAGINATION + UI COUNTS
===================================================== */
$totalPages = max(1, (int)ceil($filteredTotal / $perPage));

$statusCounts = [
    'all'      => $stats['total_count'],
    'active'   => $stats['active_count'],
    'inactive' => $stats['inactive_count'],
];

$roleCounts = [
    'admin'    => $stats['admin_count'],
    'staff'    => $stats['staff_count'],
    'customer' => $stats['customer_count'],
];

/* =====================================================
   TODAY USERS
===================================================== */
$todayUsers = 0;
try {
    $todayUsers = (int)$pdo->query("
        SELECT COUNT(*) FROM `users`
        WHERE DATE(`created_at`) = CURDATE()
    ")->fetchColumn();
} catch (PDOException $e) {
    error_log('[users_today] ' . $e->getMessage());
}
