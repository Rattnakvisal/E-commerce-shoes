<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

/* =====================================================
   HELPERS
===================================================== */
function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonInput(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

/* =====================================================
   AUTH GUARD
===================================================== */
if (
    empty($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)
) {
    respond(['success' => false, 'error' => 'Unauthorized'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/* =====================================================
   VIEW ORDER
===================================================== */
if ($method === 'GET' && $action === 'view') {

    $orderId = (int)($_GET['order_id'] ?? 0);
    if ($orderId <= 0) {
        respond(['success' => false, 'error' => 'Invalid order id'], 400);
    }

    try {
        $stmt = $pdo->prepare("
            SELECT 
                o.*,
                COALESCE(u.name, u.email, 'Guest') AS customer_name,
                u.email AS customer_email
            FROM orders o
            LEFT JOIN users u ON u.user_id = o.user_id
            WHERE o.order_id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            respond(['ok' => false, 'success' => false, 'error' => 'Order not found'], 404);
        }

        $stmt = $pdo->prepare("
            SELECT 
                oi.*,
                COALESCE(p.name, 'Deleted Product') AS product_name,
                COALESCE(p.image_url, '') AS product_image
            FROM order_items oi
            LEFT JOIN products p ON p.product_id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respond([
            'ok' => true,
            'success' => true,
            'order'   => $order,
            'items'   => $items,
            'item_count' => count($items),
        ]);
    } catch (PDOException $e) {
        error_log('[order:view] ' . $e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   COMPLETE ORDER
===================================================== */
if ($method === 'POST' && $action === 'complete') {

    $orderId = (int)(jsonInput()['order_id'] ?? 0);
    if ($orderId <= 0) {
        respond(['success' => false, 'error' => 'Invalid order id'], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT order_status
            FROM orders
            WHERE order_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order not found'], 404);
        }

        if ($order['order_status'] === 'completed') {
            $pdo->rollBack();
            respond(['success' => true, 'message' => 'Order already completed']);
        }

        $items = $pdo->prepare("
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = ?
        ");
        $items->execute([$orderId]);

        $updateStock = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE product_id = ?
        ");

        $logInventory = $pdo->prepare("
            INSERT INTO inventory_logs (product_id, change_qty, reason)
            VALUES (?, ?, ?)
        ");

        foreach ($items as $item) {
            $qty = (int)$item['quantity'];
            $pid = (int)$item['product_id'];

            if ($qty > 0 && $pid > 0) {
                $updateStock->execute([$qty, $pid]);
                $logInventory->execute([
                    $pid,
                    -$qty,
                    'Order #' . $orderId . ' completed'
                ]);
            }
        }

        $pdo->prepare(" 
            UPDATE orders
            SET order_status = 'completed'
            WHERE order_id = ?
        ")->execute([$orderId]);

        $pdo->commit();

        respond(['ok' => true, 'success' => true, 'message' => 'Order completed successfully']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[order:complete] ' . $e->getMessage());
        respond(['ok' => false, 'success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   FALLBACK
===================================================== */
respond(['ok' => false, 'success' => false, 'error' => 'Invalid action or method'], 400);
