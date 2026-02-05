<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conn.php';

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection missing']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order id']);
    exit;
}

try {
    // Ensure order belongs to user and fetch current status and payment_status
    $stmt = $pdo->prepare('SELECT order_status, payment_status FROM orders WHERE order_id = :oid AND user_id = :uid LIMIT 1');
    $stmt->execute([':oid' => $orderId, ':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }

    $current = strtolower(trim((string)($row['order_status'] ?? '')));
    $payment = strtolower(trim((string)($row['payment_status'] ?? 'unpaid')));

    // Disallow cancelling if already completed/delivered/cancelled
    if (in_array($current, ['completed', 'delivered', 'cancelled'], true)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Order cannot be cancelled']);
        exit;
    }
    // Perform update: mark cancelled and, if it was paid, set payment_status -> unpaid
    $u = $pdo->prepare(
        "UPDATE orders
         SET order_status = 'cancelled',
             payment_status = IF(payment_status = 'paid', 'unpaid', payment_status),
             updated_at = NOW()
         WHERE order_id = :oid AND user_id = :uid"
    );
    $u->execute([':oid' => $orderId, ':uid' => $userId]);

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'previous_payment_status' => $payment,
        'new_payment_status' => ($payment === 'paid' ? 'unpaid' : $payment),
    ]);
    exit;
} catch (Throwable $e) {
    error_log('[cancel_order.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal error']);
    exit;
}
