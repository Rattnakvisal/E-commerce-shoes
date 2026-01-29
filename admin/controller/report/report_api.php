<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

/* =========================================================
   DB CHECK
========================================================= */
if (!isset($conn) || !$conn instanceof PDO) {
    http_response_code(500);
    exit('Database connection not available.');
}

/* =========================================================
   INPUT & DEFAULTS
========================================================= */
$start       = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end         = $_GET['end_date']   ?? date('Y-m-d');
$type        = strtolower($_GET['type'] ?? 'html');
$reportType  = $_GET['report_type'] ?? 'summary';
$limit       = max(1, min(500, (int)($_GET['limit'] ?? 50)));

$allowedReports = ['summary', 'detailed', 'products', 'customers', 'daily', 'payment'];
if (!in_array($reportType, $allowedReports, true)) {
    $reportType = 'summary';
}

/* =========================================================
   DATE VALIDATION
========================================================= */
function safe_date(string $date): ?string
{
    return strtotime($date) ? $date : null;
}

$start = safe_date($start) ?? date('Y-m-d', strtotime('-30 days'));
$end   = safe_date($end)   ?? date('Y-m-d');

if ($start > $end) {
    [$start, $end] = [$end, $start];
}

/* =========================================================
   SUMMARY (ALWAYS INCLUDED)
========================================================= */
$summaryStmt = $conn->prepare("
    SELECT 
        COUNT(*)               AS orders_count,
        COALESCE(SUM(total),0) AS total_sales,
        COUNT(DISTINCT user_id) AS unique_customers,
        COALESCE(AVG(total),0) AS avg_order_value,
        MIN(created_at)        AS first_order_date,
        MAX(created_at)        AS last_order_date
    FROM orders
    WHERE DATE(created_at) BETWEEN :start AND :end
");
$summaryStmt->execute(compact('start', 'end'));
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   REPORT DATA
========================================================= */
$data = [];

switch ($reportType) {

    case 'detailed':
        $stmt = $conn->prepare("
            SELECT 
                o.*,
                u.NAME AS customer_name,
                u.email AS customer_email,
                COUNT(oi.order_item_id) AS item_count,
                GROUP_CONCAT(DISTINCT p.NAME SEPARATOR ', ') AS products
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.product_id
            WHERE DATE(o.created_at) BETWEEN :start AND :end
            GROUP BY o.order_id
            ORDER BY o.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $data['orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'products':
        $stmt = $conn->prepare("
            SELECT 
                p.product_id,
                p.NAME AS product_name,
                c.category_name AS category,
                SUM(oi.quantity) AS qty_sold,
                SUM(oi.quantity * oi.price) AS revenue,
                COUNT(DISTINCT o.order_id) AS order_count,
                ROUND(AVG(oi.quantity),2) AS avg_qty_per_order
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE DATE(o.created_at) BETWEEN :start AND :end
            GROUP BY p.product_id
            ORDER BY revenue DESC
        ");
        $stmt->execute(compact('start', 'end'));
        $data['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'customers':
        $stmt = $conn->prepare("
            SELECT 
                u.user_id,
                u.NAME,
                u.email,
                COUNT(o.order_id) AS order_count,
                COALESCE(SUM(o.total),0) AS total_spent,
                MIN(o.created_at) AS first_order_date,
                MAX(o.created_at) AS last_order_date
            FROM users u
            LEFT JOIN orders o 
                ON u.user_id = o.user_id
                AND DATE(o.created_at) BETWEEN :start AND :end
            GROUP BY u.user_id
            HAVING order_count > 0
            ORDER BY total_spent DESC
        ");
        $stmt->execute(compact('start', 'end'));
        $data['customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'daily':
        $stmt = $conn->prepare("
            SELECT 
                DATE(created_at) AS sale_date,
                COUNT(*) AS order_count,
                SUM(total) AS daily_sales,
                AVG(total) AS avg_order_value
            FROM orders
            WHERE DATE(created_at) BETWEEN :start AND :end
            GROUP BY DATE(created_at)
            ORDER BY sale_date
        ");
        $stmt->execute(compact('start', 'end'));
        $data['daily'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'payment':
        $stmt = $conn->prepare("
            SELECT 
                payment_method,
                COUNT(*) AS order_count,
                SUM(amount) AS total_amount,
                AVG(amount) AS avg_amount
            FROM payments
            WHERE DATE(payment_date) BETWEEN :start AND :end
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ");
        $stmt->execute(compact('start', 'end'));
        $data['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
}

/* =========================================================
   SHARED METRICS
========================================================= */
$total_users = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

$newUsersStmt = $conn->prepare("
    SELECT COUNT(*) FROM users
    WHERE DATE(created_at) BETWEEN :start AND :end
");
$newUsersStmt->execute(compact('start', 'end'));
$new_users = (int)$newUsersStmt->fetchColumn();

$topCustomerStmt = $conn->prepare("
    SELECT u.user_id, u.NAME, u.email, SUM(o.total) AS total_spent
    FROM users u
    JOIN orders o ON u.user_id = o.user_id
    WHERE DATE(o.created_at) BETWEEN :start AND :end
    GROUP BY u.user_id
    ORDER BY total_spent DESC
    LIMIT 1
");
$topCustomerStmt->execute(compact('start', 'end'));
$top_customer = $topCustomerStmt->fetch(PDO::FETCH_ASSOC) ?: [];

/* =========================================================
   STATUS SUMMARY & RECENT ORDERS
========================================================= */
$columns = $conn->query("SHOW COLUMNS FROM orders")
    ->fetchAll(PDO::FETCH_COLUMN);

$statusCandidates = ['status', 'order_status', 'state', 'order_state', 'payment_status'];
$statusCol = null;
foreach ($statusCandidates as $c) {
    if (in_array($c, $columns, true)) {
        $statusCol = $c;
        break;
    }
}

$statusSelect = $statusCol
    ? "COALESCE(o.`{$statusCol}`,'unknown') AS status"
    : "'unknown' AS status";

$statusStmt = $conn->prepare("
    SELECT {$statusSelect}, COUNT(*) AS count
    FROM orders o
    WHERE DATE(o.created_at) BETWEEN :start AND :end
    GROUP BY status
");
$statusStmt->execute(compact('start', 'end'));
$statusSummary = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

// Provide a quick lookup map: status => count
$statusSummaryMap = [];
foreach ($statusSummary as $r) {
    $statusSummaryMap[$r['status']] = (int)($r['count'] ?? 0);
}

$recentStmt = $conn->prepare("
    SELECT 
        o.order_id,
        o.created_at,
        o.total,
        {$statusSelect},
        u.NAME AS customer_name,
        u.email AS customer_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recentStmt->execute();
$recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

$bestStmt = $conn->prepare("
    SELECT 
        p.product_id,
        p.NAME AS product_name,
        SUM(oi.quantity) AS qty_sold,
        SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE DATE(o.created_at) BETWEEN :start AND :end
    GROUP BY p.product_id
    ORDER BY revenue DESC
    LIMIT 6
");
$bestStmt->execute(compact('start', 'end'));
$best = $bestStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
