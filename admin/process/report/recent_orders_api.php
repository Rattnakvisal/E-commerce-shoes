<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

header('Content-Type: application/json; charset=utf-8');

/* =====================================================
   DB CHECK
===================================================== */
if (!isset($conn) || !$conn instanceof PDO) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection unavailable'
    ]);
    exit;
}

/* =====================================================
   INPUT & LIMITS
===================================================== */
$days  = max(1, min(90, (int)($_GET['days'] ?? 7)));   // cap to 90 days
$limit = max(1, min(20, (int)($_GET['limit'] ?? 5))); // cap recent orders

$end   = date('Y-m-d');
$start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

/* =====================================================
   DAILY ORDER COUNTS
===================================================== */
$dailySql = "
    SELECT
        DATE(created_at) AS sale_date,
        COUNT(*) AS order_count
    FROM orders
    WHERE DATE(created_at) BETWEEN :start AND :end
    GROUP BY DATE(created_at)
    ORDER BY sale_date
";

$dailyStmt = $conn->prepare($dailySql);
$dailyStmt->execute(compact('start', 'end'));
$daily = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   STATUS COLUMN DETECTION
===================================================== */
$columnRows = $conn->query("SHOW COLUMNS FROM orders")
    ->fetchAll(PDO::FETCH_COLUMN);

$statusCandidates = [
    'status',
    'order_status',
    'state',
    'order_state',
    'payment_status'
];

$statusColumn = null;
foreach ($statusCandidates as $candidate) {
    if (in_array($candidate, $columnRows, true)) {
        $statusColumn = $candidate;
        break;
    }
}

$statusSelect = $statusColumn
    ? "COALESCE(o.`{$statusColumn}`, 'unknown') AS status"
    : "'unknown' AS status";

/* =====================================================
   RECENT ORDERS
===================================================== */
$recentSql = "
    SELECT
        o.order_id,
        o.created_at,
        o.total,
        {$statusSelect},
        COALESCE(u.NAME, '') AS customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    ORDER BY o.created_at DESC
    LIMIT :limit
";

$recentStmt = $conn->prepare($recentSql);
$recentStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$recentStmt->execute();
$recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   RESPONSE
===================================================== */
echo json_encode([
    'success' => true,
    'meta' => [
        'days'  => $days,
        'limit' => $limit,
        'range' => [$start, $end]
    ],
    'daily'  => $daily,
    'recent' => $recent
], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

exit;
