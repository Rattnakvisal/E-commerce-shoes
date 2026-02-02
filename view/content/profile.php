<?php
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../../includes/contract/profile_contract.php';

$user = $user ?? [];

/* Flash message */
$flash = (string)($_SESSION['message'] ?? '');
unset($_SESSION['message']);

/* Detect error message */
$isError = false;
if ($flash) {
    $low = strtolower($flash);
    $isError = str_contains($low, 'invalid')
        || str_contains($low, 'error')
        || str_contains($low, 'failed')
        || str_contains($low, 'incorrect')
        || str_contains($low, 'required')
        || str_contains($low, 'match')
        || str_contains($low, 'choose')
        || str_contains($low, 'missing')
        || str_contains($low, 'large');
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .shadow-soft {
            box-shadow: 0 10px 30px rgba(0, 0, 0, .06)
        }

        .glass {
            background: rgba(255, 255, 255, .75);
            backdrop-filter: blur(10px)
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900">

    <?php
    require_once __DIR__ . '/../../includes/shader/topbar.php';
    require_once __DIR__ . '/../../includes/shader/navbar.php';
    ?>

    <main class="max-w-6xl mx-auto px-4 py-10 sm:py-12">

        <!-- Header -->
        <section class="rounded-3xl overflow-hidden border shadow-soft bg-gradient-to-br from-black via-gray-900 to-gray-800">
            <div class="p-6 sm:p-8 lg:p-12 text-white">
                <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-gray-300">Account</p>
                        <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight mt-2">My Profile</h1>
                        <p class="text-gray-300 mt-2">Manage your account, address and password</p>
                    </div>
                    <a href="/E-commerce-shoes/view/content/index.php"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-full bg-white text-black text-sm font-extrabold hover:bg-gray-100">
                        <i class="fas fa-house mr-2"></i> Home
                    </a>
                </div>
            </div>
        </section>

        <!-- Flash -->
        <?php if ($flash): ?>
            <div class="mt-6 rounded-2xl border bg-white shadow-soft p-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center
                    <?= $isError ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' ?>">
                        <i class="fas <?= $isError ? 'fa-triangle-exclamation' : 'fa-check' ?>"></i>
                    </div>
                    <div>
                        <p class="font-extrabold"><?= $isError ? 'Notice' : 'Success' ?></p>
                        <p class="text-sm text-gray-600 mt-1"><?= e($flash) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <section class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left: Profile card -->
            <div class="bg-white border rounded-3xl shadow-soft overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <?php
                        $avatar = (string)($user['avatar_url'] ?? '');
                        $nameForInitial = (string)($user['name'] ?? 'User');
                        $initial = strtoupper(substr($nameForInitial ?: 'U', 0, 1));
                        ?>

                        <?php if ($avatar): ?>
                            <img id="avatarPreview" src="<?= e($avatar) ?>"
                                class="w-16 h-16 rounded-2xl object-cover border" alt="Avatar">
                        <?php else: ?>
                            <div id="avatarFallback"
                                class="w-16 h-16 rounded-2xl bg-gray-100 border flex items-center justify-center text-2xl font-extrabold">
                                <?= e($initial) ?>
                            </div>
                        <?php endif; ?>

                        <div class="min-w-0">
                            <p class="text-lg font-extrabold truncate"><?= e($user['name'] ?? 'User') ?></p>
                            <p class="text-sm text-gray-500 truncate"><?= e($user['email'] ?? '') ?></p>
                        </div>
                    </div>

                    <!-- ✅ ID + Email + Phone + Address -->
                    <div class="mt-6 space-y-3 text-sm">

                        <!-- ID -->
                        <div class="flex items-center justify-between gap-3 rounded-2xl border bg-gray-50 px-4 py-3">
                            <div class="flex items-center gap-3 text-gray-700">
                                <i class="fas fa-id-badge text-gray-400 w-5"></i>
                                <span class="font-semibold">Member ID</span>
                            </div>
                            <span class="font-extrabold text-gray-900">#<?= e($user['user_id'] ?? '') ?></span>
                        </div>

                        <!-- Email -->
                        <div class="flex items-center justify-between gap-3 rounded-2xl border bg-gray-50 px-4 py-3">
                            <div class="flex items-center gap-3 text-gray-700 min-w-0">
                                <i class="fas fa-envelope text-gray-400 w-5"></i>
                                <span class="font-semibold">Email</span>
                            </div>
                            <span class="text-gray-900 font-semibold truncate max-w-[220px]">
                                <?= e($user['email'] ?? '—') ?>
                            </span>
                        </div>

                        <!-- Phone -->
                        <div class="flex items-center justify-between gap-3 rounded-2xl border bg-gray-50 px-4 py-3">
                            <div class="flex items-center gap-3 text-gray-700">
                                <i class="fas fa-phone text-gray-400 w-5"></i>
                                <span class="font-semibold">Phone</span>
                            </div>
                            <span class="text-gray-900 font-semibold">
                                <?= e($user['phone'] ?? '—') ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3 rounded-2xl border bg-gray-50 px-4 py-3">
                            <div class="flex items-center gap-3 text-gray-700">
                                <i class="fas fa-phone text-gray-400 w-5"></i>
                                <span class="font-semibold">Address</span>
                            </div>
                            <span class="text-gray-900 font-semibold">
                                <?= e($user['address'] ?? '—') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Avatar upload -->
                <div class="border-t p-6">
                    <form action="/E-commerce-shoes/view/actions/profile_update.php" method="post"
                        enctype="multipart/form-data" class="space-y-3">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="action" value="avatar">

                        <label class="block text-sm font-extrabold">Change Avatar</label>

                        <input id="avatarInput" type="file" name="avatar" accept="image/*"
                            class="block w-full text-sm
                           file:mr-4 file:py-2 file:px-4 file:rounded-full
                           file:border-0 file:bg-gray-100 file:text-gray-900
                           hover:file:bg-gray-200">

                        <p class="text-xs text-gray-500">JPG / PNG / WEBP • Max 2MB</p>

                        <button class="w-full px-4 py-3 rounded-full bg-black text-white font-extrabold hover:bg-gray-900">
                            Upload
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Forms -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Update Profile -->
                <div class="bg-white border rounded-3xl shadow-soft">
                    <div class="p-6 sm:p-8">
                        <h2 class="text-xl font-extrabold">Profile Information</h2>
                        <p class="text-sm text-gray-600 mt-1">Update your personal details.</p>

                        <form action="/E-commerce-shoes/view/actions/profile_update.php" method="post"
                            class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4"
                            autocomplete="on">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="action" value="profile">

                            <div class="sm:col-span-2">
                                <label for="name" class="block text-sm font-extrabold mb-1">Full Name</label>
                                <input id="name" name="name" type="text" required
                                    value="<?= e($user['name'] ?? '') ?>"
                                    autocomplete="name"
                                    class="w-full rounded-2xl border px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black/20">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="email" class="block text-sm font-extrabold mb-1">
                                    Email <span class="text-xs font-semibold text-gray-500">(optional)</span>
                                </label>
                                <input id="email" name="email" type="email"
                                    value="<?= e($user['email'] ?? '') ?>"
                                    autocomplete="email"
                                    class="w-full rounded-2xl border px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black/20">
                                <p class="text-xs text-gray-500 mt-1">
                                    If you change email, you may need to login again.
                                </p>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-extrabold mb-1">Phone</label>
                                <input id="phone" name="phone" type="tel"
                                    value="<?= e($user['phone'] ?? '') ?>"
                                    autocomplete="tel"
                                    inputmode="tel"
                                    placeholder="e.g. 012345678"
                                    class="w-full rounded-2xl border px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black/20">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address" class="block text-sm font-extrabold mb-1">Address</label>
                                <input id="address" name="address" type="text"
                                    value="<?= e($user['address'] ?? '') ?>"
                                    autocomplete="street-address"
                                    class="w-full rounded-2xl border px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black/20">
                            </div>

                            <div class="sm:col-span-2 flex flex-col sm:flex-row gap-3 pt-2">
                                <button type="submit"
                                    class="px-5 py-3 rounded-full bg-black text-white font-extrabold hover:bg-gray-900">
                                    Save Changes
                                </button>

                                <a href="/E-commerce-shoes/view/content/profile.php"
                                    class="px-5 py-3 rounded-full bg-gray-100 text-gray-900 font-extrabold hover:bg-gray-200 text-center">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="bg-white border rounded-3xl shadow-soft">
                    <div class="p-6 sm:p-8">
                        <h2 class="text-xl font-extrabold">Change Password</h2>
                        <p class="text-sm text-gray-600 mt-1">Keep your account secure.</p>

                        <form action="/E-commerce-shoes/view/actions/profile_update.php" method="post"
                            class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4"
                            autocomplete="off">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="action" value="password">

                            <div class="sm:col-span-2">
                                <label for="current_password" class="block text-sm font-extrabold mb-1">Current Password</label>
                                <input id="current_password" type="password" name="current_password"
                                    required autocomplete="current-password"
                                    class="w-full rounded-2xl border px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black/20">
                            </div>

                            <div>
                                <label for="new_password" class="block text-sm font-extrabold mb-1">New Password</label>
                                <input id="new_password" type="password" name="new_password"
                                    required minlength="8" autocomplete="new-password"
                                    class="w-full rounded-2xl border px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black/20">
                                <p class="text-xs text-gray-500 mt-1">Minimum 8 characters recommended.</p>
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-extrabold mb-1">Confirm New Password</label>
                                <input id="confirm_password" type="password" name="confirm_password"
                                    required minlength="8" autocomplete="new-password"
                                    class="w-full rounded-2xl border px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black/20">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <!-- Avatar preview -->
    <script>
        const input = document.getElementById('avatarInput');
        if (input) {
            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) return;

                const url = URL.createObjectURL(file);

                let img = document.getElementById('avatarPreview');
                const fallback = document.getElementById('avatarFallback');

                if (!img) {
                    img = document.createElement('img');
                    img.id = 'avatarPreview';
                    img.className = 'w-16 h-16 rounded-2xl object-cover border';
                    img.alt = 'Avatar preview';
                    if (fallback) fallback.replaceWith(img);
                }

                img.src = url;
            });
        }
    </script>

</body>

</html>