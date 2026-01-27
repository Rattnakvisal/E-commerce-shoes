<?php
require_once __DIR__ . '/../../config/connection.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/* -------------------------
|  USER / SESSION DATA
--------------------------*/
$userId     = $_SESSION['user_id'] ?? null;
$userLogged = !empty($userId);

$userName = $_SESSION['name']
    ?? $_SESSION['NAME']
    ?? $_SESSION['user_name']
    ?? $_SESSION['email']
    ?? '';

$email = $_SESSION['email'] ?? '';

/* -------------------------
|  USER INITIALS
--------------------------*/
$initials = '';

if ($userName) {
    $parts = preg_split('/\s+/', trim($userName));
    $initials = strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? ''));
}

if (!$initials && $email) {
    $initials = strtoupper(substr(strtok($email, '@'), 0, 2));
}

/* -------------------------
|  USER AVATAR
--------------------------*/
if (!empty($_SESSION['avatar'])) {
    $userAvatar = $_SESSION['avatar'];
    if (!preg_match('#^https?://#i', $userAvatar) && $userAvatar[0] !== '/') {
        $userAvatar = '/' . ltrim($userAvatar, '/');
    }
} else {
    $userAvatar = 'https://ui-avatars.com/api/?name='
        . urlencode($userName ?: 'User')
        . '&background=10b981&color=fff';
}

/* -------------------------
|  CART & WISHLIST COUNT
--------------------------*/
$cartKey     = $userId ? "cart_user_$userId" : 'cart_guest';
$wishlistKey = $userId ? "wishlist_user_$userId" : 'wishlist_guest';

$navCartCount     = array_sum($_SESSION[$cartKey] ?? []);
$navWishlistCount = count($_SESSION[$wishlistKey] ?? []);

/* -------------------------
|  NAVBAR DATA
--------------------------*/
try {
    $parents = $pdo->query(
        "SELECT id, title, position FROM navbar_parents ORDER BY position, id"
    )->fetchAll(PDO::FETCH_ASSOC);

    $groups = $pdo->query(
        "SELECT id, parent_id, group_title, position, link_url 
         FROM navbar_groups ORDER BY position, id"
    )->fetchAll(PDO::FETCH_ASSOC);

    $items = $pdo->query(
        "SELECT id, group_id, item_title, position, link_url 
         FROM navbar_items ORDER BY position, id"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<!-- Navbar load error -->';
    return;
}

/* -------------------------
|  GROUP DATA STRUCTURE
--------------------------*/
$groupsByParent = [];
foreach ($groups as $group) {
    $groupsByParent[$group['parent_id']][] = $group;
}

$itemsByGroup = [];
foreach ($items as $item) {
    $itemsByGroup[$item['group_id']][] = $item;
}
