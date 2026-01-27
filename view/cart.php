<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===============================
   SESSION KEYS
================================ */
$userId = $_SESSION['user_id'] ?? null;

$cartKey = $userId ? "cart_user_$userId" : 'cart_guest';
$locationKey = $userId ? "shipping_location_user_$userId" : 'shipping_location_guest';

$_SESSION[$cartKey] ??= [];
$_SESSION[$locationKey] ??= null;

$cart = &$_SESSION[$cartKey];

/* ===============================
   HELPER: CALCULATE TOTALS
================================ */
function calculateCartTotals(PDO $pdo, array $cart): array
{
    if (empty($cart)) {
        return [
            'count' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
        ];
    }

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT product_id, price FROM products WHERE product_id IN ($placeholders)"
    );
    $stmt->execute($ids);

    $subtotal = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $subtotal += $row['price'] * $cart[$row['product_id']];
    }

    $tax = $subtotal * 0.01;

    return [
        'count' => array_sum($cart),
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $subtotal + $tax,
    ];
}

/* ===============================
   AJAX CART ACTIONS
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    switch ($action) {

        case 'add':
            if ($productId <= 0) break;

            $stmt = $pdo->prepare(
                "SELECT stock FROM products WHERE product_id = ?"
            );
            $stmt->execute([$productId]);
            $stock = (int)($stmt->fetchColumn() ?? 0);

            if ($stock <= 0) {
                echo json_encode([
                    'success' => false,
                    'error' => 'out_of_stock',
                    'message' => 'Product is out of stock'
                ]);
                exit;
            }

            $currentQty = (int)($cart[$productId] ?? 0);
            $cart[$productId] = min($currentQty + $qty, $stock);
            break;

        case 'update':
            if ($productId <= 0) break;

            if ($qty > 0) {
                $cart[$productId] = $qty;
            } else {
                unset($cart[$productId]);
            }
            break;

        case 'remove':
            unset($cart[$productId]);
            break;
    }

    $totals = calculateCartTotals($pdo, $cart);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'cart_count' => $totals['count'],
        'count' => $totals['count'],
        'subtotal' => $totals['subtotal'],
        'tax' => $totals['tax'],
        'total' => $totals['total'],
    ]);
    exit;
}

/* ===============================
   PAGE LOAD DATA
================================ */
$products = [];
$subtotal = 0;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT product_id, name, price, image_url, stock
         FROM products WHERE product_id IN ($placeholders)"
    );
    $stmt->execute($ids);

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

    <?php
    require_once '../includes/topbar.php';
    require_once '../includes/navbar.php';
    ?>

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

                                <button
                                    onclick="addToWishlist(<?= $p['product_id'] ?>)">
                                    <i class="far fa-heart text-sm"></i>
                                </button>
                            </div>

                            <!-- SHIPPING -->
                            <div class="mt-6 text-sm">
                                <p class="font-medium">Shipping</p>
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
        </aside>
    </main>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <!-- Js -->
    <script src="../view/assets/Js/prodcuts.js"></script>
    <script src="../view/assets/Js/cart.js"></script>
    <script>
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