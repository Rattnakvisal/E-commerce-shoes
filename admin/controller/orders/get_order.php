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
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

/* =====================================================
   DB
===================================================== */
$pdo ??= $conn ?? null;
if (!$pdo instanceof PDO) {
    respond(['success' => false, 'error' => 'Database connection missing'], 500);
}

/* =====================================================
   AUTH
===================================================== */
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    respond(['success' => false, 'error' => 'Unauthorized'], 403);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$input  = jsonInput();

/* =====================================================
   VIEW ORDER (WITH PAYMENT + PAYMENT METHOD)
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

                p.payment_id,
                p.amount AS paid_amount,
                p.payment_date,
                pm.method_id   AS payment_method_id,
                pm.method_code AS payment_method_code,
                pm.method_name AS payment_method_name
            FROM orders o
            LEFT JOIN users u ON u.user_id = o.user_id
            LEFT JOIN payments p ON p.order_id = o.order_id
            LEFT JOIN payment_methods pm ON pm.method_id = p.payment_method_id
            WHERE o.order_id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            respond(['success' => false, 'error' => 'Order not found'], 404);
        }

        $itemsStmt = $pdo->prepare("
            SELECT
                oi.*,
                p.name AS product_name
            FROM order_items oi
            LEFT JOIN products p ON p.product_id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $paymentInfo = [
            'payment_id'   => $order['payment_id'] ?? null,
            'method_id'    => $order['payment_method_id'] ?? null,
            'method_code'  => $order['payment_method_code'] ?? null,
            'method_name'  => $order['payment_method_name'] ?? null,
            'amount'       => (float)($order['paid_amount'] ?? 0),
            'payment_date' => $order['payment_date'] ?? null,
        ];

        unset(
            $order['payment_id'],
            $order['paid_amount'],
            $order['payment_date'],
            $order['payment_method_id'],
            $order['payment_method_code'],
            $order['payment_method_name']
        );

        respond([
            'success' => true,
            'order'   => $order,
            'payment' => $paymentInfo,
            'items'   => $items,
        ]);
    } catch (Throwable $e) {
        error_log('[orders_api view] ' . $e->getMessage());
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

        // Lock order
        $stmt = $pdo->prepare("SELECT order_status, user_id FROM orders WHERE order_id = ? FOR UPDATE");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order not found'], 404);
        }

        $currentStatus = strtolower((string)($order['order_status'] ?? ''));
        if ($currentStatus === 'completed') {
            $pdo->rollBack();
            respond(['success' => true, 'message' => 'Order already completed']);
        }

        // Reduce stock safely (ONLY if you did NOT reduce at checkout)
        // 1) Read items
        $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$items) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'No items found for this order'], 400);
        }

        // 2) Check stock before reducing
        $checkStock = $pdo->prepare("SELECT stock FROM products WHERE product_id = ? FOR UPDATE");
        foreach ($items as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['quantity'];

            if ($pid <= 0 || $qty <= 0) {
                $pdo->rollBack();
                respond(['success' => false, 'error' => 'Invalid item quantity'], 400);
            }

            $checkStock->execute([$pid]);
            $row = $checkStock->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $pdo->rollBack();
                respond(['success' => false, 'error' => "Product not found: {$pid}"], 404);
            }

            $stock = (int)$row['stock'];
            if ($stock < $qty) {
                $pdo->rollBack();
                respond(['success' => false, 'error' => "Insufficient stock for product {$pid}"], 400);
            }
        }

        // 3) Reduce stock now
        $reduce = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
        foreach ($items as $item) {
            $reduce->execute([(int)$item['quantity'], (int)$item['product_id']]);
        }

        // Mark completed
        $pdo->prepare("UPDATE orders SET order_status = 'completed' WHERE order_id = ?")
            ->execute([$orderId]);

        // Notification (registered user only)
        $userIdForNotif = isset($order['user_id']) ? (int)$order['user_id'] : 0;
        if ($userIdForNotif > 0) {
            $title = 'Order Completed';
            $message = sprintf('Your order #%d has been completed.', $orderId);

            $ins = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, is_read, created_at)
                VALUES (:uid, :title, :msg, 0, NOW())
            ");
            $ins->execute([':uid' => $userIdForNotif, ':title' => $title, ':msg' => $message]);
        }

        $pdo->commit();
        respond(['success' => true, 'message' => 'Order completed successfully']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[orders_api complete] ' . $e->getMessage());
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

        if (!$order || strtolower((string)$order['order_status']) === 'completed') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order locked'], 400);
        }

        // Restore stock first (based on old quantities)
        $restore = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $restore->execute([$orderId]);

        foreach ($restore as $row) {
            $pdo->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?")
                ->execute([(int)$row['quantity'], (int)$row['product_id']]);
        }

        // Update items + reduce stock by new quantities
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

        // Stock safety: check stock BEFORE reducing
        $checkStock = $pdo->prepare("SELECT stock FROM products WHERE product_id = ? FOR UPDATE");

        foreach ($items as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);

            if ($pid <= 0 || $qty <= 0) {
                throw new RuntimeException('Invalid item data');
            }

            $checkStock->execute([$pid]);
            $row = $checkStock->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new RuntimeException("Product not found: {$pid}");
            }
            if ((int)$row['stock'] < $qty) {
                throw new RuntimeException("Insufficient stock for product {$pid}");
            }

            $updateItem->execute([$qty, $orderId, $pid]);
            $reduceStock->execute([$qty, $pid]);
        }

        // Recalculate order total (items only)
        $pdo->prepare("
            UPDATE orders o
            SET total = (
                SELECT COALESCE(SUM(oi.quantity * oi.price),0)
                FROM order_items oi
                WHERE oi.order_id = o.order_id
            )
            WHERE o.order_id = ?
        ")->execute([$orderId]);

        // If already paid => update payment amount to new total
        $pdo->prepare("
            UPDATE payments p
            JOIN orders o ON o.order_id = p.order_id
            SET p.amount = CASE WHEN o.payment_status='paid' THEN o.total ELSE 0 END
            WHERE p.order_id = ?
        ")->execute([$orderId]);

        $pdo->commit();
        respond(['success' => true, 'message' => 'Order items updated']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[orders_api update_items] ' . $e->getMessage());
        respond(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/* =====================================================
   UPDATE PAYMENT STATUS (ALSO UPDATE payments.amount)
===================================================== */
if ($method === 'POST' && $action === 'update_payment') {

    $orderId  = (int)($input['order_id'] ?? 0);
    $payment  = strtolower(trim((string)($input['payment_status'] ?? '')));

    $allowed = ['pending', 'paid', 'failed', 'refunded'];

    if ($orderId <= 0 || !in_array($payment, $allowed, true)) {
        respond(['success' => false, 'error' => 'Invalid input'], 400);
    }

    try {
        $pdo->beginTransaction();

        // Lock order
        $stmt = $pdo->prepare("
            SELECT payment_status, order_status, total
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

        $currentPayment = strtolower((string)($order['payment_status'] ?? ''));

        if ($currentPayment === $payment) {
            $pdo->rollBack();
            respond(['success' => true, 'message' => 'Payment status unchanged']);
        }

        // Refund rules
        if ($payment === 'refunded' && $currentPayment !== 'paid') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Only paid orders can be refunded'], 400);
        }

        if ($currentPayment === 'refunded') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Refunded payment is locked'], 400);
        }

        // Update orders.payment_status
        $pdo->prepare("UPDATE orders SET payment_status = ? WHERE order_id = ?")
            ->execute([$payment, $orderId]);

        // Keep payments.amount consistent
        if ($payment === 'paid') {
            $pdo->prepare("
                UPDATE payments p
                JOIN orders o ON o.order_id = p.order_id
                SET p.amount = o.total
                WHERE p.order_id = ?
            ")->execute([$orderId]);
        } else {
            $pdo->prepare("UPDATE payments SET amount = 0 WHERE order_id = ?")
                ->execute([$orderId]);
        }

        $pdo->commit();
        respond(['success' => true, 'message' => 'Payment status updated']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[orders_api update_payment] ' . $e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   UPDATE ORDER STATUS
===================================================== */
if ($method === 'POST' && $action === 'update_status') {

    $orderId = (int)($input['order_id'] ?? 0);
    $status  = strtolower(trim((string)($input['status'] ?? '')));

    $allowed = ['pending', 'processing', 'completed', 'cancelled'];
    if ($orderId <= 0 || !in_array($status, $allowed, true)) {
        respond(['success' => false, 'error' => 'Invalid input'], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT order_status, user_id FROM orders WHERE order_id = ? FOR UPDATE");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order not found'], 404);
        }

        $current = strtolower((string)($order['order_status'] ?? ''));
        if ($current === $status) {
            $pdo->rollBack();
            respond(['success' => true, 'message' => 'Order already has requested status']);
        }

        if ($current === 'completed' && $status !== 'completed') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Cannot modify a completed order'], 400);
        }

        if ($status === 'completed') {
            // Reuse complete logic: reduce stock safely
            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$items) {
                $pdo->rollBack();
                respond(['success' => false, 'error' => 'No items found for this order'], 400);
            }

            $checkStock = $pdo->prepare("SELECT stock FROM products WHERE product_id = ? FOR UPDATE");
            foreach ($items as $item) {
                $pid = (int)$item['product_id'];
                $qty = (int)$item['quantity'];

                $checkStock->execute([$pid]);
                $row = $checkStock->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    $pdo->rollBack();
                    respond(['success' => false, 'error' => "Product not found: {$pid}"], 404);
                }
                if ((int)$row['stock'] < $qty) {
                    $pdo->rollBack();
                    respond(['success' => false, 'error' => "Insufficient stock for product {$pid}"], 400);
                }
            }

            $reduce = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
            foreach ($items as $item) {
                $reduce->execute([(int)$item['quantity'], (int)$item['product_id']]);
            }

            $pdo->prepare("UPDATE orders SET order_status = 'completed' WHERE order_id = ?")
                ->execute([$orderId]);

            $userIdForNotif = isset($order['user_id']) ? (int)$order['user_id'] : 0;
            if ($userIdForNotif > 0) {
                $title = 'Order Completed';
                $message = sprintf('Your order #%d has been completed.', $orderId);
                $ins = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, is_read, created_at)
                    VALUES (:uid, :title, :msg, 0, NOW())
                ");
                $ins->execute([':uid' => $userIdForNotif, ':title' => $title, ':msg' => $message]);
            }
        } else {
            $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?")
                ->execute([$status, $orderId]);
        }

        $pdo->commit();
        respond(['success' => true, 'message' => 'Order status updated']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[orders_api update_status] ' . $e->getMessage());
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

        $stmt = $pdo->prepare("
            SELECT payment_status, order_status, user_id
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

        if (($order['payment_status'] ?? '') !== 'paid') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order not paid or already refunded'], 400);
        }

        // Restock items
        $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $items->execute([$orderId]);

        foreach ($items as $it) {
            $pdo->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?")
                ->execute([(int)$it['quantity'], (int)$it['product_id']]);
        }

        // Mark refunded / cancelled
        $pdo->prepare("UPDATE orders SET payment_status='refunded', order_status='cancelled' WHERE order_id = ?")
            ->execute([$orderId]);

        // payments amount -> 0
        $pdo->prepare("UPDATE payments SET amount = 0 WHERE order_id = ?")
            ->execute([$orderId]);

        $pdo->commit();
        respond(['success' => true, 'message' => 'Order refunded and items restocked']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[orders_api refund] ' . $e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   FALLBACK
===================================================== */
respond(['success' => false, 'error' => 'Invalid action'], 400);
