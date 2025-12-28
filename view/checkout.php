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

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        input,
        textarea {
            outline: none;
        }
    </style>
</head>

<body class="bg-white">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="max-w-6xl mx-auto px-4 py-10">

        <!-- Page Title -->
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Checkout</h1>

        <?php if (!empty($error)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- Checkout Form -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border p-6 space-y-5">

                <h2 class="text-lg font-semibold text-gray-700 mb-2">
                    Shipping Information
                </h2>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        Full Name *
                    </label>
                    <input
                        name="name"
                        value="<?= e($_POST['name'] ?? '') ?>"
                        required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-black focus:border-black">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        Email *
                    </label>
                    <input
                        type="email"
                        name="email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-black focus:border-black">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        Address *
                    </label>
                    <input
                        name="address"
                        value="<?= e($_POST['address'] ?? '') ?>"
                        required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-black focus:border-black">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            City
                        </label>
                        <input
                            name="city"
                            value="<?= e($_POST['city'] ?? '') ?>"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">
                            Country
                        </label>
                        <input
                            name="country"
                            value="<?= e($_POST['country'] ?? '') ?>"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        Phone
                    </label>
                    <input
                        name="phone"
                        value="<?= e($_POST['phone'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        Payment Method
                    </label>
                    <select
                        name="payment"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                        <option value="card">Credit / Debit Card</option>
                        <option value="paypal">PayPal</option>
                        <option value="cod">Cash on Delivery</option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="w-full bg-black hover:bg-gray-900 text-white py-3 rounded-lg font-semibold transition">
                    Place Order
                </button>
            </form>

            <!-- Order Summary -->
            <aside class="bg-white rounded-xl shadow-sm border p-6">

                <h2 class="text-lg font-semibold text-gray-700 mb-5">
                    Order Summary
                </h2>

                <div class="space-y-4">

                    <?php foreach ($products as $p):
                        $qty = $cart[$p['product_id']];
                    ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <img
                                    src="<?= e($p['image_url']) ?>"
                                    class="w-16 h-16 rounded-lg object-cover bg-gray-100">
                                <div>
                                    <p class="font-medium text-gray-800">
                                        <?= e($p['name']) ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Qty: <?= e($qty) ?>
                                    </p>
                                </div>
                            </div>
                            <p class="font-semibold text-gray-800">
                                $<?= number_format($p['price'] * $qty, 2) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>

                    <hr>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span>$<?= number_format($subtotal, 2) ?></span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tax (2%)</span>
                        <span>$<?= number_format($tax, 2) ?></span>
                    </div>

                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>Total</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>
                </div>
            </aside>

        </div>
</body>

</html>