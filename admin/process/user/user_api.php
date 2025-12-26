<?php

declare(strict_types=1);

ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =====================================================
   AUTH & SECURITY
===================================================== */
function isAdmin(): bool
{
    return !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
}

if (!isAdmin()) {
    if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        jsonResponse(false, 'Unauthorized');
    }
    header('Location: ../../auth/login.php');
    exit;
}

/* =====================================================
   JSON RESPONSE HELPERS
===================================================== */
function jsonResponse(bool $success, string $message = '', array $data = []): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

/* =====================================================
   API ROUTER
===================================================== */
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? null;

if ($action !== null) {
    try {
        match ($action) {
            'create'       => createUser($pdo),
            'update'       => updateUser($pdo),
            'delete'       => deleteUser($pdo),
            'update_role'  => updateUserRole($pdo),
            'get_user'     => getUser($pdo),
            default        => jsonResponse(false, 'Invalid action')
        };
    } catch (Throwable $e) {
        jsonResponse(false, 'Server error');
    }
}

/* =====================================================
   USER CRUD FUNCTIONS
===================================================== */
function createUser(PDO $pdo): void
{
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'customer';

    validateUserInput($pdo, $name, $email, $password, $role, true);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (NAME, email, PASSWORD, ROLE)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$name, $email, $hash, $role]);

    jsonResponse(true, 'User created successfully');
}

function updateUser(PDO $pdo): void
{
    $id       = (int)($_POST['user_id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'customer';

    if ($id <= 0) jsonResponse(false, 'Invalid user ID');

    validateUserInput($pdo, $name, $email, $password, $role, false, $id);

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql  = "UPDATE users SET NAME=?, email=?, PASSWORD=?, ROLE=? WHERE user_id=?";
        $pdo->prepare($sql)->execute([$name, $email, $hash, $role, $id]);
    } else {
        $sql = "UPDATE users SET NAME=?, email=?, ROLE=? WHERE user_id=?";
        $pdo->prepare($sql)->execute([$name, $email, $role, $id]);
    }

    jsonResponse(true, 'User updated successfully');
}

function deleteUser(PDO $pdo): void
{
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid user ID');

    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);
    jsonResponse(true, 'User deleted successfully');
}

function updateUserRole(PDO $pdo): void
{
    $id   = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';

    if ($id <= 0 || !in_array($role, ['admin', 'staff', 'customer'], true)) {
        jsonResponse(false, 'Invalid input');
    }

    $pdo->prepare("UPDATE users SET ROLE = ? WHERE user_id = ?")
        ->execute([$role, $id]);

    jsonResponse(true, 'User role updated successfully');
}

function getUser(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid user ID');

    $stmt = $pdo->prepare(
        "SELECT user_id, NAME, email, ROLE, created_at
         FROM users WHERE user_id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) jsonResponse(false, 'User not found');

    jsonResponse(true, 'User found', ['user' => $user]);
}

/* =====================================================
   VALIDATION
===================================================== */
function validateUserInput(
    PDO $pdo,
    string $name,
    string $email,
    string $password,
    string &$role,
    bool $isCreate,
    int $userId = 0
): void {
    if ($name === '' || $email === '') {
        jsonResponse(false, 'Name and email are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Invalid email format');
    }

    if ($isCreate && strlen($password) < 6) {
        jsonResponse(false, 'Password must be at least 6 characters');
    }

    if (!in_array($role, ['admin', 'staff', 'customer'], true)) {
        $role = 'customer';
    }

    if (!isEmailUnique($pdo, $email, $userId)) {
        jsonResponse(false, 'Email already exists');
    }
}

function isEmailUnique(PDO $pdo, string $email, int $userId = 0): bool
{
    if ($userId > 0) {
        $stmt = $pdo->prepare(
            "SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1"
        );
        $stmt->execute([$email, $userId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT user_id FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
    }
    return !$stmt->fetch();
}

/* =====================================================
   PAGE DATA (ADMIN USER LIST)
===================================================== */
function getUserCount(PDO $pdo, ?string $role = null): int
{
    if ($role) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE ROLE = ?");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }
    return (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

function getUsers(PDO $pdo, string $search, string $role, string $sort, int $page, int $limit = 10): array
{
    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(NAME LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($role !== '') {
        $where[] = "ROLE = ?";
        $params[] = $role;
    }

    $sql = "FROM users";
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $count = $pdo->prepare("SELECT COUNT(*) $sql");
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $order = match ($sort) {
        'oldest' => 'created_at ASC',
        'name'   => 'NAME ASC',
        'email'  => 'email ASC',
        default  => 'created_at DESC'
    };

    $offset = ($page - 1) * $limit;
    $stmt = $pdo->prepare(
        "SELECT * $sql ORDER BY $order LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);

    return [$stmt->fetchAll(PDO::FETCH_ASSOC), $total, ceil($total / $limit)];
}

/* =====================================================
   ADMIN DASHBOARD DATA
===================================================== */
$totalUsers    = getUserCount($pdo);
$adminUsers    = getUserCount($pdo, 'admin');
$staffUsers    = getUserCount($pdo, 'staff');
$customerUsers = getUserCount($pdo, 'customer');

$search = $_GET['search'] ?? '';
$role   = $_GET['role'] ?? '';
$sort   = $_GET['sort'] ?? 'newest';
$page   = max(1, (int)($_GET['page'] ?? 1));

[$users, $totalResults, $totalPages] =
    getUsers($pdo, $search, $role, $sort, $page);
