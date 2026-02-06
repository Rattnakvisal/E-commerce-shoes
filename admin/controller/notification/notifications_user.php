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
function respond_user(bool $ok, string $msg = '', array $extra = []): never
{
    echo json_encode(['ok' => $ok, 'msg' => $msg] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

function input_user(): array
{
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? $json : ($_POST ?? []);
}

function inputId_user(): int
{
    return (int)($_POST['id'] ?? $_GET['id'] ?? 0);
}

/* -----------------------------------------
    Security
----------------------------------------- */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$userId = $_SESSION['user_id'] ?? null;
$role   = (string)($_SESSION['role'] ?? '');

if (!$userId) {
    respond_user(false, 'Unauthorized');
}

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    respond_user(false, 'Database connection missing');
}

/* -----------------------------------------
   Action
----------------------------------------- */
$action = (string)($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

try {
    switch ($action) {

        case 'fetch_unread_count': {
                if (!in_array($method, ['GET', 'POST'], true)) respond_user(false, 'Method not allowed');
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*)
                     FROM notifications
                     WHERE is_read = 0
                       AND user_id = :uid"
                );
                $stmt->execute([':uid' => $userId]);
                respond_user(true, 'OK', ['unread' => (int)$stmt->fetchColumn()]);
            }

        case 'fetch_latest': {
                if (!in_array($method, ['GET', 'POST'], true)) respond_user(false, 'Method not allowed');
                $stmt = $pdo->prepare(
                    "SELECT notification_id, title, message, is_read, created_at
                 FROM notifications
                 WHERE user_id = :uid
                 ORDER BY created_at DESC
                 LIMIT 10"
                );
                $stmt->execute([':uid' => $userId]);
                respond_user(true, 'OK', ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }

        case 'mark_all_read': {
                if ($method !== 'POST') respond_user(false, 'Method not allowed');
                $stmt = $pdo->prepare(
                    "UPDATE notifications
                 SET is_read = 1
                 WHERE is_read = 0
                   AND user_id = :uid"
                );
                $stmt->execute([':uid' => $userId]);
                respond_user(true, 'All marked read', ['affected' => $stmt->rowCount()]);
            }

        case 'mark_read': {
                if ($method !== 'POST') respond_user(false, 'Method not allowed');
                $id = inputId_user();
                if ($id <= 0) respond_user(false, 'Invalid id');

                $stmt = $pdo->prepare(
                    "UPDATE notifications
                 SET is_read = 1
                 WHERE notification_id = :id
                   AND user_id = :uid
                 LIMIT 1"
                );
                $stmt->execute([':id' => $id, ':uid' => $userId]);
                respond_user(true, 'Marked read', ['affected' => $stmt->rowCount()]);
            }

        case 'delete': {
                if ($method !== 'POST') respond_user(false, 'Method not allowed');
                $id = inputId_user();
                if ($id <= 0) respond_user(false, 'Invalid id');

                $stmt = $pdo->prepare(
                    "DELETE FROM notifications
                 WHERE notification_id = :id
                   AND user_id = :uid
                 LIMIT 1"
                );
                $stmt->execute([':id' => $id, ':uid' => $userId]);
                respond_user(true, 'Deleted', ['affected' => $stmt->rowCount()]);
            }

        case 'delete_all': {
                if ($method !== 'POST') respond_user(false, 'Method not allowed');
                $stmt = $pdo->prepare(
                    "DELETE FROM notifications
                 WHERE user_id = :uid"
                );
                $stmt->execute([':uid' => $userId]);
                respond_user(true, 'All deleted', ['affected' => $stmt->rowCount()]);
            }

        default:
            respond_user(false, 'Invalid action');
    }
} catch (Throwable $e) {
    error_log('[notifications_user] ' . $e->getMessage());
    respond_user(false, 'Server error');
}
