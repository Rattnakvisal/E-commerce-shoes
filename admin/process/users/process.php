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
$adminName = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin';
$adminFirstName = htmlspecialchars(strtok(trim($adminName), ' '));

/* =====================================================
   COLUMN EXISTENCE (CACHED)
===================================================== */
$columnCache = [];

function columnExists(string $column): bool
{
    global $pdo, $columnCache;

    if (isset($columnCache[$column])) {
        return $columnCache[$column];
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $stmt->execute([$column]);
        return $columnCache[$column] = (bool)$stmt->fetch();
    } catch (Throwable) {
        return $columnCache[$column] = false;
    }
}

/* =====================================================
   FILTERS
===================================================== */
$filters = [
    'status'     => strtolower($_GET['status'] ?? ''),
    'role'       => $_GET['role'] ?? '',
    'date_from'  => $_GET['date_from'] ?? '',
    'date_to'    => $_GET['date_to'] ?? '',
    'search'     => trim($_GET['search'] ?? ''),
    'sort'       => $_GET['sort'] ?? 'newest',
    'page'       => max(1, (int)($_GET['page'] ?? 1)),
];

$perPage = 15;
$offset  = ($filters['page'] - 1) * $perPage;

/* =====================================================
   WHERE CLAUSE BUILDER
===================================================== */
$where   = [];
$params  = [];

$hasStatus    = columnExists('status');
$hasAvatar    = columnExists('avatar_url');
$hasLastLogin = columnExists('last_login');

/* Status */
if ($hasStatus && in_array($filters['status'], ['active', 'inactive'], true)) {
    $where[]  = 'LOWER(u.status) = ?';
    $params[] = $filters['status'];
}

/* Role */
if (in_array($filters['role'], ['admin', 'staff', 'customer'], true)) {
    $where[]  = 'u.role = ?';
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
    $search  = "%{$filters['search']}%";
    array_push($params, $search, $search, $search, $search);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

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
   STATS QUERY
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
    $selectStats = [
        'COUNT(*) AS total_count',
        "SUM(CASE WHEN u.role = 'admin' THEN 1 ELSE 0 END) AS admin_count",
        "SUM(CASE WHEN u.role = 'staff' THEN 1 ELSE 0 END) AS staff_count",
        "SUM(CASE WHEN u.role = 'customer' THEN 1 ELSE 0 END) AS customer_count",
    ];

    if ($hasStatus) {
        $selectStats[] = "SUM(CASE WHEN (
            LOWER(COALESCE(u.status, '')) IN ('active','enabled','enable','true','yes','y','1')
            OR u.status = 1
        ) THEN 1 ELSE 0 END) AS active_count";

        $selectStats[] = "SUM(CASE WHEN (
            LOWER(COALESCE(u.status, '')) IN ('inactive','disabled','disable','false','no','n','0')
            OR u.status = 0
        ) THEN 1 ELSE 0 END) AS inactive_count";
    }

    $stmt = $pdo->prepare(
        "SELECT " . implode(', ', $selectStats) . " FROM users u"
    );

    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;
} catch (PDOException $e) {
    error_log('[users_stats] ' . $e->getMessage());
}

/* =====================================================
   USERS QUERY
===================================================== */
$users = [];

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

try {
    $stmt = $pdo->prepare("
        SELECT " . implode(', ', $select) . "
        FROM users u
        $whereSql
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $i => $val) {
        $stmt->bindValue($i + 1, $val);
    }

    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[users_list] ' . $e->getMessage());
}

/* =====================================================
   PAGINATION
===================================================== */
$totalUsers = (int)($stats['total_count'] ?? 0);
$totalPages = max(1, (int)ceil($totalUsers / $perPage));

/* =====================================================
   STATUS & ROLE COUNTS
===================================================== */
$statusCounts = [
    'all'      => $totalUsers,
    'active'   => (int)($stats['active_count'] ?? 0),
    'inactive' => (int)($stats['inactive_count'] ?? 0),
];

$roleCounts = [
    'admin'    => (int)($stats['admin_count'] ?? 0),
    'staff'    => (int)($stats['staff_count'] ?? 0),
    'customer' => (int)($stats['customer_count'] ?? 0),
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
    error_log('[today_users] ' . $e->getMessage());
}
