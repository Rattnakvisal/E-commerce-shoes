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
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$userId = $_SESSION['user_id'] ?? null;
$role   = (string)($_SESSION['role'] ?? '');

if (!$userId) {
    respond(false, 'Unauthorized');
}

// If you want ADMIN ONLY, uncomment this:

// Enforce admin-only access: only users with an admin role may use this API
$isAdmin = in_array(strtolower($role), ['admin', 'administrator'], true);
if (!$isAdmin) {
    respond(false, 'Forbidden');
}

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
                // allow GET or POST for read-only actions
                if (!in_array($method, ['GET', 'POST'], true)) respond(false, 'Method not allowed');
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*)
                                            FROM notifications
                                            WHERE is_read = 0"
                );
                $stmt->execute();
                respond(true, 'OK', ['unread' => (int)$stmt->fetchColumn()]);
            }

        case 'fetch_latest': {
                // allow GET or POST for read-only actions
                if (!in_array($method, ['GET', 'POST'], true)) respond(false, 'Method not allowed');
                $stmt = $pdo->prepare(
                    "SELECT notification_id, title, message, is_read, created_at
                 FROM notifications
                 ORDER BY created_at DESC
                 LIMIT 10"
                );
                $stmt->execute();
                respond(true, 'OK', ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }

        case 'mark_all_read': {
                if ($method !== 'POST') respond(false, 'Method not allowed');
                $stmt = $pdo->prepare(
                    "UPDATE notifications
                                            SET is_read = 1
                                            WHERE is_read = 0"
                );
                $stmt->execute();
                respond(true, 'All marked read', ['affected' => $stmt->rowCount()]);
            }

        case 'mark_read': {
                if ($method !== 'POST') respond(false, 'Method not allowed');
                $id = inputId();
                if ($id <= 0) respond(false, 'Invalid id');

                $stmt = $pdo->prepare(
                    "UPDATE notifications
                                 SET is_read = 1
                                 WHERE notification_id = :id
                                 LIMIT 1"
                );
                $stmt->execute([':id' => $id]);
                respond(true, 'Marked read', ['affected' => $stmt->rowCount()]);
            }

        case 'delete': {
                if ($method !== 'POST') respond(false, 'Method not allowed');
                $id = inputId();
                if ($id <= 0) respond(false, 'Invalid id');

                $stmt = $pdo->prepare(
                    "DELETE FROM notifications
                                 WHERE notification_id = :id
                                 LIMIT 1"
                );
                $stmt->execute([':id' => $id]);
                respond(true, 'Deleted', ['affected' => $stmt->rowCount()]);
            }

        case 'delete_all': {
                if ($method !== 'POST') respond(false, 'Method not allowed');
                $stmt = $pdo->prepare(
                    "DELETE FROM notifications"
                );
                $stmt->execute();
                respond(true, 'All deleted', ['affected' => $stmt->rowCount()]);
            }

        default:
            respond(false, 'Invalid action');
    }
} catch (Throwable $e) {
    error_log('[notification_api] ' . $e->getMessage());
    respond(false, 'Server error');
}
