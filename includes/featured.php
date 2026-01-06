<?php
require_once __DIR__ . '/../config/conn.php';

$featured = [];
try {
    $stmt = $conn->prepare("
        SELECT f.*, p.name AS product_name
        FROM featured_items f
        LEFT JOIN products p ON f.product_id = p.product_id
        WHERE f.is_active = 1
        ORDER BY f.position ASC
        LIMIT 8
    ");
    $stmt->execute();
    $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $featured = [];
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-p+1mYk0..." crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.tailwindcss.com"></script>

<?php if (!empty($featured)): ?>
    <section class="w-full py-10">

        <div class="max-w-7xl mx-auto">
            <!-- Horizontal Scroll -->
            <div class="flex gap-6 overflow-x-auto snap-x snap-mandatory scroll-smooth
                    scrollbar-thin scrollbar-thumb-gray-300">

                <?php foreach ($featured as $item):

                    $title = htmlspecialchars($item['title'] ?: $item['product_name']);
                    $img   = htmlspecialchars($item['image_url']);
                    $link  = "../view/products.php?product_id=" . urlencode($item['product_id']);
                ?>

                    <a href="<?= $link ?>"
                        class="group relative flex-none
                      w-[280px] sm:w-[340px] lg:w-[460px]
                      h-[420px] sm:h-[480px] lg:h-[640px]
                      snap-start overflow-hidden">

                        <!-- Image -->
                        <img src="<?= $img ?>"
                            alt="<?= $title ?>"
                            class="absolute inset-0 w-full h-full object-cover
                            transition-transform duration-700
                            group-hover:scale-105">

                        <!-- Subtle dark overlay -->
                        <div class="absolute inset-0 bg-black/10 group-hover:bg-black/20 transition"></div>

                        <!-- CTA -->
                        <div class="absolute bottom-6 left-6">
                            <span class="inline-flex items-center
                                 bg-white text-black
                                 text-sm font-medium
                                 px-5 py-2 rounded-full
                                 shadow">
                                <?= $title ?>
                            </span>
                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        </div>
    </section>
<?php endif; ?>