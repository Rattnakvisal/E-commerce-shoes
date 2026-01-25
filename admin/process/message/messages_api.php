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
        header('Location: /E-commerce-shoes/view/contact.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash']['error'] = '2';
        header('Location: /E-commerce-shoes/view/contact.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (NAME, email, message) VALUES (:name, :email, :message)");
        $stmt->execute([':name' => $name, ':email' => $email, ':message' => $message]);

        $_SESSION['flash']['success'] = 1;
        unset($_SESSION['flash']['old']);
        header('Location: /E-commerce-shoes/view/contact.php');
        exit;
    } catch (Throwable $e) {
        error_log('[messages_api][submit] ' . $e->getMessage());
        $_SESSION['flash']['error'] = '3';
        header('Location: /E-commerce-shoes/view/contact.php');
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

$table = 'contact_messages';
$hasIsRead = colExists($pdo, $table, 'is_read');
$hasReadAt = colExists($pdo, $table, 'read_at');

try {
    switch ($action) {

        // Optional: for badge refresh
        case 'unread_count': {
                if (!$hasIsRead) {
                    // no is_read column => can't calculate unread
                    respond(true, 'OK', ['count' => 0]);
                }

                $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
                $count = (int)$stmt->fetchColumn();
                respond(true, 'OK', ['count' => $count]);
            }

        case 'mark_all_read': {
                if (!$hasIsRead) {
                    // no is_read column => treat as OK (dropdown can just clear UI)
                    respond(true, 'No is_read column; skipped', ['affected' => 0]);
                }

                $sql = "UPDATE contact_messages SET is_read = 1";
                if ($hasReadAt) $sql .= ", read_at = NOW()";
                $sql .= " WHERE is_read = 0";

                $stmt = $pdo->prepare($sql);
                $stmt->execute();

                respond(true, 'All marked read', ['affected' => $stmt->rowCount()]);
            }

        case 'mark_read': {
                if ($id <= 0) respond(false, 'Invalid id');

                if (!$hasIsRead) {
                    respond(true, 'No is_read column; skipped', ['affected' => 0]);
                }

                $sql = "UPDATE contact_messages SET is_read = 1";
                if ($hasReadAt) $sql .= ", read_at = NOW()";
                $sql .= " WHERE message_id = :id LIMIT 1";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);

                respond(true, 'Marked read', ['affected' => $stmt->rowCount()]);
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
