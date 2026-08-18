<?php

declare(strict_types=1);

$telegramConfig = require __DIR__ . '/../../config/telegram.php';

if (!defined('TELEGRAM_BOT_TOKEN')) {
    define('TELEGRAM_BOT_TOKEN', (string)($telegramConfig['bot_token'] ?? ''));
}
if (!defined('TELEGRAM_CHAT_ID')) {
    define('TELEGRAM_CHAT_ID', (string)($telegramConfig['chat_id'] ?? ''));
}

final class TelegramNotificationService
{
    private const API_TIMEOUT = 15;
    private const CONNECT_TIMEOUT = 5;
    private const PARSE_MODE  = 'HTML';

    /** Where your bank logos are stored (local filesystem path) */
    private const LOGO_BASE_PATH = __DIR__ . '/../../view/assets/Payments/';

    /** Maximum media items in Telegram media group */
    private const MAX_MEDIA_GROUP = 10;

    /** Mapping payment code => display name + logo file */
    private const BANK_INFO = [
        'aba'      => ['name' => ' ABA Bank',      'logo' => 'aba.png'],
        'acleda'   => ['name' => ' ACLEDA Bank',   'logo' => 'acleda.png'],
        'wing'     => ['name' => ' WING',         'logo' => 'wing.png'],
        'chipmong' => ['name' => ' CHIP MONG',    'logo' => 'chipmong.png'],
        'bakong'   => ['name' => '🇰🇭 BAKONG',      'logo' => 'icon.png'],
    ];

    /* =====================================================
       PUBLIC API
    ====================================================== */

    public static function notifyPaymentSuccess(
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
        ?float $lat = null,
        ?float $lng = null,
        ?string $adminUrl = null
    ): bool {
        if (!self::isConfigured()) {
            error_log('Telegram notification skipped: bot token or chat ID is missing.');
            return false;
        }

        // Validate GPS (optional)
        $hasGps = self::validLatLng($lat, $lng);

        // Caption
        $items   = self::buildItems($products, $cart);
        $caption = self::buildCaption(
            $orderId,
            $name,
            $email,
            $phone,
            $address,
            $city,
            $country,
            $paymentCode,
            $total,
            $items,
            $hasGps ? $lat : null,
            $hasGps ? $lng : null
        );

        // Media: bank logo + product images (optional)
        $media = self::collectMedia($paymentCode, $products, $cart);

        // Send message with media if available
        $sent = self::sendCaptionWithOptionalMedia($caption, $media);
        if (!$sent) return false;

        // Optional: send telegram "pin" location
        // (Uncomment if you want a real location message)
        if ($hasGps) {
            self::sendLocation($lat, $lng);
        }

        // Send buttons message (adds Open Map)
        self::sendActionButtons($orderId, $adminUrl, $hasGps ? $lat : null, $hasGps ? $lng : null);
        return true;
    }

    /* =====================================================
       CONFIG / GUARDS
    ====================================================== */

    private static function isConfigured(): bool
    {
        return self::botToken() !== '' && self::chatId() !== '';
    }

    /** Return bot token: prefer defined constant, then env var */
    private static function botToken(): string
    {
        if (defined('TELEGRAM_BOT_TOKEN')) {
            return (string) TELEGRAM_BOT_TOKEN;
        }

        $v = getenv('TELEGRAM_BOT_TOKEN');
        return $v === false ? '' : (string) $v;
    }

    /** Return chat id: prefer defined constant, then env var */
    private static function chatId(): string
    {
        if (defined('TELEGRAM_CHAT_ID')) {
            return (string) TELEGRAM_CHAT_ID;
        }

        $v = getenv('TELEGRAM_CHAT_ID');
        return $v === false ? '' : (string) $v;
    }

    private static function validLatLng(?float $lat, ?float $lng): bool
    {
        if (!is_float($lat) || !is_float($lng)) return false;
        if ($lat < -90 || $lat > 90) return false;
        if ($lng < -180 || $lng > 180) return false;
        return true;
    }

    /* =====================================================
       TELEGRAM CORE
    ====================================================== */

    private static function api(string $method, array $payload): bool
    {
        $token = self::botToken();
        if ($token === '') return false;

        $url = 'https://api.telegram.org/bot' . $token . '/' . $method;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::API_TIMEOUT,
            CURLOPT_POSTFIELDS     => $payload,
        ]);

        $res  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = is_string($res) ? json_decode($res, true) : null;
        $ok = $res !== false
            && $err === ''
            && $code >= 200
            && $code < 300
            && is_array($body)
            && ($body['ok'] ?? false) === true;

        if (!$ok) {
            $description = is_array($body) ? (string)($body['description'] ?? '') : '';
            error_log(sprintf('Telegram API %s failed (HTTP %d): %s%s', $method, $code, $err, $description));
        }

        return $ok;
    }

    private static function sendMessage(string $text): bool
    {
        $chat = self::chatId();
        if ($chat === '') return false;

        return self::api('sendMessage', [
            'chat_id'                  => $chat,
            'text'                     => $text,
            'parse_mode'               => self::PARSE_MODE,
            'disable_web_page_preview' => true,
        ]);
    }

    private static function sendPhoto($photo, string $caption = ''): bool
    {
        $chat = self::chatId();
        if ($chat === '') return false;

        return self::api('sendPhoto', [
            'chat_id'    => $chat,
            'photo'      => $photo,
            'caption'    => $caption,
            'parse_mode' => self::PARSE_MODE,
        ]);
    }

    private static function sendMediaGroup(array $mediaItems, string $caption = ''): bool
    {
        if (!$mediaItems) return false;

        $media = [];
        $attachments = [];
        $i = 0;

        foreach ($mediaItems as $item) {
            $i++;
            $m = ['type' => 'photo'];

            if (is_string($item) && self::isLocalFile($item)) {
                $key = "file{$i}";
                $m['media'] = 'attach://' . $key;
                $attachments[$key] = new CURLFile($item);
            } else {
                $m['media'] = (string) $item;
            }

            if ($i === 1 && $caption !== '') {
                $m['caption'] = $caption;
                $m['parse_mode'] = self::PARSE_MODE;
            }

            $media[] = $m;

            if ($i >= self::MAX_MEDIA_GROUP) break;
        }

        $chat = self::chatId();
        if ($chat === '') return false;

        $payload = [
            'chat_id' => $chat,
            'media'   => json_encode($media, JSON_UNESCAPED_SLASHES),
        ];

        return self::api('sendMediaGroup', array_merge($payload, $attachments));
    }

    /** Send real Telegram location pin */
    private static function sendLocation(float $lat, float $lng): bool
    {
        $chat = self::chatId();
        if ($chat === '') return false;

        return self::api('sendLocation', [
            'chat_id'    => $chat,
            'latitude'   => (string)$lat,
            'longitude'  => (string)$lng,
            // optional
            'live_period' => 0,
        ]);
    }

    /* =====================================================
       BUILDERS
    ====================================================== */

    private static function money(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }

    private static function bankInfo(string $code): array
    {
        $code = strtolower(trim($code));

        return self::BANK_INFO[$code] ?? [
            'name' => strtoupper($code),
            'logo' => null,
        ];
    }

    private static function bankLogoPath(string $paymentCode): ?string
    {
        $info = self::bankInfo($paymentCode);
        if (empty($info['logo'])) return null;

        $path = self::LOGO_BASE_PATH . $info['logo'];
        return self::isLocalFile($path) ? $path : null;
    }

    private static function buildItems(array $products, array $cart): array
    {
        $items = [];

        foreach ($products as $p) {
            $pid = (int) ($p['product_id'] ?? 0);
            $qty = (int) ($cart[$pid] ?? 0);
            if ($pid <= 0 || $qty < 1) continue;

            $name  = htmlspecialchars((string)($p['name'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
            $price = (float) ($p['price'] ?? 0);

            $items[] = sprintf('• %s × %d (%s)', $name, $qty, self::money($price));
        }

        return $items;
    }

    private static function buildCaption(
        int $orderId,
        string $name,
        string $email,
        string $phone,
        string $address,
        string $city,
        string $country,
        string $paymentCode,
        float $total,
        array $items,
        ?float $lat = null,
        ?float $lng = null
    ): string {
        $bank = self::bankInfo($paymentCode);

        // prevent HTML breaking in telegram
        $name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $email   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $phone   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $address = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
        $city    = htmlspecialchars($city, ENT_QUOTES, 'UTF-8');
        $country = htmlspecialchars($country, ENT_QUOTES, 'UTF-8');

        $location = trim(implode(', ', array_filter([$address, $city, $country])));

        // Map link (only if GPS provided)
        $mapLine = '';
        if (self::validLatLng($lat, $lng)) {
            $mapUrl = 'https://www.google.com/maps?q=' . rawurlencode($lat . ',' . $lng);
            $mapLine = implode("\n", [
                '',
                '📍 <b>GPS</b>',
                "Lat: <code>{$lat}</code>",
                "Lng: <code>{$lng}</code>",
                "Map: {$mapUrl}",
            ]);
        }

        return implode("\n", [
            '━━━━━━━━━━━━━━━━━━',
            '<b>PAYMENT SUCCESS</b>',
            '━━━━━━━━━━━━━━━━━━',
            '',
            " <b>Order:</b> <code>#{$orderId}</code>",
            " <b>Name:</b> {$name}",
            " <b>Email:</b> {$email}",
            " <b>Phone:</b> {$phone}",
            '',
            ' <b>Shipping</b>',
            $location ?: '—',
            $mapLine,
            '',
            " <b>Method:</b> {$bank['name']}",
            " <b>Total:</b> <b>" . self::money($total) . "</b>",
            '',
            '🛒 <b>Items</b>',
            $items ? implode("\n", $items) : '• (no items)',
            '',
            ' <b>Time:</b> ' . date('Y-m-d H:i:s'),
            '━━━━━━━━━━━━━━━━━━',
        ]);
    }

    /* =====================================================
       MEDIA COLLECTION
    ====================================================== */
    private static function collectMedia(string $paymentCode, array $products, array $cart): array
    {
        $media = [];

        // 1) bank logo first
        $logo = self::bankLogoPath($paymentCode);
        if ($logo) $media[] = $logo;

        // 2) product images (optional)
        foreach ($products as $p) {
            $pid = (int) ($p['product_id'] ?? 0);
            $qty = (int) ($cart[$pid] ?? 0);
            if ($pid <= 0 || $qty < 1) continue;

            $img = trim((string)($p['image_url'] ?? ''));
            if ($img === '') continue;

            $resolved = self::resolveImage($img);
            if ($resolved) $media[] = $resolved;

            if (count($media) >= self::MAX_MEDIA_GROUP) break;
        }

        // remove duplicates
        $media = array_values(array_unique($media));

        // keep within telegram limit
        return array_slice($media, 0, self::MAX_MEDIA_GROUP);
    }

    private static function resolveImage(string $img): ?string
    {
        if (preg_match('#^https?://#i', $img)) return $img;

        $root = __DIR__ . '/../../';

        $p1 = $root . ltrim($img, '/\\');
        if (self::isLocalFile($p1)) return $p1;

        $p2 = $root . 'view/' . ltrim($img, '/\\');
        if (self::isLocalFile($p2)) return $p2;

        $candidates = [
            $root . 'assets/Images/' . basename($img),
            $root . 'assets/Images/products/' . basename($img),
            $root . 'view/assets/Images/' . basename($img),
            $root . 'view/assets/Images/products/' . basename($img),
            $root . 'view/assets/products/' . basename($img),
            $root . 'assets/products/' . basename($img),
        ];

        foreach ($candidates as $p) {
            if (self::isLocalFile($p)) return $p;
        }

        return null;
    }

    private static function isLocalFile(string $path): bool
    {
        return $path !== '' && file_exists($path) && is_file($path);
    }

    private static function sendCaptionWithOptionalMedia(string $caption, array $media): bool
    {
        if (!$media) {
            return self::sendMessage($caption);
        }

        if (count($media) === 1) {
            $first = $media[0];
            if (self::isLocalFile($first)) {
                return self::sendPhoto(new CURLFile($first), $caption);
            }
            return self::sendPhoto($first, $caption);
        }

        return self::sendMediaGroup($media, $caption);
    }

    /* =====================================================
       BUTTONS
    ====================================================== */

    private static function sendActionButtons(int $orderId, ?string $adminUrl, ?float $lat = null, ?float $lng = null): void
    {
        $row = [
            [
                'text' => 'Reload',
                'callback_data' => json_encode([
                    'action' => 'reload',
                    'order'  => $orderId,
                ], JSON_UNESCAPED_SLASHES),
            ],
        ];

        // ✅ Add Open Map button if GPS exists
        if (self::validLatLng($lat, $lng)) {
            $mapUrl = 'https://www.google.com/maps?q=' . rawurlencode($lat . ',' . $lng);
            $row[] = [
                'text' => 'Open Map',
                'url'  => $mapUrl,
            ];
        }

        if ($adminUrl && filter_var($adminUrl, FILTER_VALIDATE_URL)) {
            $row[] = [
                'text' => 'Open Dashboard',
                'url'  => $adminUrl,
            ];
        }

        $chat = self::chatId();
        if ($chat === '') return;

        self::api('sendMessage', [
            'chat_id'      => $chat,
            'text'         => "Order #{$orderId} processed successfully.",
            'parse_mode'   => self::PARSE_MODE,
            'reply_markup' => json_encode([
                'inline_keyboard' => [$row],
            ], JSON_UNESCAPED_SLASHES),
        ]);
    }
}
