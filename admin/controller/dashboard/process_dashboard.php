<?php
require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   SESSION & AUTH
===================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../auth/Log/login.php');
    exit;
}

/* =====================================================
   ADMIN INFO (SAFE DEFAULTS)
===================================================== */
$admin_name   = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin';
$admin_role   = $_SESSION['role'] ?? 'Administrator';
$admin_avatar = $_SESSION['avatar']
    ?? 'https://ui-avatars.com/api/?name=' . urlencode($admin_name) . '&background=6366f1&color=fff';

/* =====================================================
   DEFAULT VALUES (AVOID WARNINGS)
===================================================== */
$total_users       = 0;
$total_orders      = 0;
$revenue           = 0.0;
$conversion_rate   = 0.0;

$recent_orders     = [];
$topProducts       = [];
$lowStockProducts  = [];
$ordersByStatus    = [
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0,
];
$revenueLast7      = []; // array of ['date' => 'YYYY-MM-DD','total' => float]
$recent_users      = [];

/* =====================================================
   FETCH DASHBOARD DATA
===================================================== */
try {

    /* ---------- USERS COUNT ---------- */
    $total_users = (int)$pdo
        ->query("SELECT COUNT(*) FROM users")
        ->fetchColumn();

    /* ---------- ORDERS COUNT ---------- */
    $total_orders = (int)$pdo
        ->query("SELECT COUNT(*) FROM orders")
        ->fetchColumn();

    /* ---------- TOTAL REVENUE ---------- */
    $revenue = (float)$pdo
        ->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid'")
        ->fetchColumn();

    /* ---------- CONVERSION RATE ---------- */
    $conversion_rate = $total_users > 0
        ? round(($total_orders / $total_users) * 100, 2)
        : 0;

    /* ---------- TOP PRODUCTS ---------- */
    $stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.name,
            p.price,
            p.stock,
            COALESCE(SUM(oi.quantity), 0) AS total_quantity
        FROM products p
        LEFT JOIN order_items oi ON oi.product_id = p.product_id
        GROUP BY p.product_id
        ORDER BY total_quantity DESC
        LIMIT 5
    ");
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- LOW STOCK PRODUCTS ---------- */
    $stmt = $pdo->query("
        SELECT 
            product_id,
            name,
            stock
        FROM products
                WHERE stock IS NOT NULL
                    AND stock <= 10
        ORDER BY stock ASC
        LIMIT 5
    ");
    $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- RECENT ORDERS ---------- */
    $stmt = $pdo->query("
        SELECT 
            o.order_id AS id,
            o.total,
            o.order_status AS status,
            o.payment_status,
            o.created_at,
            COALESCE(u.name, u.email, 'Guest') AS customer
        FROM orders o
        LEFT JOIN users u ON u.user_id = o.user_id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- ORDERS BY STATUS ---------- */
    $stmt = $pdo->query(
        "SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $key = strtolower($r['order_status']);
        if (isset($ordersByStatus[$key])) {
            $ordersByStatus[$key] = (int)$r['cnt'];
        }
    }

    /* ---------- REVENUE LAST 7 DAYS ---------- */
    $stmt = $pdo->query(
        "SELECT DATE(created_at) as day, COALESCE(SUM(total),0) as total FROM orders WHERE payment_status = 'paid' GROUP BY DATE(created_at) ORDER BY DATE(created_at) DESC LIMIT 7"
    );
    $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    foreach ($rows as $r) {
        $revenueLast7[] = [
            'date' => $r['day'],
            'total' => (float)$r['total']
        ];
    }

    /* ---------- RECENT USERS ---------- */
    $stmt = $pdo->query("SELECT user_id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Admin Dashboard] ' . $e->getMessage());
}

$userRate        = $total_users > 0 ? 100 : 0;
$completedRate   = $total_orders > 0 ? (($ordersByStatus['completed'] ?? 0) / $total_orders) * 100 : 0;
$revenueTarget   = $revenue * 1.2;
$revenueProgress = $revenueTarget > 0 ? min(($revenue / $revenueTarget) * 100, 100) : 0;
$conversionBar   = $conversion_rate > 0 ? min(($conversion_rate / 3.5) * 100, 100) : 0;

/* ---------- SUMMARY (compat for templates expecting $summary) ---------- */
try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE user_id IS NOT NULL");
    $unique_customers = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $unique_customers = 0;
}

$summary = [
    'unique_customers' => $unique_customers,
    'orders_count' => (int)$total_orders,
];
