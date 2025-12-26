<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['wishlist'] ??= [];
$_SESSION['cart'] ??= [];

/* =========================
   ACTIONS (AJAX)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['product_id'] ?? 0);

    if ($action === 'add' && $id > 0) {
        $_SESSION['wishlist'][$id] = time();
    }

    if ($action === 'remove' && $id > 0) {
        unset($_SESSION['wishlist'][$id]);
    }

    if ($action === 'move_to_cart' && $id > 0) {
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
        unset($_SESSION['wishlist'][$id]);
    }

    if ($action === 'clear') {
        $_SESSION['wishlist'] = [];
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => count($_SESSION['wishlist'])
    ]);
    exit;
}

/* =========================
   FETCH PRODUCTS
========================= */
$wishlist = $_SESSION['wishlist'];
$products = [];

if ($wishlist) {
    $ids = implode(',', array_keys($wishlist));
    $stmt = $pdo->query(
        "SELECT product_id, name, price, image_url, stock
         FROM products
         WHERE product_id IN ($ids)"
    );
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Wishlist</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-white">

    <?php require_once '../includes/navbar.php'; ?>

    <main class="max-w-7xl mx-auto px-6 py-10">

        <!-- HEADER -->
        <div class="mb-10">
            <h1 class="text-2xl font-semibold">Favorites</h1>
            <p class="text-gray-500 mt-1">
                <?= count($products) ?> item<?= count($products) !== 1 ? 's' : '' ?>
            </p>
        </div>

        <?php if (!$products): ?>

            <!-- EMPTY STATE -->
            <div class="text-center py-20">
                <i class="far fa-heart text-5xl mb-6"></i>
                <h2 class="text-xl font-medium mb-3">Your wishlist is empty</h2>
                <p class="text-gray-500 mb-8">Save items you like for later.</p>

                <a href="products.php"
                    class="inline-block border border-black px-8 py-3 font-semibold">
                    Start Shopping
                </a>
            </div>

        <?php else: ?>

            <!-- GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">

                <?php foreach ($products as $p): ?>

                    <div class="group">

                        <!-- IMAGE -->
                        <div class="bg-gray-100 aspect-square flex items-center justify-center relative">
                            <img src="<?= htmlspecialchars($p['image_url']) ?>"
                                alt="<?= htmlspecialchars($p['name']) ?>"
                                class="w-full h-full object-cover group-hover:scale-105 transition">

                            <!-- REMOVE -->
                            <button onclick="removeItem(<?= $p['product_id'] ?>)"
                                class="absolute top-3 right-3 w-9 h-9 bg-white rounded-full flex items-center justify-center shadow">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- INFO -->
                        <div class="mt-4">
                            <h3 class="font-medium"><?= htmlspecialchars($p['name']) ?></h3>
                            <p class="text-gray-500 text-sm mt-1">$<?= number_format((float)$p['price'], 2) ?></p>
                        </div>

                        <!-- ACTION -->
                        <button
                            onclick="moveToCart(<?= $p['product_id'] ?>)"
                            <?= $p['stock'] <= 0 ? 'disabled' : '' ?>
                            class="mt-3 w-full border border-black py-2 text-sm font-semibold
               hover:bg-black hover:text-white transition
               <?= $p['stock'] <= 0 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                            Move to Bag
                        </button>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

    <script>
        function removeItem(id) {
            fetch('wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'remove',
                    product_id: id
                })
            }).then(() => location.reload());
        }

        function moveToCart(id) {
            fetch('wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'move_to_cart',
                    product_id: id
                })
            }).then(() => location.reload());
        }
    </script>

</body>

</html>