<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$order = $_SESSION['last_order'] ?? null;
if (!$order) {
    header('Location: products.php');
    exit;
}

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$subtotal     = (float) $order['total'];
$platformFee = $subtotal * 0.20;
$takeHome    = $subtotal - $platformFee;

$orderDates = [
    date('l, j F Y', strtotime('-2 days')),
    date('l, j F Y', strtotime('today')),
    date('l, j F Y', strtotime('+2 days')),
];
$orderTimes = ['4:30', '1:30', '2:00'];
$totalHours = '8:00';
