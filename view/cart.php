<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use per-user cart (so different users don't see each other's carts)
$userId = $_SESSION['user_id'] ?? null;
$cartSessionKey = $userId ? "cart_user_{$userId}" : 'cart_guest';
if (!isset($_SESSION[$cartSessionKey])) $_SESSION[$cartSessionKey] = [];
$cartRef = &$_SESSION[$cartSessionKey];
// shipping location per-user
$locSessionKey = $userId ? "shipping_location_user_{$userId}" : 'shipping_location_guest';
if (!isset($_SESSION[$locSessionKey])) $_SESSION[$locSessionKey] = null;

/* ===============================
   CART ACTIONS (AJAX)
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['product_id'] ?? 0);
    $qty    = (int)($_POST['quantity'] ?? 1);

    if ($action === 'update') {
        if ($qty > 0) $cartRef[$id] = $qty;
        else unset($cartRef[$id]);
    }

    if ($action === 'add') {
        if ($id > 0) {
            $existing = (int)($cartRef[$id] ?? 0);
            $cartRef[$id] = $existing + max(1, $qty);
        }
    }

    if ($action === 'remove') {
        unset($cartRef[$id]);
    }

    $count = array_sum($cartRef);
    $subtotal = 0;

    if ($count > 0) {
        $ids = implode(',', array_keys($cartRef));
        $stmt = $pdo->query("SELECT product_id, price FROM products WHERE product_id IN ($ids)");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $subtotal += $r['price'] * $cartRef[$r['product_id']];
        }
    }

    $tax = $subtotal * 0.01;
    $total = $subtotal + $tax;

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => $count,
        'cart_count' => $count,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total
    ]);
    exit;
}

$cart = $cartRef;
$products = [];
$subtotal = 0;

if ($cart) {
    $ids = implode(',', array_keys($cart));
    $stmt = $pdo->query(
        "SELECT product_id, name, price, image_url, stock
         FROM products WHERE product_id IN ($ids)"
    );
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $p) {
        $subtotal += $p['price'] * $cart[$p['product_id']];
    }
}

$tax = $subtotal * 0.02;
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bag</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-white">

    <?php require_once '../includes/navbar.php'; ?>

    <main class="max-w-7xl mx-auto px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-12">

        <!-- ================= BAG ================= -->
        <section class="lg:col-span-2">
            <h1 class="text-2xl font-semibold mb-8">Bag</h1>

            <?php if (!$products): ?>
                <p class="text-gray-500">Your bag is empty.</p>
                <a href="products.php"
                    class="inline-block mt-6 border border-black px-6 py-3 font-semibold">
                    Start Shopping
                </a>
            <?php else: ?>

                <?php foreach ($products as $p):
                    $qty = $cart[$p['product_id']];
                ?>
                    <!-- ITEM -->
                    <div class="flex gap-6 pb-10 border-b mb-10">

                        <!-- IMAGE -->
                        <div class="w-36 h-36 bg-gray-100">
                            <img src="<?= htmlspecialchars($p['image_url']) ?>"
                                class="w-full h-full object-cover">
                        </div>

                        <!-- DETAILS -->
                        <div class="flex-1">

                            <div class="flex justify-between">
                                <div>
                                    <h2 class="font-medium text-lg"><?= htmlspecialchars($p['name']) ?></h2>
                                    <p class="text-gray-600 text-sm mt-1">Men's Shoes</p>
                                    <div class="mt-4">
                                        <p class="text-gray-600 text-sm mb-2">Select Size</p>

                                        <div class="grid grid-cols-4 gap-2">
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 7</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 7.5</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 8</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 8.5</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 9</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 9.5</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 10</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 10.5</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 11</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 11.5</button>
                                            <button class="size-btn border py-2 text-sm hover:border-black">US 12</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="font-semibold text-lg">
                                    $<?= number_format((float)($p['price'] ?? 0), 2) ?>
                                </div>
                            </div>

                            <!-- ACTIONS -->
                            <div class="flex items-center gap-6 mt-6">

                                <!-- QTY -->
                                <div class="flex items-center border rounded-full px-3 py-1 gap-4">
                                    <button onclick="changeQty(<?= $p['product_id'] ?>,-1)">−</button>

                                    <input type="number"
                                        min="1"
                                        max="<?= min($p['stock'], 10) ?>"
                                        value="<?= $qty ?>"
                                        data-id="<?= $p['product_id'] ?>"
                                        class="w-8 text-center outline-none">

                                    <button onclick="changeQty(<?= $p['product_id'] ?>,1)">+</button>
                                </div>

                                <button onclick="removeItem(<?= $p['product_id'] ?>)" title="Remove">
                                    <i class="fas fa-trash"></i>
                                </button>

                                <button title="Save">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>

                            <!-- SHIPPING -->
                            <div class="mt-6 text-sm">
                                <p class="font-medium">Shipping</p>
                                <p>
                                    Arrives by Fri, Jan 9
                                    <span class="underline ml-1 cursor-pointer">Edit Location</span>
                                </p>

                                <p class="mt-4 font-medium">Free Pickup</p>
                                <p class="underline cursor-pointer">Find a Store</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- ================= SUMMARY ================= -->
        <aside>
            <h2 class="text-xl font-semibold mb-6">Summary</h2>

            <div class="space-y-4 text-sm">
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <span>$<span id="summarySubtotal"><?= number_format($subtotal, 2) ?></span></span>
                </div>

                <div class="flex justify-between">
                    <span>Estimated Shipping & Handling</span>
                    <span>Free</span>
                </div>

                <div class="flex justify-between">
                    <span>Estimated Tax (2%)</span>
                    <span>$<span id="summaryTax"><?= number_format($tax, 2) ?></span></span>
                </div>

                <hr>

                <div class="flex justify-between font-semibold text-lg">
                    <span>Total</span>
                    <span>$<span id="summaryTotal"><?= number_format($total, 2) ?></span></span>
                </div>
            </div>

            <!-- FREE SHIPPING BAR -->
            <div class="mt-6 text-sm">
                You qualify for <strong>Free Shipping</strong> as a Member!
                <span class="underline">Join us</span> or <span class="underline">Sign-in</span>

                <div class="mt-3 bg-gray-200 h-2 rounded-full">
                    <div class="bg-black h-2 rounded-full w-full"></div>
                </div>
                <p class="text-right mt-1">$50</p>
            </div>

            <button onclick="checkout()"
                class="w-full mt-8 bg-black text-white py-4 rounded-full text-lg font-semibold">
                Checkout
            </button>

            <button
                class="w-full mt-4 border py-4 rounded-full text-lg font-semibold">

                PayPal
            </button>
        </aside>

    </main>

    <script>
        function changeQty(id, delta) {
            const input = document.querySelector(`input[data-id="${id}"]`);
            let qty = parseInt(input.value) + delta;
            qty = Math.max(1, Math.min(qty, input.max));
            input.value = qty;
            updateCart(id, qty);
        }

        function updateCart(id, qty) {
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'update',
                    product_id: id,
                    quantity: qty
                })
            }).then(r => r.json()).then(d => {
                document.getElementById('summarySubtotal').textContent = d.subtotal.toFixed(2);
                document.getElementById('summaryTax').textContent = d.tax.toFixed(2);
                document.getElementById('summaryTotal').textContent = d.total.toFixed(2);
            });
        }

        function removeItem(id) {
            if (!confirm('Remove this item?')) return;
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'remove',
                    product_id: id
                })
            }).then(() => location.reload());
        }

        function checkout() {
            if (<?= count($cart) ?> === 0) alert('Cart is empty');
            else location.href = 'checkout.php';
        }
        document.querySelectorAll('.size-btn').forEach(btn => {
            btn.onclick = () => {
                document.querySelectorAll('.size-btn')
                    .forEach(b => b.classList.remove('border-black'));
                btn.classList.add('border-black');
            };
        });
    </script>

</body>

</html>