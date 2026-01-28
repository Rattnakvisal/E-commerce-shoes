<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/telegram.php';

final class TelegramNotificationService
{
    private const API_TIMEOUT = 15;
    private const PARSE_MODE  = 'HTML';

    /** Where your bank logos are stored (local filesystem path) */
    private const LOGO_BASE_PATH = __DIR__ . '/../../view/assets/Payments/';

    /** Maximum media items in Telegram media group */
    private const MAX_MEDIA_GROUP = 10;

    /** Mapping payment code => display name + logo file */
    private const BANK_INFO = [
        'aba' => ['name' => ' ABA Bank',     'logo' => 'aba.png'],
        'acleda' => ['name' => ' ACLEDA Bank', 'logo' => 'acleda.png'],
        'wing' => ['name' => ' WING',         'logo' => 'wing.png'],
        'chipmong' => ['name' => ' CHIP MONG', 'logo' => 'chipmong.png'],
        'bakong' => ['name' => '🇰🇭 BAKONG',     'logo' => 'icon.png'],
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
        ?string $adminUrl = null
    ): void {
        if (!self::isConfigured()) return;

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
            $items
        );

        // Media: bank logo + product images (optional)
        $media = self::collectMedia($paymentCode, $products, $cart);

        // Send message with media if available
        self::sendCaptionWithOptionalMedia($caption, $media);

        // Send buttons message
        self::sendActionButtons($orderId, $adminUrl);
    }

    /* =====================================================
       CONFIG / GUARDS
    ====================================================== */

    private static function isConfigured(): bool
    {
        return defined('TELEGRAM_BOT_TOKEN') && defined('TELEGRAM_CHAT_ID')
            && TELEGRAM_BOT_TOKEN !== '' && TELEGRAM_CHAT_ID !== '';
    }

    /* =====================================================
       TELEGRAM CORE
    ====================================================== */

    private static function api(string $method, array $payload): bool
    {
        $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/' . $method;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::API_TIMEOUT,
            CURLOPT_POSTFIELDS     => $payload, // if payload contains CURLFile => multipart auto
        ]);

        $res  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return !($res === false || $err) && $code >= 200 && $code < 300;
    }

    private static function sendMessage(string $text): bool
    {
        return self::api('sendMessage', [
            'chat_id'                  => TELEGRAM_CHAT_ID,
            'text'                     => $text,
            'parse_mode'               => self::PARSE_MODE,
            'disable_web_page_preview' => true,
        ]);
    }

    private static function sendPhoto($photo, string $caption = ''): bool
    {
        return self::api('sendPhoto', [
            'chat_id'    => TELEGRAM_CHAT_ID,
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

            // Local file path
            if (is_string($item) && self::isLocalFile($item)) {
                $key = "file{$i}";
                $m['media'] = 'attach://' . $key;
                $attachments[$key] = new CURLFile($item);
            } else {
                // Remote URL
                $m['media'] = (string) $item;
            }

            // Caption only on the first media item
            if ($i === 1 && $caption !== '') {
                $m['caption'] = $caption;
                $m['parse_mode'] = self::PARSE_MODE;
            }

            $media[] = $m;

            if ($i >= self::MAX_MEDIA_GROUP) break;
        }

        $payload = [
            'chat_id' => TELEGRAM_CHAT_ID,
            'media'   => json_encode($media, JSON_UNESCAPED_SLASHES),
        ];

        return self::api('sendMediaGroup', array_merge($payload, $attachments));
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

            $name  = (string) ($p['name'] ?? '');
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
        array $items
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
            $location,
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
        // Remote URL
        if (preg_match('#^https?://#i', $img)) return $img;

        // Try local relative to project root
        $root = __DIR__ . '/../../';

        // 1) exact relative path from project root
        $p1 = $root . ltrim($img, '/\\');
        if (self::isLocalFile($p1)) return $p1;

        // 2) inside /view
        $p2 = $root . 'view/' . ltrim($img, '/\\');
        if (self::isLocalFile($p2)) return $p2;

        // 3) common images folders
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

    private static function sendCaptionWithOptionalMedia(string $caption, array $media): void
    {
        if (!$media) {
            self::sendMessage($caption);
            return;
        }

        // One media only => sendPhoto with caption
        if (count($media) === 1) {
            $first = $media[0];
            if (self::isLocalFile($first)) {
                self::sendPhoto(new CURLFile($first), $caption);
            } else {
                self::sendPhoto($first, $caption); // remote url
            }
            return;
        }

        // Multiple => media group with caption on first
        self::sendMediaGroup($media, $caption);
    }

    /* =====================================================
       BUTTONS
    ====================================================== */

    private static function sendActionButtons(int $orderId, ?string $adminUrl): void
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

        if ($adminUrl && filter_var($adminUrl, FILTER_VALIDATE_URL)) {
            $row[] = [
                'text' => 'Open Dashboard',
                'url'  => $adminUrl,
            ];
        }

        self::api('sendMessage', [
            'chat_id'      => TELEGRAM_CHAT_ID,
            'text'         => "Order #{$orderId} processed successfully.",
            'parse_mode'   => self::PARSE_MODE,
            'reply_markup' => json_encode([
                'inline_keyboard' => [$row],
            ], JSON_UNESCAPED_SLASHES),
        ]);
    }
}
