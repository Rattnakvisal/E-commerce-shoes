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
            SELECT o.*, COALESCE(u.name, u.email, 'Guest') AS customer_name
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
   UPDATE ORDER ITEMS
===================================================== */
if ($method === 'POST' && $action === 'update_items') {

    $orderId = (int)($input['order_id'] ?? 0);
    $items   = $input['items'] ?? [];

    if ($orderId <= 0 || !is_array($items) || !$items) {
        respond(['success' => false, 'error' => 'Invalid input'], 400);
    }

    try {
        $pdo->beginTransaction();

        // Lock order
        $stmt = $pdo->prepare("
            SELECT order_status
            FROM orders
            WHERE order_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order || $order['order_status'] === 'completed') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order locked'], 400);
        }

        // Restore stock first
        $restore = $pdo->prepare("
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = ?
        ");
        $restore->execute([$orderId]);

        foreach ($restore as $row) {
            $pdo->prepare("
                UPDATE products
                SET stock = stock + ?
                WHERE product_id = ?
            ")->execute([(int)$row['quantity'], (int)$row['product_id']]);
        }

        // Update items
        $updateItem = $pdo->prepare("
            UPDATE order_items
            SET quantity = ?
            WHERE order_id = ? AND product_id = ?
        ");

        $reduceStock = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE product_id = ?
        ");

        foreach ($items as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);

            if ($pid <= 0 || $qty <= 0) {
                throw new RuntimeException('Invalid item data');
            }

            $updateItem->execute([$qty, $orderId, $pid]);
            $reduceStock->execute([$qty, $pid]);
        }

        // Recalculate order total
        $pdo->prepare("
            UPDATE orders o
            SET total = (
                SELECT SUM(oi.quantity * oi.price)
                FROM order_items oi
                WHERE oi.order_id = o.order_id
            )
            WHERE o.order_id = ?
        ")->execute([$orderId]);

        $pdo->commit();

        respond(['success' => true, 'message' => 'Order items updated']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   UPDATE PAYMENT STATUS
===================================================== */
if ($method === 'POST' && $action === 'update_payment') {

    $orderId = (int)($input['order_id'] ?? 0);
    $payment = strtolower(trim((string)($input['payment_status'] ?? '')));

    $allowed = ['pending', 'paid', 'failed', 'refunded'];

    if ($orderId <= 0 || !in_array($payment, $allowed, true)) {
        respond(['success' => false, 'error' => 'Invalid input'], 400);
    }

    try {
        $pdo->beginTransaction();

        // Lock order
        $stmt = $pdo->prepare("
            SELECT payment_status, order_status
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

        $currentPayment = strtolower((string)$order['payment_status']);

        if ($currentPayment === $payment) {
            $pdo->rollBack();
            respond(['success' => true, 'message' => 'Payment status unchanged']);
        }

        // Refund rules
        if ($payment === 'refunded' && $currentPayment !== 'paid') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Only paid orders can be refunded'], 400);
        }

        // Prevent payment change after refund
        if ($currentPayment === 'refunded') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Refunded payment is locked'], 400);
        }

        // Update payment status
        $upd = $pdo->prepare("
            UPDATE orders
            SET payment_status = ?
            WHERE order_id = ?
        ");
        $upd->execute([$payment, $orderId]);

        $pdo->commit();

        respond([
            'success' => true,
            'message' => 'Payment status updated'
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
       UPDATE ORDER STATUS
    ===================================================== */
if ($method === 'POST' && $action === 'update_status') {
    $orderId = (int)($input['order_id'] ?? 0);
    $status = strtolower(trim((string)($input['status'] ?? '')));

    $allowed = ['pending', 'processing', 'completed', 'cancelled'];
    if ($orderId <= 0 || !in_array($status, $allowed, true)) {
        respond(['success' => false, 'error' => 'Invalid input'], 400);
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

        $current = strtolower((string)$order['order_status']);
        if ($current === $status) {
            $pdo->rollBack();
            respond(['success' => true, 'message' => 'Order already has requested status']);
        }

        // Prevent changing a completed order back to another status
        if ($current === 'completed' && $status !== 'completed') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Cannot modify a completed order'], 400);
        }

        if ($status === 'completed') {
            // reduce stock same as complete endpoint
            $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items->execute([$orderId]);

            foreach ($items as $item) {
                $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?")->execute([(int)$item['quantity'], (int)$item['product_id']]);
            }

            $pdo->prepare("UPDATE orders SET order_status = 'completed' WHERE order_id = ?")->execute([$orderId]);
        } else {
            $upd = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            $upd->execute([$status, $orderId]);
        }

        $pdo->commit();
        respond(['success' => true, 'message' => 'Order status updated']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   REFUND ORDER
===================================================== */
if ($method === 'POST' && $action === 'refund') {
    $orderId = (int)($input['order_id'] ?? 0);
    if ($orderId <= 0) {
        respond(['success' => false, 'error' => 'Invalid order id'], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT payment_status, order_status, user_id FROM orders WHERE order_id = ? FOR UPDATE");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order not found'], 404);
        }

        if (($order['payment_status'] ?? '') !== 'paid') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order not paid or already refunded'], 400);
        }

        // Restock items
        $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $items->execute([$orderId]);

        foreach ($items as $it) {
            $pdo->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?")->execute([(int)$it['quantity'], (int)$it['product_id']]);
        }

        // Mark order refunded / cancelled
        $pdo->prepare("UPDATE orders SET payment_status = 'refunded', order_status = 'cancelled' WHERE order_id = ?")->execute([$orderId]);

        $pdo->commit();

        respond(['success' => true, 'message' => 'Order refunded and items restocked']);
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
