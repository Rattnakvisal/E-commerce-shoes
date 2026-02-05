<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

if (!isset($conn) || !$conn instanceof PDO) {
    http_response_code(500);
    exit('Database connection not available.');
}

/* =========================================================
   HELPERS
========================================================= */
function parseDateYmd(string $date): ?string
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt) return null;
    // ensure strict match (no partial parsing)
    return $dt->format('Y-m-d') === $date ? $date : null;
}

/* =========================================================
   INPUT & DEFAULTS
========================================================= */
$today    = date('Y-m-d');
$defaultS = date('Y-m-d', strtotime('-30 days'));

$start = parseDateYmd((string)($_GET['start_date'] ?? '')) ?? $defaultS;
$end   = parseDateYmd((string)($_GET['end_date'] ?? '')) ?? $today;

if ($start > $end) {
    [$start, $end] = [$end, $start];
}

$format = strtolower((string)($_GET['type'] ?? 'html')); // html | json | csv (if you add)
$reportType = (string)($_GET['report_type'] ?? 'summary');

$limit = (int)($_GET['limit'] ?? 50);
$limit = max(1, min(500, $limit));

$allowedReports = [
    'summary',
    'detailed',
    'products',
    'customers',
    'daily',
    'payment',
];
if (!in_array($reportType, $allowedReports, true)) {
    $reportType = 'summary';
}

try {
    /* =========================================================
       SUMMARY (ALWAYS INCLUDED)
    ========================================================= */
    $summaryStmt = $conn->prepare("
        SELECT 
            COUNT(*)                    AS orders_count,
            COALESCE(SUM(total),0)      AS total_sales,
            COUNT(DISTINCT user_id)     AS unique_customers,
            COALESCE(AVG(total),0)      AS avg_order_value,
            MIN(created_at)             AS first_order_date,
            MAX(created_at)             AS last_order_date
        FROM orders
        WHERE DATE(created_at) BETWEEN :start AND :end
    ");
    $summaryStmt->execute(['start' => $start, 'end' => $end]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'orders_count' => 0,
        'total_sales' => 0.0,
        'unique_customers' => 0,
        'avg_order_value' => 0.0,
        'first_order_date' => null,
        'last_order_date' => null,
    ];

    // cast types
    $summary['orders_count']      = (int)($summary['orders_count'] ?? 0);
    $summary['total_sales']       = (float)($summary['total_sales'] ?? 0);
    $summary['unique_customers']  = (int)($summary['unique_customers'] ?? 0);
    $summary['avg_order_value']   = (float)($summary['avg_order_value'] ?? 0);

    /* =========================================================
       REPORT DATA
    ========================================================= */
    $data = [];

    switch ($reportType) {

        case 'detailed': {
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
            }

        case 'products': {
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
                $stmt->execute(['start' => $start, 'end' => $end]);

                $data['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            }

        case 'customers': {
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
                $stmt->execute(['start' => $start, 'end' => $end]);

                $data['customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            }

        case 'daily': {
                $stmt = $conn->prepare("
                SELECT 
                    DATE(created_at) AS sale_date,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(total),0) AS daily_sales,
                    COALESCE(AVG(total),0) AS avg_order_value
                FROM orders
                WHERE DATE(created_at) BETWEEN :start AND :end
                GROUP BY DATE(created_at)
                ORDER BY sale_date
            ");
                $stmt->execute(['start' => $start, 'end' => $end]);

                $data['daily'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            }

        case 'payment': {
                $stmt = $conn->prepare("
                SELECT 
                    payment_method,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(amount),0) AS total_amount,
                    COALESCE(AVG(amount),0) AS avg_amount
                FROM payments
                WHERE DATE(payment_date) BETWEEN :start AND :end
                GROUP BY payment_method
                ORDER BY total_amount DESC
            ");
                $stmt->execute(['start' => $start, 'end' => $end]);

                $data['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            }

        case 'summary':
        default:
            // no extra dataset
            break;
    }

    /* =========================================================
       SHARED METRICS
    ========================================================= */
    $total_users = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

    $newUsersStmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM users
        WHERE DATE(created_at) BETWEEN :start AND :end
    ");
    $newUsersStmt->execute(['start' => $start, 'end' => $end]);
    $new_users = (int)$newUsersStmt->fetchColumn();

    // include avatar/profile image if available in users table
    $userCols = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $avatarCol = null;
    foreach (['avatar', 'image', 'profile_image', 'photo', 'avatar_url'] as $c) {
        if (in_array($c, $userCols, true)) {
            $avatarCol = $c;
            break;
        }
    }

    $selectAvatar = $avatarCol ? "u.`$avatarCol` AS avatar, " : '';

    $topCustomerSql = "
        SELECT {$selectAvatar}u.user_id, u.NAME, u.email, COALESCE(SUM(o.total),0) AS total_spent
        FROM users u
        JOIN orders o ON u.user_id = o.user_id
        WHERE DATE(o.created_at) BETWEEN :start AND :end
        GROUP BY u.user_id
        ORDER BY total_spent DESC
        LIMIT 1
    ";

    $topCustomerStmt = $conn->prepare($topCustomerSql);
    $topCustomerStmt->execute(['start' => $start, 'end' => $end]);
    $top_customer = $topCustomerStmt->fetch(PDO::FETCH_ASSOC) ?: ['user_id' => '—', 'total_spent' => 0];

    /* =========================================================
       STATUS COLUMN DETECTION + STATUS SUMMARY
    ========================================================= */
    $columns = $conn->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);

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
    $statusStmt->execute(['start' => $start, 'end' => $end]);
    $statusSummary = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    $statusSummaryMap = [];
    foreach ($statusSummary as $r) {
        $statusSummaryMap[(string)($r['status'] ?? 'unknown')] = (int)($r['count'] ?? 0);
    }

    /* =========================================================
       RECENT ORDERS
    ========================================================= */
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

    /* =========================================================
       BEST PRODUCTS (TOP 6)
    ========================================================= */
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
    $bestStmt->execute(['start' => $start, 'end' => $end]);
    $best = $bestStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    /* =========================================================
       SHIPPING / LOCATION DETAILS
    ========================================================= */
    $shippingDetails = [];

    // Check for a dedicated shipping table
    $hasShippingTable = (bool)$conn->query("SHOW TABLES LIKE 'shipping'")->fetchColumn();
    if ($hasShippingTable) {
        $sql = "
            SELECT
                COALESCE(u.NAME, '') AS user,
                s.address AS address,
                s.city AS city,
                s.country AS country,
                s.latitude AS lat,
                s.longitude AS lng,
                s.status AS ship_status,
                o.created_at AS shipped_at
            FROM shipping s
            LEFT JOIN orders o ON s.order_id = o.order_id
            LEFT JOIN users u ON o.user_id = u.user_id
            WHERE DATE(o.created_at) BETWEEN :start AND :end
            ORDER BY o.created_at DESC
            LIMIT 8
        ";

        try {
            $shipStmt = $conn->prepare($sql);
            $shipStmt->execute(['start' => $start, 'end' => $end]);
            $rows = $shipStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $shippingDetails[] = [
                    'user' => (string)($r['user'] ?? ''),
                    'address' => $r['address'] ?? null,
                    'city' => $r['city'] ?? null,
                    'country' => $r['country'] ?? null,
                    'lat' => $r['lat'] ?? null,
                    'lng' => $r['lng'] ?? null,
                    'ship_status' => $r['ship_status'] ?? null,
                    'shipped_at' => $r['shipped_at'] ?? null,
                ];
            }
        } catch (Throwable $e) {
            // ignore and leave empty
            $shippingDetails = [];
        }
    } else {
        // Fallback: try to derive addresses/coords from orders/users columns
        $orderCols = $conn->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        $userCols  = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

        $pick = function (array $available, array $candidates): ?string {
            foreach ($candidates as $c) {
                if (in_array($c, $available, true)) return $c;
            }
            return null;
        };

        $addrCandidates = ['shipping_address', 'shipping_address1', 'address', 'address_line1', 'ship_address', 'ship_address1'];
        $cityCandidates = ['shipping_city', 'city', 'town'];
        $countryCandidates = ['shipping_country', 'country'];
        $latCandidates = ['shipping_lat', 'lat', 'latitude'];
        $lngCandidates = ['shipping_lng', 'lng', 'longitude'];

        $orderAddr = $pick($orderCols, $addrCandidates);
        $orderCity = $pick($orderCols, $cityCandidates);
        $orderCountry = $pick($orderCols, $countryCandidates);
        $orderLat = $pick($orderCols, $latCandidates);
        $orderLng = $pick($orderCols, $lngCandidates);

        $userAddr = $pick($userCols, $addrCandidates);
        $userCity = $pick($userCols, $cityCandidates);
        $userCountry = $pick($userCols, $countryCandidates);
        $userLat = $pick($userCols, $latCandidates);
        $userLng = $pick($userCols, $lngCandidates);

        // Build column expressions dynamically
        $addrExpr = $orderAddr ? "COALESCE(o.`$orderAddr`, u.`$userAddr`)" : ($userAddr ? "u.`$userAddr`" : null);
        $cityExpr = $orderCity ? "COALESCE(o.`$orderCity`, u.`$userCity`)" : ($userCity ? "u.`$userCity`" : null);
        $countryExpr = $orderCountry ? "COALESCE(o.`$orderCountry`, u.`$userCountry`)" : ($userCountry ? "u.`$userCountry`" : null);
        $latExpr = $orderLat ? "COALESCE(o.`$orderLat`, u.`$userLat`)" : ($userLat ? "u.`$userLat`" : null);
        $lngExpr = $orderLng ? "COALESCE(o.`$orderLng`, u.`$userLng`)" : ($userLng ? "u.`$userLng`" : null);

        if ($addrExpr !== null || $latExpr !== null || $lngExpr !== null) {
            $selects = [
                "COALESCE(u.NAME, '') AS user",
                ($addrExpr !== null ? "$addrExpr AS address" : "NULL AS address"),
                ($cityExpr !== null ? "$cityExpr AS city" : "NULL AS city"),
                ($countryExpr !== null ? "$countryExpr AS country" : "NULL AS country"),
                ($latExpr !== null ? "$latExpr AS lat" : "NULL AS lat"),
                ($lngExpr !== null ? "$lngExpr AS lng" : "NULL AS lng"),
                "o.created_at AS shipped_at",
            ];

            $sql = 'SELECT ' . implode(', ', $selects) . ' FROM orders o LEFT JOIN users u ON o.user_id = u.user_id WHERE DATE(o.created_at) BETWEEN :start AND :end ORDER BY o.created_at DESC LIMIT 8';

            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute(['start' => $start, 'end' => $end]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    $lat = $r['lat'] ?? null;
                    $lng = $r['lng'] ?? null;
                    $shippingDetails[] = [
                        'user' => (string)($r['user'] ?? ''),
                        'address' => $r['address'] ?? null,
                        'city' => $r['city'] ?? null,
                        'country' => $r['country'] ?? null,
                        'lat' => $lat ?: null,
                        'lng' => $lng ?: null,
                        'ship_status' => null,
                        'shipped_at' => $r['shipped_at'] ?? null,
                    ];
                }
            } catch (Throwable $e) {
                // ignore, leave empty
                $shippingDetails = [];
            }
        }
    }

    /* =========================================================
       JS-SAFE ARRAYS (for charts)
    ========================================================= */
    $topLabels  = array_map(fn($p) => (string)($p['product_name'] ?? ''), $best);
    $topRevenue = array_map(fn($p) => (float)($p['revenue'] ?? 0), $best);
    $topQty     = array_map(fn($p) => (int)($p['qty_sold'] ?? 0), $best);

    $totalSales = array_sum($topRevenue);
    $totalSales = max($totalSales, 1);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Report query failed: ' . $e->getMessage());
}
