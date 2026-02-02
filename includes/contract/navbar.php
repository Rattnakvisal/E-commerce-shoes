<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/* =========================
   DB connection normalize
========================= */
$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    echo '<!-- Navbar: missing DB connection -->';
    return;
}

/* =========================
   Helpers
========================= */
function s(string $key, string $default = ''): string
{
    return trim((string)($_SESSION[$key] ?? $default));
}

function makeInitials(string $name, string $email): string
{
    $name = trim($name);
    if ($name !== '') {
        // Take first character of first 2 words (supports Khmer + Unicode)
        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';

        $i1 = $first !== '' ? mb_substr($first, 0, 1, 'UTF-8') : '';
        $i2 = $second !== '' ? mb_substr($second, 0, 1, 'UTF-8') : '';

        $ini = mb_strtoupper($i1 . $i2, 'UTF-8');
        if ($ini !== '') return $ini;
    }

    if ($email !== '') {
        $user = (string)strtok($email, '@');
        return strtoupper(substr($user, 0, 2));
    }

    return 'U';
}

function normalizeAvatarUrl(?string $url): string
{
    $url = trim((string)$url);
    if ($url === '') return '';

    // allow absolute http(s) or internal /path
    if (preg_match('#^https?://#i', $url)) return $url;
    if ($url[0] !== '/') $url = '/' . ltrim($url, '/');
    return $url;
}

function avatarFallback(string $name): string
{
    return 'https://ui-avatars.com/api/?name=' . urlencode($name ?: 'User')
        . '&background=111827&color=fff&bold=true&rounded=true&size=128';
}

/* =========================
   USER / SESSION
========================= */
$userId     = (int)($_SESSION['user_id'] ?? 0);
$userLogged = $userId > 0;

// use the best available session keys
$userName = s('name');
if ($userName === '') $userName = s('NAME');
if ($userName === '') $userName = s('user_name');
$email = s('email');

// If name empty but email exists, show email as label
$displayName = $userName !== '' ? $userName : ($email !== '' ? $email : 'Guest');

$initials = makeInitials($displayName, $email);

/* =========================
   AVATAR (Updated Profile)
   Priority:
   1) session avatar_url
   2) DB users.avatar_url (refresh occasionally)
   3) ui-avatars fallback
========================= */
$userAvatar = normalizeAvatarUrl($_SESSION['avatar_url'] ?? $_SESSION['avatar'] ?? '');

if ($userLogged && $userAvatar === '') {
    try {
        $stmt = $pdo->prepare("SELECT avatar_url, name, email FROM users WHERE user_id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // optionally refresh session name/email too (helps navbar stay updated)
        $dbName = trim((string)($row['name'] ?? ''));
        $dbEmail = trim((string)($row['email'] ?? ''));

        if ($dbName !== '') {
            $_SESSION['name'] = $dbName;
            $displayName = $dbName;
        }
        if ($dbEmail !== '' && $email === '') {
            $_SESSION['email'] = $dbEmail;
            $email = $dbEmail;
        }

        $userAvatar = normalizeAvatarUrl((string)($row['avatar_url'] ?? ''));
        if ($userAvatar !== '') {
            $_SESSION['avatar_url'] = $userAvatar; // cache
        }

        // recompute initials if name updated
        $initials = makeInitials($displayName, $email);
    } catch (Throwable $e) {
        // silent fail for navbar
    }
}

if ($userAvatar === '') {
    $userAvatar = avatarFallback($displayName);
}

/* =========================
   CART & WISHLIST COUNT
========================= */
$cartKey     = $userLogged ? "cart_user_$userId" : 'cart_guest';
$wishlistKey = $userLogged ? "wishlist_user_$userId" : 'wishlist_guest';

$navCartCount = 0;
if (!empty($_SESSION[$cartKey]) && is_array($_SESSION[$cartKey])) {
    // if cart is [productId => qty]
    $navCartCount = array_sum(array_map('intval', $_SESSION[$cartKey]));
}

$navWishlistCount = 0;
if (!empty($_SESSION[$wishlistKey]) && is_array($_SESSION[$wishlistKey])) {
    $navWishlistCount = count($_SESSION[$wishlistKey]);
}

/* =========================
   NAVBAR DATA (Parents/Groups/Items)
========================= */
try {
    $parents = $pdo->query("SELECT id, title, position FROM navbar_parents ORDER BY position, id")
        ->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $groups = $pdo->query("SELECT id, parent_id, group_title, position, link_url
                           FROM navbar_groups ORDER BY position, id")
        ->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $items = $pdo->query("SELECT id, group_id, item_title, position, link_url
                          FROM navbar_items ORDER BY position, id")
        ->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    echo '<!-- Navbar load error -->';
    return;
}

/* =========================
   GROUP DATA STRUCTURE
========================= */
$groupsByParent = [];
foreach ($groups as $g) {
    $pid = (int)($g['parent_id'] ?? 0);
    $groupsByParent[$pid][] = $g;
}

$itemsByGroup = [];
foreach ($items as $it) {
    $gid = (int)($it['group_id'] ?? 0);
    $itemsByGroup[$gid][] = $it;
}
