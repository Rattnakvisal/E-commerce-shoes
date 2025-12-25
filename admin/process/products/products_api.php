<?php
require_once __DIR__ . '/../../../config/conn.php'; // PDO connection

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
        SUM(status = 'active') AS active,
        SUM(status = 'inactive') AS inactive,
        SUM(stock) AS total_stock,
        AVG(price) AS avg_price
    FROM products
")->fetch(PDO::FETCH_ASSOC);
