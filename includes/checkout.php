<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conn.php';

$pdo ??= $conn ?? null;
if (!$pdo instanceof PDO) {
    die('Database connection missing.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;
$cartSessionKey = $userId ? "cart_user_{$userId}" : 'cart_guest';
if (!isset($_SESSION[$cartSessionKey])) $_SESSION[$cartSessionKey] = [];
$cart = &$_SESSION[$cartSessionKey];

if (!$userId) {
    $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/view/checkout.php';
    header('Location: ../auth/Log/login.php');
    exit;
}

if (!$cart) {
    header('Location: products.php');
    exit;
}

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* =====================================================
   LOAD PAYMENT METHODS (dynamic)
===================================================== */
$paymentMethods = [];
try {
    $pm = $pdo->query("
        SELECT method_id, method_code, method_name
        FROM payment_methods
        WHERE is_active = 1
        ORDER BY method_name
    ");
    $paymentMethods = $pm->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $t) {
    $paymentMethods = [];
}

/* Build map code => id */
$methodCodeToId = [];
foreach ($paymentMethods as $m) {
    $methodCodeToId[strtolower(trim($m['method_code']))] = (int)$m['method_id'];
}

/* =====================================================
   LOAD CART PRODUCTS (SAFE IN QUERY)
===================================================== */
$productIds = array_keys($cart);
$productIds = array_values(array_filter(array_map('intval', $productIds), fn($v) => $v > 0));

if (!$productIds) {
    header('Location: products.php');
    exit;
}

$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$stmt = $pdo->prepare("
    SELECT product_id, name, price, image_url, stock
    FROM products
    WHERE product_id IN ($placeholders)
");
$stmt->execute($productIds);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* If cart has products not found */
if (!$products) {
    $_SESSION[$cartSessionKey] = [];
    header('Location: products.php');
    exit;
}

/* =====================================================
   CALCULATE TOTAL
===================================================== */
$subtotal = 0.0;
foreach ($products as $p) {
    $pid = (int)$p['product_id'];
    $qty = (int)($cart[$pid] ?? 0);
    if ($qty < 1) continue;

    $subtotal += ((float)$p['price']) * $qty;
}

$tax   = $subtotal * 0.02;
$total = $subtotal + $tax;

$error = null;

/* =====================================================
   CHECKOUT SUBMIT
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');

    // payment is now method_code (aba/acleda/wing/...)
    $paymentCode = strtolower(trim($_POST['payment'] ?? 'aba'));

    if ($name === '' || $email === '' || $address === '') {
        $error = 'Please fill required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!isset($methodCodeToId[$paymentCode])) {
        $error = 'Invalid payment method.';
    } else {
        try {
            $pdo->beginTransaction();

            $confirmedPayment = !empty($_POST['confirm_paid']) && (string)$_POST['confirm_paid'] === '1';
            $payment_status = $confirmedPayment ? 'paid' : 'unpaid';

            // Create order
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (user_id, order_type, total, payment_status, order_status)
                VALUES (?, 'online', ?, ?, 'pending')
            ");
            $orderStmt->execute([$userId, $total, $payment_status]);
            $dbOrderId = (int)$pdo->lastInsertId();

            if ($dbOrderId <= 0) {
                throw new Exception('Failed to create order.');
            }

            // Insert shipping
            $shipStmt = $pdo->prepare("
                INSERT INTO shipping (order_id, address, city, country)
                VALUES (?, ?, ?, ?)
            ");
            $shipStmt->execute([$dbOrderId, $address, $city, $country]);

            // Prepare statements for items, inventory
            $itemStmt        = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stockStmt       = $pdo->prepare("SELECT stock FROM products WHERE product_id = ? FOR UPDATE");
            $updateStockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
            $invLogStmt      = $pdo->prepare("INSERT INTO inventory_logs (product_id, change_qty, reason) VALUES (?, ?, 'order')");

            // Insert items & update stock
            foreach ($products as $p) {
                $pid = (int)$p['product_id'];
                $qty = (int)($cart[$pid] ?? 0);
                if ($qty < 1) continue;

                $stockStmt->execute([$pid]);
                $row = $stockStmt->fetch(PDO::FETCH_ASSOC);
                $currentStock = $row ? (int)$row['stock'] : 0;

                if ($currentStock < $qty) {
                    throw new Exception('Insufficient stock for product: ' . ($p['name'] ?? ''));
                }

                $itemStmt->execute([$dbOrderId, $pid, $qty, (float)$p['price']]);
                $updateStockStmt->execute([$qty, $pid]);
                $invLogStmt->execute([$pid, -$qty]);
            }

            // Insert payment (NEW SCHEMA)
            $paymentMethodId = $methodCodeToId[$paymentCode];
            $payAmount = $confirmedPayment ? $total : 0.00;

            $payStmt = $pdo->prepare("
                INSERT INTO payments (order_id, payment_method_id, amount)
                VALUES (?, ?, ?)
            ");
            $payStmt->execute([$dbOrderId, $paymentMethodId, $payAmount]);

            $pdo->commit();

            // Store last_order for summary page
            $_SESSION['last_order'] = [
                'order_id'    => $dbOrderId,
                'name'        => $name,
                'email'       => $email,
                'address'     => $address,
                'city'        => $city,
                'country'     => $country,
                'phone'       => $phone,
                'payment'     => $paymentCode,
                'items'       => $products,
                'quantities'  => $cart,
                'subtotal'    => $subtotal,
                'tax'         => $tax,
                'total'       => $total,
                'created_at'  => date('Y-m-d H:i:s'),
            ];

            // Admin notification
            try {
                $noteTitle = ($payment_status === 'paid') ? 'New paid order' : 'New order placed';
                $noteMsg = sprintf('Order #%d by %s (%s) — %s', $dbOrderId, $name, $email, number_format($total, 2));
                $nstmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type, reference_id, is_read, created_at)
                    VALUES (NULL, :title, :msg, :type, :ref, 0, NOW())
                ");
                $nstmt->execute([
                    ':title' => $noteTitle,
                    ':msg'   => $noteMsg,
                    ':type'  => 'payment',
                    ':ref'   => $dbOrderId,
                ]);
            } catch (Throwable) {
                // ignore
            }

            // Clear cart
            $_SESSION[$cartSessionKey] = [];

            header('Location: order_success.php');
            exit;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Order failed: ' . $ex->getMessage();
        }
    }
}
