<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   SESSION + JSON
===================================================== */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

/* =====================================================
   PDO
===================================================== */
$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection missing']);
    exit;
}

/* =====================================================
   AUTH
===================================================== */
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

/* =====================================================
   HELPERS
===================================================== */
function respond(bool $success, string $message = '', int $code = 200, array $data = []): void
{
    http_response_code($code);
    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $data),
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

function req(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/* =====================================================
   COLUMN CACHE
===================================================== */
$__colCache = [];

function colExists(string $col): bool
{
    global $pdo, $__colCache;

    if (isset($__colCache[$col])) return $__colCache[$col];

    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `users` LIKE ?");
        $st->execute([$col]);
        return $__colCache[$col] = (bool)$st->fetch();
    } catch (Throwable) {
        return $__colCache[$col] = false;
    }
}

/* =====================================================
   VALIDATORS
===================================================== */
function validRole(string $role): bool
{
    return in_array($role, ['admin', 'staff', 'customer'], true);
}

function normalizeStatus(string $raw): string
{
    $raw = strtolower(trim($raw));
    return match (true) {
        in_array($raw, ['1', 'true', 'yes', 'y', 'active', 'enabled'], true)   => 'active',
        in_array($raw, ['0', 'false', 'no', 'n', 'inactive', 'disabled'], true) => 'inactive',
        default => '',
    };
}

/* =====================================================
   UPLOAD HELPERS
===================================================== */
function storeAvatar(array $file, int $userId): ?string
{
    $maxBytes = 2 * 1024 * 1024; // 2MB
    $allowed = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/gif' => 'gif',
    ];

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
    if (!isset($file['size']) || $file['size'] > $maxBytes) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) return null;

    $ext = $allowed[$mime];

    $uploadDir = __DIR__ . '/../../../assets/Images/avatars';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    $filename = sprintf('avatar_%d_%d.%s', $userId, time(), $ext);
    $dst = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dst)) return null;

    return '/MyBrand_Ecommerce/assets/Images/avatars/' . $filename;
}

function deleteAvatarByUrl(string $url): void
{
    $marker = '/assets/Images/avatars/';
    $pos = strpos($url, $marker);
    if ($pos === false) return;
    $basename = substr($url, $pos + strlen($marker));
    if ($basename === '') return;
    $filePath = __DIR__ . '/../../../assets/Images/avatars/' . $basename;
    if (is_file($filePath)) @unlink($filePath);
}

/* =====================================================
   ACTION ROUTER
===================================================== */
$action = (string) req('action', '');

try {

    /* =================================================
       GET USER
    ================================================== */
    if ($action === 'get_user') {

        $id = (int) req('id');
        if ($id <= 0) respond(false, 'Invalid user ID', 400);

        $cols = ['user_id', 'name', 'email', 'role', 'created_at'];
        foreach (['phone', 'status', 'avatar_url', 'last_login'] as $c) {
            if (colExists($c)) $cols[] = $c;
        }

        $sql = "SELECT " . implode(', ', array_map(fn($c) => "`$c`", $cols)) . "
                FROM `users` WHERE `user_id` = ? LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);

        $user = $st->fetch(PDO::FETCH_ASSOC);
        if (!$user) respond(false, 'User not found', 404);

        respond(true, 'OK', 200, ['user' => $user]);
    }

    /* =================================================
       CREATE USER
    ================================================== */
    if ($action === 'create') {

        $name     = trim((string) req('name'));
        $email    = trim((string) req('email'));
        $phone    = trim((string) req('phone'));
        $password = (string) req('password');
        $role     = (string) req('role', 'customer');

        if ($name === '' || $email === '' || strlen($password) < 6) {
            respond(false, 'Invalid input', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(false, 'Invalid email', 400);
        }

        if (!validRole($role)) $role = 'customer';

        $st = $pdo->prepare("SELECT 1 FROM `users` WHERE `email` = ? LIMIT 1");
        $st->execute([$email]);
        if ($st->fetchColumn()) respond(false, 'Email already exists', 400);

        $fields = ['name', 'email', 'password', 'role'];
        $values = [$name, $email, password_hash($password, PASSWORD_DEFAULT), $role];

        if (colExists('phone')) {
            $fields[] = 'phone';
            $values[] = ($phone !== '' ? $phone : null);
        }

        if (colExists('status')) {
            $fields[] = 'status';
            $values[] = normalizeStatus((string)req('status', 'active')) ?: 'active';
        }

        $placeholders = rtrim(str_repeat('?,', count($values)), ',');
        $sql = "INSERT INTO `users` (`" . implode('`,`', $fields) . "`, `created_at`)
                VALUES ($placeholders, NOW())";
        $pdo->prepare($sql)->execute($values);

        $newId = (int)$pdo->lastInsertId();

        // process avatar upload if present
        if (!empty($_FILES['avatar']) && is_array($_FILES['avatar'])) {
            $url = storeAvatar($_FILES['avatar'], $newId);
            if ($url !== null && colExists('avatar_url')) {
                $pdo->prepare("UPDATE `users` SET `avatar_url` = ? WHERE `user_id` = ?")->execute([$url, $newId]);
            }
        }

        respond(true, 'User created successfully', 200, [
            'user_id' => $newId
        ]);
    }

    /* =================================================
       UPDATE USER
    ================================================== */
    if ($action === 'update') {

        $id    = (int) req('user_id');
        $name  = trim((string) req('name'));
        $email = trim((string) req('email'));
        $phone = trim((string) req('phone'));
        $role  = (string) req('role', 'customer');
        $pass  = (string) req('password');

        if ($id <= 0 || $name === '' || $email === '') {
            respond(false, 'Invalid input', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(false, 'Invalid email', 400);
        }

        if (!validRole($role)) $role = 'customer';

        $st = $pdo->prepare("SELECT 1 FROM `users` WHERE `email` = ? AND `user_id` != ?");
        $st->execute([$email, $id]);
        if ($st->fetchColumn()) respond(false, 'Email already exists', 400);

        $set = ['`name` = ?', '`email` = ?', '`role` = ?'];
        $vals = [$name, $email, $role];

        if (colExists('phone')) {
            $set[] = '`phone` = ?';
            $vals[] = ($phone !== '' ? $phone : null);
        }

        if (colExists('status')) {
            $status = normalizeStatus((string)req('status')) ?: 'active';
            if ($id === (int)$_SESSION['user_id'] && $status !== 'active') {
                respond(false, 'Cannot change your own status', 400);
            }
            $set[] = '`status` = ?';
            $vals[] = $status;
        }

        if ($pass !== '') {
            if (strlen($pass) < 6) respond(false, 'Password too short', 400);
            $set[] = '`password` = ?';
            $vals[] = password_hash($pass, PASSWORD_DEFAULT);
        }

        $vals[] = $id;

        $sql = "UPDATE `users` SET " . implode(', ', $set) . " WHERE `user_id` = ?";
        $pdo->prepare($sql)->execute($vals);

        // handle avatar upload (replace existing)
        $old = (string) req('old_avatar', '');
        if (!empty($_FILES['avatar']) && is_array($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $url = storeAvatar($_FILES['avatar'], $id);
            if ($url !== null && colExists('avatar_url')) {
                $pdo->prepare("UPDATE `users` SET `avatar_url` = ? WHERE `user_id` = ?")->execute([$url, $id]);
                if ($old !== '' && $old !== '__REMOVE__') deleteAvatarByUrl($old);
            }
        } else {
            // if frontend requested removal explicitly, clear avatar_url and delete file
            if ($old === '__REMOVE__' && colExists('avatar_url')) {
                // delete existing file if any
                $st = $pdo->prepare("SELECT `avatar_url` FROM `users` WHERE `user_id` = ? LIMIT 1");
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if (!empty($row['avatar_url'])) deleteAvatarByUrl($row['avatar_url']);
                $pdo->prepare("UPDATE `users` SET `avatar_url` = '' WHERE `user_id` = ?")->execute([$id]);
            }
        }

        respond(true, 'User updated successfully');
    }

    /* =================================================
       DELETE USER
    ================================================== */
    if ($action === 'delete') {

        $id = (int) req('user_id');
        if ($id <= 0) respond(false, 'Invalid user ID', 400);
        if ($id === (int)$_SESSION['user_id']) respond(false, 'Cannot delete yourself', 400);

        $st = $pdo->prepare("SELECT `role` FROM `users` WHERE `user_id` = ?");
        $st->execute([$id]);
        $u = $st->fetch(PDO::FETCH_ASSOC);

        if (!$u) respond(false, 'User not found', 404);
        if ($u['role'] === 'admin') respond(false, 'Cannot delete admin user', 400);

        $pdo->prepare("DELETE FROM `users` WHERE `user_id` = ?")->execute([$id]);

        respond(true, 'User deleted');
    }

    /* =================================================
       UPDATE ROLE
    ================================================== */
    if ($action === 'update_role') {

        $id = (int) req('user_id');
        $role = (string) req('role');

        if ($id <= 0 || !validRole($role)) respond(false, 'Invalid input', 400);
        if ($id === (int)$_SESSION['user_id'] && $role !== 'admin') {
            respond(false, 'Cannot change your own admin role', 400);
        }

        $pdo->prepare("UPDATE `users` SET `role` = ? WHERE `user_id` = ?")
            ->execute([$role, $id]);

        respond(true, 'Role updated');
    }

    /* =================================================
       UPDATE STATUS
    ================================================== */
    if ($action === 'update_status') {

        if (!colExists('status')) respond(false, 'Status not supported', 400);

        $id = (int) req('user_id');
        $status = normalizeStatus((string) req('status'));

        if ($id <= 0 || $status === '') respond(false, 'Invalid input', 400);
        if ($id === (int)$_SESSION['user_id']) respond(false, 'Cannot change your own status', 400);

        $pdo->prepare("UPDATE `users` SET `status` = ? WHERE `user_id` = ?")
            ->execute([$status, $id]);

        respond(true, 'Status updated');
    }

    respond(false, 'Invalid action', 400);
} catch (Throwable $e) {
    error_log('[USERS_API] ' . $e->getMessage());
    respond(false, 'Internal server error', 500);
}
