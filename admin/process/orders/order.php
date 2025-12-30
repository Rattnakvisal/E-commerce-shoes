<?php

/**
 * =====================================================
 * ORDERS MANAGEMENT (ADMIN)
 * =====================================================
 */

require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   SESSION & AUTH
===================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../auth/login.php');
    exit;
}

/* =====================================================
   FETCH ORDERS (SAFE DEFAULT)
===================================================== */
$orders = [];

try {
    $stmt = $pdo->query("
        SELECT 
            o.order_id,
            o.user_id,
            o.total,
            o.order_status,
            o.payment_status,
            o.order_type,
            o.created_at,
            COALESCE(u.name, u.email, 'Guest') AS customer
        FROM orders o
        LEFT JOIN users u ON u.user_id = o.user_id
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[orders_list] ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Admin</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icons -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50">

    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>

    <main class="md:ml-64 p-6">
        <div class="bg-white rounded-xl shadow p-6">

            <h1 class="text-xl font-semibold mb-4">All Orders</h1>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b text-gray-500">
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="ordersTable">
                        <?php if (!$orders): ?>
                            <tr>
                                <td colspan="8" class="py-4 text-center text-gray-500">
                                    No orders found
                                </td>
                            </tr>
                            <?php else: foreach ($orders as $o): ?>
                                <tr class="border-b hover:bg-gray-50" data-row="<?= (int)$o['order_id'] ?>">
                                    <td class="py-3 font-medium">#<?= (int)$o['order_id'] ?></td>
                                    <td><?= htmlspecialchars($o['customer']) ?></td>
                                    <td>$<?= number_format((float)$o['total'], 2) ?></td>
                                    <td><?= ucfirst($o['payment_status']) ?></td>
                                    <td class="status"><?= ucfirst($o['order_status']) ?></td>
                                    <td><?= strtoupper($o['order_type']) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></td>
                                    <td class="space-x-2">
                                        <button
                                            class="btn-view bg-indigo-600 text-white px-2 py-1 rounded text-xs"
                                            data-id="<?= (int)$o['order_id'] ?>">
                                            View
                                        </button>

                                        <?php if ($o['order_status'] !== 'completed'): ?>
                                            <button
                                                class="btn-complete bg-green-600 text-white px-2 py-1 rounded text-xs"
                                                data-id="<?= (int)$o['order_id'] ?>">
                                                Complete
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($o['order_status'] !== 'cancelled'): ?>
                                            <button
                                                class="btn-cancel bg-red-600 text-white px-2 py-1 rounded text-xs"
                                                data-id="<?= (int)$o['order_id'] ?>">
                                                Cancel
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <!-- Orders actions -->
    <script src="../../../assets/js/orders.js"></script>
</body>

</html>