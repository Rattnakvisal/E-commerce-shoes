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

function respond(bool $success, string $message = '', int $code = 200, array $data = []): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
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

function columnExists(string $col): bool
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $stmt->execute([$col]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return false;
    }
}

$action = get('action') ?: post('action');

try {
    if ($action === 'get_user') {
        $id = (int)get('id');
        if ($id <= 0) respond(false, 'Invalid user ID', 400);

        $hasStatus = columnExists('status');
        $cols = 'user_id, name, email, role' . ($hasStatus ? ', status' : '') . ', created_at';

        $stmt = $pdo->prepare("SELECT $cols FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) respond(false, 'User not found', 404);
        respond(true, 'OK', 200, ['user' => $user]);
    }

    if ($action === 'create') {
        $name = trim(post('name'));
        $email = trim(post('email'));
        $password = post('password');
        $role = post('role', 'customer');

        if ($name === '' || $email === '' || strlen($password) < 6) {
            respond(false, 'Invalid input', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(false, 'Invalid email', 400);
        }

        if (!in_array($role, ['admin', 'staff', 'customer'], true)) {
            $role = 'customer';
        }

        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) respond(false, 'Email already exists', 400);

        $hasStatus = columnExists('status');
        $status = post('status', 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if ($hasStatus) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        }

        respond(true, 'User created successfully');
    }

    if ($action === 'update') {
        $id = (int)post('user_id');
        $name = trim(post('name'));
        $email = trim(post('email'));
        $password = post('password');
        $role = post('role', 'customer');

        if ($id <= 0 || $name === '' || $email === '') respond(false, 'Invalid input', 400);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, 'Invalid email', 400);
        if (!in_array($role, ['admin', 'staff', 'customer'], true)) $role = 'customer';

        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) respond(false, 'Email already exists', 400);

        if ($password !== '') {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $role, $id]);
        }

        respond(true, 'User updated successfully');
    }

    if ($action === 'delete') {
        $id = (int)post('user_id');
        if ($id <= 0) respond(false, 'Invalid user ID', 400);
        if ($id === (int)$_SESSION['user_id']) respond(false, 'Cannot delete your own account', 400);

        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) respond(false, 'User not found', 404);
        if ($user['role'] === 'admin') respond(false, 'Cannot delete admin user', 400);
        $delStmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $delStmt->execute([$id]);

        respond(true, 'User deleted');
    }

    if ($action === 'update_role') {
        $id = (int)post('user_id');
        $role = post('role');
        if ($id <= 0 || !in_array($role, ['admin', 'staff', 'customer'], true)) respond(false, 'Invalid input', 400);
        if ($id === (int)$_SESSION['user_id'] && $role !== 'admin') respond(false, 'Cannot change your own admin role', 400);

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$role, $id]);

        respond(true, 'Role updated');
    }

    if ($action === 'update_status') {
        $id = (int)post('user_id');
        $status = post('status');
        if ($id <= 0 || !in_array($status, ['active', 'inactive'], true)) respond(false, 'Invalid input', 400);
        if (!columnExists('status')) respond(false, 'Status column not available on this schema', 400);
        if ($id === (int)$_SESSION['user_id']) respond(false, 'Cannot change your own status', 400);

        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) respond(false, 'User not found', 404);
        if ($user['role'] === 'admin') respond(false, 'Cannot change status of admin user', 400);

        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->execute([$status, $id]);

        respond(true, 'Status updated');
    }

    respond(false, 'Invalid action', 400);
} catch (Throwable $e) {
    error_log('[USERS_API_FIXED] ' . $e->getMessage());
    respond(false, sprintf('Server error: %s', $e->getMessage()), 500);
}
