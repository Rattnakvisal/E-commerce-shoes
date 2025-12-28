<?php
require_once __DIR__ . '/slides_api.php';
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
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Include Admin Navbar -->
    <?php require_once __DIR__ . '/../../../admin/include/navbar.php'; ?>
    <main class="md:ml-64 p-4 md:p-6">
        <!-- Page Header -->
        <div class="mb-6 animate-fade-in">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                <!-- Title -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Slides Management
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Manage your home page slides
                    </p>
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

                    <button
                        onclick="refreshData()"
                        title="Refresh"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg
                       hover:bg-gray-200 transition">
                        <i class="fas fa-sync-alt"></i>
                    </button>

                </div>
            </div>
        </div>


        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-images text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Slides</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalSlidesAll; ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-eye text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Active Slides</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $activeSlidesAll; ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-yellow-100 text-yellow-600 mr-4">
                        <i class="fas fa-eye-slash text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Inactive Slides</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $inactiveSlidesAll; ?></p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Slides Filters -->
        <div class="bg-white rounded-xl shadow mb-6 animate-fade-in">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Filter Slides
                </h3>

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <!-- Search -->
                    <form method="GET" class="flex-1 max-w-md">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </span>
                            <input
                                type="text"
                                name="q"
                                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                                placeholder="Search by title or description..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </form>

                    <!-- Filters -->
                    <div class="flex flex-wrap items-center gap-3">

                        <!-- Status -->
                        <select
                            name="status"
                            class="px-3 py-2 border border-gray-300 rounded-lg
                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Status</option>
                            <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>
                        </select>

                        <!-- Display Order -->
                        <select
                            name="order"
                            class="px-3 py-2 border border-gray-300 rounded-lg
                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Display Order</option>
                            <option value="asc" <?= ($_GET['order'] ?? '') === 'asc' ? 'selected' : '' ?>>
                                Lowest First
                            </option>
                            <option value="desc" <?= ($_GET['order'] ?? '') === 'desc' ? 'selected' : '' ?>>
                                Highest First
                            </option>
                        </select>

                        <!-- Actions -->
                        <button
                            type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg
                           hover:bg-indigo-700 transition">
                            Apply
                        </button>

                        <a
                            href="slides.php"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg
                           hover:bg-gray-200 transition">
                            Clear
                        </a>
                    </div>
                </div>
            </div>
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
                                            <button onclick="openEditModal(
                                                <?php echo $slide['slides_id']; ?>,
                                                '<?php echo addslashes($slide['title']); ?>',
                                                '<?php echo addslashes($slide['description']); ?>',
                                                '<?php echo addslashes($slide['link_url']); ?>',
                                                '<?php echo addslashes($slide['button_text']); ?>',
                                                <?php echo $slide['display_order']; ?>,
                                                <?php echo $slide['is_active']; ?>,
                                                '<?php echo addslashes($slide['image_url']); ?>'
                                            )"
                                                class="text-indigo-600 p-2 hover:text-indigo-900 transition">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $slide['slides_id']; ?>"
                                                onclick="return confirmDelete('<?php echo addslashes($slide['title']); ?>')"
                                                class="text-red-600 p-2 hover:text-red-900 transition">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
        </div>
    </main>
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
</main>

<!-- Modal Overlay -->
<div id="modalOverlay" class="fixed inset-0 bg-black/40 hidden z-40"></div>
<!-- Slide Modal -->
<div id="slideModal"
    class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-y-auto">

    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 animate-fade-in">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">
                Add Slide
            </h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <!-- Modal Body -->
        <form method="POST" enctype="multipart/form-data" id="slideForm">
            <input type="hidden" name="slide_id" id="slideId">
            <input type="hidden" name="old_image" id="oldImage">

            <div class="p-6 space-y-5">

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Title *
                    </label>
                    <input type="text"
                        name="title"
                        id="modalTitleInput"
                        required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2
                        focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Enter slide title">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <textarea name="description"
                        id="modalDescription"
                        rows="3"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2
                        focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Enter slide description"></textarea>
                </div>

                <!-- Link & Button Text -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Link URL
                        </label>
                        <input type="url"
                            name="link_url"
                            id="modalLinkUrl"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2
                            focus:ring-2 focus:ring-indigo-500"
                            placeholder="https://example.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Button Text
                        </label>
                        <input type="text"
                            name="button_text"
                            id="modalButtonText"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2
                            focus:ring-2 focus:ring-indigo-500"
                            placeholder="Learn More">
                    </div>
                </div>

                <!-- Display Order & Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Display Order
                        </label>
                        <input type="number"
                            name="display_order"
                            id="modalDisplayOrder"
                            min="1"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2
                            focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>
                        <select name="is_active"
                            id="modalIsActive"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2
                            focus:ring-2 focus:ring-indigo-500">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Current Image -->
                <div id="currentImageContainer" class="hidden">
                    <p class="text-sm text-gray-600 mb-2">Current Image</p>
                    <img id="currentImagePreview"
                        class="rounded-lg border max-h-40">
                </div>

                <!-- Image Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Slide Image
                    </label>

                    <input type="file"
                        name="image"
                        id="imageUpload"
                        accept=".jpg,.jpeg,.png,.gif,.webp"
                        onchange="previewNewImage(this)"
                        class="block w-full text-sm text-gray-600
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-lg file:border-0
                        file:text-sm file:font-semibold
                        file:bg-indigo-50 file:text-indigo-600
                        hover:file:bg-indigo-100">

                    <p class="text-xs text-gray-500 mt-1">
                        Max size: 5MB. JPG, PNG, GIF, WebP
                    </p>
                </div>

                <!-- New Image Preview -->
                <div id="newImageContainer" class="hidden">
                    <p class="text-sm text-gray-600 mb-2">New Image Preview</p>
                    <img id="newImagePreview"
                        class="rounded-lg border max-h-40">
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-xl">
                <button type="button"
                    onclick="closeModal()"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">
                    Cancel
                </button>

                <button type="submit"
                    name="save_slide"
                    class="px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                    Save Slide
                </button>
            </div>
        </form>
    </div>
</div>
<script src="/assets/Js/notifications.js"></script>
<script>
    // Modal Functions
    function openAddModal() {
        document.getElementById('modalTitle').innerHTML = ' Add New Slide';
        document.getElementById('slideId').value = '0';
        document.getElementById('oldImage').value = '';
        document.getElementById('modalTitleInput').value = '';
        document.getElementById('modalDescription').value = '';
        document.getElementById('modalLinkUrl').value = '';
        document.getElementById('modalButtonText').value = '';
        document.getElementById('modalDisplayOrder').value = '<?php echo $totalSlides + 1; ?>';
        const statusEl = document.getElementById('modalIsActive');
        if (statusEl) statusEl.value = '1';

        // Hide current image
        document.getElementById('currentImageContainer').classList.add('hidden');
        document.getElementById('newImageContainer').classList.add('hidden');

        openModal();
    }

    function openEditModal(id, title, description, linkUrl, buttonText, displayOrder, isActive, imageUrl) {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit mr-2 text-yellow-600"></i> Edit Slide';
        document.getElementById('slideId').value = id;
        document.getElementById('oldImage').value = imageUrl;
        document.getElementById('modalTitleInput').value = title;
        document.getElementById('modalDescription').value = description;
        document.getElementById('modalLinkUrl').value = linkUrl;
        document.getElementById('modalButtonText').value = buttonText;
        document.getElementById('modalDisplayOrder').value = displayOrder;
        const statusEl = document.getElementById('modalIsActive');
        if (statusEl) statusEl.value = isActive == 1 ? '1' : '0';

        // Show current image
        if (imageUrl) {
            document.getElementById('currentImagePreview').src = imageUrl;
            document.getElementById('currentImageContainer').classList.remove('hidden');
        } else {
            document.getElementById('currentImageContainer').classList.add('hidden');
        }

        // Hide new image preview
        document.getElementById('newImageContainer').classList.add('hidden');

        openModal();
    }

    function openModal() {
        const slideModal = document.getElementById('slideModal');
        const overlay = document.getElementById('modalOverlay');
        if (slideModal) {
            slideModal.classList.remove('hidden');
            slideModal.classList.add('flex');
        }
        if (overlay) {
            overlay.classList.remove('hidden');
        }
        // prevent background scroll
        document.documentElement.style.overflow = 'hidden';
    }

    function closeModal() {
        const slideModal = document.getElementById('slideModal');
        const overlay = document.getElementById('modalOverlay');
        if (slideModal) {
            slideModal.classList.add('hidden');
            slideModal.classList.remove('flex');
        }
        if (overlay) {
            overlay.classList.add('hidden');
        }
        document.documentElement.style.overflow = '';
        const img = document.getElementById('imageUpload');
        if (img) img.value = '';
    }

    // Close modal when clicking overlay
    document.getElementById('modalOverlay').addEventListener('click', closeModal);

    // Image preview
    function previewNewImage(input) {
        const newImageContainer = document.getElementById('newImageContainer');
        const newImagePreview = document.getElementById('newImagePreview');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                newImagePreview.src = e.target.result;
                newImageContainer.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            newImageContainer.classList.add('hidden');
        }
    }

    // Delete confirmation
    function confirmDelete(title) {
        event.preventDefault();
        const url = event.currentTarget.href;

        Swal.fire({
            title: 'Delete Slide?',
            html: `Are you sure you want to delete <strong>"${title}"</strong>?<br><br>This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });

        return false;
    }

    // Form validation
    document.getElementById('slideForm').addEventListener('submit', function(e) {
        const title = document.getElementById('modalTitleInput').value.trim();
        const image = document.getElementById('imageUpload').files.length;
        const slideId = document.getElementById('slideId').value;
        const isEditMode = slideId !== '0';

        if (!title) {
            e.preventDefault();
            Swal.fire({
                title: 'Missing Title',
                text: 'Please enter a slide title',
                icon: 'warning',
                confirmButtonColor: '#3b82f6'
            });
            return false;
        }

        if (!isEditMode && !image) {
            e.preventDefault();
            Swal.fire({
                title: 'Missing Image',
                text: 'Please upload an image for the slide',
                icon: 'warning',
                confirmButtonColor: '#3b82f6'
            });
            return false;
        }

        // Show loading animation
        Swal.fire({
            title: 'Saving...',
            text: 'Please wait while we save your slide',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    });

    // Small UI helpers used by refresh and other actions
    function showLoading(message = 'Loading...') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: message,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        } else {
            console.log('Loading:', message);
        }
    }

    function showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#3b82f6'
            });
        } else {
            console.error(message);
        }
    }

    function showToast(message, icon = 'success') {
        if (typeof Swal !== 'undefined') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: icon,
                title: message
            });
        } else {
            console.log(message);
        }
    }

    /* =====================================================
       REFRESH FUNCTION
    ===================================================== */
    function refreshData() {
        try {
            showLoading('Refreshing data...');
            // mark for post-reload success message
            localStorage.setItem('menu_refreshed', '1');
            setTimeout(() => {
                window.location.reload();
            }, 150);
        } catch (error) {
            Swal.close();
            showError('Failed to refresh data');
            console.error('Refresh error:', error);
        }
    }

    // After reload, show a short toast if refresh was requested
    document.addEventListener('DOMContentLoaded', () => {
        try {
            if (localStorage.getItem('menu_refreshed')) {
                localStorage.removeItem('menu_refreshed');
                showToast('Data refreshed!', 'success');
            }
        } catch (e) {
            // ignore
        }
    });


    // Flash message handling
    <?php if (isset($_SESSION['flash_message'])): ?>
        Swal.fire({
            icon: '<?php echo $_SESSION['flash_message']['type']; ?>',
            title: '<?php echo $_SESSION['flash_message']['type'] === 'success' ? 'Success!' : 'Error!'; ?>',
            text: '<?php echo addslashes($_SESSION['flash_message']['text']); ?>',
            confirmButtonColor: '#3b82f6',
            timer: 3000,
            timerProgressBar: true
        });
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    // Error handling
    <?php if (!empty($errors)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            html: '<?php echo implode("<br>", array_map('addslashes', $errors)); ?>',
            confirmButtonColor: '#3b82f6'
        });
    <?php endif; ?>
</script>
</body>

</html>