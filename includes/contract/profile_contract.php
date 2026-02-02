<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) session_start();

/* ================= AUTH ================= */
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/E-commerce-shoes/view/content/profile.php';
    header('Location: /E-commerce-shoes/view/auth/Log/login.php');
    exit;
}

/* ================= DB ================= */
$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection missing.');
}

/* ================= HELPERS ================= */
function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function colExists(PDO $pdo, string $table, string $col): bool
{
    static $cache = [];
    $key = $table . '.' . $col;
    if (isset($cache[$key])) return $cache[$key];

    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$col]);
    $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

    return $cache[$key];
}

/* ================= CSRF ================= */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf'];

/* ================= BUILD SELECT (SAFE) ================= */
$cols = ['user_id', 'name', 'email'];

if (colExists($pdo, 'users', 'phone'))      $cols[] = 'phone';
if (colExists($pdo, 'users', 'address'))    $cols[] = 'address';
if (colExists($pdo, 'users', 'avatar_url')) $cols[] = 'avatar_url';

$select = implode(', ', array_map(fn($c) => "`$c`", $cols));

/* ================= FETCH USER ================= */
try {
    $stmt = $pdo->prepare("SELECT $select FROM `users` WHERE `user_id` = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('[profile_contract] ' . $e->getMessage());
    $user = [];
}

/* ================= NORMALIZE OUTPUT ================= */
$user['user_id']    = (int)($user['user_id'] ?? $userId);
$user['name']       = (string)($user['name'] ?? '');
$user['email']      = (string)($user['email'] ?? '');
$user['phone']      = (string)($user['phone'] ?? '');
$user['address']    = (string)($user['address'] ?? '');
$user['avatar_url'] = (string)($user['avatar_url'] ?? '');

/* ================= UPDATE SESSION (Navbar uses session) ================= */
if ($user['name'] !== '')  $_SESSION['name'] = $user['name'];
if ($user['email'] !== '') $_SESSION['email'] = $user['email'];

$_SESSION['phone'] = $user['phone'];
$_SESSION['address'] = $user['address'];

// Append cache-busting query param to avatar URL so browsers show updated image
if ($user['avatar_url'] !== '') {
    $rel = ltrim($user['avatar_url'], '/');
    $local = __DIR__ . '/../../' . $rel;
    if (is_file($local)) {
        $sep = strpos($user['avatar_url'], '?') === false ? '?' : '&';
        $user['avatar_url'] .= $sep . 'v=' . @filemtime($local);
    }

    $_SESSION['avatar_url'] = $user['avatar_url'];
}
/* ================= DONE ================= */