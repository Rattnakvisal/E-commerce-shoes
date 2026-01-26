<?php
require_once __DIR__ . '/slides_api.php';
$queryBase = $_GET ?? [];
unset($queryBase['status'], $queryBase['page']);

$currentStatus = (string)($_GET['status'] ?? '');

$tabs = [
    [
        'label'      => 'All Slides',
        'status'     => '',
        'countKey'   => 'all',
        'pill'       => 'bg-gray-100 text-gray-600',
        'activeText' => 'text-indigo-600',
    ],
    [
        'label'      => 'Active',
        'status'     => 'active',
        'countKey'   => 'active',
        'pill'       => 'bg-green-100 text-green-700',
        'activeText' => 'text-green-600',
    ],
    [
        'label'      => 'Inactive',
        'status'     => 'inactive',
        'countKey'   => 'inactive',
        'pill'       => 'bg-gray-100 text-gray-700',
        'activeText' => 'text-gray-700',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slides Management</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../../../assets/Css/slide.css">
    <link rel="stylesheet" href="../../../assets/Css/reports.css">
    <style>
        /* Fade-in animation */
        .animate-fade-in {
            animation: fadeIn 0.35s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Include Admin Navbar -->
    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>
    <main class="md:ml-64 min-h-screen animate-fade-in">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="mb-6 ">
                <div class="flex flex-col lg:flex-row mb-6 lg:items-center lg:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900">Slides <span class="gradient-text font-extrabold">Management</span></h1>
                        </div>
                        <p class="text-gray-600 ml-1">Manage and track all slides in your store.</p>
                    </div>
                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <button
                            onclick="openAddModal()"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg
                       hover:bg-indigo-700 transition">
                            <i class="fas fa-plus mr-2"></i>
                            Add Slide
                        </button>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 fade-in-up">

                    <!-- Total Slides -->
                    <div class="stat-card bg-gradient-to-br from-white to-blue-50/50 rounded-2xl p-6 shadow-soft-xl border border-blue-100/50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <p class="text-sm text-gray-500">Total Slides</p>
                                <p class="text-2xl font-bold mt-2 text-gray-900">
                                    <?= number_format((int)($statusCounts['all'] ?? 0)) ?>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-images text-xl"></i>
                            </div>
                        </div>

                        <div class="mt-4 relative z-10">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <div>All slides</div>
                                <div>100%</div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Slides -->
                    <div class="stat-card bg-gradient-to-br from-white to-green-50/50 rounded-2xl p-6 shadow-soft-xl border border-green-100/50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <p class="text-sm text-gray-500">Active Slides</p>
                                <p class="text-2xl font-bold mt-2 text-gray-900">
                                    <?= number_format((int)($statusCounts['active'] ?? 0)) ?>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-eye text-xl"></i>
                            </div>
                        </div>

                        <div class="mt-4 relative z-10">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <div>Visible on site</div>
                                <div>
                                    <?= round((($statusCounts['active'] ?? 0) / max(($statusCounts['all'] ?? 1), 1)) * 100, 1) ?>%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inactive Slides -->
                    <div class="stat-card bg-gradient-to-br from-white to-yellow-50/50 rounded-2xl p-6 shadow-soft-xl border border-yellow-100/50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-yellow-500/5 rounded-full -translate-y-10 translate-x-10"></div>

                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div>
                                <p class="text-sm text-gray-500">Inactive Slides</p>
                                <p class="text-2xl font-bold mt-2 text-gray-900">
                                    <?= number_format((int)($statusCounts['inactive'] ?? 0)) ?>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-3 rounded-xl shadow-md">
                                <i class="fas fa-eye-slash text-xl"></i>
                            </div>
                        </div>

                        <div class="mt-4 relative z-10">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                <div>
                                    <?= ($statusCounts['inactive'] ?? 0) > 0 ? 'Hidden slides' : 'All active' ?>
                                </div>
                                <div>
                                    <?= round((($statusCounts['inactive'] ?? 0) / max(($statusCounts['all'] ?? 1), 1)) * 100, 1) ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white">
                    <div class="border-b border-gray-200">
                        <nav class="flex gap-6 px-6 py-4 overflow-x-auto">

                            <?php foreach ($tabs as $t): ?>
                                <?php
                                $isActive = ($t['status'] === $currentStatus);

                                $href = '?' . http_build_query(array_merge(
                                    $queryBase,
                                    ['status' => $t['status']]
                                ));

                                $linkClass = $isActive
                                    ? "{$t['activeText']} border-b-2 border-indigo-600"
                                    : "text-gray-500 hover:text-gray-700
                                    border-b-2 border-transparent
                                    transition-all duration-200";

                                $count = (int)($statusCounts[$t['countKey']] ?? 0);
                                ?>

                                <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                                    class="flex items-center gap-2 pb-2 text-sm font-medium <?= $linkClass ?>">
                                    <?= htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') ?>

                                    <span class="px-2 py-0.5 rounded-full text-xs <?= $t['pill'] ?>">
                                        <?= $count ?>
                                    </span>
                                </a>

                            <?php endforeach; ?>

                        </nav>
                    </div>
                </div>

                <!-- Slides Filters (products-style) -->
                <form method="GET" class="bg-white rounded-xl shadow mb-8 p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 items-end">

                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Search</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </span>
                                <input
                                    type="text"
                                    name="q"
                                    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                                    placeholder="Search by title or description..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Status</label>
                            <select
                                name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Status</option>
                                <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <!-- Display Order -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Display Order</label>
                            <select
                                name="order"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Display Order</option>
                                <option value="asc" <?= ($_GET['order'] ?? '') === 'asc' ? 'selected' : '' ?>>Lowest First</option>
                                <option value="desc" <?= ($_GET['order'] ?? '') === 'desc' ? 'selected' : '' ?>>Highest First</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 justify-end lg:col-span-2">
                            <a href="slides.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">Clear</a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Apply</button>
                        </div>
                    </div>
                </form>

                <?php if ($totalSlides > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b">
                                    <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">ID</th>
                                    <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">TITLE</th>
                                    <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">ORDER</th>
                                    <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">BUTTON</th>
                                    <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">STATUS</th>
                                    <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">IMAGE</th>
                                    <th class="py-3 px-6 text-left text-sm font-medium text-gray-700">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($slides as $slide): ?>
                                    <tr class="table-row">
                                        <td class="py-4 px-6">
                                            <span class="text-sm font-medium text-gray-900"><?php echo $slide['slides_id']; ?></span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="max-w-xs">
                                                <div class="font-medium text-gray-900 truncate">
                                                    <?php echo htmlspecialchars($slide['title']); ?>
                                                </div>
                                                <?php if (!empty($slide['description'])): ?>
                                                    <div class="text-sm text-gray-500 truncate mt-1">
                                                        <?php echo htmlspecialchars(substr($slide['description'], 0, 60)); ?>
                                                        <?php if (strlen($slide['description']) > 60): ?>...<?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">
                                                <?php echo $slide['display_order']; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?php if (!empty($slide['button_text'])): ?>
                                                <span class="text-sm text-blue-600 font-medium">
                                                    <?php echo htmlspecialchars($slide['button_text']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-sm text-gray-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <a href="?toggle=<?php echo $slide['slides_id']; ?>"
                                                class="status-badge <?php echo $slide['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $slide['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </a>
                                        </td>
                                        <td class="py-4 px-6">
                                            <img src="<?php echo $slide['image_url']; ?>"
                                                alt="<?php echo htmlspecialchars($slide['title']); ?>"
                                                class="thumbnail">
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex space-x-2">
                                                <button onclick="confirmEditSlide(
                                                <?php echo $slide['slides_id']; ?>,
                                                '<?php echo addslashes($slide['title']); ?>',
                                                '<?php echo addslashes($slide['description']); ?>',
                                                '<?php echo addslashes($slide['link_url']); ?>',
                                                '<?php echo addslashes($slide['button_text']); ?>',
                                                <?php echo $slide['display_order']; ?>,
                                                <?php echo $slide['is_active']; ?>,
                                                '<?php echo addslashes($slide['image_url']); ?>'
                                            )"
                                                    class="inline-flex items-center px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 text-sm hover-lift">
                                                    <i class="fas fa-edit mr-2"></i> Edit
                                                </button>
                                                <a href="?delete=<?php echo $slide['slides_id']; ?>"
                                                    onclick="return confirmDelete('<?php echo addslashes($slide['title']); ?>')"
                                                    class="inline-flex items-center px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 text-sm hover-lift">
                                                    <i class="fas fa-trash mr-2"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Table Footer -->
                    <div class="px-6 py-4 border-t bg-gray-50">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo $totalSlides; ?> slide<?php echo $totalSlides !== 1 ? 's' : ''; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="py-12 text-center">
                        <i class="fas fa-images text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No slides found</h3>
                        <p class="text-gray-500 mb-4">Get started by adding your first slide</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Overlay -->
    <div id="modalOverlay" class="fixed inset-0 bg-black/40 hidden z-40"></div>
    <!-- Slide Modal -->
    <div id="slideModal" class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-y-auto">
        <div class="bg-white w-full max-w-2xl mx-4 rounded-xl shadow-xl animate-fade-in">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-plus mr-2 text-indigo-600"></i> Add Slide
                </h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Body -->
            <form id="slideForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="slide_id" id="slideId">
                <input type="hidden" name="old_image" id="oldImage">

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left column: fields -->
                    <div class="space-y-5">
                        <!-- Title -->
                        <div>
                            <label for="modalTitleInput" class="block text-sm font-medium text-gray-700 mb-1">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="modalTitleInput"
                                name="title"
                                type="text"
                                required
                                placeholder="Enter slide title"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="modalDescription" class="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea
                                id="modalDescription"
                                name="description"
                                rows="3"
                                placeholder="Enter slide description"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>

                        <!-- Link & Button -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="modalLinkUrl" class="block text-sm font-medium text-gray-700 mb-1">
                                    Link URL
                                </label>
                                <input
                                    id="modalLinkUrl"
                                    name="link_url"
                                    type="url"
                                    placeholder="https://example.com"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="modalButtonText" class="block text-sm font-medium text-gray-700 mb-1">
                                    Button Text
                                </label>
                                <input
                                    id="modalButtonText"
                                    name="button_text"
                                    type="text"
                                    placeholder="Learn More"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Order & Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="modalDisplayOrder" class="block text-sm font-medium text-gray-700 mb-1">
                                    Display Order
                                </label>
                                <input
                                    id="modalDisplayOrder"
                                    name="display_order"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="modalIsActive" class="block text-sm font-medium text-gray-700 mb-1">
                                    Status
                                </label>
                                <select
                                    id="modalIsActive"
                                    name="is_active"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Right column: current image, upload, preview, actions -->
                    <div>
                        <!-- Current Image -->
                        <div id="currentImageContainer" class="hidden">
                            <p class="text-sm text-gray-600 mb-2">Current Image</p>
                            <img id="currentImagePreview" class="w-full h-64 object-cover rounded-lg border-2 border-gray-300">
                            <video id="currentVideoPreview" class="w-full h-64 object-cover rounded-lg border-2 border-gray-300 hidden" controls muted playsinline></video>
                        </div>

                        <!-- Upload -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Slide Image</label>

                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition">
                                <input
                                    id="imageUpload"
                                    name="image"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.gif,.webp"
                                    onchange="previewNewImage(this)"
                                    class="hidden">
                                <label for="imageUpload" class="cursor-pointer">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 mb-3 flex items-center justify-center bg-indigo-50 rounded-full">
                                            <i class="fas fa-cloud-upload-alt text-indigo-600 text-xl"></i>
                                        </div>
                                        <p class="text-sm font-medium text-gray-700 mb-1">Upload Image</p>
                                        <p class="text-xs text-gray-500">Click to browse or drag and drop</p>
                                        <p class="text-xs text-gray-400 mt-2">JPG, PNG, GIF, WebP up to 5MB</p>
                                    </div>
                                </label>
                            </div>

                            <p class="text-xs text-gray-500 mt-2">Max size: 5MB. JPG, PNG, GIF, WebP</p>
                        </div>

                        <!-- New Preview -->
                        <div id="newImageContainer" class="hidden mt-4">
                            <p class="text-sm text-gray-600 mb-2">New Image Preview</p>
                            <img id="newImagePreview" class="w-full h-64 object-cover rounded-lg border-2 border-gray-300">
                            <video id="newVideoPreview" class="w-full h-64 object-cover rounded-lg border-2 border-gray-300 hidden" controls muted playsinline></video>
                        </div>

                        <!-- Actions -->
                        <div class="mt-6 md:mt-12 flex justify-end gap-3">
                            <button type="button"
                                onclick="closeModal()"
                                class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-100">
                                Cancel
                            </button>
                            <button type="submit"
                                name="save_slide"
                                class="px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                                Save Slide
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script src="../../../assets/Js/slides.js"></script>
    <script src="../../../assets/js/reports.js"></script>
    <?php if (!empty($flash)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var _type = '<?php echo isset($flash['type']) ? addslashes($flash['type']) : ''; ?>';
                var _text = '<?php echo isset($flash['text']) ? addslashes($flash['text']) : ''; ?>';
                if (_type === 'success') {
                    if (typeof showSuccess === 'function') showSuccess(_text);
                    else Swal.fire({
                        icon: 'success',
                        title: _text,
                        timer: 1200,
                        showConfirmButton: false
                    });
                } else {
                    if (typeof showError === 'function') showError(_text);
                    else Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: _text
                    });
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>