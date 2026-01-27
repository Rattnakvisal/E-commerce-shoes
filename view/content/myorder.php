<?php
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../../includes/contract/myorders.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Orders</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">

    <?php
    require_once __DIR__ . '/../../includes/shader/topbar.php';
    require_once __DIR__ . '/../../includes/shader/navbar.php';
    ?>

    <main class="max-w-6xl mx-auto px-4 py-12">

        <!-- Page Header -->
        <div class="mb-10">
            <h1 class="text-3xl font-extrabold tracking-tight">My Orders</h1>
            <p class="text-gray-600 mt-2">Track, review, and manage your purchases</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-xl border p-10 text-center">
                <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                <p class="text-lg font-medium">You haven’t placed any orders yet</p>
                <a href="products.php"
                    class="inline-block mt-4 px-6 py-3 bg-black text-white rounded-full hover:bg-gray-900">
                    Start Shopping
                </a>
            </div>
        <?php else: ?>

            <div class="space-y-8">

                <?php foreach ($orders as $order):
                    $oid = (int)$order['order_id'];
                ?>

                    <!-- ================= ORDER CARD ================= -->
                    <article class="bg-white rounded-2xl shadow-sm border overflow-hidden">

                        <!-- Header -->
                        <div class="p-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

                            <div>
                                <p class="text-sm text-gray-500">Order #<?= e($oid) ?></p>
                                <p class="text-xs text-gray-400">
                                    <?= e(date('d M Y · H:i', strtotime($order['created_at']))) ?>
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?= badge($order['payment_status']) ?>">
                                    <?= ucfirst(e($order['payment_status'])) ?>
                                </span>

                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                    <?= ucfirst(e($order['order_status'])) ?>
                                </span>

                                <span class="text-xl font-bold">
                                    $<?= number_format((float)$order['total'], 2) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Progress -->
                        <div class="px-6 pb-4">
                            <div class="flex items-center text-xs text-gray-500 gap-3">
                                <span class="font-medium text-black">Placed</span>
                                <div class="flex-1 h-px bg-gray-300"></div>
                                <span class="<?= $order['payment_status'] === 'paid' ? 'font-medium text-black' : '' ?>">
                                    Paid
                                </span>
                                <div class="flex-1 h-px bg-gray-300"></div>
                                <span class="<?= $order['order_status'] === 'completed' ? 'font-medium text-black' : '' ?>">
                                    Completed
                                </span>
                            </div>
                        </div>

                        <!-- Items -->
                        <details class="border-t group">
                            <summary class="px-6 py-4 cursor-pointer text-sm font-medium flex justify-between items-center hover:bg-gray-50">
                                <span>View Items</span>
                                <i class="fas fa-chevron-down transition-transform group-open:rotate-180"></i>
                            </summary>

                            <div class="px-6 py-4">
                                <div class="order-items-container text-sm text-gray-500"
                                    data-order-id="<?= $oid ?>">
                                    Click to load items…
                                </div>
                            </div>
                        </details>

                        <!-- Footer -->
                        <div class="border-t px-6 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                            <div class="text-sm text-gray-600">
                                <p class="font-medium text-gray-800">Shipping Address</p>
                                <p>
                                    <?= e($order['address'] ?? '') ?>,
                                    <?= e($order['city'] ?? '') ?>,
                                    <?= e($order['country'] ?? '') ?>
                                </p>
                            </div>

                            <div class="flex gap-3">
                                <button onclick="window.print()"
                                    class="px-5 py-2 rounded-full bg-black text-white text-sm hover:bg-gray-900">
                                    Print / PDF
                                </button>
                            </div>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </main>

    <?php require_once __DIR__ . '/../../includes/shader/footer.php'; ?>

    <script src="../assets/Js/myorder.js"></script>

</body>

</html>