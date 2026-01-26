<?php

declare(strict_types=1);

function login_set_session_and_cookie(PDO $conn, array $user): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    //prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role']    = $user['role'] ?? 'user';
    $_SESSION['email']   = $user['email'] ?? '';
    $_SESSION['name']    = $user['name'] ?? '';

    //auth token for "remember me" style login
    $auth_token = bin2hex(random_bytes(32));
    $_SESSION['auth_token'] = $auth_token;

    //cookie options (better than old parameters)
    setcookie('auth_token', $auth_token, [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => false,     // set true when using https
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    try {
        $stmt = $conn->prepare("UPDATE users SET auth_token = ? WHERE user_id = ?");
        $stmt->execute([$auth_token, $user['user_id']]);
    } catch (Throwable $e) {
        error_log('[auth_token update failed] ' . $e->getMessage());
    }
}

function redirect_by_role(string $role): void
{
    //absolute paths (works from any file location)
    switch ($role) {
        case 'admin':
            header('Location: /E-commerce-shoes/admin/dashboard.php');
            break;

        case 'staff':
            header('Location: /E-commerce-shoes/pos/staff_dashboard.php');
            break;

        default:
            header('Location: /E-commerce-shoes/view/index.php');
    }
    exit;
}
