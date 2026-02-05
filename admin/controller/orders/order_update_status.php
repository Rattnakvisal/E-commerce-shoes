<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function jsonInput(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) respond(['success' => false, 'error' => 'Database connection missing'], 500);

/* ================= AUTH ================= */
$userId = (int)($_SESSION['user_id'] ?? 0);
$role   = (string)($_SESSION['role'] ?? '');
if ($userId <= 0 || !in_array($role, ['admin', 'staff'], true)) {
    respond(['success' => false, 'error' => 'Unauthorized'], 401);
}

/* ================= INPUT ================= */
$in = jsonInput();
$orderId   = (int)($in['order_id'] ?? 0);
$toStatus  = strtolower(trim((string)($in['to_status'] ?? '')));
$note      = trim((string)($in['note'] ?? '')); // cancellation reason / admin note

$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
if ($orderId <= 0) respond(['success' => false, 'error' => 'Invalid order_id'], 422);
if (!in_array($toStatus, $allowedStatuses, true)) respond(['success' => false, 'error' => 'Invalid to_status'], 422);

/* ================= TRANSITION RULES (like image) =================
   Flow: pending -> processing -> shipped -> delivered -> completed
   Cancel allowed: pending, processing, shipped, delivered
*/
$nextAllowed = [
    'pending'    => ['processing', 'cancelled'],
    'processing' => ['shipped', 'cancelled'],
    'shipped'    => ['delivered', 'cancelled'],
    'delivered'  => ['completed', 'cancelled'],
    'completed'  => [], // no change
    'cancelled'  => [], // no change
];

try {
    $pdo->beginTransaction();

    // lock order
    $st = $pdo->prepare("SELECT order_id, order_status FROM orders WHERE order_id = :id FOR UPDATE");
    $st->execute([':id' => $orderId]);
    $order = $st->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $pdo->rollBack();
        respond(['success' => false, 'error' => 'Order not found'], 404);
    }

    $fromStatus = strtolower((string)$order['order_status']);

    if (!isset($nextAllowed[$fromStatus])) {
        $pdo->rollBack();
        respond(['success' => false, 'error' => 'Order status invalid in DB'], 409);
    }

    if ($toStatus === $fromStatus) {
        $pdo->rollBack();
        respond(['success' => false, 'error' => 'Order already in this status'], 409);
    }

    if (!in_array($toStatus, $nextAllowed[$fromStatus], true)) {
        $pdo->rollBack();
        respond([
            'success' => false,
            'error' => "Not allowed: $fromStatus → $toStatus"
        ], 409);
    }

    // require note when cancelling
    if ($toStatus === 'cancelled' && $note === '') {
        $pdo->rollBack();
        respond(['success' => false, 'error' => 'Cancel reason is required'], 422);
    }

    // build update with timestamps
    $set = ["order_status = :to"];
    $bind = [':to' => $toStatus, ':id' => $orderId];

    $columnExists = function (PDO $db, string $table, string $col): bool {
        try {
            $st = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:t AND COLUMN_NAME=:c");
            $st->execute([':t' => $table, ':c' => $col]);
            return ((int)$st->fetchColumn()) > 0;
        } catch (Throwable $e) {
            return false;
        }
    };

    if ($toStatus === 'processing' && $columnExists($pdo, 'orders', 'processing_at')) $set[] = "processing_at = COALESCE(processing_at, NOW())";
    if ($toStatus === 'shipped' && $columnExists($pdo, 'orders', 'shipped_at'))    $set[] = "shipped_at  = COALESCE(shipped_at, NOW())";
    if ($toStatus === 'delivered' && $columnExists($pdo, 'orders', 'delivered_at'))  $set[] = "delivered_at  = COALESCE(delivered_at, NOW())";
    if ($toStatus === 'completed' && $columnExists($pdo, 'orders', 'completed_at'))  $set[] = "completed_at  = COALESCE(completed_at, NOW())";

    if ($toStatus === 'cancelled') {
        $set[] = "cancelled_at = NOW()";
        $set[] = "cancelled_reason = :reason";
        $bind[':reason'] = $note;
    }

    $sql = "UPDATE orders SET " . implode(", ", $set) . " WHERE order_id = :id";
    $st = $pdo->prepare($sql);
    $st->execute($bind);

    // log
    $st = $pdo->prepare("
        INSERT INTO order_status_logs (order_id, changed_by, from_status, to_status, note)
        VALUES (:oid, :uid, :from, :to, :note)
    ");
    $st->execute([
        ':oid'  => $orderId,
        ':uid'  => $userId,
        ':from' => $fromStatus,
        ':to'   => $toStatus,
        ':note' => ($toStatus === 'cancelled') ? $note : ($note ?: null),
    ]);

    $pdo->commit();

    respond([
        'success' => true,
        'message' => "Status updated: $fromStatus → $toStatus",
        'order_id' => $orderId,
        'from_status' => $fromStatus,
        'to_status' => $toStatus,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[order_update_status] ' . $e->getMessage());
    respond(['success' => false, 'error' => 'Server error'], 500);
}
