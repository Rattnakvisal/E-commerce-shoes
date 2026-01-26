<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userLogged = false;
$userName   = '';
$initials   = '';

if (!empty($_SESSION['user_id'])) {

    $userLogged = true;

    // Prefer session name (set from normal login OR Google login)
    $userName = (string)($_SESSION['name'] ?? '');

    // Fallback: fetch from DB if missing
    if ($userName === '') {

        require_once __DIR__ . '/../config/conn.php';

        $stmt = $conn->prepare(
            "SELECT name, full_name
             FROM users
             WHERE user_id = ?
             LIMIT 1"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $userName = (string)($user['name'] ?? $user['full_name'] ?? 'User');

        // update session so next time no DB query
        $_SESSION['name'] = $userName;
    }

    // Build initials (works for 1 or many words)
    $clean = trim(preg_replace('/\s+/', ' ', $userName));
    $parts = $clean === '' ? [] : explode(' ', $clean);

    $first = $parts[0] ?? '';
    $second = $parts[1] ?? '';

    $initials = strtoupper(
        mb_substr($first, 0, 1, 'UTF-8') .
            mb_substr($second, 0, 1, 'UTF-8')
    );

    // If only 1 word name => 1 initial
    if ($second === '') {
        $initials = strtoupper(mb_substr($first, 0, 1, 'UTF-8'));
    }

    if ($initials === '') {
        $initials = 'U';
    }
}
