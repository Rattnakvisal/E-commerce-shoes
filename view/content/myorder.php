<?php
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../../includes/contract/myorders.php';

$ordersArr = $orders ?? [];

/* ===== Summary Stats ===== */
$totalOrders = count($ordersArr);
$paidCount = 0;
$pendingCount = 0;
$totalSpent = 0.0;

foreach ($ordersArr as $o) {
    $ps = strtolower(trim((string)($o['payment_status'] ?? '')));
    $os = strtolower(trim((string)($o['order_status'] ?? '')));

    if ($ps === 'paid') {
        $paidCount++;
    }

    // Consider an order 'pending' if its order_status indicates it's not completed
    if (in_array($os, ['pending', 'processing'], true)) {
        $pendingCount++;
    }

    $totalSpent += (float)($o['total'] ?? 0);
}

/* ===== Padding System (ONE place to control) ===== */
$padX = 'px-5 sm:px-6 lg:px-8';
$padY = 'py-4 sm:py-5';
$cardPad = $padX . ' ' . $padY;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Orders</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .glass {
            background: rgba(255, 255, 255, .75);
            backdrop-filter: blur(10px);
        }

        .shadow-soft {
            box-shadow: 0 10px 30px rgba(0, 0, 0, .06);
        }

        /* smooth details */
        details[open] summary~* {
            animation: sweep .18s ease-in-out;
        }

        @keyframes sweep {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900">

    <?php
    require_once __DIR__ . '/../../includes/shader/topbar.php';
    require_once __DIR__ . '/../../includes/shader/navbar.php';
    ?>

    <main class="max-w-6xl mx-auto px-4 py-10 sm:py-12">

        <!-- HERO HEADER -->
        <section class="rounded-3xl overflow-hidden border shadow-soft bg-gradient-to-br from-black via-gray-900 to-gray-800">
            <div class="p-6 sm:p-8 lg:p-12 text-white">
                <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-gray-300">Account</p>
                        <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight mt-2">My Orders</h1>
                        <p class="text-gray-300 mt-2">Track, review, and manage your purchases</p>
                    </div>

                    <a href="products.php"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-full bg-white text-black text-sm font-extrabold hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-white/60">
                        <i class="fas fa-bag-shopping mr-2"></i>
                        Continue Shopping
                    </a>
                </div>

                <!-- SUMMARY (scroll on mobile) -->
                <div class="mt-8 overflow-x-auto">
                    <div class="min-w-max flex gap-3 sm:gap-4">
                        <?php
                        $summaryCards = [
                            ['label' => 'Total Orders', 'value' => (int)$totalOrders],
                            ['label' => 'Paid',        'value' => (int)$paidCount],
                            ['label' => 'Pending',     'value' => (int)$pendingCount],
                            ['label' => 'Total Spent', 'value' => '$' . number_format($totalSpent, 2)],
                        ];
                        foreach ($summaryCards as $c):
                        ?>
                            <div class="glass rounded-2xl px-5 py-4 sm:px-6 sm:py-5 border border-white/15">
                                <p class="text-xs text-gray-200"><?= e($c['label']) ?></p>
                                <p class="text-2xl font-extrabold mt-1"><?= e((string)$c['value']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sticky mini summary for mobile -->
        <div class="sticky top-2 z-10 mt-6 sm:hidden">
            <div class="bg-white/90 backdrop-blur border rounded-2xl shadow-soft px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Orders</p>
                    <p class="text-sm font-extrabold"><?= (int)$totalOrders ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Spent</p>
                    <p class="text-sm font-extrabold">$<?= number_format($totalSpent, 2) ?></p>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <section class="mt-8">

            <?php if (empty($ordersArr)): ?>
                <div class="bg-white rounded-3xl border shadow-soft p-10 text-center">
                    <div class="mx-auto w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                        <i class="fas fa-box-open text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-xl font-extrabold">No orders yet</p>
                    <p class="text-gray-600 mt-2">When you place an order, it will show up here.</p>
                    <a href="products.php"
                        class="inline-flex items-center mt-6 px-6 py-3 rounded-full bg-black text-white font-extrabold hover:bg-gray-900">
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>

                <div class="space-y-5">
                    <?php foreach ($ordersArr as $order):
                        $oid = (int)($order['order_id'] ?? 0);
                        $created = (string)($order['created_at'] ?? '');
                        $dt = $created ? date('d M Y · H:i', strtotime($created)) : '';

                        $paymentStatus = strtolower((string)($order['payment_status'] ?? 'pending'));
                        $orderStatus   = strtolower((string)($order['order_status'] ?? 'processing'));

                        // pills (keep your badge() + map orderPill here if you want)
                        $orderPill = match ($orderStatus) {
                            'completed'  => 'bg-indigo-100 text-indigo-700',
                            'shipped'    => 'bg-blue-100 text-blue-700',
                            'processing' => 'bg-gray-100 text-gray-700',
                            'cancelled'  => 'bg-red-100 text-red-700',
                            default      => 'bg-gray-100 text-gray-700'
                        };

                        $isPaid      = $paymentStatus === 'paid';
                        $isCompleted = $orderStatus === 'completed';
                    ?>

                        <article id="order-<?= $oid ?>" class="bg-white border rounded-3xl overflow-hidden shadow-soft">

                            <!-- TOP -->
                            <div class="<?= $cardPad ?> flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs uppercase tracking-widest text-gray-400">Order</span>
                                        <span class="text-lg font-extrabold tracking-tight">#<?= e((string)$oid) ?></span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1"><?= e($dt) ?></p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                    <span class="px-3 py-1 rounded-full text-xs font-extrabold <?= badge($paymentStatus) ?>">
                                        <?= ucfirst(e($paymentStatus)) ?>
                                    </span>
                                    <span class="px-3 py-1 rounded-full text-xs font-extrabold <?= $orderPill ?>">
                                        <?= ucfirst(e($orderStatus)) ?>
                                    </span>
                                    <span class="text-lg font-extrabold ml-2">
                                        $<?= number_format((float)($order['total'] ?? 0), 2) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- PROGRESS -->
                            <div class="<?= $padX ?> pb-5">
                                <div class="grid grid-cols-3 gap-3 sm:gap-4 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-full bg-black text-white flex items-center justify-center font-extrabold">1</span>
                                        <span class="font-semibold">Placed</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-full <?= $isPaid ? 'bg-black text-white' : 'bg-gray-100 text-gray-500' ?> flex items-center justify-center font-extrabold">2</span>
                                        <span class="<?= $isPaid ? 'font-semibold text-gray-900' : 'text-gray-500' ?>">Paid</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-full <?= $isCompleted ? 'bg-black text-white' : 'bg-gray-100 text-gray-500' ?> flex items-center justify-center font-extrabold">3</span>
                                        <span class="<?= $isCompleted ? 'font-semibold text-gray-900' : 'text-gray-500' ?>">Completed</span>
                                    </div>
                                </div>
                            </div>

                            <!-- DETAILS -->
                            <details class="border-t group">
                                <summary class="px-5 sm:px-6 lg:px-8 py-4 cursor-pointer text-sm font-extrabold flex justify-between items-center hover:bg-gray-50">
                                    <span class="flex items-center gap-2">
                                        <i class="fas fa-receipt text-gray-400"></i>
                                        Items & Details
                                    </span>
                                    <i class="fas fa-chevron-down transition-transform group-open:rotate-180"></i>
                                </summary>

                                <div class="<?= $padX ?> pb-7">
                                    <div class="rounded-2xl border bg-gray-50 p-5">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            <p class="text-sm text-gray-600">
                                                Load items for order <span class="font-extrabold">#<?= e((string)$oid) ?></span>
                                            </p>

                                            <button type="button"
                                                class="load-items-btn px-4 py-2 rounded-full bg-black text-white text-xs font-extrabold hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-black/20"
                                                data-order-id="<?= $oid ?>">
                                                Load Items
                                            </button>
                                        </div>

                                        <div class="order-items-container mt-4 text-sm text-gray-700" data-order-id="<?= $oid ?>">
                                            <span class="text-gray-500">Click “Load Items” to view products…</span>
                                        </div>
                                    </div>

                                    <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        <div class="rounded-2xl border p-4">
                                            <p class="text-xs text-gray-500 font-semibold">Shipping Address</p>
                                            <p class="text-sm font-extrabold mt-1">
                                                <?= e((string)($order['address'] ?? '')) ?>
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                <?= e((string)($order['city'] ?? '')) ?>, <?= e((string)($order['country'] ?? '')) ?>
                                            </p>
                                        </div>

                                        <div class="rounded-2xl border p-4">
                                            <p class="text-xs text-gray-500 font-semibold">Actions</p>
                                            <div class="flex flex-wrap gap-2 mt-2">
                                                <button type="button"
                                                    class="px-4 py-2 rounded-full bg-gray-100 text-gray-900 text-xs font-extrabold hover:bg-gray-200"
                                                    onclick="printOrder(<?= $oid ?>)">
                                                    <i class="fas fa-print mr-2"></i> Print
                                                </button>

                                                <a href="contact.php"
                                                    class="px-4 py-2 rounded-full bg-indigo-50 text-indigo-700 text-xs font-extrabold hover:bg-indigo-100">
                                                    <i class="fas fa-headset mr-2"></i> Help
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </details>

                        </article>

                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </section>

    </main>

    <?php require_once __DIR__ . '/../../includes/shader/footer.php'; ?>

    <script src="../assets/Js/myorder.js"></script>

    <script>
        /* ===== Print (clean + hide buttons) ===== */
        function printOrder(orderId) {
            const el = document.getElementById('order-' + orderId);
            if (!el) return window.print();

            const css = `
      body{font-family: ui-sans-serif, system-ui; padding:24px; background:#fff;}
      .no-print{display:none !important;}
      article{border:1px solid #e5e7eb; border-radius:18px; overflow:hidden;}
      summary{display:none;}
      details{border-top:0;}
    `;

            const w = window.open('', '_blank');
            w.document.open();
            w.document.write(`
      <!doctype html>
      <html lang="en">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Order #${orderId}</title>
        <style>${css}</style>
        <script src="https://cdn.tailwindcss.com"><\/script>
      </head>
      <body>
        ${el.outerHTML}
        <script>
          window.onload = function () {
            window.print();
            setTimeout(function(){ window.close(); }, 300);
          };
        <\/script>
      </body>
      </html>
    `);
            w.document.close();
        }

        /* ===== Load items: cache so it loads only once per order ===== */
        const orderItemsLoaded = new Set();

        document.querySelectorAll('.load-items-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-order-id');
                if (!id) return;

                const box = document.querySelector('.order-items-container[data-order-id="' + id + '"]');
                if (!box) return;

                // already loaded => just open details content
                if (orderItemsLoaded.has(id)) return;

                orderItemsLoaded.add(id);

                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-not-allowed');
                btn.textContent = 'Loading...';

                box.innerHTML = `
          <div class="animate-pulse space-y-2">
            <div class="h-3 bg-gray-200 rounded w-2/3"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
            <div class="h-3 bg-gray-200 rounded w-3/4"></div>
          </div>`;

                // If your myorder.js has loadOrderItems(orderId) function:
                if (typeof window.loadOrderItems === 'function') {
                    Promise.resolve(window.loadOrderItems(id)).finally(() => {
                        btn.disabled = false;
                        btn.classList.remove('opacity-70', 'cursor-not-allowed');
                        btn.textContent = 'Loaded';
                    });
                } else {
                    // fallback: click container if your current script uses click event
                    box.click();
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.classList.remove('opacity-70', 'cursor-not-allowed');
                        btn.textContent = 'Loaded';
                    }, 900);
                }
            });
        });
    </script>

</body>

</html>