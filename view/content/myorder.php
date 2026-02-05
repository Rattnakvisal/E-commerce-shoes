<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../../includes/contract/myorders.php'; // provides $orders

$ordersArr = $orders ?? [];

/* ---------- helpers ---------- */
if (!function_exists('e')) {
    function e($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}
function money($n): string
{
    return '$' . number_format((float)$n, 2);
}

function pillStatus(string $s): string
{
    $s = strtolower(trim($s));
    return match ($s) {
        'delivered', 'completed' => 'text-emerald-700 bg-emerald-50 border-emerald-100',
        'processing'             => 'text-indigo-700 bg-indigo-50 border-indigo-100',
        'shipped'                => 'text-sky-700 bg-sky-50 border-sky-100',
        'pending'                => 'text-amber-700 bg-amber-50 border-amber-100',
        'cancelled'              => 'text-rose-700 bg-rose-50 border-rose-100',
        default                  => 'text-gray-700 bg-gray-50 border-gray-100',
    };
}

function pillPayment(string $s): string
{
    $s = strtolower(trim($s));
    return match ($s) {
        'paid'     => 'text-emerald-700 bg-emerald-50 border-emerald-100',
        'unpaid'   => 'text-amber-700 bg-amber-50 border-amber-100',
        'pending'  => 'text-amber-700 bg-amber-50 border-amber-100',
        'failed'   => 'text-rose-700 bg-rose-50 border-rose-100',
        'refunded' => 'text-purple-700 bg-purple-50 border-purple-100',
        default    => 'text-gray-700 bg-gray-50 border-gray-100',
    };
}

/* ---- NEW: nicer labels/icons/dots/shipping pill ---- */
function niceStatusLabel(string $s): string
{
    $s = strtolower(trim($s));
    return match ($s) {
        'completed' => 'Completed',
        'delivered' => 'Delivered',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'pending' => 'Pending',
        'cancelled' => 'Cancelled',
        'paid' => 'Paid',
        'unpaid' => 'Unpaid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        default => ucfirst($s),
    };
}

function pillShipping(string $s): string
{
    $s = strtolower(trim($s));
    return match ($s) {
        'shipped'    => 'text-sky-700 bg-sky-50 border-sky-100',
        'in_transit' => 'text-blue-700 bg-blue-50 border-blue-100',
        'delivered'  => 'text-emerald-700 bg-emerald-50 border-emerald-100',
        'pending'    => 'text-amber-700 bg-amber-50 border-amber-100',
        'cancelled'  => 'text-rose-700 bg-rose-50 border-rose-100',
        default      => 'text-gray-700 bg-gray-50 border-gray-100',
    };
}

function statusDot(string $s): string
{
    $s = strtolower(trim($s));
    return match ($s) {
        'completed', 'delivered', 'paid' => 'bg-emerald-500',
        'processing'                     => 'bg-indigo-500',
        'pending', 'unpaid'              => 'bg-amber-500',
        'cancelled', 'failed'            => 'bg-rose-500',
        'refunded'                       => 'bg-purple-500',
        default                          => 'bg-gray-400',
    };
}

function paymentIcon(string $s): string
{
    $s = strtolower(trim($s));
    return match ($s) {
        'paid'     => 'fa-circle-check',
        'unpaid'   => 'fa-circle-exclamation',
        'pending'  => 'fa-clock',
        'failed'   => 'fa-triangle-exclamation',
        'refunded' => 'fa-rotate-left',
        default    => 'fa-circle-info',
    };
}

/* ---------- UI tabs ---------- */
$tab = strtolower(trim((string)($_GET['tab'] ?? 'all')));
$allowedTabs = ['all', 'summary', 'completed', 'cancelled'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'all';

$dateFrom = (string)($_GET['from'] ?? '');
$dateTo   = (string)($_GET['to'] ?? '');

function tabLink(string $t, string $label, string $activeTab, string $from, string $to): string
{
    $qs = http_build_query(array_filter(['tab' => $t, 'from' => $from, 'to' => $to], fn($v) => $v !== ''));
    $active = $t === $activeTab;
    return '<a href="?' . e($qs) . '" class="text-sm font-extrabold px-1 pb-3 border-b-2 ' .
        ($active ? 'text-indigo-600 border-indigo-600' : 'text-gray-500 border-transparent hover:text-gray-700') .
        '">' . e($label) . '</a>';
}

/* ---------- filter orders by tab + date ---------- */
$filtered = [];
foreach ($ordersArr as $o) {
    $os = strtolower(trim((string)($o['order_status'] ?? 'pending')));
    $created = (string)($o['created_at'] ?? '');

    if ($tab === 'completed' && $os !== 'completed') continue;
    if ($tab === 'cancelled' && $os !== 'cancelled') continue;

    // date filter (created_at)
    if ($dateFrom !== '') {
        $fromTs = strtotime($dateFrom . ' 00:00:00');
        if ($created && strtotime($created) < $fromTs) continue;
    }
    if ($dateTo !== '') {
        $toTs = strtotime($dateTo . ' 23:59:59');
        if ($created && strtotime($created) > $toTs) continue;
    }

    $filtered[] = $o;
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Order History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Force a simple, readable system font across this page */
        html,
        body,
        input,
        button,
        select,
        textarea {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-weight: 400 !important;
        }

        .font-extrabold,
        .font-black,
        .font-medium,
        strong,
        b {
            font-weight: 400 !important;
        }

        /* Headings and titles */
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-weight: 400 !important;
        }

        /* Order progress: grid-based labels and full-width track */
        .order-progress {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
            align-items: center;
        }

        .order-progress-label {
            text-align: center;
            font-size: 11px;
            color: #6b7280;
            font-weight: 400 !important;
        }

        .order-progress-track {
            grid-column: 1 / -1;
            height: 8px;
            border-radius: 999px;
            background: #f3f4f6;
            /* bg-gray-100 */
            border: 1px solid #e5e7eb;
            /* border-gray-200 */
            overflow: hidden;
            margin-top: 6px;
        }

        .order-progress-fill {
            height: 100%;
            background: #6366f1;
            /* indigo-600 */
            border-radius: 999px;
            width: 0%;
            transition: width 260ms ease;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php
    require_once __DIR__ . '/../../includes/shader/topbar.php';
    require_once __DIR__ . '/../../includes/shader/navbar.php';
    ?>

    <main class="max-w-6xl mx-auto px-4 py-8 sm:py-10">

        <!-- Header row -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Order History</h1>
                <p class="text-sm text-gray-500 mt-1">Track, review, and manage your purchases</p>
            </div>

            <!-- Date range -->
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="<?= e($tab) ?>">
                <div class="flex items-center gap-2 bg-white border rounded-2xl px-3 py-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-gray-400"></i>
                    <input name="from" type="date" value="<?= e($dateFrom) ?>" class="text-sm outline-none bg-transparent">
                </div>
                <span class="text-sm text-gray-400">To</span>
                <div class="flex items-center gap-2 bg-white border rounded-2xl px-3 py-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-gray-400"></i>
                    <input name="to" type="date" value="<?= e($dateTo) ?>" class="text-sm outline-none bg-transparent">
                </div>
                <button class="ml-1 px-4 py-2 rounded-2xl bg-indigo-600 text-white text-sm font-extrabold hover:bg-indigo-700 shadow-sm">
                    Apply
                </button>
            </form>
        </div>

        <!-- Tabs -->
        <div class="mt-6 bg-white border rounded-3xl overflow-hidden">
            <div class="px-5 sm:px-7 pt-6">
                <div class="flex items-center gap-8">
                    <?= tabLink('all', 'All Order', $tab, $dateFrom, $dateTo) ?>
                    <?= tabLink('summary', 'Summary', $tab, $dateFrom, $dateTo) ?>
                    <?= tabLink('completed', 'Completed', $tab, $dateFrom, $dateTo) ?>
                    <?= tabLink('cancelled', 'Cancelled', $tab, $dateFrom, $dateTo) ?>
                </div>
            </div>

            <!-- Content -->
            <div class="p-5 sm:p-7">

                <?php if ($tab === 'summary'): ?>
                    <?php
                    $total = count($filtered);
                    $c = 0;
                    $x = 0;
                    $paid = 0;
                    $spent = 0.0;
                    foreach ($filtered as $o) {
                        $os = strtolower((string)($o['order_status'] ?? 'pending'));
                        $ps = strtolower((string)($o['payment_status'] ?? 'unpaid'));
                        if ($os === 'completed') $c++;
                        if ($os === 'cancelled') $x++;
                        if ($ps === 'paid') {
                            $paid++;
                            $spent += (float)($o['total'] ?? 0);
                        }
                    }
                    ?>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="bg-gray-50 border rounded-3xl p-5">
                            <p class="text-xs text-gray-500 font-extrabold">Total Orders</p>
                            <p class="text-2xl font-extrabold mt-2"><?= (int)$total ?></p>
                        </div>
                        <div class="bg-emerald-50 border border-emerald-100 rounded-3xl p-5">
                            <p class="text-xs text-emerald-700 font-extrabold">Completed</p>
                            <p class="text-2xl font-extrabold mt-2 text-emerald-900"><?= (int)$c ?></p>
                        </div>
                        <div class="bg-rose-50 border border-rose-100 rounded-3xl p-5">
                            <p class="text-xs text-rose-700 font-extrabold">Cancelled</p>
                            <p class="text-2xl font-extrabold mt-2 text-rose-900"><?= (int)$x ?></p>
                        </div>
                        <div class="bg-indigo-50 border border-indigo-100 rounded-3xl p-5">
                            <p class="text-xs text-indigo-700 font-extrabold">Paid Total</p>
                            <p class="text-2xl font-extrabold mt-2 text-indigo-900"><?= money($spent) ?></p>
                        </div>
                    </div>

                <?php else: ?>

                    <?php if (empty($filtered)): ?>
                        <div class="text-center py-16">
                            <div class="mx-auto w-14 h-14 rounded-3xl bg-gray-100 flex items-center justify-center border">
                                <i class="fa-solid fa-box-open text-gray-400 text-2xl"></i>
                            </div>
                            <p class="mt-4 text-lg font-extrabold">No orders found</p>
                            <p class="text-sm text-gray-500 mt-1">Try changing your filters.</p>
                        </div>
                    <?php else: ?>

                        <div class="space-y-6">

                            <?php foreach ($filtered as $o):
                                $oid = (int)($o['order_id'] ?? 0);
                                $created = (string)($o['created_at'] ?? '');
                                $dateNice = $created ? date('d M Y', strtotime($created)) : '';
                                $os = strtolower(trim((string)($o['order_status'] ?? 'pending')));
                                $ps = strtolower(trim((string)($o['payment_status'] ?? 'unpaid')));
                                $total = (float)($o['total'] ?? 0);
                                $ship = strtolower(trim((string)($o['shipping_status'] ?? '')));
                                $payDate = (string)($o['payment_date'] ?? $created);
                                $payNice = $payDate ? date('jS F Y', strtotime($payDate)) : '';
                                $step = 1;
                                if ($os === 'processing') $step = 2;
                                if ($os === 'shipped' || $ship === 'shipped') $step = 3;
                                if ($os === 'delivered') $step = 4;
                                if ($os === 'completed') $step = 5;
                                if ($os === 'cancelled') $step = 0;

                                // compute percent width snapped to step centers (Placed=0%, Processing=25%, Shipped=50%, Delivered=75%, Completed=100%)
                                if ($step === 0) {
                                    $progressWidth = '10%';
                                } else {
                                    $max = 5;
                                    $pct = (int)round((($step - 1) / ($max - 1)) * 100);
                                    $progressWidth = $pct . '%';
                                }
                            ?>

                                <!-- Premium Order Card -->
                                <section class="group border border-gray-200 rounded-3xl bg-white overflow-hidden hover:shadow-lg hover:border-gray-300 transition"
                                    data-order-id="<?= $oid ?>"
                                    data-order-status="<?= e($os) ?>"
                                    data-pay-status="<?= e($ps) ?>">

                                    <!-- Top bar (modern) -->
                                    <div class="px-5 sm:px-7 py-5 border-b bg-gradient-to-b from-white to-gray-50/40
                                                flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-indigo-50 border border-indigo-100">
                                                    <i class="fa-solid fa-receipt text-indigo-600"></i>
                                                </span>
                                                <div>
                                                    <p class="text-sm font-extrabold text-gray-900">Order #<?= e((string)$oid) ?></p>
                                                    <p class="text-xs text-gray-500">Paid on: <?= e($payNice) ?></p>
                                                </div>
                                            </div>
                                            <?php if ($dateNice): ?>
                                                <p class="text-xs text-gray-500 ml-12">Placed: <?= e($dateNice) ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button"
                                                class="px-4 py-2 rounded-2xl border border-gray-200 bg-white text-sm font-extrabold text-gray-700
                                                       hover:bg-gray-50 hover:border-gray-300 transition"
                                                onclick="printOrderCard(<?= $oid ?>)">
                                                <i class="fa-solid fa-file-invoice mr-2 text-gray-500"></i>Invoice
                                            </button>

                                            <a href="products.php"
                                                class="px-4 py-2 rounded-2xl bg-indigo-600 text-white text-sm font-extrabold
                                                       hover:bg-indigo-700 transition shadow-sm">
                                                <i class="fa-solid fa-bag-shopping mr-2"></i>Buy Again
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Items list -->
                                    <div class="divide-y" id="itemsWrap-<?= $oid ?>">
                                        <div class="px-5 sm:px-7 py-6">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-extrabold">Items</p>
                                            </div>

                                            <div class="order-items-container mt-4 text-sm text-gray-500"
                                                data-order-id="<?= $oid ?>">
                                                Loading items...
                                            </div>
                                        </div>
                                    </div>

                                    <!-- BEST Bottom bar -->
                                    <div class="border-t bg-white">
                                        <div class="px-5 sm:px-7 py-5">
                                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                                                <!-- Left: Status pills + meta -->
                                                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold border <?= pillPayment($ps) ?>">
                                                            <span class="h-2 w-2 rounded-full <?= statusDot($ps) ?>"></span>
                                                            <i class="fa-solid <?= e(paymentIcon($ps)) ?>"></i>
                                                            Payment: <?= e(niceStatusLabel($ps)) ?>
                                                        </span>

                                                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold border <?= pillStatus($os) ?>">
                                                            <span class="h-2 w-2 rounded-full <?= statusDot($os) ?>"></span>
                                                            <i class="fa-solid fa-truck-fast"></i>
                                                            Order: <?= e(niceStatusLabel($os)) ?>
                                                        </span>

                                                        <?php if ($ship !== ''): ?>
                                                            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold border <?= pillShipping($ship) ?>">
                                                                <span class="h-2 w-2 rounded-full <?= statusDot($ship) ?>"></span>
                                                                <i class="fa-solid fa-box"></i>
                                                                Shipping: <?= e(niceStatusLabel($ship)) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="text-xs text-gray-500 sm:pl-2">
                                                        <span class="inline-flex items-center gap-2">
                                                            <i class="fa-regular fa-calendar"></i>
                                                            <?= $dateNice ? e($dateNice) : '—' ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Right: Total + actions -->
                                                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                                                    <div class="flex items-baseline justify-between sm:justify-end gap-3 rounded-2xl bg-gray-50 border border-gray-200 px-4 py-3">
                                                        <p class="text-xs font-extrabold text-gray-500">Total</p>
                                                        <p class="text-lg font-extrabold text-gray-900"><?= money($total) ?></p>
                                                    </div>

                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <?php if ($os === 'pending' || $os === 'processing' || $os === 'shipped'): ?>
                                                            <button type="button"
                                                                class="px-4 py-2 rounded-2xl bg-rose-600 text-white text-sm font-extrabold hover:bg-rose-700 transition"
                                                                onclick="requestCancel(<?= $oid ?>)">
                                                                <i class="fa-solid fa-xmark mr-2"></i>Cancel
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                            </div>

                                            <!-- Optional progress -->
                                            <div class="mt-4">
                                                <div class="order-progress">
                                                    <div class="order-progress-label">Placed</div>
                                                    <div class="order-progress-label">Processing</div>
                                                    <div class="order-progress-label">Shipped</div>
                                                    <div class="order-progress-label">Delivered</div>
                                                    <div class="order-progress-label">Completed</div>

                                                    <div class="order-progress-track" role="progressbar" aria-valuenow="<?= (int)$step ?>" aria-valuemin="0" aria-valuemax="5">
                                                        <div class="order-progress-fill" style="width: <?= e($progressWidth) ?>;"></div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                </section>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>

    </main>

    <?php require_once __DIR__ . '/../../includes/shader/footer.php'; ?>

    <script src="/E-commerce-shoes/view/assets/Js/myorder.js"></script>

</body>

</html>