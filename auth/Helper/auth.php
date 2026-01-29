<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userLogged = false;
$userName   = '';
$initials   = '';

if (empty($_SESSION['user_id']) && !empty($_COOKIE['auth_token'])) {

    require_once __DIR__ . '/../../config/conn.php';

    // Ensure PDO $conn
    if (!isset($conn) || !($conn instanceof PDO)) {
        if (isset($pdo) && $pdo instanceof PDO) $conn = $pdo;
    }

    if (isset($conn) && $conn instanceof PDO) {
        $stmt = $conn->prepare(
            "SELECT user_id, name, email, role
            FROM users
            WHERE auth_token = ?
            LIMIT 1"
        );
        $stmt->execute([(string)$_COOKIE['auth_token']]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($u) {
            $_SESSION['user_id'] = $u['user_id'];
            $_SESSION['name']    = $u['name'] ?? '';
            $_SESSION['email']   = $u['email'] ?? '';
            $_SESSION['role']    = $u['role'] ?? 'customer';
        }
    }
}

/**
 * Logged in?
 */
if (!empty($_SESSION['user_id'])) {
    $userLogged = true;

    $userName = (string)($_SESSION['name'] ?? '');
    if ($userName === '') {

        require_once __DIR__ . '/../../config/conn.php';

        // Ensure PDO $conn
        if (!isset($conn) || !($conn instanceof PDO)) {
            if (isset($pdo) && $pdo instanceof PDO) $conn = $pdo;
        }

        if (isset($conn) && $conn instanceof PDO) {

            $stmt = $conn->prepare(
                "SELECT name, full_name
                FROM users
                WHERE user_id = ?
                LIMIT 1"
            );
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $userName = (string)($user['name'] ?? $user['full_name'] ?? 'User');

            // Update session so next time no DB query
            $_SESSION['name'] = $userName;
        } else {
            $userName = 'User';
        }
    }

    // Build initials (1 or many words)
    $clean = trim((string)preg_replace('/\s+/', ' ', $userName));
    $parts = $clean === '' ? [] : explode(' ', $clean);

    $first  = $parts[0] ?? '';
    $second = $parts[1] ?? '';

    if ($first !== '' && $second !== '') {
        $initials = strtoupper(
            mb_substr($first, 0, 1, 'UTF-8') .
                mb_substr($second, 0, 1, 'UTF-8')
        );
    } elseif ($first !== '') {
        $initials = strtoupper(mb_substr($first, 0, 1, 'UTF-8'));
    } else {
        $initials = 'U';
    }
}
