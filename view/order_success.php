<?php
require_once __DIR__ . '/../config/conn.php';
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
</head>

<body class="bg-white">
    <?php
    require_once __DIR__ . '/../includes/topbar.php';
    require_once __DIR__ . '/../includes/navbar.php';
    require_once __DIR__ . '/../includes/order_success.php';
    require_once __DIR__ . '/../includes/footer.php';
    ?>
</body>

</html>