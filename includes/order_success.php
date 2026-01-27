<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$order = $_SESSION['last_order'] ?? null;
if (!$order) {
    header('Location: products.php');
    exit;
}

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$subtotal     = (float) $order['total'];
$platformFee = $subtotal * 0.20;
$takeHome    = $subtotal - $platformFee;

$orderDates = [
    date('l, j F Y', strtotime('-2 days')),
    date('l, j F Y', strtotime('today')),
    date('l, j F Y', strtotime('+2 days')),
];
$orderTimes = ['4:30', '1:30', '2:00'];
$totalHours = '8:00';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Order Complete</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="min-h-screen flex items-center justify-center px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl grid grid-cols-1 md:grid-cols-2 overflow-hidden">

            <!-- ================= LEFT : SUCCESS ================= -->
            <div class="p-10 flex flex-col items-center justify-center text-center">

                <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mb-6">
                    <i class="fas fa-check text-3xl text-green-600"></i>
                </div>

                <h2 class="text-2xl font-bold mb-2">
                    Order Submitted
                </h2>

                <p class="text-gray-600 max-w-sm mb-8">
                    Your order has been placed successfully and is on its way.
                    We’ve sent a confirmation to your email.
                </p>

                <div class="flex flex-col gap-3 w-full max-w-xs">

                    <button onclick="window.print()"
                        class="w-full py-3 rounded-full bg-black text-white font-semibold hover:bg-gray-900 transition">
                        Print / Save PDF
                    </button>

                    <a href="products.php"
                        class="w-full py-3 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
                        Continue Shopping
                    </a>

                </div>
            </div>

            <!-- ================= RIGHT : SUMMARY ================= -->
            <div class="bg-gray-50 p-8 md:p-10">

                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-sm text-gray-500">Order Summary</p>
                        <p class="font-bold text-lg">#<?= e($order['order_id']) ?></p>
                    </div>
                    <a href="products.php" class="text-gray-400 hover:text-gray-600 text-xl">
                        &times;
                    </a>
                </div>

                <div class="space-y-5 text-sm">

                    <div>
                        <p class="text-gray-500">Client</p>
                        <p class="font-medium"><?= e($order['name']) ?></p>
                    </div>

                    <div>
                        <p class="text-gray-500">Service</p>
                        <p class="font-medium">Product Purchase</p>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white rounded-xl p-4 space-y-3">
                        <?php foreach ($orderDates as $i => $d): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600"><?= $d ?></span>
                                <span class="font-medium"><?= $orderTimes[$i] ?></span>
                            </div>
                        <?php endforeach; ?>

                        <div class="pt-3 border-t flex justify-between font-semibold">
                            <span>Total Time</span>
                            <span><?= $totalHours ?></span>
                        </div>
                    </div>

                    <!-- Payment -->
                    <div class="pt-4 space-y-3">

                        <div class="flex justify-between">
                            <span class="text-gray-600">Order Value</span>
                            <span>$<?= number_format($subtotal, 2) ?></span>
                        </div>

                        <div class="flex justify-between text-red-600">
                            <span>Platform Fee (20%)</span>
                            <span>- $<?= number_format($platformFee, 2) ?></span>
                        </div>

                        <div class="flex justify-between text-green-600 text-lg font-bold pt-3 border-t">
                            <span>Take Home</span>
                            <span>$<?= number_format($takeHome, 2) ?></span>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </main>

</body>

</html>