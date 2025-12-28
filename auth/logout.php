<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    try {
        require_once __DIR__ . '/../config/conn.php';
        $upd = $pdo->prepare('UPDATE users SET auth_token = NULL WHERE user_id = ?');
        $upd->execute([$userId]);
    } catch (Exception $e) {
        // ignore
    }
}

// Clear session data
$_SESSION = [];

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$httponly = true;
$cookieParams = session_get_cookie_params();
$pathsToTry = ['/', $cookieParams['path'] ?? '/', '/E-commerce-shoes'];

foreach ($_COOKIE as $cName => $cVal) {
    $lower = strtolower($cName);
    if (stripos($lower, 'token') !== false || stripos($lower, 'auth') !== false || stripos($lower, 'remember') !== false) {
        // try clearing with session params and common paths
        foreach ($pathsToTry as $path) {
            @setcookie($cName, '', time() - 42000, $path, $cookieParams['domain'] ?? '', $secure, $httponly);
            @setcookie($cName, '', time() - 42000, $path, '', $secure, $httponly);
        }
        // also remove from PHP superglobal
        unset($_COOKIE[$cName]);
    }
}

// Clear session cookie if set
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: login.php?loggedout=1');
exit;
