<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function respond(bool $ok, string $msg = '', array $extra = []): never
{
    echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(false, 'Method not allowed');
}

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    respond(false, 'Database connection missing');
}

$action = (string)($_GET['action'] ?? '');
$id     = (int)($_GET['id'] ?? 0);

// Public contact form submission handling (no action or action=submit)
if (($action === '' || $action === 'submit')
    && ($_SERVER['REQUEST_METHOD'] === 'POST')
    && (isset($_POST['name']) || isset($_POST['email']) || isset($_POST['message']))
) {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    // preserve old values for redisplay
    $_SESSION['flash']['old'] = ['name' => $name, 'email' => $email, 'message' => $message];

    if ($name === '' || $email === '' || $message === '') {
        $_SESSION['flash']['error'] = '1';
        header('Location: /E-commerce-shoes/view/content/contact.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash']['error'] = '2';
        header('Location: /E-commerce-shoes/view/content/contact.php');
        exit;
    }

    try {
        // Insert contact message
        $stmt = $pdo->prepare("INSERT INTO contact_messages (NAME, email, message) VALUES (:name, :email, :message)");
        $stmt->execute([':name' => $name, ':email' => $email, ':message' => $message]);
        $messageId = (int)$pdo->lastInsertId();

        // Create notification for all admin users
        $adminStmt = $pdo->prepare("SELECT user_id FROM users WHERE ROLE = 'admin' AND STATUS = 'active'");
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($admins)) {
            $notifStmt = $pdo->prepare(
                "INSERT INTO notifications (user_id, title, message, TYPE, reference_id) 
                 VALUES (:user_id, :title, :message, :type, :ref_id)"
            );

            foreach ($admins as $adminId) {
                $notifStmt->execute([
                    ':user_id' => $adminId,
                    ':title' => "New Contact Message from $name",
                    ':message' => "New message from $name ({$email}): " . substr($message, 0, 100),
                    ':type' => 'system',
                    ':ref_id' => $messageId
                ]);
            }
        }

        $_SESSION['flash']['success'] = 1;
        unset($_SESSION['flash']['old']);
        header('Location: /E-commerce-shoes/view/content/contact.php');
        exit;
    } catch (Throwable $e) {
        error_log('[messages_api][submit] ' . $e->getMessage());
        $_SESSION['flash']['error'] = '3';
        header('Location: /E-commerce-shoes/view/content/contact.php');
        exit;
    }
}

// Allow unauthenticated access for read-only badge/count requests
if ($action !== 'unread_count') {
    if (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
        respond(false, 'Unauthorized');
    }
}

function colExists(PDO $pdo, string $table, string $col): bool
{
    static $cache = [];
    $key = $table . '.' . $col;
    if (array_key_exists($key, $cache)) return $cache[$key];

    $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :t
              AND COLUMN_NAME = :c";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':t' => $table, ':c' => $col]);

    return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
}

try {
    switch ($action) {

        // Optional: for badge refresh (contact messages don't have is_read column)
        case 'unread_count': {
                respond(true, 'OK', ['count' => 0]);
            }

        case 'mark_all_read': {
                // No-op: contact messages table doesn't support read status
                respond(true, 'OK', ['affected' => 0]);
            }

        case 'mark_read': {
                if ($id <= 0) respond(false, 'Invalid id');

                // No-op: contact messages table doesn't support read status
                respond(true, 'OK', ['affected' => 0]);
            }

        case 'delete': {
                if ($id <= 0) respond(false, 'Invalid id');

                $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE message_id = :id LIMIT 1");
                $stmt->execute([':id' => $id]);

                respond(true, 'Deleted', ['affected' => $stmt->rowCount()]);
            }

        case 'delete_all': {
                $stmt = $pdo->prepare("DELETE FROM contact_messages");
                $stmt->execute();

                respond(true, 'All deleted', ['affected' => $stmt->rowCount()]);
            }

        default:
            respond(false, 'Unknown action');
    }
} catch (Throwable $e) {
    error_log('[messages_api] ' . $e->getMessage());
    respond(false, 'Server error');
}
