<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;

/* ----------------------------
   DB: clear auth_token
---------------------------- */
if ($userId) {
    try {
        require_once __DIR__ . '/../../config/conn.php';

        // Support either $conn or $pdo
        if (!isset($conn) || !($conn instanceof PDO)) {
            if (isset($pdo) && $pdo instanceof PDO) {
                $conn = $pdo;
            }
        }

        if (isset($conn) && $conn instanceof PDO) {
            $upd = $conn->prepare("UPDATE users SET auth_token = NULL WHERE user_id = ?");
            $upd->execute([$userId]);
        }
    } catch (Throwable $e) {
        // ignore
    }
}

/* ----------------------------
   Clear session data
---------------------------- */
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    // clear session cookie
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'] ?: '/',
        'domain'   => $params['domain'] ?? '',
        'secure'   => (bool)($params['secure'] ?? false),
        'httponly' => (bool)($params['httponly'] ?? true),
        'samesite' => 'Lax',
    ]);
}

/* ----------------------------
   Clear auth_token cookie
---------------------------- */
setcookie('auth_token', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_destroy();

/* ----------------------------
   Redirect
---------------------------- */
header('Location: /E-commerce-shoes/auth/Log/login.php?loggedout=1');
exit;
