<?php
require_once __DIR__ . '/../config/connection.php';

$slides = [];

try {
    $stmt = $conn->prepare("
        SELECT title, description, image_url, link_url
        FROM slides
        WHERE is_active = 1
        ORDER BY display_order ASC
    ");
    $stmt->execute();
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $slides = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Hero Slider</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .dot-active {
            background-color: white;
        }
    </style>
</head>

<body class="bg-white">
    <?php if (!empty($slides)): ?>
        <div class="max-w-7xl mx-auto relative overflow-hidden">
            <!-- Slides Track -->
            <div id="sliderTrack" class="flex transition-transform duration-700 ease-in-out">

                <?php foreach ($slides as $i => $slide): ?>
                    <?php
                    $title = htmlspecialchars($slide['title']);
                    $desc  = htmlspecialchars($slide['description']);
                    $img   = htmlspecialchars($slide['image_url']);
                    $link  = htmlspecialchars($slide['link_url']);
                    ?>

                    <div class="min-w-full relative h-[520px] md:h-[620px] lg:h-[700px]">

                        <!-- Media (Image or Video) -->
                        <?php $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION)); ?>
                        <?php if ($ext === 'mp4'): ?>
                            <video class="absolute inset-0 w-full h-full object-cover" autoplay muted loop playsinline>
                                <source src="<?= $img ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php else: ?>
                            <img src="<?= $img ?>" class="absolute inset-0 w-full h-full object-cover">
                        <?php endif; ?>

                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>

                        <!-- Content -->
                        <div class="absolute inset-0 flex items-center">
                            <div class="px-8 md:px-16 max-w-4xl text-white">

                                <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold uppercase leading-none">
                                    <?= $title ?>
                                </h1>

                                <?php if ($desc): ?>
                                    <p class="mt-4 max-w-xl text-lg text-white/90">
                                        <?= $desc ?>
                                    </p>
                                <?php endif; ?>

                                <div class="mt-8 flex items-center gap-4">
                                    <a href="<?= $link ?>" class="px-6 py-3 bg-white text-black font-semibold rounded-full hover:bg-gray-100">
                                        Shop
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <!-- Bottom Dots -->
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2">
                <?php foreach ($slides as $i => $s): ?>
                    <button data-index="<?= $i ?>"
                        class="dot w-2.5 h-2.5 rounded-full bg-white/40 <?= $i === 0 ? 'dot-active' : '' ?>">
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Right Controls -->
            <div class="absolute bottom-6 right-6 flex items-center gap-3">

                <button id="playPause"
                    class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center">
                    <i id="playPauseIcon" class="fas fa-pause"></i>
                </button>

                <button id="prevBtn"
                    class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <button id="nextBtn"
                    class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center">
                    <i class="fas fa-chevron-right"></i>
                </button>

            </div>

        </div>

    <?php endif; ?>
    <script src="../view/assets/Js/slide.js"></script>
</body>

</html>