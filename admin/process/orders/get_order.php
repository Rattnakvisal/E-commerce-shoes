<?php
require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

/* =====================================================
   AUTH GUARD
===================================================== */
if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

/* =====================================================
   INPUT
===================================================== */
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/* =====================================================
   ACTION: VIEW ORDER
   GET ?action=view&order_id=1
===================================================== */
if ($method === 'GET' && $action === 'view') {

    $order_id = (int)($_GET['order_id'] ?? 0);

    if ($order_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order id']);
        exit;
    }

    try {
        // Order header
        $stmt = $pdo->prepare("
            SELECT 
                o.order_id,
                o.user_id,
                o.total,
                o.order_status,
                o.payment_status,
                o.order_type,
                o.created_at,
                COALESCE(u.name, u.email, 'Guest') AS customer,
                u.email
            FROM orders o
            LEFT JOIN users u ON u.user_id = o.user_id
            WHERE o.order_id = ?
            LIMIT 1
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }

        // Order items
        $stmt = $pdo->prepare("
            SELECT 
                oi.product_id,
                oi.quantity,
                oi.price,
                COALESCE(p.name,'') AS name,
                COALESCE(p.image_url,'') AS image_url
            FROM order_items oi
            LEFT JOIN products p ON p.product_id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Shipping (optional)
        $stmt = $pdo->prepare("
            SELECT * FROM shipping WHERE order_id = ? LIMIT 1
        ");
        $stmt->execute([$order_id]);
        $shipping = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'order'    => $order,
            'items'    => $items,
            'shipping' => $shipping
        ]);
        exit;
    } catch (PDOException $e) {
        error_log('[order_view] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
        exit;
    }
}

/* =====================================================
   ACTION: COMPLETE ORDER
   POST { order_id }
===================================================== */
if ($method === 'POST' && $action === 'complete') {

    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = (int)($input['order_id'] ?? 0);

    if ($order_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order id']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Lock order row
        $stmt = $pdo->prepare("
            SELECT order_status
            FROM orders
            WHERE order_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }

        // Prevent double completion
        if ($order['order_status'] === 'completed') {
            $pdo->rollBack();
            echo json_encode([
                'ok' => true,
                'message' => 'Order already completed'
            ]);
            exit;
        }

        // Fetch items
        $stmt = $pdo->prepare("
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare statements
        $updateStock = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE product_id = ?
        ");

        $logInventory = $pdo->prepare("
            INSERT INTO inventory_logs (product_id, change_qty, reason)
            VALUES (?, ?, ?)
        ");

        // Deduct stock
        foreach ($items as $item) {
            $qty = (int)$item['quantity'];
            $pid = (int)$item['product_id'];

            $updateStock->execute([$qty, $pid]);
            $logInventory->execute([
                $pid,
                -$qty,
                'Order #' . $order_id . ' completed'
            ]);
        }

        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders
            SET order_status = 'completed'
            WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);

        $pdo->commit();

        echo json_encode(['ok' => true]);
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[order_complete] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
        exit;
    }
}

/* =====================================================
   ACTION: CANCEL ORDER
   POST { order_id }
===================================================== */
if ($method === 'POST' && $action === 'cancel') {

    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = (int)($input['order_id'] ?? 0);

    if ($order_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order id']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT order_status FROM orders WHERE order_id = ? FOR UPDATE");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }

        if ($order['order_status'] === 'cancelled') {
            $pdo->rollBack();
            echo json_encode([
                'ok' => true,
                'message' => 'Order already cancelled'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?");
        $stmt->execute([$order_id]);

        $pdo->commit();

        echo json_encode(['ok' => true]);
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[order_cancel] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
        exit;
    }
}

/* =====================================================
   FALLBACK
===================================================== */
http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
exit;
