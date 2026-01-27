<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/conn.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user_id'] ?? null;
$oid = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$userId || $oid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    $check = $pdo->prepare('SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? LIMIT 1');
    $check->execute([$oid, $userId]);
    $found = $check->fetchColumn();
    if (!$found) {
        http_response_code(403);
        echo json_encode(['error' => 'Order not found or access denied']);
        exit;
    }

    // Fetch items
    $itStmt = $pdo->prepare("SELECT oi.product_id, oi.quantity, oi.price, COALESCE(p.name, '') AS name, COALESCE(p.image_url, '') AS image_url
        FROM order_items oi
        LEFT JOIN products p ON p.product_id = oi.product_id
        WHERE oi.order_id = :oid");
    $itStmt->execute(['oid' => $oid]);
    $items = $itStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['items' => $items]);
} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $ex->getMessage()]);
}
