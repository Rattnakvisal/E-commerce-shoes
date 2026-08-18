<?php
require_once __DIR__ . '/../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent headers-sent when there's no last order — redirect early
if (empty($_SESSION['last_order'])) {
    header('Location: /MyBrand_Ecommerce/view/content/products.php');
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmed</title>
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-extrabold,
        .font-black,
        .font-medium,
        strong,
        b {
            font-weight: 400 !important;
        }
    </style>
</head>

<body class="bg-white">
    <?php
    require_once __DIR__ . '/../../includes/shader/topbar.php';
    require_once __DIR__ . '/../../includes/shader/navbar.php';
    require_once __DIR__ . '/../../includes/shader/order_success.php';
    require_once __DIR__ . '/../../includes/shader/footer.php';
    ?>
</body>

</html>