<?php
require_once __DIR__ . '/../../../config/conn.php';

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

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
    // Ensure stats keys exist and are numeric
    $stats = array_merge([
        'total' => 0,
        'active' => 0,
        'inactive' => 0,
        'total_stock' => 0
    ], (array)$stats);
    $stats['total'] = (int)($stats['total'] ?? 0);
    $stats['active'] = (int)($stats['active'] ?? 0);
    $stats['inactive'] = (int)($stats['inactive'] ?? 0);
    $stats['total_stock'] = (int)($stats['total_stock'] ?? 0);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Ensure pagination and totals are always defined
$totalProducts = isset($totalProducts) ? (int)$totalProducts : 0;
$totalPages = isset($totalPages) ? max(1, (int)$totalPages) : 1;

// Guarantee variables exist for the view
$products = $products ?? [];
$categories = $categories ?? [];
$message = $message ?? '';
$error = $error ?? '';
