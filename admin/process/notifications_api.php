<?php

declare(strict_types=1);
$target = __DIR__ . '/notification/notifications_api.php';
if (is_file($target)) {
    require $target;
    return;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => false, 'msg' => 'Not found']);
