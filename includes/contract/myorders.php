<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= AUTH ================= */
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/view/myorder.php';
    header('Location: ../auth/Log/login.php');
    exit;
}

/* ================= DB ================= */
$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    die('Database connection missing');
}

/* ================= HELPERS ================= */
function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function badge(string $status): string
{
    return match (strtolower($status)) {
        'paid'      => 'bg-green-100 text-green-700',
        'unpaid'    => 'bg-yellow-100 text-yellow-700',
        'cancelled' => 'bg-red-100 text-red-700',
        default     => 'bg-gray-100 text-gray-700',
    };
}

/* ================= FETCH ORDERS ================= */
$orders = [];
try {
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
        LEFT JOIN shipping s ON s.order_id = o.order_id
        WHERE o.user_id = :uid
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([':uid' => (int)$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[myorder] ' . $e->getMessage());
    $orders = [];
}
?>
<!-- HTML BELOW (your page UI) -->