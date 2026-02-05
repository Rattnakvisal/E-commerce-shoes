<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/conn.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function money($n): string
{
    return '$' . number_format((float)$n, 2);
}

$last = $_SESSION['last_order'] ?? null;

if (!$last) {
    header('Location: /E-commerce-shoes/view/content/products.php');
    exit;
}

unset($_SESSION['last_order']);

// Normalize / defaults
$orderId   = $last['order_id'] ?? '';
$name      = $last['name'] ?? '';
$email     = $last['email'] ?? '';
$phone     = $last['phone'] ?? '';
$address   = $last['address'] ?? '';
$city      = $last['city'] ?? '';
$country   = $last['country'] ?? '';
$payment   = strtoupper((string)($last['payment'] ?? ''));
$subtotal  = (float)($last['subtotal'] ?? 0);
$tax       = (float)($last['tax'] ?? 0);
$shipping  = (float)($last['shipping'] ?? 0);
$discount  = (float)($last['discount'] ?? 0);
$total     = (float)($last['total'] ?? ($subtotal + $tax + $shipping - $discount));

$items  = $last['items'] ?? [];
$qtyMap = $last['quantities'] ?? [];

$orderDate = $last['created_at'] ?? date('Y-m-d H:i:s');

// GPS (optional)
$lat = isset($last['lat']) && $last['lat'] !== '' ? (float)$last['lat'] : null;
$lng = isset($last['lng']) && $last['lng'] !== '' ? (float)$last['lng'] : null;

$hasGps = is_float($lat) && is_float($lng);

// Map links
$googleMapsUrl = $hasGps ? "https://www.google.com/maps?q=" . rawurlencode($lat . "," . $lng) : null;

// OpenStreetMap embed (simple)
$osmEmbedUrl = null;
if ($hasGps) {
    // zoom=16 good for delivery
    $osmEmbedUrl = "https://www.openstreetmap.org/export/embed.html?bbox=" .
        rawurlencode(($lng - 0.01) . "," . ($lat - 0.01) . "," . ($lng + 0.01) . "," . ($lat + 0.01)) .
        "&layer=mapnik&marker=" . rawurlencode($lat . "," . $lng);
}
