<?php
require_once __DIR__ . '/../../../config/conn.php';

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

/* ==========================
   PAGINATION
========================== */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

/* ==========================
   FILTERS
========================== */
$search = $_GET['search'] ?? '';
$category_id = (int)($_GET['category_id'] ?? 0);
$status = $_GET['status'] ?? '';
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 0);

$where = [];
$params = [];

/* ==========================
   BUILD WHERE
========================== */
if ($search !== '') {
    $where[] = "(p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($category_id > 0) {
    $where[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if ($status !== '') {
    $where[] = "p.status = :status";
    $params[':status'] = $status;
}

if ($min_price > 0) {
    $where[] = "p.price >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price > 0) {
    $where[] = "p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ==========================
   COUNT TOTAL
========================== */
$countSql = "SELECT COUNT(*) FROM products p $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

/* ==========================
   FETCH PRODUCTS
========================== */
$sql = "
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    $whereClause
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
   CATEGORIES
========================== */
$categories = $pdo->query("
    SELECT * FROM categories ORDER BY category_name
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
   STATS
========================== */
$stats = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive,
        SUM(stock) AS total_stock
    FROM products
")->fetch(PDO::FETCH_ASSOC);

// Initialize variables
$products = [];
$categories = [];
$stats = [
    'total' => 0,
    'active' => 0,
    'total_stock' => 0,
    'inactive' => 0
];
$message = '';
$error = '';
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Fetch categories
    $stmt = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build WHERE clause
    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($category_id && $category_id !== '') {
        $where[] = "p.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    if ($status && $status !== '') {
        $where[] = "p.status = :status";
        $params[':status'] = $status;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total products
    $countSql = "SELECT COUNT(*) as total FROM products p $whereClause";
    $stmt = $conn->prepare($countSql);
    $stmt->execute($params);
    $totalProducts = $stmt->fetchColumn();
    $totalPages = ceil($totalProducts / $limit);

    $sql = "SELECT p.*, c.category_name, 
            p.image_url as image_url
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            $whereClause 
            ORDER BY p.created_at DESC 
            LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch stats
    $statsSql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
        SUM(stock) as total_stock
        FROM products";

    $stmt = $conn->query($statsSql);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_stock'] = $stats['total_stock'] ?? 0;
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
