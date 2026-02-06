<?php

declare(strict_types=1);

// Simple router: include admin handler for admins, otherwise include user handler.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adminTarget = __DIR__ . '/notification/notifications_admin.php';
$userTarget = __DIR__ . '/notification/notifications_user.php';

$role = (string)($_SESSION['role'] ?? '');
$isAdmin = in_array(strtolower($role), ['admin', 'administrator'], true);

$target = $isAdmin && is_file($adminTarget) ? $adminTarget : $userTarget;

if (is_file($target)) {
    require $target;
    return;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => false, 'msg' => 'Not found']);
