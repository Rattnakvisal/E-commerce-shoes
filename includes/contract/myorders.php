<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= AUTH ================= */
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/E-commerce-shoes/view/myorder.php');
    if (strpos($uri, '/') !== 0) $uri = '/E-commerce-shoes/view/myorder.php'; // internal only
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
 */
function badge(string $status): string
{
    $s = strtolower(trim($status));
    return match ($s) {
        'paid'      => 'bg-emerald-100 text-emerald-700',
        'pending'   => 'bg-amber-100 text-amber-700',
        'unpaid'    => 'bg-amber-100 text-amber-700',
        'failed'    => 'bg-red-100 text-red-700',
        'refunded'  => 'bg-blue-100 text-blue-700',
        'cancelled' => 'bg-gray-200 text-gray-700',
        default     => 'bg-gray-100 text-gray-700',
    };
}

/**
 * Order status pill classes (ONLY order_status values)
 */
function orderPill(string $status): string
{
    $s = strtolower(trim($status));
    return match ($s) {
        'completed'  => 'bg-indigo-100 text-indigo-700',
        'shipped'    => 'bg-blue-100 text-blue-700',
        'processing' => 'bg-gray-100 text-gray-700',
        'cancelled'  => 'bg-red-100 text-red-700',
        default      => 'bg-gray-100 text-gray-700',
    };
}

/**
 * One place to control padding classes in UI
 * Use it in your myorder.php view
 */
function uiPad(string $size = 'md'): string
{
    return match ($size) {
        'sm' => 'px-4 py-3 lg:px-5 lg:py-4',
        'md' => 'px-5 py-4 lg:px-6 lg:py-5',
        'lg' => 'px-6 py-5 lg:px-8 lg:py-6',
        default => 'px-5 py-4 lg:px-6 lg:py-5',
    };
}

/* ================= FETCH ORDERS ================= */
$orders = [];
$summary = [
    'total_orders' => 0,
    'paid' => 0,
    'pending' => 0,
    'total_spent' => 0.0,
];

try {
    /**
     * ✅ IMPORTANT FIX:
     * If shipping table has many rows per order_id, JOIN will duplicate orders.
     * This query joins ONLY the latest shipping record per order (by shipping_id).
     * If your shipping table uses another primary key name, tell me (ex: id).
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
            s.country
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
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($orders as &$o) {
        $o['order_id'] = (int)($o['order_id'] ?? 0);
        $o['total'] = (float)($o['total'] ?? 0);

        $o['payment_status'] = strtolower((string)($o['payment_status'] ?? 'pending'));
        $o['order_status']   = strtolower((string)($o['order_status'] ?? 'processing'));

        $o['created_at'] = (string)($o['created_at'] ?? '');

        $o['address'] = (string)($o['address'] ?? '');
        $o['city']    = (string)($o['city'] ?? '');
        $o['country'] = (string)($o['country'] ?? '');
    }
    unset($o);

    // summary
    $summary['total_orders'] = count($orders);

    foreach ($orders as $o) {
        $summary['total_spent'] += (float)$o['total'];

        if ($o['payment_status'] === 'paid') {
            $summary['paid']++;
        }
        if (in_array($o['payment_status'], ['pending', 'unpaid'], true)) {
            $summary['pending']++;
        }
    }
} catch (Throwable $e) {
    error_log('[myorders.php] ' . $e->getMessage());
    $orders = [];
}
