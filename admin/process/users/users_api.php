<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

/* ================= HELPERS ================= */
function respond(bool $success, string $message = '', int $code = 200, array $data = []): void
{
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

function post(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = '')
{
    return $_GET[$key] ?? $default;
}

/**
 * Cached column existence check (FAST)
 */
function columnExists(string $column): bool
{
    static $columns = null;
    global $pdo;

    if ($columns === null) {
        try {
            $columns = $pdo
                ->query("DESCRIBE users")
                ->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $e) {
            return false;
        }
    }
    return in_array($column, $columns, true);
}

$hasStatus = columnExists('status');
$action = get('action') ?: post('action');

/* ================= API ================= */
try {

    /* ========== GET USER ========== */
    if ($action === 'get_user') {
        $id = (int)get('id');
        if ($id <= 0) respond(false, 'Invalid user ID', 400);

        $cols = ['user_id', 'name', 'email', 'role'];
        if ($hasStatus) $cols[] = 'status';
        $cols[] = 'created_at';

        $stmt = $pdo->prepare("
            SELECT " . implode(', ', $cols) . "
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) respond(false, 'User not found', 404);
        respond(true, 'OK', 200, ['user' => $user]);
    }

    /* ========== CREATE USER ========== */
    if ($action === 'create') {
        $name     = trim(post('name'));
        $email    = trim(post('email'));
        $password = post('password');
        $role     = post('role', 'customer');
        $status   = strtolower(post('status', 'active'));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            respond(false, 'Invalid input', 400);
        }

        if (!in_array($role, ['admin', 'staff', 'customer'], true)) {
            $role = 'customer';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) respond(false, 'Email already exists', 400);

        if ($hasStatus) {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $status
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role
            ]);
        }

        respond(true, 'User created successfully');
    }

    /* ========== UPDATE USER ========== */
    if ($action === 'update') {
        $id       = (int)post('user_id');
        $name     = trim(post('name'));
        $email    = trim(post('email'));
        $password = post('password');
        $role     = post('role', 'customer');

        if ($id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(false, 'Invalid input', 400);
        }

        if (!in_array($role, ['admin', 'staff', 'customer'], true)) {
            $role = 'customer';
        }

        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) respond(false, 'Email already exists', 400);

        if ($password !== '') {
            $stmt = $pdo->prepare("
                UPDATE users
                SET name = ?, email = ?, password = ?, role = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
                $id
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users
                SET name = ?, email = ?, role = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$name, $email, $role, $id]);
        }

        respond(true, 'User updated successfully');
    }

    /* ========== UPDATE STATUS (SAME AS PRODUCTS) ========== */
    if ($action === 'update_status') {
        if (!$hasStatus) respond(false, 'Status not supported on this schema', 400);

        $id     = (int)post('user_id');
        $status = strtolower(post('status'));

        if ($id <= 0 || !in_array($status, ['active', 'inactive'], true)) {
            respond(false, 'Invalid input', 400);
        }

        if ($id === (int)$_SESSION['user_id']) {
            respond(false, 'Cannot change your own status', 400);
        }

        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) respond(false, 'User not found', 404);
        if ($user['role'] === 'admin') respond(false, 'Cannot deactivate admin', 400);

        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->execute([$status, $id]);

        respond(true, 'Status updated');
    }

    /* ========== UPDATE ROLE ========== */
    if ($action === 'update_role') {
        $id   = (int)post('user_id');
        $role = post('role');

        if ($id <= 0 || !in_array($role, ['admin', 'staff', 'customer'], true)) {
            respond(false, 'Invalid input', 400);
        }

        if ($id === (int)$_SESSION['user_id'] && $role !== 'admin') {
            respond(false, 'Cannot change your own admin role', 400);
        }

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$role, $id]);

        respond(true, 'Role updated');
    }

    /* ========== DELETE USER ========== */
    if ($action === 'delete') {
        $id = (int)post('user_id');

        if ($id <= 0) respond(false, 'Invalid user ID', 400);
        if ($id === (int)$_SESSION['user_id']) respond(false, 'Cannot delete yourself', 400);

        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) respond(false, 'User not found', 404);
        if ($user['role'] === 'admin') respond(false, 'Cannot delete admin user', 400);

        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$id]);

        respond(true, 'User deleted');
    }

    respond(false, 'Invalid action', 400);
} catch (Throwable $e) {
    error_log('[USERS_API] ' . $e->getMessage());
    respond(false, 'Server error', 500);
}
