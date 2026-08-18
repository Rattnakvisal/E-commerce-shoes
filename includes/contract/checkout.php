<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

$pdo ??= $conn ?? null;
if (!$pdo instanceof PDO) {
    die('Database connection missing.');
}

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/* =========================
   HELPERS
========================= */
function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function redirect(string $url): void
{
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    }
    echo "<script>location.href=" . json_encode($url) . ";</script>";
    exit;
}

function checkoutColExists(PDO $pdo, string $table, string $col): bool
{
    static $cache = [];
    $key = $table . '.' . $col;
    if (isset($cache[$key])) return $cache[$key];

    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$col]);
    $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

    return $cache[$key];
}

/* =========================
   AUTH
========================= */
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/MyBrand_Ecommerce/view/checkout.php';
    redirect('/MyBrand_Ecommerce/auth/Log/login.php');
}

// FK safe: confirm user exists
$userCols = ['user_id', 'name', 'email'];
if (checkoutColExists($pdo, 'users', 'phone')) {
    $userCols[] = 'phone';
}
if (checkoutColExists($pdo, 'users', 'address')) {
    $userCols[] = 'address';
}
$u = $pdo->prepare("SELECT " . implode(', ', $userCols) . " FROM users WHERE user_id = :id LIMIT 1");
$u->execute([':id' => $userId]);
$userRow = $u->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    session_destroy();
    redirect('/MyBrand_Ecommerce/auth/Log/login.php');
}

/* =========================
   CART (SESSION)
========================= */
$cartSessionKey = "cart_user_{$userId}";
if (!isset($_SESSION[$cartSessionKey]) || !is_array($_SESSION[$cartSessionKey])) {
    $_SESSION[$cartSessionKey] = [];
}
$cart = &$_SESSION[$cartSessionKey];

if (!$cart) {
    redirect('/MyBrand_Ecommerce/view/content/products.php');
}

/* =====================================================
   LOAD PAYMENT METHODS
===================================================== */
$paymentMethods = [];
try {
    $pm = $pdo->query("
        SELECT method_id, method_code, method_name
        FROM payment_methods
        WHERE is_active = 1
        ORDER BY method_name
    ");
    $paymentMethods = $pm->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
    $paymentMethods = [];
}

$methodCodeToId = [];
foreach ($paymentMethods as $m) {
    $methodCodeToId[strtolower(trim((string)$m['method_code']))] = (int)$m['method_id'];
}

/* =====================================================
   LOAD CART PRODUCTS
===================================================== */
$productIds = array_keys($cart);
$productIds = array_values(array_filter(array_map('intval', $productIds), fn($v) => $v > 0));

if (!$productIds) {
    redirect('/MyBrand_Ecommerce/view/content/products.php');
}

$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$stmt = $pdo->prepare("
    SELECT product_id, name, price, image_url, stock
    FROM products
    WHERE product_id IN ($placeholders) AND status = 'active'
");
$stmt->execute($productIds);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

if (!$products) {
    $_SESSION[$cartSessionKey] = [];
    redirect('/MyBrand_Ecommerce/view/content/products.php');
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
$success = null;

/* =====================================================
   DEFAULT FORM VALUES
===================================================== */
$prefillName  = (string)($userRow['name'] ?? '');
$prefillEmail = (string)($userRow['email'] ?? '');
$prefillPhone = (string)($userRow['phone'] ?? '');
$prefillAddr  = (string)($userRow['address'] ?? '');

$form = [
    'name'    => $prefillName,
    'email'   => $prefillEmail,
    'phone'   => $prefillPhone,
    'address' => $prefillAddr,
    'city'    => '',
    'country' => 'Cambodia',
    'lat'     => '',
    'lng'     => '',
    'payment' => '',
    'confirm_paid' => '0',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // keep values on error
    foreach ($form as $k => $v) {
        if (isset($_POST[$k])) $form[$k] = trim((string)$_POST[$k]);
    }

    $name    = trim((string)($_POST['name'] ?? ''));
    $email   = trim((string)($_POST['email'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $city    = trim((string)($_POST['city'] ?? ''));
    $country = trim((string)($_POST['country'] ?? ''));
    $phone   = trim((string)($_POST['phone'] ?? ''));

    // gps
    $latRaw = $_POST['lat'] ?? null;
    $lngRaw = $_POST['lng'] ?? null;
    $lat = ($latRaw !== null && $latRaw !== '') ? (float)$latRaw : null;
    $lng = ($lngRaw !== null && $lngRaw !== '') ? (float)$lngRaw : null;

    // payment method code (aba/acleda/wing/...)
    $paymentCode = strtolower(trim((string)($_POST['payment'] ?? '')));

    // paid confirm checkbox
    $confirmedPayment = !empty($_POST['confirm_paid']) && (string)$_POST['confirm_paid'] === '1';

    if ($name === '' || $email === '' || $address === '' || $phone === '') {
        $error = 'Please fill required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!isset($methodCodeToId[$paymentCode])) {
        $error = 'Invalid payment method.';
    } elseif ($lat !== null && ($lat < -90 || $lat > 90)) {
        $error = 'Invalid latitude value.';
    } elseif ($lng !== null && ($lng < -180 || $lng > 180)) {
        $error = 'Invalid longitude value.';
    } else {

        // STATUS PIPELINE AT CHECKOUT
        $payment_status = $confirmedPayment ? 'paid' : 'unpaid';
        $order_status   = $confirmedPayment ? 'processing' : 'pending';

        // shipping.status enum: pending/shipped/delivered
        $ship_status    = $confirmedPayment ? 'pending' : 'pending'; // keep safe

        try {
            $pdo->beginTransaction();

            // CREATE ORDER
            $orderColumns = ['user_id', 'order_type', 'total', 'payment_status', 'order_status'];
            $orderValues = [$userId, 'online', $total, $payment_status, $order_status];

            if (checkoutColExists($pdo, 'orders', 'latitude')) {
                $orderColumns[] = 'latitude';
                $orderValues[] = $lat;
            }
            if (checkoutColExists($pdo, 'orders', 'longitude')) {
                $orderColumns[] = 'longitude';
                $orderValues[] = $lng;
            }

            $orderPlaceholders = implode(', ', array_fill(0, count($orderColumns), '?'));
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (" . implode(', ', $orderColumns) . ")
                VALUES ($orderPlaceholders)
            ");
            $orderStmt->execute($orderValues);

            $dbOrderId = (int)$pdo->lastInsertId();
            if ($dbOrderId <= 0) {
                throw new Exception('Failed to create order.');
            }

            // INSERT SHIPPING
            $shippingColumns = ['order_id', 'address'];
            $shippingValues = [$dbOrderId, $address];

            if (checkoutColExists($pdo, 'shipping', 'city')) {
                $shippingColumns[] = 'city';
                $shippingValues[] = $city;
            }
            if (checkoutColExists($pdo, 'shipping', 'country')) {
                $shippingColumns[] = 'country';
                $shippingValues[] = $country;
            }
            if (checkoutColExists($pdo, 'shipping', 'latitude')) {
                $shippingColumns[] = 'latitude';
                $shippingValues[] = $lat;
            }
            if (checkoutColExists($pdo, 'shipping', 'longitude')) {
                $shippingColumns[] = 'longitude';
                $shippingValues[] = $lng;
            }
            if (checkoutColExists($pdo, 'shipping', 'status') || checkoutColExists($pdo, 'shipping', 'STATUS')) {
                $shippingColumns[] = 'status';
                $shippingValues[] = $ship_status;
            }

            $shippingPlaceholders = implode(', ', array_fill(0, count($shippingColumns), '?'));
            $shipStmt = $pdo->prepare("
                INSERT INTO shipping (" . implode(', ', $shippingColumns) . ")
                VALUES ($shippingPlaceholders)
            ");
            $shipStmt->execute($shippingValues);

            // ORDER ITEMS + STOCK
            $itemStmt  = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");

            $stockStmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = ? FOR UPDATE");
            $updateStockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");

            // optional inventory log
            $invLogStmt = null;
            try {
                $invLogStmt = $pdo->prepare("
                    INSERT INTO inventory_logs (product_id, change_qty, reason)
                    VALUES (?, ?, 'order')
                ");
            } catch (Throwable) {
                $invLogStmt = null;
            }

            foreach ($products as $p) {
                $pid = (int)$p['product_id'];
                $qty = (int)($cart[$pid] ?? 0);
                if ($qty < 1) continue;

                $stockStmt->execute([$pid]);
                $currentStock = (int)$stockStmt->fetchColumn();

                if ($currentStock < $qty) {
                    throw new Exception('Insufficient stock for product: ' . ($p['name'] ?? ''));
                }

                $itemStmt->execute([$dbOrderId, $pid, $qty, (float)$p['price']]);
                $updateStockStmt->execute([$qty, $pid]);

                if ($invLogStmt) {
                    $invLogStmt->execute([$pid, -$qty]);
                }
            }

            // PAYMENT RECORD
            $paymentMethodId = $methodCodeToId[$paymentCode];
            $payAmount = $confirmedPayment ? $total : 0.00;

            $payStmt = $pdo->prepare("
                INSERT INTO payments (order_id, payment_method_id, amount)
                VALUES (?, ?, ?)
            ");
            $payStmt->execute([$dbOrderId, $paymentMethodId, $payAmount]);

            $pdo->commit();

            // SAVE LAST ORDER IN SESSION
            $_SESSION['last_order'] = [
                'order_id'    => $dbOrderId,
                'name'        => $name,
                'email'       => $email,
                'address'     => $address,
                'city'        => $city,
                'country'     => $country,
                'phone'       => $phone,
                'payment'     => $paymentCode,
                'payment_status' => $payment_status,
                'order_status'   => $order_status,
                'shipping_status' => $ship_status,
                'lat'         => $lat,
                'lng'         => $lng,
                'items'       => $products,
                'quantities'  => $cart,
                'subtotal'    => $subtotal,
                'tax'         => $tax,
                'total'       => $total,
                'created_at'  => date('Y-m-d H:i:s'),
            ];

            // NOTIFICATIONS (optional)
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
                    ':type'  => 'order',
                    ':ref'   => $dbOrderId,
                ]);
            } catch (Throwable) { /* ignore */
            }

            try {
                $unstmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type, reference_id, is_read, created_at)
                    VALUES (:uid, :title, :msg, :type, :ref, 0, NOW())
                ");
                $unstmt->execute([
                    ':uid'   => $userId,
                    ':title' => 'Order confirmed',
                    ':msg'   => sprintf('Your order #%d has been received. Total: %s', $dbOrderId, number_format($total, 2)),
                    ':type'  => 'order',
                    ':ref'   => $dbOrderId,
                ]);
            } catch (Throwable) { /* ignore */
            }

            // TELEGRAM (paid only)
            try {
                if ($payment_status === 'paid') {
                    require_once __DIR__ . '/../../auth/Helper/telegram.php';
                    telegram_notify_payment_success(
                        $dbOrderId,
                        $name,
                        $email,
                        $phone,
                        $address,
                        $city,
                        $country,
                        $paymentCode,
                        (float)$total,
                        $products,
                        $cart,
                        $lat,
                        $lng
                    );
                }
            } catch (Throwable) { /* ignore */
            }

            // Clear cart
            $_SESSION[$cartSessionKey] = [];

            redirect('/MyBrand_Ecommerce/view/content/order_success.php');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Order failed: ' . $ex->getMessage();
        }
    }
}
