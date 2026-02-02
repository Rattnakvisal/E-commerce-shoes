<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../../includes/contract/myorders.php'; // should define $orders and helper e(), badge()

$ordersArr = $orders ?? [];

/* ===== Helpers (fallback if not provided) ===== */
if (!function_exists('e')) {
    function e($s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('badge')) {
    function badge(string $status): string
    {
        $s = strtolower(trim($status));
        return match ($s) {
            'paid'      => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'pending'   => 'bg-amber-50 text-amber-700 border border-amber-200',
            'failed'    => 'bg-red-50 text-red-700 border border-red-200',
            'refunded'  => 'bg-slate-50 text-slate-700 border border-slate-200',
            default     => 'bg-gray-50 text-gray-700 border border-gray-200',
        };
    }
}
function money($n): string
{
    return '$' . number_format((float)$n, 2);
}

/* ===== Summary Stats ===== */
$totalOrders = count($ordersArr);
$paidCount = 0;
$pendingCount = 0;
$totalSpent = 0.0;

foreach ($ordersArr as $o) {
    $ps = strtolower(trim((string)($o['payment_status'] ?? '')));
    $os = strtolower(trim((string)($o['order_status'] ?? '')));

    if ($ps === 'paid') $paidCount++;
    if (in_array($os, ['pending', 'processing'], true)) $pendingCount++;
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

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
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
                        class="no-print inline-flex items-center justify-center px-5 py-3 rounded-full bg-white text-black text-sm font-extrabold hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-white/60">
                        <i class="fas fa-bag-shopping mr-2"></i>
                        Continue Shopping
                    </a>
                </div>

                <!-- PREMIUM SUMMARY GRID -->
                <div class="mt-8">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                        <?php
                        $summaryCards = [
                            ['label' => 'Total Orders', 'value' => (int)$totalOrders, 'icon' => 'fa-box', 'bg' => 'bg-white', 'ring' => 'ring-white/10'],
                            ['label' => 'Paid', 'value' => (int)$paidCount, 'icon' => 'fa-circle-check', 'bg' => 'bg-emerald-50', 'ring' => 'ring-emerald-200/60'],
                            ['label' => 'Pending', 'value' => (int)$pendingCount, 'icon' => 'fa-clock', 'bg' => 'bg-amber-50', 'ring' => 'ring-amber-200/60'],
                            ['label' => 'Total Spent', 'value' => '$' . number_format($totalSpent, 2), 'icon' => 'fa-wallet', 'bg' => 'bg-indigo-50', 'ring' => 'ring-indigo-200/60'],
                        ];
                        foreach ($summaryCards as $c):
                        ?>
                            <div class="<?= e($c['bg']) ?> rounded-3xl border border-white/10 ring-1 <?= e($c['ring']) ?> shadow-soft p-4 sm:p-5 text-gray-900">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-gray-500"><?= e($c['label']) ?></p>
                                    <div class="w-9 h-9 rounded-2xl bg-black/90 text-white flex items-center justify-center">
                                        <i class="fa-solid <?= e($c['icon']) ?>"></i>
                                    </div>
                                </div>
                                <p class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-3">
                                    <?= e((string)$c['value']) ?>
                                </p>
                                <div class="mt-3 h-1.5 rounded-full bg-black/5 overflow-hidden">
                                    <div class="h-full rounded-full bg-black/70 w-2/3"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </section>

        <!-- Sticky mini summary for mobile -->
        <div class="sticky top-2 z-10 mt-6 sm:hidden no-print">
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

                <!-- FILTER + SEARCH BAR -->
                <div class="bg-white border rounded-3xl shadow-soft p-4 sm:p-5 mb-5 no-print">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-2xl bg-gray-100 flex items-center justify-center">
                                <i class="fa-solid fa-filter text-gray-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-extrabold">Filter Orders</p>
                                <p class="text-xs text-gray-500">Search by order # or status</p>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                            <div class="relative flex-1">
                                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input id="orderSearch" type="text"
                                    placeholder="Search order #123..."
                                    class="w-full pl-11 pr-4 py-3 rounded-2xl border bg-white focus:outline-none focus:ring-2 focus:ring-black/10">
                            </div>

                            <select id="statusFilter"
                                class="w-full sm:w-48 px-4 py-3 rounded-2xl border bg-white font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-black/10">
                                <option value="">All status</option>
                                <option value="processing">Processing</option>
                                <option value="pending">Pending</option>
                                <option value="shipped">Shipped</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>

                            <select id="payFilter"
                                class="w-full sm:w-44 px-4 py-3 rounded-2xl border bg-white font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-black/10">
                                <option value="">All payments</option>
                                <option value="paid">Paid</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <?php foreach ($ordersArr as $order):
                        $oid = (int)($order['order_id'] ?? 0);

                        $created = (string)($order['created_at'] ?? '');
                        $dt = $created ? date('d M Y · H:i', strtotime($created)) : '';

                        $paymentStatus = strtolower((string)($order['payment_status'] ?? 'pending'));
                        $orderStatus   = strtolower((string)($order['order_status'] ?? 'processing'));

                        $orderPill = match ($orderStatus) {
                            'completed'  => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                            'shipped'    => 'bg-blue-50 text-blue-700 border border-blue-200',
                            'processing' => 'bg-gray-50 text-gray-700 border border-gray-200',
                            'pending'    => 'bg-amber-50 text-amber-700 border border-amber-200',
                            'cancelled'  => 'bg-red-50 text-red-700 border border-red-200',
                            default      => 'bg-gray-50 text-gray-700 border border-gray-200'
                        };

                        $isPaid      = $paymentStatus === 'paid';
                        $isCompleted = $orderStatus === 'completed';

                        $subtotal = (float)($order['subtotal'] ?? 0);
                        $tax      = (float)($order['tax'] ?? 0);
                        $shipping = (float)($order['shipping'] ?? 0);
                        $discount = (float)($order['discount'] ?? 0);
                        $total    = (float)($order['total'] ?? 0);
                    ?>

                        <article
                            id="order-<?= $oid ?>"
                            data-order-id="<?= $oid ?>"
                            data-order-status="<?= e($orderStatus) ?>"
                            data-pay-status="<?= e($paymentStatus) ?>"
                            class="bg-white border rounded-3xl overflow-hidden shadow-soft">
                            <!-- HEADER (improved padding + total block) -->
                            <div class="<?= $cardPad ?> flex flex-col gap-4">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-3 flex-wrap">
                                            <span class="text-[11px] uppercase tracking-widest text-gray-400">Order</span>
                                            <span class="text-xl font-extrabold tracking-tight">#<?= e((string)$oid) ?></span>

                                            <?php if ($isPaid): ?>
                                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <i class="fa-solid fa-circle-check"></i> Paid
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <i class="fa-regular fa-calendar mr-1"></i><?= e($dt) ?>
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                        <span class="px-3 py-1.5 rounded-full text-xs font-extrabold <?= badge($paymentStatus) ?>">
                                            <?= ucfirst(e($paymentStatus)) ?>
                                        </span>

                                        <span class="px-3 py-1.5 rounded-full text-xs font-extrabold <?= $orderPill ?>">
                                            <?= ucfirst(e($orderStatus)) ?>
                                        </span>

                                        <div class="px-4 py-2 rounded-2xl bg-black text-white font-extrabold text-sm">
                                            Total: <?= money($total) ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- MINI TOTAL GRID -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                    <div class="rounded-2xl border bg-gray-50 px-4 py-3">
                                        <p class="text-gray-500 font-semibold">Subtotal</p>
                                        <p class="font-extrabold"><?= money($subtotal) ?></p>
                                    </div>
                                    <div class="rounded-2xl border bg-gray-50 px-4 py-3">
                                        <p class="text-gray-500 font-semibold">Tax</p>
                                        <p class="font-extrabold"><?= money($tax) ?></p>
                                    </div>
                                    <div class="rounded-2xl border bg-gray-50 px-4 py-3">
                                        <p class="text-gray-500 font-semibold">Shipping</p>
                                        <p class="font-extrabold"><?= money($shipping) ?></p>
                                    </div>
                                    <div class="rounded-2xl border bg-gray-50 px-4 py-3">
                                        <p class="text-gray-500 font-semibold">Discount</p>
                                        <p class="font-extrabold">-<?= money($discount) ?></p>
                                    </div>
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
                            <details class="border-t">
                                <summary class="no-print px-5 sm:px-6 lg:px-8 py-4 cursor-pointer text-sm font-extrabold flex justify-between items-center hover:bg-gray-50">
                                    <span class="flex items-center gap-2">
                                        <i class="fas fa-receipt text-gray-400"></i>
                                        Items & Details
                                    </span>
                                    <i class="fas fa-chevron-down transition-transform group-open:rotate-180"></i>
                                </summary>

                                <div class="<?= $padX ?> pb-7">
                                    <div class="rounded-3xl border bg-gradient-to-br from-gray-50 to-white p-5 sm:p-6">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            <p class="text-sm text-gray-600">
                                                Items for order <span class="font-extrabold">#<?= e((string)$oid) ?></span>
                                            </p>

                                            <button type="button"
                                                class="no-print load-items-btn px-4 py-2 rounded-full bg-black text-white text-xs font-extrabold hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-black/20"
                                                data-order-id="<?= $oid ?>">
                                                Load Items
                                            </button>
                                        </div>

                                        <div class="order-items-container mt-4" data-order-id="<?= $oid ?>">
                                            <div class="text-gray-600 text-sm flex items-center gap-2">
                                                <i class="fa-regular fa-eye text-gray-400"></i>
                                                Click <b>Load Items</b> to view products for this order.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        <div class="rounded-2xl border p-4">
                                            <p class="text-xs text-gray-500 font-semibold">Shipping Address</p>
                                            <p class="text-sm font-extrabold mt-1"><?= e((string)($order['address'] ?? '')) ?></p>
                                            <p class="text-sm text-gray-600"><?= e((string)($order['city'] ?? '')) ?>, <?= e((string)($order['country'] ?? '')) ?></p>
                                        </div>

                                        <div class="rounded-2xl border p-4">
                                            <p class="text-xs text-gray-500 font-semibold">Actions</p>
                                            <div class="flex flex-wrap gap-2 mt-2 no-print">
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
        /* ===============================
   Print One Order (clean)
================================ */
        function printOrder(orderId) {
            const el = document.getElementById('order-' + orderId);
            if (!el) return window.print();

            const css = `
        body{font-family: ui-sans-serif,system-ui; padding:24px; background:#fff;}
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

        /* ===============================
           Filters (FAST + accurate)
        ================================ */
        const searchEl = document.getElementById('orderSearch');
        const statusEl = document.getElementById('statusFilter');
        const payEl = document.getElementById('payFilter');

        function applyFilters() {
            const q = (searchEl?.value || '').trim().toLowerCase().replace('#', '');
            const s = (statusEl?.value || '').trim().toLowerCase();
            const p = (payEl?.value || '').trim().toLowerCase();

            document.querySelectorAll('article[id^="order-"]').forEach(card => {
                const oid = (card.dataset.orderId || '').toLowerCase();
                const os = (card.dataset.orderStatus || '').toLowerCase();
                const ps = (card.dataset.payStatus || '').toLowerCase();

                const searchMatch = !q || oid.includes(q);
                const statusMatch = !s || os === s;
                const payMatch = !p || ps === p;

                card.style.display = (searchMatch && statusMatch && payMatch) ? '' : 'none';
            });
        }
        [searchEl, statusEl, payEl].forEach(el => el && el.addEventListener('input', applyFilters));

        /* ===============================
           Load items (premium UI + cache)
           IMPORTANT: change endpoint to your actual backend
        ================================ */
        const loaded = new Set();

        async function loadOrderItems(orderId) {
            // ✅ CHANGE THIS to your real endpoint:
            // Example: /E-commerce-shoes/view/actions/order_items.php?order_id=123
            const url = `/E-commerce-shoes/view/actions/order_items.php?order_id=${encodeURIComponent(orderId)}`;

            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!res.ok) throw new Error('Failed to load items');

            // Expect JSON format like:
            // [{name, image_url, price, qty, size, color}, ...]
            return await res.json();
        }

        document.querySelectorAll('.load-items-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-order-id');
                if (!id) return;

                const box = document.querySelector('.order-items-container[data-order-id="' + id + '"]');
                if (!box) return;

                if (loaded.has(id)) return;

                loaded.add(id);
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-not-allowed');
                btn.textContent = 'Loading...';

                box.innerHTML = `
          <div class="animate-pulse space-y-3">
            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            <div class="h-20 bg-gray-200 rounded-2xl"></div>
            <div class="h-20 bg-gray-200 rounded-2xl"></div>
          </div>
        `;

                try {
                    const items = await loadOrderItems(id);

                    if (!Array.isArray(items) || items.length === 0) {
                        box.innerHTML = `<div class="text-sm text-gray-600">No items found.</div>`;
                    } else {
                        box.innerHTML = `
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    ${items.map(it => {
                        const img = (it.image_url || '');
                        const name = (it.name || '');
                        const qty  = Number(it.qty || 0);
                        const price = Number(it.price || 0);
                        const size = it.size ? `<span class="px-2 py-1 rounded-full bg-white border text-[11px] font-bold">Size ${it.size}</span>` : '';
                        const color = it.color ? `<span class="px-2 py-1 rounded-full bg-white border text-[11px] font-bold">${it.color}</span>` : '';
                        const line = (qty * price).toFixed(2);

                        return `
                          <div class="flex gap-3 p-3 rounded-2xl border bg-white">
                            <img src="${img}" class="w-16 h-16 rounded-2xl object-cover bg-gray-100 border" alt="">
                            <div class="min-w-0 flex-1">
                              <p class="font-extrabold text-sm text-gray-900 truncate">${name}</p>
                              <div class="mt-1 flex flex-wrap gap-1">${size}${color}</div>
                              <p class="text-xs text-gray-500 mt-1">$${price.toFixed(2)} × ${qty}</p>
                            </div>
                            <div class="font-extrabold text-sm text-gray-900 whitespace-nowrap">$${line}</div>
                          </div>
                        `;
                    }).join('')}
                  </div>
                `;
                    }

                    btn.textContent = 'Loaded';
                } catch (err) {
                    box.innerHTML = `<div class="text-sm text-red-600">Error loading items. Please try again.</div>`;
                    loaded.delete(id);
                    btn.textContent = 'Load Items';
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('opacity-70', 'cursor-not-allowed');
                }
            });
        });
    </script>

</body>

</html>