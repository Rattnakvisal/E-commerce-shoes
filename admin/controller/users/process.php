<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   DB & SESSION
===================================================== */
$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection missing.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/Log/login.php');
    exit;
}

/* =====================================================
   ADMIN INFO
===================================================== */
$adminName      = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin';
$adminFirstName = htmlspecialchars(strtok(trim((string)$adminName), ' '), ENT_QUOTES, 'UTF-8');

/* =====================================================
   COLUMN EXISTENCE CACHE
===================================================== */
$columnCache = [];

function columnExists(string $column): bool
{
    global $pdo, $columnCache;

    if (array_key_exists($column, $columnCache)) {
        return (bool)$columnCache[$column];
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $stmt->execute([$column]);
        $exists = (bool)$stmt->fetch();
        $columnCache[$column] = $exists;
        return $exists;
    } catch (Throwable $e) {
        $columnCache[$column] = false;
        return false;
    }
}

/* =====================================================
   FILTERS (STRICT)
===================================================== */
$status = (string)($_GET['status'] ?? '');
$role   = (string)($_GET['role'] ?? '');

$filters = [
    'status' => in_array($status, ['active', 'inactive'], true) ? $status : '',
    'role'   => in_array($role, ['admin', 'staff', 'customer'], true) ? $role : '',
    'date_from' => (string)($_GET['date_from'] ?? ''),
    'date_to'   => (string)($_GET['date_to'] ?? ''),
    'search'    => trim((string)($_GET['search'] ?? '')),
    'sort'      => (string)($_GET['sort'] ?? 'newest'),
    'page'      => max(1, (int)($_GET['page'] ?? 1)),
];

$perPage = 15;
$offset  = ($filters['page'] - 1) * $perPage;

/* =====================================================
   COLUMN FLAGS
===================================================== */
$hasStatus    = columnExists('status');
$hasAvatar    = columnExists('avatar_url');
$hasLastLogin = columnExists('last_login');

/* =====================================================
   SAFE SQL CONDITIONS (NO COLLATION FUNCTIONS)
   - Your schema uses ENUM for role/status, so direct compare is best.
===================================================== */
$roleSql = "u.role"; // ENUM('admin','staff','customer')
$statusActiveSql   = "(u.status = 'active')";
$statusInactiveSql = "(u.status = 'inactive')";

/* =====================================================
   WHERE CLAUSE BUILDER
===================================================== */
$where  = [];
$params = [];

/* Status */
if ($hasStatus && $filters['status'] !== '') {
    $where[] = ($filters['status'] === 'active') ? $statusActiveSql : $statusInactiveSql;
}

/* Role */
if ($filters['role'] !== '') {
    $where[]  = "$roleSql = ?";
    $params[] = $filters['role'];
}

/* Date range */
if ($filters['date_from'] !== '') {
    $where[]  = 'DATE(u.created_at) >= ?';
    $params[] = $filters['date_from'];
}
if ($filters['date_to'] !== '') {
    $where[]  = 'DATE(u.created_at) <= ?';
    $params[] = $filters['date_to'];
}

/* Search */
if ($filters['search'] !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.user_id LIKE ?)';
    $like = '%' . $filters['search'] . '%';
    array_push($params, $like, $like, $like, $like);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* =====================================================
   FILTERED COUNT
===================================================== */
$filteredTotal = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users u $whereSql");
    foreach ($params as $i => $val) {
        $stmt->bindValue($i + 1, $val);
    }
    $stmt->execute();
    $filteredTotal = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('[users_count] ' . $e->getMessage());
}

/* =====================================================
   SORTING (SAFE)
===================================================== */
$orderBy = match ($filters['sort']) {
    'oldest'     => 'u.created_at ASC',
    'name_asc'   => 'u.name ASC',
    'name_desc'  => 'u.name DESC',
    'email_asc'  => 'u.email ASC',
    'email_desc' => 'u.email DESC',
    default      => 'u.created_at DESC',
};

/* =====================================================
   GLOBAL STATS
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
    $selectStats = [];
    $selectStats[] = "COUNT(*) AS total_count";
    $selectStats[] = "SUM($roleSql = 'admin') AS admin_count";
    $selectStats[] = "SUM($roleSql = 'staff') AS staff_count";
    $selectStats[] = "SUM($roleSql = 'customer') AS customer_count";

    if ($hasStatus) {
        $selectStats[] = "SUM($statusActiveSql) AS active_count";
        $selectStats[] = "SUM($statusInactiveSql) AS inactive_count";
    } else {
        $selectStats[] = "0 AS active_count";
        $selectStats[] = "0 AS inactive_count";
    }

    $stmt = $pdo->query("
        SELECT " . implode(",\n               ", $selectStats) . "
        FROM users u
    ");

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($row)) {
        $stats = array_merge($stats, $row);
    }
} catch (PDOException $e) {
    error_log('[users_stats] ' . $e->getMessage());
}

/* Normalize stats to int */
foreach ($stats as $k => $v) {
    $stats[$k] = (int)$v;
}

/* =====================================================
   USERS QUERY
===================================================== */
$select = [
    'u.user_id',
    'u.name',
    'u.email',
    'u.role',
    'u.created_at',
];

if ($hasStatus)    $select[] = 'u.status';
if ($hasAvatar)    $select[] = 'u.avatar_url';
if ($hasLastLogin) $select[] = 'u.last_login';

try {
    $stmt = $pdo->prepare("
        SELECT " . implode(', ', $select) . "
        FROM users u
        $whereSql
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ");

    foreach ($params as $i => $val) {
        $stmt->bindValue($i + 1, $val);
    }

    $stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset,  PDO::PARAM_INT);

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('[users_list] ' . $e->getMessage());
    $users = [];
}

/* =====================================================
   PAGINATION
===================================================== */
$totalUsers = $stats['total_count'];
$totalPages = max(1, (int)ceil($filteredTotal / $perPage));

/* =====================================================
   COUNTS FOR UI
===================================================== */
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
        SELECT COUNT(*) FROM users
        WHERE DATE(created_at) = CURDATE()
    ")->fetchColumn();
} catch (PDOException $e) {
    error_log('[users_today] ' . $e->getMessage());
}
