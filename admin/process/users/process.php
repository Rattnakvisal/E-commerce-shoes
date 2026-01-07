<?php require_once __DIR__ . '/../../../config/conn.php';
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

/* =====================================================
   SESSION & AUTH
===================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

/* =====================================================
   ADMIN INFO
===================================================== */
$adminName = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin';
$adminFirstName = htmlspecialchars(explode(' ', trim($adminName))[0]);

/* =====================================================
   GET FILTERS FROM URL
===================================================== */
$filters = [
    'status' => $_GET['status'] ?? '',
    'role' => $_GET['role'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest',
    'page' => isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1
];

/* =====================================================
   BUILD SQL QUERY WITH FILTERS
===================================================== */
$whereClauses = [];
$params = [];

// helper to check for optional columns
function columnExists(string $col): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $stmt->execute([$col]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

// Status filter (only apply if `status` column exists)
$hasStatus = columnExists('status');
if ($hasStatus && $filters['status'] && in_array($filters['status'], ['active', 'inactive'])) {
    $whereClauses[] = "u.status = ?";
    $params[] = $filters['status'];
} else {
    // If the schema doesn't have status, ignore status filter
    if (!$hasStatus) {
        $filters['status'] = '';
    }
}

// Role filter
if ($filters['role'] && in_array($filters['role'], ['admin', 'staff', 'customer'])) {
    $whereClauses[] = "u.role = ?";
    $params[] = $filters['role'];
}

// Date range filter
if ($filters['date_from']) {
    $whereClauses[] = "DATE(u.created_at) >= ?";
    $params[] = $filters['date_from'];
}
if ($filters['date_to']) {
    $whereClauses[] = "DATE(u.created_at) <= ?";
    $params[] = $filters['date_to'];
}

// Search filter
if ($filters['search']) {
    $whereClauses[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.user_id LIKE ?)";
    $searchTerm = "%{$filters['search']}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Build WHERE clause
$whereSql = $whereClauses ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Sorting
$orderBy = match ($filters['sort']) {
    'oldest' => "u.created_at ASC",
    'name_asc' => "u.name ASC",
    'name_desc' => "u.name DESC",
    'email_asc' => "u.email ASC",
    'email_desc' => "u.email DESC",
    default => "u.created_at DESC"
};

/* =====================================================
   FETCH USERS WITH FILTERS
===================================================== */
$users = [];
$totalUsers = 0;
$stats = [];
$perPage = 15;
$offset = ($filters['page'] - 1) * $perPage;
$totalPages = 1;

try {
    $countSelect = ["COUNT(*) as total_count"];
    if ($hasStatus) {
        $countSelect[] = "SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active_count";
        $countSelect[] = "SUM(CASE WHEN u.status = 'inactive' THEN 1 ELSE 0 END) as inactive_count";
    } else {
        $countSelect[] = "0 as active_count";
        $countSelect[] = "0 as inactive_count";
    }
    $countSelect[] = "SUM(CASE WHEN u.role = 'admin' THEN 1 ELSE 0 END) as admin_count";
    $countSelect[] = "SUM(CASE WHEN u.role = 'staff' THEN 1 ELSE 0 END) as staff_count";
    $countSelect[] = "SUM(CASE WHEN u.role = 'customer' THEN 1 ELSE 0 END) as customer_count";

    $countQuery = "SELECT\n            " . implode(",\n            ", $countSelect) . "\n        FROM users u\n        $whereSql\n    ";

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $stats = $countStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $totalUsers = $stats['total_count'] ?? 0;

    // Fetch users with pagination. Only select optional columns if they exist.
    $hasLastLogin = columnExists('last_login');
    $hasAvatar = columnExists('avatar_url');

    $selectExtras = [];
    if ($hasStatus) $selectExtras[] = 'u.status';
    if ($hasLastLogin) $selectExtras[] = 'u.last_login';
    if ($hasAvatar) $selectExtras[] = 'u.avatar_url';

    $selectSql = "u.user_id, u.name, u.email, u.phone, u.role" . (empty($selectExtras) ? '' : ', ' . implode(', ', $selectExtras)) . ', u.created_at';

    $query = "SELECT $selectSql FROM users u\n        $whereSql\n        ORDER BY $orderBy\n        LIMIT $perPage OFFSET $offset";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($totalUsers) && !empty($users)) {
        $totalUsers = count($users);
    }

    // Calculate total pages (ensure at least 1)
    $totalPages = max(1, (int)ceil($totalUsers / $perPage));
} catch (PDOException $e) {
    error_log('[users_list] ' . $e->getMessage());

    try {
        $fallbackWhere = [];
        $fallbackParams = [];

        // Role filter (still supported)
        if ($filters['role'] && in_array($filters['role'], ['admin', 'staff', 'customer'])) {
            $fallbackWhere[] = "u.role = ?";
            $fallbackParams[] = $filters['role'];
        }

        // Date range filter
        if ($filters['date_from']) {
            $fallbackWhere[] = "DATE(u.created_at) >= ?";
            $fallbackParams[] = $filters['date_from'];
        }
        if ($filters['date_to']) {
            $fallbackWhere[] = "DATE(u.created_at) <= ?";
            $fallbackParams[] = $filters['date_to'];
        }

        // Search filter
        if ($filters['search']) {
            $fallbackWhere[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.user_id LIKE ?)";
            $s = "%{$filters['search']}%";
            $fallbackParams[] = $s;
            $fallbackParams[] = $s;
            $fallbackParams[] = $s;
            $fallbackParams[] = $s;
        }

        $fallbackWhereSql = $fallbackWhere ? "WHERE " . implode(' AND ', $fallbackWhere) : '';

        // Simple count
        $countSql = "SELECT COUNT(*) as total_count FROM users u $fallbackWhereSql";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($fallbackParams);
        $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
        $totalUsers = $countData['total_count'] ?? 0;

        // Simple select without optional columns
        $query = "
            SELECT u.user_id, u.name, u.email, u.phone, u.role, u.created_at
            FROM users u
            $fallbackWhereSql
            ORDER BY " . ($orderBy ?: 'u.created_at DESC') . "
            LIMIT $perPage OFFSET $offset
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute($fallbackParams);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Derive totals and pages
        if (empty($totalUsers) && !empty($users)) {
            $totalUsers = count($users);
        }
        $totalPages = max(1, (int)ceil($totalUsers / $perPage));

        // minimal stats
        $stats = [
            'total_count' => $totalUsers,
            'active_count' => 0,
            'inactive_count' => 0,
            'admin_count' => 0,
            'staff_count' => 0,
            'customer_count' => 0
        ];
    } catch (PDOException $e2) {
        error_log('[users_list_fallback] ' . $e2->getMessage());
        $users = [];
    }
}

if (empty($users)) {
    try {
        $simpleStmt = $pdo->prepare("SELECT user_id, name, email, phone, role, status, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $simpleStmt->execute([(int)$perPage, (int)$offset]);
        $users = $simpleStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (empty($totalUsers) && !empty($users)) {
            $totalUsers = count($users);
        }
    } catch (PDOException $e) {
        error_log('[users_simple_fallback] ' . $e->getMessage());
    }
}

/* =====================================================
   STATUS COUNTS FOR FILTER TABS
===================================================== */
$statusCounts = [
    'all' => $totalUsers,
    'active' => $stats['active_count'] ?? 0,
    'inactive' => $stats['inactive_count'] ?? 0,
];

/* =====================================================
   ROLE COUNTS
===================================================== */
$roleCounts = [
    'admin' => $stats['admin_count'] ?? 0,
    'staff' => $stats['staff_count'] ?? 0,
    'customer' => $stats['customer_count'] ?? 0,
];

/* =====================================================
   GET TODAY'S NEW USERS
===================================================== */
$todayUsers = 0;

try {
    $todayStmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM users 
        WHERE DATE(created_at) = CURDATE()
    ");
    $todayData = $todayStmt->fetch(PDO::FETCH_ASSOC);
    $todayUsers = $todayData['count'] ?? 0;
} catch (PDOException $e) {
    error_log($e->getMessage());
}
