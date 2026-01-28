<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
$last = $_SESSION['last_order'] ?? null;

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

if (!$last) {
    header('Location: /E-commerce-shoes/view/content/products.php');
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order Success</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">

    <?php
    require_once __DIR__ . '/../../includes/shader/topbar.php';
    require_once __DIR__ . '/../../includes/shader/navbar.php';
    ?>

    <main class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white rounded-2xl shadow p-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Order placed successfully</h1>
            </div>

            <p class="text-gray-600 mb-6">
                Order <b>#<?= e($last['order_id']) ?></b> has been received.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-5 rounded-xl border">
                    <h2 class="font-semibold text-gray-900 mb-2">Customer</h2>
                    <p class="text-sm text-gray-700">Name: <?= e($last['name']) ?></p>
                    <p class="text-sm text-gray-700">Email: <?= e($last['email']) ?></p>
                    <p class="text-sm text-gray-700">Phone: <?= e($last['phone']) ?></p>
                </div>

                <div class="p-5 rounded-xl border">
                    <h2 class="font-semibold text-gray-900 mb-2">Shipping</h2>
                    <p class="text-sm text-gray-700"><?= e($last['address']) ?></p>
                    <p class="text-sm text-gray-700"><?= e($last['city']) ?> <?= e($last['country']) ?></p>
                    <p class="text-sm text-gray-700">Payment: <?= e(strtoupper($last['payment'])) ?></p>
                </div>
            </div>

            <div class="mt-8">
                <h2 class="font-semibold text-gray-900 mb-3">Items</h2>

                <div class="space-y-3">
                    <?php
                    $items = $last['items'] ?? [];
                    $qtyMap = $last['quantities'] ?? [];

                    foreach ($items as $p):
                        $pid = (int)$p['product_id'];
                        $qty = (int)($qtyMap[$pid] ?? 0);
                        if ($qty < 1) continue;
                    ?>
                        <div class="flex items-center justify-between border rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <img src="<?= e($p['image_url']) ?>" class="w-14 h-14 rounded-lg object-cover bg-gray-100">
                                <div>
                                    <div class="font-semibold text-gray-900"><?= e($p['name']) ?></div>
                                    <div class="text-sm text-gray-600">$<?= number_format((float)$p['price'], 2) ?> × <?= $qty ?></div>
                                </div>
                            </div>
                            <div class="font-bold">
                                $<?= number_format(((float)$p['price'] * $qty), 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 border-t pt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span>$<?= number_format((float)$last['subtotal'], 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax</span>
                        <span>$<?= number_format((float)$last['tax'], 2) ?></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total</span>
                        <span class="text-blue-600">$<?= number_format((float)$last['total'], 2) ?></span>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <a href="/E-commerce-shoes/view/content/products.php"
                        class="px-5 py-3 rounded-xl bg-gray-900 text-white font-semibold hover:bg-black">
                        Continue shopping
                    </a>
                    <a href="/E-commerce-shoes/view/content/orders.php"
                        class="px-5 py-3 rounded-xl border font-semibold hover:bg-gray-50">
                        View my orders
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../../includes/shader/footer.php'; ?>
</body>

</html>