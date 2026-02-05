<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Database connection missing']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);
$oid    = (int)($_GET['order_id'] ?? 0);

if ($userId <= 0 || $oid <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    // Confirm this order belongs to user
    $check = $pdo->prepare("
        SELECT order_id
        FROM orders
        WHERE order_id = :oid AND user_id = :uid
        LIMIT 1
    ");
    $check->execute([':oid' => $oid, ':uid' => $userId]);

    if (!$check->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Order not found or access denied']);
        exit;
    }

    // Load items
    $itStmt = $pdo->prepare("
        SELECT
            oi.product_id,
            oi.quantity AS qty,
            oi.price,
            COALESCE(p.name, '') AS name,
            COALESCE(p.image_url, '') AS image_url,
            (oi.quantity * oi.price) AS line_total
        FROM order_items oi
        LEFT JOIN products p ON p.product_id = oi.product_id
        WHERE oi.order_id = :oid
        ORDER BY oi.product_id ASC
    ");
    $itStmt->execute([':oid' => $oid]);
    $items = $itStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Normalize numeric values (optional but nice)
    foreach ($items as &$it) {
        $it['product_id'] = (int)($it['product_id'] ?? 0);
        $it['qty']        = (int)($it['qty'] ?? 0);
        $it['price']      = (float)($it['price'] ?? 0);
        $it['line_total'] = (float)($it['line_total'] ?? 0);
        $it['name']       = (string)($it['name'] ?? '');
        $it['image_url']  = (string)($it['image_url'] ?? '');
    }
    unset($it);

    echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
} catch (Throwable $ex) {
    error_log('[order_items.php] ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
