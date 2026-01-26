<?php

declare(strict_types=1);

function ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function login_set_session_and_cookie(PDO $conn, array $user): void
{
    ensure_session();

    // Prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role']    = $user['role'] ?? 'customer';
    $_SESSION['email']   = $user['email'] ?? '';
    $_SESSION['name']    = $user['name'] ?? '';

    // Create auth token for "remember me" style login
    $auth_token = bin2hex(random_bytes(32));
    $_SESSION['auth_token'] = $auth_token;

    // Cookie options (set secure=true when using https)
    setcookie('auth_token', $auth_token, [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    try {
        $stmt = $conn->prepare("UPDATE users SET auth_token = ? WHERE user_id = ?");
        $stmt->execute([$auth_token, $user['user_id']]);
    } catch (Throwable $e) {
        error_log('[helpers] auth_token update failed: ' . $e->getMessage());
    }
}

function restore_login_from_cookie(PDO $conn): void
{
    ensure_session();

    if (!empty($_SESSION['user_id'])) {
        return; // already logged in
    }

    $token = (string)($_COOKIE['auth_token'] ?? '');
    if ($token === '') return;

    try {
        $stmt = $conn->prepare(
            "SELECT user_id, name, email, role
             FROM users
             WHERE auth_token = ?
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role']    = $user['role'] ?? 'customer';
            $_SESSION['email']   = $user['email'] ?? '';
            $_SESSION['name']    = $user['name'] ?? '';
        }
    } catch (Throwable $e) {
        error_log('[helpers] restore_login_from_cookie failed: ' . $e->getMessage());
    }
}

function redirect_by_role(string $role): void
{
    $role = strtolower(trim($role));

    switch ($role) {
        case 'admin':
            header('Location: /E-commerce-shoes/admin/dashboard.php');
            break;

        case 'staff':
            header('Location: /E-commerce-shoes/pos/staff_dashboard.php');
            break;

        case 'customer':
        case 'user':
        default:
            header('Location: /E-commerce-shoes/view/index.php');
            break;
    }
    exit;
}

function require_role(string ...$allowedRoles): void
{
    ensure_session();

    $role = strtolower((string)($_SESSION['role'] ?? ''));
    $allowedRoles = array_map(fn($r) => strtolower(trim($r)), $allowedRoles);

    if ($role === '' || !in_array($role, $allowedRoles, true)) {
        header('Location: /E-commerce-shoes/auth/login.php');
        exit;
    }
}

function logout_user(PDO $conn): void
{
    ensure_session();

    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        try {
            $stmt = $conn->prepare("UPDATE users SET auth_token = NULL WHERE user_id = ?");
            $stmt->execute([$userId]);
        } catch (Throwable $e) {
            // ignore
        }
    }

    $_SESSION = [];

    setcookie('auth_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'] ?: '/',
            'domain'   => $params['domain'] ?? '',
            'secure'   => (bool)($params['secure'] ?? false),
            'httponly' => (bool)($params['httponly'] ?? true),
            'samesite' => 'Lax',
        ]);
    }

    session_destroy();
}
