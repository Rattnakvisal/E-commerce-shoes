<?php

declare(strict_types=1);

$telegramConfig = require __DIR__ . '/../../config/telegram.php';

if (!defined('TELEGRAM_BOT_TOKEN')) {
    define('TELEGRAM_BOT_TOKEN', (string)($telegramConfig['bot_token'] ?? ''));
}
if (!defined('TELEGRAM_CHAT_ID')) {
    define('TELEGRAM_CHAT_ID', (string)($telegramConfig['chat_id'] ?? ''));
}

// Optionally load the richer notification service if present
@require_once __DIR__ . '/../../includes/telegram/telegram.php';

function telegram_send_message(string $text): bool
{
    if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_CHAT_ID')) return false;

    $token = TELEGRAM_BOT_TOKEN;
    $chatId = TELEGRAM_CHAT_ID;

    if ($token === '' || $chatId === '') return false;

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $payload = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => http_build_query($payload),
    ]);

    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false || $err) return false;
    return $code >= 200 && $code < 300;
}

function telegram_notify_payment_success(
    int $orderId,
    string $name,
    string $email,
    string $phone,
    string $address,
    string $city,
    string $country,
    string $paymentCode,
    float $total,
    array $products,
    array $cart,
    $lat = null,
    $lng = null,
    ?string $adminUrl = null
): bool {
    if (is_string($lat) && $lng === null && $adminUrl === null && filter_var($lat, FILTER_VALIDATE_URL)) {
        $adminUrl = $lat;
        $lat = null;
    }

    $lat = is_numeric($lat) ? (float)$lat : null;
    $lng = is_numeric($lng) ? (float)$lng : null;

    // If the full service class is available, delegate to it
    if (class_exists('TelegramNotificationService')) {
        try {
            return TelegramNotificationService::notifyPaymentSuccess(
                $orderId,
                $name,
                $email,
                $phone,
                $address,
                $city,
                $country,
                $paymentCode,
                $total,
                $products,
                $cart,
                $lat,
                $lng,
                $adminUrl
            );

        } catch (Throwable) {
            // fallback to simple message below
        }
    }

    // Build a simple text message as a fallback
    $location = trim(implode(', ', array_filter([$address, $city, $country])));
    $lines = [];
    $lines[] = "Order: #{$orderId}";
    $lines[] = "Name: {$name}";
    $lines[] = "Email: {$email}";
    $lines[] = "Phone: {$phone}";
    $lines[] = "Shipping: {$location}";
    $lines[] = "Method: " . strtoupper($paymentCode);
    $lines[] = "Total: $" . number_format($total, 2);
    $lines[] = "Items:";

    foreach ($products as $p) {
        $pid = (int)($p['product_id'] ?? 0);
        $qty = (int)($cart[$pid] ?? 0);
        if ($qty < 1) continue;
        $lines[] = sprintf('%s x%d - $%s', $p['name'] ?? 'Item', $qty, number_format((float)($p['price'] ?? 0), 2));
    }

    $text = implode("\n", $lines);
    return telegram_send_message($text);
}
