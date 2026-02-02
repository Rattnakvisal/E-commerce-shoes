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

function tableExists(PDO $pdo, string $table): bool
{
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];

    try {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
            LIMIT 1
        ");
        $stmt->execute([$table]);
        $cache[$table] = (bool)$stmt->fetchColumn();
        return $cache[$table];
    } catch (Throwable $e) {
        error_log("[Navbar] tableExists($table) failed: " . $e->getMessage());
        $cache[$table] = false;
        return false;
    }
}

function colExists(PDO $pdo, string $table, string $col): bool
{
    static $cache = [];
    $key = $table . '.' . $col;
    if (isset($cache[$key])) return $cache[$key];

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$col]);
        $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        return $cache[$key];
    } catch (Throwable $e) {
        error_log("[Navbar] colExists($key) failed: " . $e->getMessage());
        $cache[$key] = false;
        return false;
    }
}

/* ============================================================
   DB
============================================================ */
require_once __DIR__ . '/../../config/conn.php';

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    error_log('[Navbar] PDO connection not available');

    // Safe defaults so UI never breaks
    $userId = (int)($_SESSION['user_id'] ?? 0);
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
$userId    = (int)($_SESSION['user_id'] ?? 0);
$role      = (string)($_SESSION['role'] ?? 'admin');
$adminName = (string)($_SESSION['admin_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin');

$currentRole  = $role;
$admin_name   = $adminName;
$admin_role   = ucfirst($role);
$admin_avatar = avatarUrl($adminName, (string)($_SESSION['admin_avatar'] ?? $_SESSION['avatar'] ?? ''));

/* ============================================================
   NOTIFICATIONS (robust)
   - supports:
     1) notifications table only (with is_read)
     2) global notifications (user_id NULL) + notification_reads mapping per user
============================================================ */
$unreadCount = 0;
$notifications = [];

try {
    $hasNotifications = tableExists($pdo, 'notifications');
    if (!$hasNotifications) {
        // no table -> safe empty
        $unreadCount = 0;
        $notifications = [];
    } else {
        $hasReadsTable  = tableExists($pdo, 'notification_reads');
        $hasIsReadCol   = colExists($pdo, 'notifications', 'is_read');
        $hasUserIdCol   = colExists($pdo, 'notifications', 'user_id');
        $hasCreatedAt   = colExists($pdo, 'notifications', 'created_at');

        // We require user_id column to do per-user properly; if not exist, show latest only
        if (!$hasUserIdCol) {
            $unreadCount = 0;
            $notifications = $pdo->query("
                SELECT notification_id, title, message, " . ($hasIsReadCol ? "is_read" : "0 AS is_read") . ",
                       " . ($hasCreatedAt ? "created_at" : "NULL AS created_at") . "
                FROM notifications
                ORDER BY " . ($hasCreatedAt ? "created_at" : "notification_id") . " DESC
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // If user not logged-in, treat only global notifications as unread=0 (or show list)
            $uid = $userId;

            if ($uid <= 0) {
                // show latest global only
                $notifications = $pdo->query("
                    SELECT notification_id, title, message,
                           1 AS is_read,
                           " . ($hasCreatedAt ? "created_at" : "NULL AS created_at") . "
                    FROM notifications
                    WHERE user_id IS NULL
                    ORDER BY " . ($hasCreatedAt ? "created_at" : "notification_id") . " DESC
                    LIMIT 10
                ")->fetchAll(PDO::FETCH_ASSOC);
                $unreadCount = 0;
            } else {
                if ($hasReadsTable) {
                    // ✅ Best mode: global notifications tracked with notification_reads
                    $sqlCount = "
                        SELECT COUNT(*)
                        FROM notifications n
                        LEFT JOIN notification_reads nr
                          ON nr.notification_id = n.notification_id
                         AND nr.user_id = :uid
                        WHERE (n.user_id = :uid OR n.user_id IS NULL)
                          AND (
                                (n.user_id = :uid AND " . ($hasIsReadCol ? "n.is_read = 0" : "1=0") . ")
                                OR (n.user_id IS NULL AND nr.notification_id IS NULL)
                              )
                    ";
                    $stmt = $pdo->prepare($sqlCount);
                    $stmt->execute([':uid' => $uid]);
                    $unreadCount = (int)$stmt->fetchColumn();

                    $sqlList = "
                        SELECT
                            n.notification_id,
                            n.title,
                            n.message,
                            CASE
                              WHEN n.user_id IS NULL
                                THEN IF(nr.notification_id IS NULL, 0, 1)
                              ELSE " . ($hasIsReadCol ? "n.is_read" : "1") . "
                            END AS is_read,
                            " . ($hasCreatedAt ? "n.created_at" : "NULL AS created_at") . "
                        FROM notifications n
                        LEFT JOIN notification_reads nr
                          ON nr.notification_id = n.notification_id
                         AND nr.user_id = :uid
                        WHERE (n.user_id = :uid OR n.user_id IS NULL)
                        ORDER BY " . ($hasCreatedAt ? "n.created_at" : "n.notification_id") . " DESC
                        LIMIT 10
                    ";
                    $stmt = $pdo->prepare($sqlList);
                    $stmt->execute([':uid' => $uid]);
                    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    // ✅ Fallback: no notification_reads table
                    // user notifications unread = is_read=0, global treated as unread always (or count them)
                    $userUnread = 0;
                    if ($hasIsReadCol) {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
                        $stmt->execute([':uid' => $uid]);
                        $userUnread = (int)$stmt->fetchColumn();
                    }

                    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id IS NULL");
                    $globalCount = (int)$stmt->fetchColumn();

                    $unreadCount = $userUnread + $globalCount;

                    $stmt = $pdo->prepare("
                        SELECT notification_id, title, message,
                               " . ($hasIsReadCol ? "is_read" : "1 AS is_read") . ",
                               " . ($hasCreatedAt ? "created_at" : "NULL AS created_at") . "
                        FROM notifications
                        WHERE user_id = :uid OR user_id IS NULL
                        ORDER BY " . ($hasCreatedAt ? "created_at" : "notification_id") . " DESC
                        LIMIT 10
                    ");
                    $stmt->execute([':uid' => $uid]);
                    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }
    }
} catch (Throwable $e) {
    error_log('[Navbar] Notifications query failed: ' . $e->getMessage());
    $unreadCount = 0;
    $notifications = [];
}

/* ============================================================
   CONTACT MESSAGES (robust)
   - If contact_messages has is_read: count unread only
   - If no is_read: count last 7 days (your old behavior)
============================================================ */
$messagesCount = 0;
$contactMessages = [];

try {
    $hasContact = tableExists($pdo, 'contact_messages');
    if (!$hasContact) {
        $messagesCount = 0;
        $contactMessages = [];
    } else {
        $hasIsRead = colExists($pdo, 'contact_messages', 'is_read');
        $hasCreatedAt = colExists($pdo, 'contact_messages', 'created_at');

        if ($hasIsRead) {
            $messagesCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();

            $contactMessages = $pdo->query("
                SELECT message_id, NAME, email, message, " . ($hasCreatedAt ? "created_at" : "NULL AS created_at") . ", is_read
                FROM contact_messages
                ORDER BY " . ($hasCreatedAt ? "created_at" : "message_id") . " DESC
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            if ($hasCreatedAt) {
                $messagesCount = (int)$pdo->query(
                    "SELECT COUNT(*) FROM contact_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
                )->fetchColumn();
            } else {
                $messagesCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
            }

            $contactMessages = $pdo->query("
                SELECT message_id, NAME, email, message, " . ($hasCreatedAt ? "created_at" : "NULL AS created_at") . "
                FROM contact_messages
                ORDER BY " . ($hasCreatedAt ? "created_at" : "message_id") . " DESC
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Throwable $e) {
    error_log('[Navbar] Contact messages query failed: ' . $e->getMessage());
    $messagesCount = 0;
    $contactMessages = [];
}
