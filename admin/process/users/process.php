<?php
require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   DB & SESSION
===================================================== */
$pdo ??= $conn ?? null;
if (!$pdo) {
    die('Database connection missing.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

/* =====================================================
   ADMIN INFO
===================================================== */
$adminName      = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin';
$adminFirstName = htmlspecialchars(strtok(trim($adminName), ' '));

/* =====================================================
   COLUMN EXISTENCE CACHE
===================================================== */
$columnCache = [];

function columnExists(string $column): bool
{
    global $pdo, $columnCache;

    if (array_key_exists($column, $columnCache)) {
        return $columnCache[$column];
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $stmt->execute([$column]);
        return $columnCache[$column] = (bool) $stmt->fetch();
    } catch (Throwable) {
        return $columnCache[$column] = false;
    }
}

/* =====================================================
   FILTERS (STRICT)
===================================================== */
$filters = [
    'status' => in_array($_GET['status'] ?? '', ['active', 'inactive'], true)
        ? $_GET['status']
        : '',
    'role' => in_array($_GET['role'] ?? '', ['admin', 'staff', 'customer'], true)
        ? $_GET['role']
        : '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to'] ?? '',
    'search'    => trim($_GET['search'] ?? ''),
    'sort'      => $_GET['sort'] ?? 'newest',
    'page'      => max(1, (int) ($_GET['page'] ?? 1)),
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
   SQL NORMALIZERS
===================================================== */
$roleSql = "LOWER(TRIM(REPLACE(COALESCE(u.role,''), CHAR(160), '')))";
$statusActiveSql = "
    LOWER(COALESCE(u.status,'')) IN ('active','enabled','enable','true','yes','y','1')
    OR u.status = 1
";
$statusInactiveSql = "
    LOWER(COALESCE(u.status,'')) IN ('inactive','disabled','disable','false','no','n','0')
    OR u.status = 0
";

/* =====================================================
   WHERE CLAUSE BUILDER
===================================================== */
$where  = [];
$params = [];

/* Status */
if ($hasStatus && $filters['status']) {
    $where[] = $filters['status'] === 'active'
        ? "($statusActiveSql)"
        : "($statusInactiveSql)";
}

/* Role */
if ($filters['role']) {
    $where[]  = "$roleSql = ?";
    $params[] = $filters['role'];
}

/* Date range */
if ($filters['date_from']) {
    $where[]  = 'DATE(u.created_at) >= ?';
    $params[] = $filters['date_from'];
}
if ($filters['date_to']) {
    $where[]  = 'DATE(u.created_at) <= ?';
    $params[] = $filters['date_to'];
}

/* Search */
if ($filters['search']) {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.user_id LIKE ?)';
    $like = '%' . $filters['search'] . '%';
    array_push($params, $like, $like, $like, $like);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* =====================================================
   FILTERED COUNT
===================================================== */
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users u $whereSql");
    foreach ($params as $i => $val) {
        $stmt->bindValue($i + 1, $val);
    }
    $stmt->execute();
    $filteredTotal = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('[users_count] ' . $e->getMessage());
    $filteredTotal = 0;
}

/* =====================================================
   SORTING
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
    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_count,
            SUM($roleSql = 'admin')    AS admin_count,
            SUM($roleSql = 'staff')    AS staff_count,
            SUM($roleSql = 'customer') AS customer_count,
            SUM($statusActiveSql)      AS active_count,
            SUM($statusInactiveSql)    AS inactive_count
        FROM users u
    ");

    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;
} catch (PDOException $e) {
    error_log('[users_stats] ' . $e->getMessage());
}

/* Normalize stats */
foreach ($stats as $k => $v) {
    $stats[$k] = (int) $v;
}

/* =====================================================
   USERS QUERY
===================================================== */
$select = [
    'u.user_id',
    'u.name',
    'u.email',
    'u.phone',
    'u.role',
    'u.created_at',
];

if ($hasStatus)    $select[] = 'u.status';
if ($hasAvatar)    $select[] = 'u.avatar_url';
if ($hasLastLogin) $select[] = 'u.last_login';

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

// Bind limit and offset as positional params after any filter params
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset,  PDO::PARAM_INT);

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   PAGINATION
===================================================== */
$totalUsers  = $stats['total_count'];
$totalPages  = max(1, (int) ceil($filteredTotal / $perPage));

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
$todayUsers = (int) $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE DATE(created_at) = CURDATE()
")->fetchColumn();
