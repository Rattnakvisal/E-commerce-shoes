<?php
// user_api.php
ob_start();
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config/conn.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication and authorization
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handlePostRequest();
    } else {
        handleGetRequest();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

/* =====================================================
   HELPER FUNCTIONS
===================================================== */

/**
 * Check if user is admin
 */
function isAdmin(): bool
{
    return !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Handle POST requests
 */
function handlePostRequest(): void
{
    global $pdo;

    $action = $_POST['action'] ?? '';

    match ($action) {
        'delete' => deleteUser($pdo),
        'update_role' => updateUserRole($pdo),
        'create', 'update' => handleUserCreateUpdate($pdo, $action),
        default => jsonError('Invalid action')
    };
}

/**
 * Handle GET requests
 */
function handleGetRequest(): void
{
    global $pdo;

    $action = $_GET['action'] ?? '';

    if ($action === 'get_user') {
        getUser($pdo);
    } else {
        jsonError('Invalid action');
    }
}

/**
 * Send JSON error response
 */
function jsonError(string $message): void
{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * Send JSON success response
 */
function jsonSuccess(string $message, array $data = []): void
{
    $response = ['success' => true, 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

/* =====================================================
   USER OPERATIONS
===================================================== */

/**
 * Delete a user
 */
function deleteUser(PDO $pdo): void
{
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        jsonError('Invalid user id');
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    jsonSuccess('User deleted successfully');
}

/**
 * Update user role
 */
function updateUserRole(PDO $pdo): void
{
    $userId = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';

    if ($userId <= 0 || !in_array($role, ['admin', 'staff', 'customer'])) {
        jsonError('Invalid input');
    }

    $stmt = $pdo->prepare("UPDATE users SET ROLE = ? WHERE user_id = ?");
    $stmt->execute([$role, $userId]);
    jsonSuccess('User role updated successfully');
}

/**
 * Create or update a user
 */
function handleUserCreateUpdate(PDO $pdo, string $action): void
{
    // Get and validate input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    // Validation
    if (empty($name) || empty($email)) {
        jsonError('Name and email are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Invalid email format');
    }

    if ($action === 'create' && (empty($password) || strlen($password) < 6)) {
        jsonError('Password must be at least 6 characters');
    }

    if (!in_array($role, ['admin', 'staff', 'customer'])) {
        $role = 'customer';
    }

    // Check email uniqueness
    if (!isEmailUnique($pdo, $email, $userId, $action)) {
        jsonError('Email already exists');
    }

    // Perform create or update
    if ($action === 'create') {
        createUser($pdo, $name, $email, $password, $role);
    } else {
        updateUser($pdo, $name, $email, $password, $role, $userId);
    }
}

/**
 * Check if email is unique
 */
function isEmailUnique(PDO $pdo, string $email, int $userId, string $action): bool
{
    if ($action === 'create') {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
    } else {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1');
        $stmt->execute([$email, $userId]);
    }
    return !$stmt->fetch();
}

/**
 * Create a new user
 */
function createUser(PDO $pdo, string $name, string $email, string $password, string $role): void
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (NAME, email, PASSWORD, ROLE) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $hash, $role]);
    jsonSuccess('User created successfully');
}

/**
 * Update an existing user
 */
function updateUser(PDO $pdo, string $name, string $email, string $password, string $role, int $userId): void
{
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET NAME = ?, email = ?, PASSWORD = ?, ROLE = ? WHERE user_id = ?');
        $stmt->execute([$name, $email, $hash, $role, $userId]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET NAME = ?, email = ?, ROLE = ? WHERE user_id = ?');
        $stmt->execute([$name, $email, $role, $userId]);
    }
    jsonSuccess('User updated successfully');
}

/**
 * Get a single user by ID
 */
function getUser(PDO $pdo): void
{
    if (!isset($_GET['id'])) {
        jsonError('User ID required');
    }

    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        jsonSuccess('User found', ['user' => $user]);
    } else {
        jsonError('User not found');
    }
}
