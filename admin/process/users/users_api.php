<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   SESSION & HEADERS
===================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

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
function respond(
    bool $success,
    string $message = '',
    int $code = 200,
    array $data = []
): void {
    http_response_code($code);
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $data
    ));
    exit;
}

function request(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/* =====================================================
   COLUMN CACHE
===================================================== */
$columnCache = [];

function columnExists(string $column): bool
{
    global $pdo, $columnCache;

    if (array_key_exists($column, $columnCache)) {
        return $columnCache[$column];
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $stmt->execute([$column]);
        return $columnCache[$column] = (bool)$stmt->fetch();
    } catch (Throwable) {
        return $columnCache[$column] = false;
    }
}

/* =====================================================
   ACTION ROUTER
===================================================== */
$action = request('action');

try {
    switch ($action) {

        /* ================= GET USER ================= */
        case 'get_user': {
                $id = (int)request('id');
                if ($id <= 0) respond(false, 'Invalid user ID', 400);

                $cols = ['user_id', 'name', 'email', 'role', 'created_at'];
                if (columnExists('status')) {
                    $cols[] = 'status';
                }

                $stmt = $pdo->prepare(
                    "SELECT " . implode(', ', $cols) . " FROM users WHERE user_id = ? LIMIT 1"
                );
                $stmt->execute([$id]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user) respond(false, 'User not found', 404);

                respond(true, 'OK', 200, ['user' => $user]);
            }

            /* ================= CREATE ================= */
        case 'create': {
                $name     = trim((string)request('name'));
                $email    = trim((string)request('email'));
                $password = (string)request('password');
                $role     = request('role', 'customer');

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
                if ($stmt->fetch()) {
                    respond(false, 'Email already exists', 400);
                }

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $hasStatus    = columnExists('status');
                $status       = strtolower((string)request('status', 'active'));

                if (!in_array($status, ['active', 'inactive'], true)) {
                    $status = 'active';
                }

                if ($hasStatus) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (name, email, password, role, status, created_at)
                     VALUES (?, ?, ?, ?, ?, NOW())"
                    );
                    $stmt->execute([$name, $email, $passwordHash, $role, $status]);
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (name, email, password, role, created_at)
                     VALUES (?, ?, ?, ?, NOW())"
                    );
                    $stmt->execute([$name, $email, $passwordHash, $role]);
                }

                respond(true, 'User created successfully');
            }

            /* ================= UPDATE ================= */
        case 'update': {
                $id       = (int)request('user_id');
                $name     = trim((string)request('name'));
                $email    = trim((string)request('email'));
                $password = (string)request('password');
                $role     = request('role', 'customer');

                if ($id <= 0 || $name === '' || $email === '') {
                    respond(false, 'Invalid input', 400);
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    respond(false, 'Invalid email', 400);
                }

                if (!in_array($role, ['admin', 'staff', 'customer'], true)) {
                    $role = 'customer';
                }

                $stmt = $pdo->prepare(
                    "SELECT 1 FROM users WHERE email = ? AND user_id != ?"
                );
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    respond(false, 'Email already exists', 400);
                }

                if ($password !== '') {
                    $stmt = $pdo->prepare(
                        "UPDATE users
                     SET name = ?, email = ?, password = ?, role = ?
                     WHERE user_id = ?"
                    );
                    $stmt->execute([
                        $name,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $role,
                        $id
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE users
                     SET name = ?, email = ?, role = ?
                     WHERE user_id = ?"
                    );
                    $stmt->execute([$name, $email, $role, $id]);
                }

                respond(true, 'User updated successfully');
            }

            /* ================= DELETE ================= */
        case 'delete': {
                $id = (int)request('user_id');
                if ($id <= 0) respond(false, 'Invalid user ID', 400);
                if ($id === (int)$_SESSION['user_id']) {
                    respond(false, 'Cannot delete your own account', 400);
                }

                $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) respond(false, 'User not found', 404);
                if ($user['role'] === 'admin') {
                    respond(false, 'Cannot delete admin user', 400);
                }

                $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);

                respond(true, 'User deleted');
            }

            /* ================= UPDATE ROLE ================= */
        case 'update_role': {
                $id   = (int)request('user_id');
                $role = request('role');

                if ($id <= 0 || !in_array($role, ['admin', 'staff', 'customer'], true)) {
                    respond(false, 'Invalid input', 400);
                }

                if ($id === (int)$_SESSION['user_id'] && $role !== 'admin') {
                    respond(false, 'Cannot change your own admin role', 400);
                }

                $pdo->prepare(
                    "UPDATE users SET role = ? WHERE user_id = ?"
                )->execute([$role, $id]);

                respond(true, 'Role updated');
            }

            /* ================= UPDATE STATUS ================= */
        case 'update_status': {
                if (!columnExists('status')) {
                    respond(false, 'Status column not available', 400);
                }

                $id     = (int)request('user_id');
                $status = strtolower((string)request('status'));

                if ($id <= 0 || !in_array($status, ['active', 'inactive'], true)) {
                    respond(false, 'Invalid input', 400);
                }

                if ($id === (int)$_SESSION['user_id']) {
                    respond(false, 'Cannot change your own status', 400);
                }

                $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = ?");
                $stmt->execute([$id]);
                if (!$stmt->fetch()) respond(false, 'User not found', 404);

                $pdo->prepare(
                    "UPDATE users SET status = ? WHERE user_id = ?"
                )->execute([$status, $id]);

                respond(true, 'Status updated');
            }

        default:
            respond(false, 'Invalid action', 400);
    }
} catch (Throwable $e) {
    error_log('[USERS_API] ' . $e->getMessage());
    respond(false, 'Internal server error', 500);
}
