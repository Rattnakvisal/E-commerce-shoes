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

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmed</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Print Styles -->
    <style>
        @media print {

            nav,
            .print-hidden {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            main {
                max-width: 100% !important;
                padding: 0 !important;
            }

            .border {
                border: none !important;
            }
        }
    </style>
</head>

<body class="bg-white">

    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="max-w-4xl mx-auto px-6 py-16">

        <!-- ✅ Success Alert -->
        <div id="successAlert"
            class="mb-8 rounded-lg border border-green-200 bg-green-50 px-6 py-4
                text-green-800 flex items-center justify-between print-hidden">

            <div class="flex items-center gap-3">
                <i class="fas fa-circle-check text-green-600 text-lg"></i>
                <span class="font-medium">
                    Your order has been placed successfully!
                </span>
            </div>

            <button onclick="document.getElementById('successAlert').remove()"
                class="text-green-700 hover:text-green-900 text-xl leading-none">
                &times;
            </button>
        </div>

        <!-- Success Icon -->
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 flex items-center justify-center rounded-full border-4 border-black">
                <i class="fas fa-check text-3xl"></i>
            </div>
        </div>

        <!-- Headline -->
        <h1 class="text-center text-3xl md:text-4xl font-extrabold tracking-tight mb-3">
            Order Confirmed
        </h1>

        <p class="text-center text-gray-600 mb-10">
            Thank you for your purchase. Your order has been successfully placed.
        </p>

        <!-- Order Card -->
        <div class="border border-gray-200 rounded-xl p-8 max-w-2xl mx-auto">

            <!-- Order Info -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <p class="text-sm text-gray-500">Order ID</p>
                    <p class="font-semibold"><?= e($order['order_id']) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Order Date</p>
                    <p class="font-semibold"><?= e($order['created_at']) ?></p>
                </div>
            </div>

            <hr class="mb-6">

            <!-- Shipping -->
            <div class="mb-6">
                <p class="font-semibold mb-2">Shipping Address</p>
                <p><?= e($order['name']) ?></p>
                <p class="text-gray-600">
                    <?= e($order['address']) ?>,
                    <?= e($order['city']) ?>,
                    <?= e($order['country']) ?>
                </p>
            </div>

            <!-- Email -->
            <div class="mb-6">
                <p class="font-semibold mb-1">Confirmation Email</p>
                <p class="text-gray-600"><?= e($order['email']) ?></p>
            </div>

            <hr class="mb-6">

            <!-- Items -->
            <div class="space-y-3">
                <?php foreach ($order['items'] as $item):
                    $qty = $order['quantities'][$item['product_id']] ?? 1;
                ?>
                    <div class="flex justify-between text-sm">
                        <span><?= e($item['name']) ?> × <?= e($qty) ?></span>
                        <span>$<?= number_format($item['price'] * $qty, 2) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <hr class="my-6">

            <!-- Total -->
            <div class="flex justify-between text-lg font-bold">
                <span>Total</span>
                <span>$<?= number_format((float)$order['total'], 2) ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-10 flex flex-wrap justify-center gap-4 print-hidden">

            <button onclick="window.print()"
                class="px-8 py-3 bg-indigo-600 text-white font-semibold rounded-full
                   hover:bg-indigo-700 transition flex items-center gap-2">
                <i class="fas fa-file-pdf"></i>
                Print / Save PDF
            </button>

            <a href="products.php"
                class="px-8 py-3 bg-black text-white font-semibold rounded-full
                  hover:bg-gray-900 transition">
                Continue Shopping
            </a>

            <a href="../view/index.php"
                class="px-8 py-3 border border-black text-black font-semibold rounded-full
                  hover:bg-gray-100 transition">
                Back to Home
            </a>
        </div>

    </main>

    <!-- Auto-hide success alert -->
    <script>
        setTimeout(() => {
            const alert = document.getElementById('successAlert');
            if (alert) alert.remove();
        }, 5000);
    </script>

</body>

</html>