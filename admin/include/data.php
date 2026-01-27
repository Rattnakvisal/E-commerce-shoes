<?php

declare(strict_types=1);

/* ============================================================
   SESSION
============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_start();
    } else {
        error_log('[Navbar] session_start skipped: headers already sent');
    }
}

/* ============================================================
   HELPERS
============================================================ */
function avatarUrl(string $name, string $avatar = ''): string
{
    if ($avatar !== '') return $avatar;
    $initials = rawurlencode($name !== '' ? $name : 'Admin');
    return "https://ui-avatars.com/api/?name={$initials}&background=ffffff&color=111827&rounded=true&size=128";
}

/* ============================================================
   DB
============================================================ */
require_once __DIR__ . '/../../config/conn.php';

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    error_log('[Navbar] PDO connection not available');

    // Safe defaults so UI never breaks
    $userId = $_SESSION['user_id'] ?? null;
    $role = (string)($_SESSION['role'] ?? 'admin');
    $adminName = (string)($_SESSION['admin_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin');

    $currentRole  = $role;
    $admin_name   = $adminName;
    $admin_role   = ucfirst($role);
    $admin_avatar = avatarUrl($adminName, (string)($_SESSION['admin_avatar'] ?? $_SESSION['avatar'] ?? ''));

    $unreadCount = 0;
    $notifications = [];
    $messagesCount = 0;
    $contactMessages = [];

    return;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ============================================================
   SESSION DATA
============================================================ */
$userId    = $_SESSION['user_id'] ?? null;
$role      = (string)($_SESSION['role'] ?? 'admin');
$adminName = (string)($_SESSION['admin_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin');

$currentRole  = $role;
$admin_name   = $adminName;
$admin_role   = ucfirst($role);
$admin_avatar = avatarUrl($adminName, (string)($_SESSION['admin_avatar'] ?? $_SESSION['avatar'] ?? ''));

/* ============================================================
   NOTIFICATIONS
============================================================ */
$unreadCount = 0;
$notifications = [];

try {
    $sqlCount = "
                SELECT COUNT(*)
                FROM notifications n
                LEFT JOIN notification_reads nr ON nr.notification_id = n.notification_id AND nr.user_id = :uid
                WHERE (n.user_id = :uid OR n.user_id IS NULL)
                    AND (
                        (n.user_id = :uid AND n.is_read = 0)
                        OR (n.user_id IS NULL AND nr.notification_id IS NULL)
                    )
        ";
    $stmt = $pdo->prepare($sqlCount);
    $stmt->execute([':uid' => $userId]);
    $unreadCount = (int)$stmt->fetchColumn();

    $sqlList = "
                SELECT n.notification_id, n.title, n.message,
                CASE WHEN n.user_id IS NULL THEN IF(nr.notification_id IS NULL, 0, 1) ELSE n.is_read END AS is_read,
                n.created_at
                FROM notifications n
                LEFT JOIN notification_reads nr ON nr.notification_id = n.notification_id AND nr.user_id = :uid
                WHERE (n.user_id = :uid OR n.user_id IS NULL)
                ORDER BY n.created_at DESC
                LIMIT 10
        ";
    $stmt = $pdo->prepare($sqlList);
    $stmt->execute([':uid' => $userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[Navbar] Notifications query failed: ' . $e->getMessage());
    $unreadCount = 0;
    $notifications = [];
}

/* ============================================================
   CONTACT MESSAGES
============================================================ */
$messagesCount = 0;
$contactMessages = [];

try {
    $messagesCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM contact_messages WHERE is_read = 0"
    )->fetchColumn();

    $contactMessages = $pdo->query(
        "SELECT message_id, NAME, email, message, is_read, created_at
         FROM contact_messages
         ORDER BY created_at DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[Navbar] Contact messages query failed: ' . $e->getMessage());
    $messagesCount = 0;
    $contactMessages = [];
}
