<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;
$cartSessionKey = $userId ? "cart_user_{$userId}" : 'cart_guest';
if (!isset($_SESSION[$cartSessionKey])) $_SESSION[$cartSessionKey] = [];
$cart = &$_SESSION[$cartSessionKey];

if (!$userId) {
    $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/view/checkout.php';
    header('Location: ../auth/login.php');
    exit;
}

if (!$cart) {
    header('Location: products.php');
    exit;
}

$ids = implode(',', array_keys($cart));
$stmt = $pdo->query(
    "SELECT product_id, name, price, image_url, stock FROM products WHERE product_id IN ($ids)"
);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($products as $p) {
    $subtotal += $p['price'] * $cart[$p['product_id']];
}

$tax = $subtotal * 0.02;
$total = $subtotal + $tax;

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $payment = trim($_POST['payment'] ?? 'card');

    if ($name === '' || $email === '' || $address === '') {
        $error = 'Please fill required fields.';
    } else {
        try {
            $pdo->beginTransaction();

            $payment_status = in_array($payment, ['card', 'paypal']) ? 'paid' : 'unpaid';

            $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, order_type, total, payment_status, order_status) VALUES (?, 'online', ?, ?, 'pending')");
            $orderStmt->execute([$userId, $total, $payment_status]);
            $dbOrderId = (int)$pdo->lastInsertId();

            if ($dbOrderId <= 0) throw new Exception('Failed to create order.');

            // insert shipping
            $shipStmt = $pdo->prepare("INSERT INTO shipping (order_id, address, city, country) VALUES (?, ?, ?, ?)");
            $shipStmt->execute([$dbOrderId, $address, $city, $country]);

            // prepare statements for items, payments and inventory
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $payStmt = $pdo->prepare("INSERT INTO payments (order_id, payment_method, amount) VALUES (?, ?, ?)");
            $stockStmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = ? FOR UPDATE");
            $updateStockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
            $invLogStmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, change_qty, reason) VALUES (?, ?, 'order')");

            // insert each item, check stock
            foreach ($products as $p) {
                $pid = (int)$p['product_id'];
                $qty = (int)($cart[$pid] ?? 1);

                // lock and check stock
                $stockStmt->execute([$pid]);
                $row = $stockStmt->fetch(PDO::FETCH_ASSOC);
                $currentStock = $row ? (int)$row['stock'] : 0;
                if ($currentStock < $qty) {
                    throw new Exception('Insufficient stock for product: ' . $p['name']);
                }

                $itemStmt->execute([$dbOrderId, $pid, $qty, $p['price']]);

                // decrement stock
                $updateStockStmt->execute([$qty, $pid]);
                $invLogStmt->execute([$pid, -$qty]);
            }

            $payMethod = $payment === 'cod' ? 'cash' : ($payment === 'card' ? 'card' : ($payment === 'paypal' ? 'paypal' : $payment));
            $payAmount = $payment_status === 'paid' ? $total : 0;
            $payStmt->execute([$dbOrderId, $payMethod, $payAmount]);

            $pdo->commit();

            // store last_order for summary page
            $_SESSION['last_order'] = [
                'order_id' => $dbOrderId,
                'name' => $name,
                'email' => $email,
                'address' => $address,
                'city' => $city,
                'country' => $country,
                'phone' => $phone,
                'payment' => $payment,
                'items' => $products,
                'quantities' => $cart,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'created_at' => date('Y-m-d H:i:s')
            ];

            // create admin notification about the order (so it appears in admin navbar)
            try {
                $noteTitle = $payment_status === 'paid' ? 'New paid order' : 'New order placed';
                $noteMsg = sprintf('Order #%d by %s (%s) — %s', $dbOrderId, $name, $email, number_format($total, 2));
                $nstmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id, is_read, created_at) VALUES (NULL, :title, :msg, :type, :ref, 0, NOW())");
                $nstmt->execute([
                    ':title' => $noteTitle,
                    ':msg' => $noteMsg,
                    ':type' => 'payment',
                    ':ref' => $dbOrderId
                ]);
            } catch (Throwable $e) {
                // ignore notification errors
            }

            // clear cart
            $_SESSION[$cartSessionKey] = [];

            header('Location: order_success.php');
            exit;
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Order failed: ' . $ex->getMessage();
        }
    }
}
