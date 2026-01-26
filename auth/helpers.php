<?php

declare(strict_types=1);

function login_set_session_and_cookie(PDO $conn, array $user): void
{
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role']    = $user['role'] ?? 'user';
    $_SESSION['email']   = $user['email'] ?? '';
    $_SESSION['name']    = $user['name'] ?? '';

    // create auth token cookie (optional but matches your code)
    $auth_token = bin2hex(random_bytes(32));
    $_SESSION['auth_token'] = $auth_token;

    setcookie('auth_token', $auth_token, time() + 60 * 60 * 24 * 30, '/', '', false, true);

    try {
        $stmt = $conn->prepare("UPDATE users SET auth_token = ? WHERE user_id = ?");
        $stmt->execute([$auth_token, $user['user_id']]);
    } catch (Throwable $e) {
        // ignore
    }
}

function redirect_by_role(string $role): void
{
    switch ($role) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'staff':
            header('Location: ../pos/staff_dashboard.php');
            break;
        default:
            header('Location: ../view/index.php');
    }
    exit;
}
