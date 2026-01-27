<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/* =========================
   FILTER INPUTS
========================= */
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$gender = $_GET['gender'] ?? '';
$price_min = (int)($_GET['price_min'] ?? 0);
$price_max = (int)($_GET['price_max'] ?? 1000);
$availability = $_GET['availability'] ?? '';
$pickup = $_GET['pickup'] ?? '';

/* =========================
   FETCH CATEGORIES WITH COUNTS
========================= */
$category_counts = $pdo->query(
    "SELECT c.category_id, c.category_name, COUNT(p.product_id) as product_count
     FROM categories c
     LEFT JOIN products p ON c.category_id = p.category_id AND p.status = 'active'
     GROUP BY c.category_id, c.category_name
     ORDER BY c.category_name"
)->fetchAll(PDO::FETCH_ASSOC);

$total_products = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch()['count'];


$where = ["p.status = 'active'"];
$params = [];

// Category filter
if ($category !== '') {
    $where[] = "p.category_id = ?";
    $params[] = $category;
}

// Gender filter
if ($gender !== '') {
    $where[] = "(c.category_name LIKE ? OR p.name LIKE ?)";
    $params[] = "%$gender%";
    $params[] = "%$gender%";
}

// Price range filter
if ($price_min > 0 || $price_max < 1000) {
    $where[] = "p.price BETWEEN ? AND ?";
    $params[] = $price_min;
    $params[] = $price_max;
}

// Availability filter
if ($availability === 'in_stock') {
    $where[] = "p.stock > 0";
}

if ($pickup === 'pick_up_today') {
    $where[] = "p.stock > 10";
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$baseFrom = "FROM products p LEFT JOIN categories c ON c.category_id = p.category_id";

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) as total $baseFrom $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$order = match ($sort) {
    'price_low'  => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name_asc'   => 'p.name ASC',
    'name_desc'  => 'p.name DESC',
    default      => 'p.created_at DESC'
};

$selectSql = "SELECT p.product_id, p.name, p.price, p.image_url, c.category_name, p.created_at, p.stock $baseFrom $whereSql ORDER BY $order LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($selectSql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = (int)ceil($total / $limit);

// Get price range for slider
$price_range = $pdo->query("SELECT MIN(price) as min, MAX(price) as max FROM products WHERE status = 'active'")->fetch();
$db_min_price = (int)($price_range['min'] ?? 0);
$db_max_price = (int)($price_range['max'] ?? 1000);

// Ensure filters are within range
$price_min = max($price_min, $db_min_price);
$price_max = min($price_max, $db_max_price);

// Helper function for safe output
function e($string): string
{
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
