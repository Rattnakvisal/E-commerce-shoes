<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userLogged = false;
$userName   = '';
$initials   = '';

if (!empty($_SESSION['user_id'])) {

    require_once __DIR__ . '/../config/conn.php'; // PDO MySQL

    $stmt = $pdo->prepare("
        SELECT full_name 
        FROM users 
        WHERE user_id = :id
        LIMIT 1
    ");
    $stmt->execute([
        ':id' => $_SESSION['user_id']
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userLogged = true;
        $userName   = $user['full_name'] ?? 'User';

        $parts = explode(' ', trim($userName));
        $initials = strtoupper(
            substr($parts[0], 0, 1) .
            (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
        );
    }
}
