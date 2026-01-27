<?php
require_once __DIR__ . '/../../config/conn.php';

$featured = [];
try {
    $stmt = $conn->prepare("
        SELECT f.*, p.name AS product_name
        FROM featured_items f
        LEFT JOIN products p ON f.product_id = p.product_id
        WHERE f.is_active = 1
        ORDER BY f.position ASC
        LIMIT 8
    ");
    $stmt->execute();
    $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $featured = [];
}
