<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conn.php';

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    echo json_encode(['success' => false, 'message' => 'Database connection missing.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));

// optional: avoid heavy search for very short terms
if ($q === '' || mb_strlen($q) < 2) {
    echo json_encode([
        'success' => true,
        'q' => $q,
        'categories' => [],
        'products' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$limitCategories = 8;
$limitProducts   = 12;

$response = [
    'success' => true,
    'q' => $q,
    'categories' => [],
    'products' => [],
];

try {
    // Escape LIKE wildcards: \ % _
    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q);
    $term    = '%' . $escaped . '%';
    $prefix  = $escaped . '%';

    /* -----------------------------
       Categories
    ------------------------------ */
    $stmt = $pdo->prepare("
        SELECT category_id, category_name
        FROM categories
        WHERE category_name LIKE :term
        ORDER BY
          (category_name = :exact) DESC,
          (category_name LIKE :prefix) DESC,
          category_name ASC
        LIMIT {$limitCategories}
    ");
    $stmt->execute([
        ':term'   => $term,
        ':exact'  => $q,
        ':prefix' => $prefix,
    ]);
    $response['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    /* -----------------------------
       Products
    ------------------------------ */
    $stmt = $pdo->prepare("
        SELECT
            p.product_id,
            p.NAME AS name,
            p.image_url,
            p.price,
            p.category_id,
            c.category_name
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        WHERE
            p.NAME LIKE :term
            OR p.DESCRIPTION LIKE :term
            OR c.category_name LIKE :term
        ORDER BY
            (p.NAME = :exact) DESC,
            (p.NAME LIKE :prefix) DESC,
            p.NAME ASC
        LIMIT {$limitProducts}
    ");
    $stmt->execute([
        ':term'   => $term,
        ':exact'  => $q,
        ':prefix' => $prefix,
    ]);
    $response['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('[search_api] ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'Search failed.'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
