<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../config/conn.php';

// simple helper to redirect to login page in Log/
function redirect_login(array $qs = [])
{
    $query = $qs ? ('?' . http_build_query($qs)) : '';
    header('Location: Log/login.php' . $query);
    exit;
}

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    redirect_login(['verify' => 0]);
}

try {
    $tokenHash = hash('sha256', $token);

    $stmt = $conn->prepare(
        "SELECT ev.user_id, ev.expires_at, u.email, u.email_verified
         FROM email_verifications ev
         JOIN users u ON u.user_id = ev.user_id
         WHERE ev.token_hash = ?
         LIMIT 1"
    );

    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        redirect_login(['verify' => 0]);
    }

    if (!empty($row['email_verified'])) {
        // already verified
        redirect_login(['verify' => 1, 'email' => $row['email']]);
    }

    if (strtotime((string)$row['expires_at']) < time()) {
        // expired
        // remove expired token
        $conn->prepare('DELETE FROM email_verifications WHERE token_hash = ?')->execute([$tokenHash]);
        redirect_login(['verify' => 0, 'email' => $row['email']]);
    }

    // mark user verified and remove token
    $conn->beginTransaction();
    $conn->prepare('UPDATE users SET email_verified = 1 WHERE user_id = ?')
        ->execute([(int)$row['user_id']]);
    $conn->prepare('DELETE FROM email_verifications WHERE user_id = ?')
        ->execute([(int)$row['user_id']]);
    $conn->commit();

    redirect_login(['verify' => 1, 'email' => $row['email']]);
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('[verify-email] ' . $e->getMessage());
    redirect_login(['verify' => 0]);
}
