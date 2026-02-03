<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lon = isset($_GET['lon']) ? (float)$_GET['lon'] : null;

if (!is_finite($lat) || !is_finite($lon) || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

$lang = 'en';

$url = sprintf(
    'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=%s&lon=%s&addressdetails=1&accept-language=%s',
    rawurlencode((string)$lat),
    rawurlencode((string)$lon),
    rawurlencode($lang)
);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 12,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_HTTPHEADER     => [
        // Nominatim prefers a real User-Agent
        'User-Agent: E-commerce-shoes/1.0 (contact: admin@myshop.local)',
        'Accept: application/json'
    ],
]);

$res  = curl_exec($ch);
$err  = curl_error($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($res === false || $err || $code < 200 || $code >= 300) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Reverse geocode failed',
        'http_code' => $code,
        'curl_error' => $err ?: null
    ]);
    exit;
}

echo $res;
