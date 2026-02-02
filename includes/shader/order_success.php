<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function money($n): string
{
    return '$' . number_format((float)$n, 2);
}

$last = $_SESSION['last_order'] ?? null;

if (!$last) {
    header('Location: /E-commerce-shoes/view/content/products.php');
    exit;
}

unset($_SESSION['last_order']);

// Normalize / defaults
$orderId   = $last['order_id'] ?? '';
$name      = $last['name'] ?? '';
$email     = $last['email'] ?? '';
$phone     = $last['phone'] ?? '';
$address   = $last['address'] ?? '';
$city      = $last['city'] ?? '';
$country   = $last['country'] ?? '';
$payment   = strtoupper((string)($last['payment'] ?? ''));
$subtotal  = (float)($last['subtotal'] ?? 0);
$tax       = (float)($last['tax'] ?? 0);
$shipping  = (float)($last['shipping'] ?? 0);
$discount  = (float)($last['discount'] ?? 0);
$total     = (float)($last['total'] ?? ($subtotal + $tax + $shipping - $discount));

$items  = $last['items'] ?? [];
$qtyMap = $last['quantities'] ?? [];

$orderDate = $last['created_at'] ?? date('Y-m-d H:i:s');
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

    <main class="max-w-5xl mx-auto px-4 py-10">

        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Order confirmed 🎉</h1>
                    <p class="text-gray-600 mt-1">
                        Thanks, <span class="font-semibold"><?= e($name) ?></span> — your order
                        <span class="font-semibold">#<?= e($orderId) ?></span> is being processed.
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border text-gray-700">
                    <i class="fa-regular fa-calendar"></i>
                    <?= e($orderDate) ?>
                </span>
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border text-gray-700">
                    <i class="fa-solid fa-credit-card"></i>
                    Payment: <b class="ml-1"><?= e($payment ?: 'N/A') ?></b>
                </span>
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700">
                    <i class="fa-solid fa-bolt"></i>
                    Status: Confirmed
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left: details + items -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Progress -->
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h2 class="font-semibold text-gray-900 mb-4">What happens next</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="p-4 rounded-xl border bg-gray-50">
                            <div class="flex items-center gap-2 font-semibold text-gray-900">
                                <span class="w-7 h-7 rounded-full bg-emerald-600 text-white inline-flex items-center justify-center">1</span>
                                Confirmed
                            </div>
                            <p class="text-gray-600 mt-2">We received your order and are preparing it.</p>
                        </div>
                        <div class="p-4 rounded-xl border">
                            <div class="flex items-center gap-2 font-semibold text-gray-900">
                                <span class="w-7 h-7 rounded-full bg-gray-200 text-gray-700 inline-flex items-center justify-center">2</span>
                                Packed
                            </div>
                            <p class="text-gray-600 mt-2">Your items will be packed carefully.</p>
                        </div>
                        <div class="p-4 rounded-xl border">
                            <div class="flex items-center gap-2 font-semibold text-gray-900">
                                <span class="w-7 h-7 rounded-full bg-gray-200 text-gray-700 inline-flex items-center justify-center">3</span>
                                Shipped
                            </div>
                            <p class="text-gray-600 mt-2">You’ll receive tracking once shipped.</p>
                        </div>
                    </div>
                </div>

                <!-- Customer + Shipping -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border p-6">
                        <h2 class="font-semibold text-gray-900 mb-3">Customer</h2>
                        <div class="space-y-2 text-sm text-gray-700">
                            <p><span class="text-gray-500">Name:</span> <?= e($name) ?></p>
                            <p><span class="text-gray-500">Email:</span> <?= e($email) ?></p>
                            <p><span class="text-gray-500">Phone:</span> <?= e($phone) ?></p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border p-6">
                        <h2 class="font-semibold text-gray-900 mb-3">Shipping</h2>
                        <div class="space-y-2 text-sm text-gray-700">
                            <p class="leading-relaxed"><?= e($address) ?></p>
                            <p><?= e($city) ?> <?= e($country) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-900">Items</h2>
                        <button onclick="window.print()" class="text-sm px-3 py-2 rounded-xl border hover:bg-gray-50">
                            <i class="fa-solid fa-print mr-2"></i>Print receipt
                        </button>
                    </div>

                    <div class="space-y-3">
                        <?php
                        $hasAny = false;
                        foreach ($items as $p):
                            $pid = (int)($p['product_id'] ?? 0);
                            $qty = (int)($qtyMap[$pid] ?? 0);
                            if ($qty < 1) continue;
                            $hasAny = true;

                            $pName  = (string)($p['name'] ?? '');
                            $pImg   = (string)($p['image_url'] ?? '');
                            $pPrice = (float)($p['price'] ?? 0);
                            $line   = $pPrice * $qty;
                        ?>
                            <div class="flex items-center justify-between gap-4 border rounded-2xl p-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <img
                                        src="<?= e($pImg) ?>"
                                        alt="<?= e($pName) ?>"
                                        class="w-16 h-16 rounded-xl object-cover bg-gray-100 border"
                                        loading="lazy">
                                    <div class="min-w-0">
                                        <div class="font-semibold text-gray-900 truncate"><?= e($pName) ?></div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <?= money($pPrice) ?> <span class="mx-1">×</span> <?= $qty ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="font-bold text-gray-900 whitespace-nowrap"><?= money($line) ?></div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!$hasAny): ?>
                            <div class="p-4 rounded-2xl border bg-gray-50 text-gray-600 text-sm">
                                No items found for this order.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-3">
                    <a href="/E-commerce-shoes/view/content/products.php"
                        class="px-5 py-3 rounded-2xl bg-gray-900 text-white font-semibold hover:bg-black">
                        <i class="fa-solid fa-bag-shopping mr-2"></i>Continue shopping
                    </a>

                    <a href="/E-commerce-shoes/view/content/orders.php"
                        class="px-5 py-3 rounded-2xl border font-semibold hover:bg-gray-50">
                        <i class="fa-regular fa-rectangle-list mr-2"></i>View my orders
                    </a>
                </div>

            </div>

            <!-- Right: summary -->
            <aside class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border p-6 sticky top-6">
                    <h2 class="font-semibold text-gray-900 mb-4">Order summary</h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium"><?= money($subtotal) ?></span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax</span>
                            <span class="font-medium"><?= money($tax) ?></span>
                        </div>

                        <?php if ($shipping > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium"><?= money($shipping) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($discount > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Discount</span>
                                <span class="font-medium text-emerald-700">-<?= money($discount) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="border-t pt-3 flex justify-between text-base">
                            <span class="font-semibold text-gray-900">Total</span>
                            <span class="font-extrabold text-blue-600"><?= money($total) ?></span>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl bg-blue-50 border border-blue-100 p-4 text-sm text-blue-800">
                        <div class="font-semibold mb-1"><i class="fa-solid fa-circle-info mr-2"></i>Need help?</div>
                        <div class="text-blue-700">
                            If you have any questions about your order, contact support and include order <b>#<?= e($orderId) ?></b>.
                        </div>
                    </div>
                </div>
            </aside>

        </div>

    </main>

    <?php require_once __DIR__ . '/../../includes/shader/footer.php'; ?>

</body>

</html>