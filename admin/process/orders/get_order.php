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
    echo json_encode($data);
    exit;
}

function jsonInput(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

/* =====================================================
   AUTH
===================================================== */
if (
    empty($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)
) {
    respond(['success' => false, 'error' => 'Unauthorized'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = jsonInput();

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
            SELECT o.*, COALESCE(u.name, u.email, 'Guest') customer
            FROM orders o
            LEFT JOIN users u ON u.user_id = o.user_id
            WHERE o.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            respond(['success' => false, 'error' => 'Order not found'], 404);
        }

        $items = $pdo->prepare("
            SELECT oi.*, p.name product_name
            FROM order_items oi
            LEFT JOIN products p ON p.product_id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $items->execute([$orderId]);

        respond([
            'success' => true,
            'order'   => $order,
            'items'   => $items->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   COMPLETE ORDER
===================================================== */
if ($method === 'POST' && $action === 'complete') {

    $orderId = (int)($input['order_id'] ?? 0);
    if ($orderId <= 0) {
        respond(['success' => false, 'error' => 'Invalid order id'], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT order_status FROM orders WHERE order_id = ? FOR UPDATE");
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

        $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $items->execute([$orderId]);

        foreach ($items as $item) {
            $pdo->prepare("
                UPDATE products SET stock = stock - ?
                WHERE product_id = ?
            ")->execute([(int)$item['quantity'], (int)$item['product_id']]);
        }

        $pdo->prepare("
            UPDATE orders SET order_status = 'completed'
            WHERE order_id = ?
        ")->execute([$orderId]);

        $pdo->commit();

        respond(['success' => true, 'message' => 'Order completed successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   FALLBACK
===================================================== */
respond(['success' => false, 'error' => 'Invalid action'], 400);
