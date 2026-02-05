<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// -----------------------------
// Input
// -----------------------------
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lon = isset($_GET['lon']) ? (float)$_GET['lon'] : null;
$lang = isset($_GET['lang']) ? preg_replace('/[^a-zA-Z\-]/', '', (string)$_GET['lang']) : 'en';

if (!is_finite($lat) || !is_finite($lon) || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates'], JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------
// Real-time protection: cache + rate limit
// -----------------------------
$cacheDir = __DIR__ . '/../../storage/geocode_cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
}

// Round to reduce cache keys (0.0005 ~ 55m)
$latKey = number_format($lat, 4, '.', '');
$lonKey = number_format($lon, 4, '.', '');
$cacheKey = sha1($latKey . '|' . $lonKey . '|' . $lang);
$cacheFile = $cacheDir . '/' . $cacheKey . '.json';

// Cache TTL (seconds)
$ttl = 60;

// If cached, return immediately
if (is_file($cacheFile) && (time() - (int)filemtime($cacheFile) <= $ttl)) {
    $cached = file_get_contents($cacheFile);
    if ($cached !== false && $cached !== '') {
        echo $cached;
        exit;
    }
}

// Basic per-IP rate limit (1 request / 2 seconds)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rlFile = $cacheDir . '/rate_' . sha1($ip) . '.txt';
$now = time();
$minInterval = 2;

if (is_file($rlFile)) {
    $last = (int)trim((string)file_get_contents($rlFile));
    if ($last > 0 && ($now - $last) < $minInterval) {
        http_response_code(429);
        echo json_encode([
            'error' => 'Too many requests',
            'retry_after' => $minInterval - ($now - $last),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
@file_put_contents($rlFile, (string)$now, LOCK_EX);

// -----------------------------
// Call Nominatim
// -----------------------------
$url = sprintf(
    'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=%s&lon=%s&addressdetails=1&accept-language=%s',
    rawurlencode($latKey),
    rawurlencode($lonKey),
    rawurlencode($lang ?: 'en')
);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 12,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER     => [
        // IMPORTANT: use a real UA. Replace with your domain/email.
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
        'error'      => 'Reverse geocode failed',
        'http_code'  => $code,
        'curl_error' => $err ?: null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Ensure JSON
$decoded = json_decode($res, true);
if (!is_array($decoded)) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid JSON from provider'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Save cache
@file_put_contents($cacheFile, json_encode($decoded, JSON_UNESCAPED_UNICODE), LOCK_EX);

// Return
echo json_encode($decoded, JSON_UNESCAPED_UNICODE);
