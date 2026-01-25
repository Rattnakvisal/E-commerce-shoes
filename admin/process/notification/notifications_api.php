<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -----------------------------------------
   Helpers
----------------------------------------- */
function respond(bool $ok, string $msg = '', array $extra = []): never
{
    echo json_encode(['ok' => $ok, 'msg' => $msg] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

function input(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? $json : ($_POST ?? []);
}

function inputId(): int
{
    return (int)($_POST['id'] ?? $_GET['id'] ?? 0);
}

/* -----------------------------------------
   Security
----------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(false, 'Method not allowed');
}

$userId = $_SESSION['user_id'] ?? null;
$role   = (string)($_SESSION['role'] ?? '');

if (!$userId) {
    respond(false, 'Unauthorized');
}

// If you want ADMIN ONLY, uncomment this:
// if ($role !== 'admin') respond(false, 'Unauthorized');

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    respond(false, 'Database connection missing');
}

/* -----------------------------------------
   Action
----------------------------------------- */
$action = (string)($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

try {
    switch ($action) {

        case 'fetch_unread_count': {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*)
                 FROM notifications
                 WHERE is_read = 0
                   AND (user_id = :uid OR user_id IS NULL)"
                );
                $stmt->execute([':uid' => $userId]);
                respond(true, 'OK', ['unread' => (int)$stmt->fetchColumn()]);
            }

        case 'fetch_latest': {
                $stmt = $pdo->prepare(
                    "SELECT notification_id, title, message, is_read, created_at
                 FROM notifications
                 WHERE (user_id = :uid OR user_id IS NULL)
                 ORDER BY created_at DESC
                 LIMIT 10"
                );
                $stmt->execute([':uid' => $userId]);
                respond(true, 'OK', ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }

        case 'mark_all_read': {
                $stmt = $pdo->prepare(
                    "UPDATE notifications
                 SET is_read = 1
                 WHERE is_read = 0
                   AND (user_id = :uid OR user_id IS NULL)"
                );
                $stmt->execute([':uid' => $userId]);
                respond(true, 'All marked read', ['affected' => $stmt->rowCount()]);
            }

        case 'mark_read': {
                $id = inputId();
                if ($id <= 0) respond(false, 'Invalid id');

                $stmt = $pdo->prepare(
                    "UPDATE notifications
                 SET is_read = 1
                 WHERE notification_id = :id
                   AND (user_id = :uid OR user_id IS NULL)
                 LIMIT 1"
                );
                $stmt->execute([':id' => $id, ':uid' => $userId]);
                respond(true, 'Marked read', ['affected' => $stmt->rowCount()]);
            }

        case 'delete': {
                $id = inputId();
                if ($id <= 0) respond(false, 'Invalid id');

                $stmt = $pdo->prepare(
                    "DELETE FROM notifications
                 WHERE notification_id = :id
                   AND (user_id = :uid OR user_id IS NULL)
                 LIMIT 1"
                );
                $stmt->execute([':id' => $id, ':uid' => $userId]);
                respond(true, 'Deleted', ['affected' => $stmt->rowCount()]);
            }

        case 'delete_all': {
                $stmt = $pdo->prepare(
                    "DELETE FROM notifications
                 WHERE (user_id = :uid OR user_id IS NULL)"
                );
                $stmt->execute([':uid' => $userId]);
                respond(true, 'All deleted', ['affected' => $stmt->rowCount()]);
            }

        default:
            respond(false, 'Invalid action');
    }
} catch (Throwable $e) {
    error_log('[notification_api] ' . $e->getMessage());
    respond(false, 'Server error');
}
