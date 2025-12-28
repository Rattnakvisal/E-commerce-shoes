<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -------------------------------------------------
 | Helpers
 ------------------------------------------------- */
function respond(bool $ok, array $data = []): void
{
    echo json_encode(['ok' => $ok] + $data);
    exit;
}

function inputId(): int
{
    return (int)($_POST['id'] ?? $_GET['id'] ?? 0);
}

$userId = $_SESSION['user_id'] ?? null;
$action = $_GET['action'] ?? '';

if ($userId === null) {
    respond(false, ['error' => 'Unauthorized']);
}

/* -------------------------------------------------
 | Actions
 ------------------------------------------------- */
try {

    switch ($action) {

        case 'fetch_unread_count':
            $stmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM notifications
                 WHERE is_read = 0
                   AND (user_id = :uid OR user_id IS NULL)"
            );
            $stmt->execute(['uid' => $userId]);

            respond(true, [
                'unread' => (int) $stmt->fetchColumn()
            ]);
            break;

        case 'fetch_latest':
            $stmt = $pdo->prepare(
                "SELECT notification_id, title, message, is_read, created_at
                 FROM notifications
                 WHERE (user_id = :uid OR user_id IS NULL)
                 ORDER BY created_at DESC
                 LIMIT 10"
            );
            $stmt->execute(['uid' => $userId]);

            respond(true, [
                'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        case 'mark_all_read':
            $stmt = $pdo->prepare(
                "UPDATE notifications
                 SET is_read = 1
                 WHERE is_read = 0
                   AND (user_id = :uid OR user_id IS NULL)"
            );
            $stmt->execute(['uid' => $userId]);

            respond(true);
            break;

        case 'mark_read':
            $id = inputId();
            if ($id <= 0) respond(false);

            $stmt = $pdo->prepare(
                "UPDATE notifications
                 SET is_read = 1
                 WHERE notification_id = :id
                   AND (user_id = :uid OR user_id IS NULL)"
            );
            $stmt->execute([
                'id'  => $id,
                'uid' => $userId
            ]);

            respond(true);
            break;

        case 'create':
            $input = $_POST ?: json_decode(file_get_contents('php://input'), true) ?: [];

            $title   = trim((string)($input['title'] ?? ''));
            $message = trim((string)($input['message'] ?? ''));

            if ($title === '' || $message === '') {
                respond(false);
            }

            $allowedTypes = ['order', 'payment', 'inventory', 'shipping', 'system'];
            $type = in_array($input['type'] ?? '', $allowedTypes, true)
                ? $input['type']
                : 'system';

            $targetUser = isset($input['user_id']) && is_numeric($input['user_id'])
                ? (int)$input['user_id']
                : null;

            $referenceId = isset($input['reference_id']) && is_numeric($input['reference_id'])
                ? (int)$input['reference_id']
                : null;

            $stmt = $pdo->prepare(
                "INSERT INTO notifications
                    (user_id, title, message, type, reference_id, is_read, created_at)
                 VALUES
                    (:uid, :title, :message, :type, :ref, 0, NOW())"
            );

            $stmt->execute([
                'uid'     => $targetUser,
                'title'   => $title,
                'message' => $message,
                'type'    => $type,
                'ref'     => $referenceId
            ]);

            respond(true, [
                'id' => (int) $pdo->lastInsertId()
            ]);
            break;

        case 'delete':
            $id = inputId();
            if ($id <= 0) respond(false);

            $stmt = $pdo->prepare(
                "DELETE FROM notifications
                 WHERE notification_id = :id
                   AND (user_id = :uid OR user_id IS NULL)"
            );
            $stmt->execute([
                'id'  => $id,
                'uid' => $userId
            ]);

            respond(true);
            break;

        case 'delete_all':
            $stmt = $pdo->prepare(
                "DELETE FROM notifications
                 WHERE (user_id = :uid OR user_id IS NULL)"
            );
            $stmt->execute(['uid' => $userId]);

            respond(true);
            break;

        default:
            respond(false, ['error' => 'Invalid action']);
    }
} catch (Throwable $e) {
    respond(false, ['error' => 'Server error']);
}
