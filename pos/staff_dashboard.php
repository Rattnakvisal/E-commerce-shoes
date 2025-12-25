<?php
require_once __DIR__ . '/../config/conn.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login
if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Only allow staff or admin to view this page
$role = $_SESSION['role'] ?? null;
if ($role !== 'staff' && $role !== 'admin') {
    header('Location: ../view/index.php');
    exit;
}

$staff_name = $_SESSION['name'] ?? $_SESSION['admin_name'] ?? 'Staff';

// Simple stats (example queries)
try {
    $totalPosOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_type = 'pos'")->fetchColumn();
    $today = date('Y-m-d');
    $todaysOrdersStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ? AND order_type = 'pos'");
    $todaysOrdersStmt->execute([$today]);
    $todaysOrders = (int)$todaysOrdersStmt->fetchColumn();
} catch (Exception $e) {
    $totalPosOrders = 0;
    $todaysOrders = 0;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Staff Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen">
    <?php require_once __DIR__ . '/../admin/include/navbar.php'; ?>
</body>

</html>