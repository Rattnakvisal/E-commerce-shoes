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
    if (!defined('RAW_REQUEST_BODY')) define('RAW_REQUEST_BODY', $raw);
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

function normStatus(?string $s): string
{
    return strtolower(trim((string)$s));
}

function allow(string $v, array $allowed): string
{
    $v = normStatus($v);
    return in_array($v, $allowed, true) ? $v : '';
}

/**
 * Allowed flow:
 * pending -> processing -> shipped -> delivered -> completed
 * pending -> cancelled
 * processing -> cancelled
 * shipped -> cancelled
 * delivered -> cancelled
 */
function canTransition(string $from, string $to): bool
{
    $from = normStatus($from);
    $to   = normStatus($to);

    $flow = [
        'pending'    => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered', 'cancelled'],
        'delivered'  => ['completed', 'cancelled'],
        'completed'  => [],
        'cancelled'  => [],
    ];

    return in_array($to, $flow[$from] ?? [], true);
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
$action = (string)($_GET['action'] ?? '');

// prefer JSON body, but fall back to form-encoded POST
$input  = array_merge($_POST ?? [], jsonInput());

/* =====================================================
   COMMON JOIN: latest payment + method
===================================================== */
$joinLatestPayment = "
LEFT JOIN (
  SELECT p1.*
  FROM payments p1
  JOIN (
    SELECT order_id, MAX(payment_date) max_date
    FROM payments
    GROUP BY order_id
  ) x ON x.order_id = p1.order_id AND x.max_date = p1.payment_date
) lp ON lp.order_id = o.order_id
LEFT JOIN payment_methods pm ON pm.method_id = lp.payment_method_id
";

/* =====================================================
   GET ORDERS (LIST / ALL)
   GET: ?action=list&status=&payment=&type=&search=&date_from=&date_to=&sort=&page=&per_page=
===================================================== */
if ($method === 'GET' && $action === 'list') {

    $status   = allow((string)($_GET['status'] ?? ''),  ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', '']);
    $payment  = allow((string)($_GET['payment'] ?? ''), ['pending', 'paid', 'failed', 'refunded', 'unpaid', '']);
    $type     = allow((string)($_GET['type'] ?? ''),    ['pos', 'online', '']);

    $search   = trim((string)($_GET['search'] ?? ''));
    $dateFrom = trim((string)($_GET['date_from'] ?? ''));
    $dateTo   = trim((string)($_GET['date_to'] ?? ''));

    $sort = allow((string)($_GET['sort'] ?? 'newest'), ['newest', 'oldest', 'total_asc', 'total_desc']);
    if ($sort === '') $sort = 'newest';

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_GET['per_page'] ?? 15);
    $perPage = max(1, min(200, $perPage));
    $offset  = ($page - 1) * $perPage;

    $where  = [];
    $params = [];

    if ($status !== '') {
        $where[] = "LOWER(o.order_status) = :st";
        $params[':st'] = $status;
    }
    if ($payment !== '') {
        $where[] = "LOWER(o.payment_status) = :ps";
        $params[':ps'] = $payment;
    }
    if ($type !== '') {
        $where[] = "LOWER(o.order_type) = :tp";
        $params[':tp'] = $type;
    }
    if ($dateFrom !== '') {
        $where[] = "o.created_at >= :df";
        $params[':df'] = $dateFrom . " 00:00:00";
    }
    if ($dateTo !== '') {
        $where[] = "o.created_at <= :dt";
        $params[':dt'] = $dateTo . " 23:59:59";
    }
    if ($search !== '') {
        $where[] = "(CAST(o.order_id AS CHAR) LIKE :q OR COALESCE(u.name,'') LIKE :q OR COALESCE(u.email,'') LIKE :q)";
        $params[':q'] = "%" . $search . "%";
    }

    $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

    $orderBy = match ($sort) {
        'oldest'     => "o.created_at ASC",
        'total_asc'  => "o.total ASC",
        'total_desc' => "o.total DESC",
        default      => "o.created_at DESC",
    };

    try {
        $countSql = "
            SELECT COUNT(*)
            FROM orders o
            LEFT JOIN users u ON u.user_id = o.user_id
            $joinLatestPayment
            $whereSql
        ";
        $st = $pdo->prepare($countSql);
        $st->execute($params);
        $total = (int)$st->fetchColumn();

        $listSql = "
            SELECT
              o.order_id, o.user_id, o.total,
              o.order_status, o.payment_status, o.order_type,
              o.created_at,

              COALESCE(u.name, u.email, 'Guest') AS customer_name,
              u.email AS customer_email,

              lp.payment_id,
              lp.amount AS paid_amount,
              lp.payment_date,
              pm.method_code AS payment_method_code,
              pm.method_name AS payment_method_name,

              (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.order_id=o.order_id) AS items_count
            FROM orders o
            LEFT JOIN users u ON u.user_id = o.user_id
            $joinLatestPayment
            $whereSql
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ";

        $st = $pdo->prepare($listSql);
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        $orders = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($orders as &$o) {
            $o['order_id'] = (int)($o['order_id'] ?? 0);
            $o['user_id'] = isset($o['user_id']) ? (int)$o['user_id'] : 0;
            $o['total'] = (float)($o['total'] ?? 0);
            $o['items_count'] = (int)($o['items_count'] ?? 0);
            $o['order_status'] = normStatus($o['order_status'] ?? '');
            $o['payment_status'] = normStatus($o['payment_status'] ?? '');
            $o['order_type'] = normStatus($o['order_type'] ?? '');
            $o['paid_amount'] = (float)($o['paid_amount'] ?? 0);
        }
        unset($o);

        $totalPages = max(1, (int)ceil($total / $perPage));

        // status counts
        $statusCounts = [
            'all'        => 0,
            'pending'    => 0,
            'processing' => 0,
            'delivered'  => 0,
            'completed'  => 0,
            'cancelled'  => 0,
        ];

        $row = $pdo->query("SELECT COUNT(*) c FROM orders")->fetch(PDO::FETCH_ASSOC) ?: [];
        $statusCounts['all'] = (int)($row['c'] ?? 0);

        $rows = $pdo->query("SELECT LOWER(order_status) st, COUNT(*) cnt FROM orders GROUP BY LOWER(order_status)")
            ->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $r) {
            $k = (string)($r['st'] ?? '');
            if ($k !== '' && array_key_exists($k, $statusCounts)) {
                $statusCounts[$k] = (int)($r['cnt'] ?? 0);
            }
        }

        // stats
        $stats = [
            'total_orders'   => $statusCounts['all'],
            'pending_count'  => $statusCounts['pending'],
            'today_orders'   => 0,
            'today_revenue'  => 0.0,
            'total_revenue'  => 0.0,
        ];

        $row = $pdo->query("
            SELECT COUNT(*) today_orders, COALESCE(SUM(total),0) today_revenue
            FROM orders
            WHERE DATE(created_at)=CURDATE()
        ")->fetch(PDO::FETCH_ASSOC) ?: [];
        $stats['today_orders']  = (int)($row['today_orders'] ?? 0);
        $stats['today_revenue'] = (float)($row['today_revenue'] ?? 0);

        $row = $pdo->query("SELECT COALESCE(SUM(total),0) total_revenue FROM orders WHERE LOWER(payment_status)='paid'")
            ->fetch(PDO::FETCH_ASSOC) ?: [];
        $stats['total_revenue'] = (float)($row['total_revenue'] ?? 0);

        respond([
            'success' => true,
            'filters' => [
                'status' => $status,
                'payment' => $payment,
                'type' => $type,
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'status_counts' => $statusCounts,
            'stats' => $stats,
            'orders' => $orders,
        ]);
    } catch (Throwable $e) {
        error_log('[orders_api list] ' . $e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   VIEW ORDER (WITH LATEST PAYMENT + PAYMENT METHOD)
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

                lp.payment_id,
                lp.amount AS paid_amount,
                lp.payment_date,
                pm.method_id   AS payment_method_id,
                pm.method_code AS payment_method_code,
                pm.method_name AS payment_method_name
            FROM orders o
            LEFT JOIN users u ON u.user_id = o.user_id
            $joinLatestPayment
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
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

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
   COMPLETE ORDER (requires delivered -> completed)
===================================================== */
if ($method === 'POST' && $action === 'complete') {

    $orderId = (int)($input['order_id'] ?? 0);
    if ($orderId <= 0) {
        respond(['success' => false, 'error' => 'Invalid order id'], 400);
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

        $currentStatus = normStatus($order['order_status'] ?? 'pending');

        if ($currentStatus === 'completed') {
            $pdo->rollBack();
            respond(['success' => true, 'message' => 'Order already completed']);
        }

        if ($currentStatus !== 'delivered') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order must be delivered before completing'], 400);
        }

        // reduce stock ONLY if you didn't reduce at checkout
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

        // notification
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
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[orders_api complete] ' . $e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   UPDATE ORDER ITEMS
   - Locked if delivered/completed/cancelled
===================================================== */
if ($method === 'POST' && $action === 'update_items') {

    $orderId = (int)($input['order_id'] ?? 0);
    $items   = $input['items'] ?? [];

    if ($orderId <= 0 || !is_array($items) || !$items) {
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

        $statusNow = normStatus($order['order_status'] ?? 'pending');
        if (in_array($statusNow, ['shipped', 'delivered', 'completed', 'cancelled'], true)) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order locked'], 400);
        }

        // restore stock from old quantities
        $restore = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $restore->execute([$orderId]);
        foreach ($restore as $row) {
            $pdo->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?")
                ->execute([(int)$row['quantity'], (int)$row['product_id']]);
        }

        $updateItem  = $pdo->prepare("UPDATE order_items SET quantity = ? WHERE order_id = ? AND product_id = ?");
        $reduceStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
        $checkStock  = $pdo->prepare("SELECT stock FROM products WHERE product_id = ? FOR UPDATE");

        foreach ($items as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);

            if ($pid <= 0 || $qty <= 0) {
                throw new RuntimeException('Invalid item data');
            }

            $checkStock->execute([$pid]);
            $row = $checkStock->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException("Product not found: {$pid}");
            if ((int)$row['stock'] < $qty) throw new RuntimeException("Insufficient stock for product {$pid}");

            $updateItem->execute([$qty, $orderId, $pid]);
            $reduceStock->execute([$qty, $pid]);
        }

        // recalc total
        $pdo->prepare("
            UPDATE orders o
            SET total = (
                SELECT COALESCE(SUM(oi.quantity * oi.price),0)
                FROM order_items oi
                WHERE oi.order_id = o.order_id
            )
            WHERE o.order_id = ?
        ")->execute([$orderId]);

        // if paid update payments amount to new total (else 0)
        $pdo->prepare("
            UPDATE payments p
            JOIN orders o ON o.order_id = p.order_id
            SET p.amount = CASE WHEN LOWER(o.payment_status)='paid' THEN o.total ELSE 0 END
            WHERE p.order_id = ?
        ")->execute([$orderId]);

        $pdo->commit();
        respond(['success' => true, 'message' => 'Order items updated']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[orders_api update_items] ' . $e->getMessage());
        respond(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/* =====================================================
   UPDATE PAYMENT STATUS (ALSO UPDATE payments.amount)
===================================================== */
if ($method === 'POST' && $action === 'update_payment') {

    $orderId  = (int)($input['order_id'] ?? 0);
    $payment  = normStatus($input['payment_status'] ?? '');

    $allowed = ['pending', 'paid', 'failed', 'refunded'];
    if ($orderId <= 0 || !in_array($payment, $allowed, true)) {
        respond(['success' => false, 'error' => 'Invalid input'], 400);
    }

    try {
        $pdo->beginTransaction();

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

        $currentPayment = normStatus($order['payment_status'] ?? '');

        if ($currentPayment === $payment) {
            $pdo->rollBack();
            respond(['success' => true, 'message' => 'Payment status unchanged']);
        }

        if ($payment === 'refunded' && $currentPayment !== 'paid') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Only paid orders can be refunded'], 400);
        }

        if ($currentPayment === 'refunded') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Refunded payment is locked'], 400);
        }

        $pdo->prepare("UPDATE orders SET payment_status = ? WHERE order_id = ?")
            ->execute([$payment, $orderId]);

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
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[orders_api update_payment] ' . $e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   UPDATE ORDER STATUS
   - Enforces flow pending -> processing -> shipped -> delivered -> completed
   - Uses stock reduction ONLY when moving to completed (if you didn't reduce at checkout)
===================================================== */
if ($method === 'POST' && $action === 'update_status') {

    $orderId = (int)($input['order_id'] ?? 0);
    $status  = normStatus($input['status'] ?? '');

    $allowed = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
    if ($orderId <= 0 || !in_array($status, $allowed, true)) {
        $raw = defined('RAW_REQUEST_BODY') ? RAW_REQUEST_BODY : '';
        error_log(sprintf('[orders_api update_status] invalid input: order_id=%s status=%s raw=%s', json_encode($orderId), $status, substr((string)$raw, 0, 200)));
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

        $current = normStatus($order['order_status'] ?? 'pending');

        if ($current === $status) {
            $pdo->rollBack();
            respond(['success' => true, 'message' => 'Order already has requested status']);
        }

        if (!canTransition($current, $status)) {
            $pdo->rollBack();
            respond(['success' => false, 'error' => "Invalid status transition: {$current} -> {$status}"], 400);
        }

        // If moving to completed: reduce stock safely
        if ($status === 'completed') {

            if ($current !== 'delivered') {
                $pdo->rollBack();
                respond(['success' => false, 'error' => 'Order must be delivered before completing'], 400);
            }

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

            // notify
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
        if ($pdo->inTransaction()) $pdo->rollBack();
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

        $os = normStatus($order['order_status'] ?? '');
        if ($os === 'completed') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Cannot refund a completed order'], 400);
        }

        if (normStatus($order['payment_status'] ?? '') !== 'paid') {
            $pdo->rollBack();
            respond(['success' => false, 'error' => 'Order not paid or already refunded'], 400);
        }

        // restock items
        $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $items->execute([$orderId]);
        foreach ($items as $it) {
            $pdo->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?")
                ->execute([(int)$it['quantity'], (int)$it['product_id']]);
        }

        // mark refunded + cancelled
        $pdo->prepare("UPDATE orders SET payment_status='refunded', order_status='cancelled' WHERE order_id = ?")
            ->execute([$orderId]);

        // payments amount -> 0
        $pdo->prepare("UPDATE payments SET amount = 0 WHERE order_id = ?")
            ->execute([$orderId]);

        $pdo->commit();
        respond(['success' => true, 'message' => 'Order refunded and items restocked']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[orders_api refund] ' . $e->getMessage());
        respond(['success' => false, 'error' => 'Server error'], 500);
    }
}

/* =====================================================
   FALLBACK
===================================================== */
respond(['success' => false, 'error' => 'Invalid action'], 400);
