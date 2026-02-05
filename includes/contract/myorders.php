<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= AUTH ================= */
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/E-commerce-shoes/view/content/myorder.php');
    if (strpos($uri, '/') !== 0) $uri = '/E-commerce-shoes/view/content/myorder.php';
    $_SESSION['after_login'] = $uri;

    header('Location: /E-commerce-shoes/view/auth/Log/login.php');
    exit;
}

/* ================= DB ================= */
$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection missing');
}

/* ================= HELPERS ================= */
function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/**
 * Payment status badge classes
 * supports: unpaid, pending, paid, failed, refunded
 */
function badge(string $status): string
{
    $s = strtolower(trim($status));
    return match ($s) {
        'paid'     => 'bg-emerald-100 text-emerald-700',
        'unpaid'   => 'bg-amber-100 text-amber-700',
        'pending'  => 'bg-amber-100 text-amber-700',
        'failed'   => 'bg-rose-100 text-rose-700',
        'refunded' => 'bg-purple-100 text-purple-700',
        default    => 'bg-gray-100 text-gray-700',
    };
}

/**
 * Order status pill classes
 * pending -> processing -> delivered -> completed
 */
function orderPill(string $status): string
{
    $s = strtolower(trim($status));
    return match ($s) {
        'pending'    => 'bg-amber-100 text-amber-700',
        'processing' => 'bg-slate-100 text-slate-700',
        'delivered'  => 'bg-indigo-100 text-indigo-700',
        'completed'  => 'bg-emerald-100 text-emerald-700',
        'cancelled'  => 'bg-rose-100 text-rose-700',
        default      => 'bg-gray-100 text-gray-700',
    };
}

/**
 * Shipping status pill (optional)
 */
function shipPill(string $status): string
{
    $s = strtolower(trim($status));
    return match ($s) {
        'pending'   => 'bg-amber-100 text-amber-700',
        'shipped'   => 'bg-sky-100 text-sky-700',
        'delivered' => 'bg-emerald-100 text-emerald-700',
        default     => 'bg-gray-100 text-gray-700',
    };
}

/* ================= FETCH ORDERS ================= */
$orders = [];
$summary = [
    'total_orders'     => 0,
    'paid'             => 0,
    'unpaid'           => 0,
    'pending'          => 0,
    'failed'           => 0,
    'refunded'         => 0,

    // totals
    'total_spent_all'  => 0.0, // all orders total
    'total_spent_paid' => 0.0, // only paid totals
];

try {
    /**
     * ✅ Join latest shipping record per order (no duplicates)
     * Supports BOTH column names:
     * - shipping.status (recommended)
     * - shipping.STATUS (old)
     */
    $stmt = $pdo->prepare("
        SELECT
            o.order_id,
            o.total,
            o.payment_status,
            o.order_status,
            o.created_at,

            s.address,
            s.city,
            s.country,

            COALESCE(s.status, s.STATUS) AS shipping_status
        FROM orders o
        LEFT JOIN shipping s
          ON s.shipping_id = (
              SELECT s2.shipping_id
              FROM shipping s2
              WHERE s2.order_id = o.order_id
              ORDER BY s2.shipping_id DESC
              LIMIT 1
          )
        WHERE o.user_id = :uid
        ORDER BY o.order_id DESC
    ");
    $stmt->execute([':uid' => $userId]);

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($orders as &$o) {
        $o['order_id'] = (int)($o['order_id'] ?? 0);
        $o['total']    = (float)($o['total'] ?? 0);

        $o['payment_status']  = strtolower(trim((string)($o['payment_status'] ?? 'unpaid')));
        $o['order_status']    = strtolower(trim((string)($o['order_status'] ?? 'pending')));
        $o['shipping_status'] = strtolower(trim((string)($o['shipping_status'] ?? '')));

        $o['created_at'] = (string)($o['created_at'] ?? '');

        $o['address'] = (string)($o['address'] ?? '');
        $o['city']    = (string)($o['city'] ?? '');
        $o['country'] = (string)($o['country'] ?? '');

        // totals
        $summary['total_spent_all'] += $o['total'];
        if ($o['payment_status'] === 'paid') {
            $summary['total_spent_paid'] += $o['total'];
        }

        // counts
        if (isset($summary[$o['payment_status']])) {
            $summary[$o['payment_status']]++;
        }
    }
    unset($o);

    $summary['total_orders'] = count($orders);
} catch (Throwable $e) {
    error_log('[myorders.php] ' . $e->getMessage());
    $orders = [];
}
