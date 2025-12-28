<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userLogged = false;
$userName   = '';
$initials   = '';

if (!empty($_SESSION['user_id'])) {

    $userLogged = true;

    // Prefer session name (already set at login)
    $userName = $_SESSION['name'] ?? '';

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
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $userName = $user['name']
            ?? $user['full_name']
            ?? 'User';
    }

    /* Build initials */
    $parts = preg_split('/\s+/', trim($userName));
    $initials = strtoupper(
        substr($parts[0] ?? '', 0, 1) .
            substr($parts[1] ?? '', 0, 1)
    );
}
