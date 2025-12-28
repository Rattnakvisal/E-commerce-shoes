<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -------------------------------------------------
   Auth Guard
------------------------------------------------- */
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/view/my_orders.php';
    header('Location: ../auth/login.php');
    exit;
}

/* -------------------------------------------------
   Helpers
------------------------------------------------- */
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function badge(string $status): string
{
    return match ($status) {
        'paid' => 'bg-green-100 text-green-700',
        'unpaid' => 'bg-yellow-100 text-yellow-700',
        'cancelled' => 'bg-red-100 text-red-700',
        default => 'bg-gray-100 text-gray-700'
    };
}

/* -------------------------------------------------
   Fetch Orders
------------------------------------------------- */
try {
    $stmt = $pdo->prepare(
        "SELECT o.order_id, o.total, o.payment_status, o.order_status, o.created_at,
                s.address, s.city, s.country
         FROM orders o
         LEFT JOIN shipping s ON s.order_id = o.order_id
         WHERE o.user_id = :uid
         ORDER BY o.created_at DESC"
    );
    $stmt->execute(['uid' => $userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $orders = [];
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icons -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-white">

    <?php
    require_once '../includes/topbar.php';
    require_once '../includes/navbar.php';
    ?>

    <main class="max-w-6xl mx-auto px-4 py-12">

        <h1 class="text-2xl font-bold mb-8">My Orders</h1>

        <?php if (empty($orders)): ?>
            <div class="border rounded-lg p-8 text-center text-gray-600">
                You haven’t placed any orders yet.
                <a href="products.php" class="text-indigo-600 font-medium">
                    Start shopping
                </a>
            </div>
        <?php else: ?>

            <div class="space-y-8">

                <?php foreach ($orders as $order):

                    $oid = (int)$order['order_id'];

                    // Items will be loaded on demand via AJAX to avoid large queries and handle missing tables
                    $items = [];
                    $itemsError = false;
                ?>

                <!-- ===============================
                 Order Card
                ================================ -->
                    <article class="border rounded-xl shadow-sm overflow-hidden">

                        <!-- Header -->
                        <div class="p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                            <div>
                                <p class="text-sm text-gray-500">
                                    Order #<?= e($oid) ?>
                                </p>
                                <p class="text-xs text-gray-400">
                                    <?= e(date('d M Y H:i', strtotime($order['created_at']))) ?>
                                </p>
                            </div>

                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?= badge($order['payment_status']) ?>">
                                    Payment: <?= e(ucfirst($order['payment_status'])) ?>
                                </span>

                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                    Status: <?= e(ucfirst($order['order_status'])) ?>
                                </span>

                                <span class="text-lg font-bold">
                                    $<?= number_format((float)$order['total'], 2) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="px-6 pb-4 text-xs text-gray-500 flex items-center gap-2">
                            <span class="font-medium text-black">Placed</span>
                            <i class="fas fa-arrow-right"></i>
                            <span class="<?= $order['payment_status'] === 'paid' ? 'font-medium text-black' : '' ?>">
                                Paid
                            </span>
                            <i class="fas fa-arrow-right"></i>
                            <span class="<?= $order['order_status'] === 'completed' ? 'font-medium text-black' : '' ?>">
                                Completed
                            </span>
                        </div>

                        <!-- Items -->
                        <details class="border-t">
                            <summary class="cursor-pointer px-6 py-4 text-sm font-medium hover:bg-gray-50">
                                View items (<?= count($items) ?>)
                            </summary>

                            <div class="px-6 py-4">
                                <div class="order-items-container" data-order-id="<?= $oid ?>">
                                    <div class="text-sm text-gray-500">Click to load items.</div>
                                </div>
                            </div>
                        </details>

                        <!-- Shipping & Actions -->
                        <div class="border-t px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                            <div class="text-sm text-gray-600">
                                <p class="font-medium">Shipping Address</p>
                                <p>
                                    <?= e($order['address'] ?? '') ?>,
                                    <?= e($order['city'] ?? '') ?>,
                                    <?= e($order['country'] ?? '') ?>
                                </p>
                            </div>

                            <div class="flex gap-3">
                                <a href="order_detail.php?order_id=<?= $oid ?>"
                                    class="px-4 py-2 text-sm rounded-full border hover:bg-gray-100">
                                    View Details
                                </a>

                                <button onclick="window.print()"
                                    class="px-4 py-2 text-sm rounded-full bg-indigo-600 text-white hover:bg-indigo-700">
                                    Print / PDF
                                </button>
                            </div>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </main>

    <?php require_once '../includes/footer.php'; ?>

    <script>
        // Load items for an order when the details element is opened
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('details').forEach(details => {
                details.addEventListener('toggle', async function () {
                    if (!details.open) return;
                    const container = details.querySelector('.order-items-container');
                    if (!container) return;
                    const orderId = container.dataset.orderId;
                    // already loaded?
                    if (container.dataset.loaded === '1') return;
                    container.innerHTML = '<div class="text-sm text-gray-500">Loading items...</div>';

                    try {
                        const res = await fetch(`/E-commerce-shoes/view/order_items.php?order_id=${encodeURIComponent(orderId)}`, { credentials: 'same-origin' });
                        if (!res.ok) throw new Error('Failed to load');
                        const data = await res.json();
                        if (data.error) {
                            container.innerHTML = `<div class="text-sm text-red-500">${data.error}</div>`;
                            return;
                        }
                        const items = data.items || [];
                        if (items.length === 0) {
                            container.innerHTML = '<div class="text-sm text-gray-500">No items recorded for this order.</div>';
                        } else {
                            const list = document.createElement('div');
                            list.className = 'space-y-3';
                            items.forEach(it => {
                                const row = document.createElement('div');
                                row.className = 'flex justify-between text-sm';
                                row.innerHTML = `
                                    <div>
                                        <p class="font-medium">${(it.name||('Product #' + it.product_id)).replace(/</g,'&lt;')}</p>
                                        <p class="text-xs text-gray-500">Qty ${it.quantity} × $${Number(it.price).toFixed(2)}</p>
                                    </div>
                                    <p class="font-semibold">$${(it.quantity * it.price).toFixed(2)}</p>
                                `;
                                list.appendChild(row);
                            });
                            container.innerHTML = '';
                            container.appendChild(list);
                        }
                        container.dataset.loaded = '1';
                    } catch (err) {
                        container.innerHTML = '<div class="text-sm text-red-500">Unable to load items.</div>';
                    }
                });
            });
        });
    </script>

</body>

</html>