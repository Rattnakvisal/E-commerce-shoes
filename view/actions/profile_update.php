<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) session_start();

/* ================= AUTH ================= */
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: /E-commerce-shoes/view/auth/Log/login.php');
    exit;
}

/* ================= DB ================= */
$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection missing.');
}

/* ================= CSRF ================= */
$csrf = (string)($_POST['csrf'] ?? '');
if (empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $csrf)) {
    $_SESSION['message'] = 'Invalid request (CSRF).';
    header('Location: /E-commerce-shoes/view/content/profile.php');
    exit;
}

/* ================= Helpers ================= */
function colExists(PDO $pdo, string $table, string $col): bool
{
    static $cache = [];
    $key = $table . '.' . $col;
    if (isset($cache[$key])) return $cache[$key];

    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$col]);
    $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    return $cache[$key];
}

function redirectWith(string $msg): void
{
    $_SESSION['message'] = $msg;
    header('Location: /E-commerce-shoes/view/content/profile.php');
    exit;
}

$action = (string)($_POST['action'] ?? 'profile');

try {
    /* Optional columns (safe for old schema) */
    $hasAddress = colExists($pdo, 'users', 'address');
    $hasAvatar  = colExists($pdo, 'users', 'avatar_url');

    /* ================= UPDATE PROFILE ================= */
    if ($action === 'profile') {
        $name    = trim((string)($_POST['name'] ?? ''));
        $email   = trim((string)($_POST['email'] ?? ''));
        $phone   = trim((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));

        if ($name === '') throw new RuntimeException('Name is required.');
        if (mb_strlen($name, 'UTF-8') > 120) throw new RuntimeException('Name is too long.');

        if ($email === '') throw new RuntimeException('Email is required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }
        if (mb_strlen($email, 'UTF-8') > 190) throw new RuntimeException('Email is too long.');

        if ($phone !== '' && mb_strlen($phone, 'UTF-8') > 30) {
            throw new RuntimeException('Phone is too long.');
        }
        if ($hasAddress && $address !== '' && mb_strlen($address, 'UTF-8') > 255) {
            throw new RuntimeException('Address is too long.');
        }

        /* Check duplicate email (exclude current user) */
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :e AND user_id <> :id LIMIT 1");
        $stmt->execute([':e' => $email, ':id' => $userId]);
        if ($stmt->fetch()) {
            throw new RuntimeException('This email is already used by another account.');
        }

        /* Update users table */
        if ($hasAddress) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET name = :n, email = :e, phone = :p, address = :a
                WHERE user_id = :id
            ");
            $stmt->execute([
                ':n' => $name,
                ':e' => $email,
                ':p' => $phone,
                ':a' => $address,
                ':id' => $userId
            ]);

            // update session for navbar
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $_SESSION['address'] = $address;
        } else {
            $stmt = $pdo->prepare("
                UPDATE users
                SET name = :n, email = :e, phone = :p
                WHERE user_id = :id
            ");
            $stmt->execute([
                ':n' => $name,
                ':e' => $email,
                ':p' => $phone,
                ':id' => $userId
            ]);

            // update session for navbar
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $_SESSION['address'] = $_SESSION['address'] ?? '';
        }

        redirectWith('Profile updated successfully.');
    }

    /* ================= UPDATE PASSWORD ================= */
    if ($action === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if ($new !== $confirm) throw new RuntimeException('Passwords do not match.');
        if (strlen($new) < 6) throw new RuntimeException('Password must be at least 6 characters.');

        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $hash = (string)($stmt->fetchColumn() ?? '');

        if (!$hash || !password_verify($current, $hash)) {
            throw new RuntimeException('Current password is incorrect.');
        }

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :pw WHERE user_id = :id");
        $stmt->execute([':pw' => $newHash, ':id' => $userId]);

        redirectWith('Password updated successfully.');
    }

    /* ================= UPDATE AVATAR ================= */
    if ($action === 'avatar') {
        if (!$hasAvatar) {
            throw new RuntimeException('Avatar feature is not available (missing avatar_url column).');
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please choose an image.');
        }

        // limit size (2MB)
        if (!empty($_FILES['avatar']['size']) && (int)$_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            throw new RuntimeException('Image too large. Max 2MB.');
        }

        $tmp = (string)$_FILES['avatar']['tmp_name'];
        $info = @getimagesize($tmp);
        if (!$info) throw new RuntimeException('Invalid image file.');

        $ext = match ($info['mime']) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => ''
        };
        if ($ext === '') throw new RuntimeException('Only JPG, PNG, WEBP allowed.');

        // store avatars only under assets/Images/avatars
        $dir = __DIR__ . '/../../assets/Images/avatars';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create assets avatar folder.');
        }

        $file = 'u' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $path = $dir . '/' . $file;

        if (!move_uploaded_file($tmp, $path)) {
            throw new RuntimeException('Upload failed.');
        }

        $storeUrl = '/E-commerce-shoes/assets/Images/avatars/' . $file;

        $stmt = $pdo->prepare("UPDATE users SET avatar_url = :u WHERE user_id = :id");
        $stmt->execute([':u' => $storeUrl, ':id' => $userId]);

        // cache-bust using local mtime
        $finalUrl = $storeUrl;
        $local = realpath(__DIR__ . '/../../') . '/assets/Images/avatars/' . $file;
        if (is_file($local)) {
            $sep = strpos($finalUrl, '?') === false ? '?' : '&';
            $finalUrl .= $sep . 'v=' . @filemtime($local);
        }

        $_SESSION['avatar_url'] = $finalUrl;

        redirectWith('Avatar updated successfully.');
    }

    throw new RuntimeException('Unknown action.');
} catch (Throwable $e) {
    redirectWith($e->getMessage());
}
