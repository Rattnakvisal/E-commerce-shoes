<?php
// Logout endpoint: destroy session and redirect to login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Capture user id before destroying session so we can clear DB token
$userId = $_SESSION['user_id'] ?? null;

// Attempt to clear persisted auth token in DB (ignore errors)
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

// Remove auth_token cookie if present
setcookie('auth_token', '', time() - 42000, '/', '', false, true);

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

// Destroy session
session_destroy();

// Redirect back to login with a flag
header('Location: login.php?loggedout=1');
exit;
