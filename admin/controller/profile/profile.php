<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../../config/conn.php';
require_once __DIR__ . '/../../include/navbar.php';

if (!isset($conn) || !$conn instanceof PDO) {
    http_response_code(500);
    exit('Database connection not available.');
}

/* ===========================
   HELPERS
=========================== */
if (!function_exists('e')) {
    function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

/* ===========================
   AUTH (admin only)
=========================== */
$userId = (int)($_SESSION['user_id'] ?? 0);
$role   = (string)($_SESSION['role'] ?? '');

if ($userId <= 0 || strtolower($role) !== 'admin') {
    header('Location: /admin/Log/login.php');
    exit;
}

/* ===========================
   CSRF
=========================== */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf'];

/* ===========================
   FETCH USER
=========================== */
$stmt = $conn->prepare('SELECT user_id, NAME, email, password FROM users WHERE user_id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('User not found');
}

$errors = [];
$success = '';

/* ===========================
   POST
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postedCsrf)) {
        $errors[] = 'Invalid CSRF token. Please refresh and try again.';
    }

    $name  = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));

    // password fields
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword     = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

    // Check email uniqueness (ignore current user)
    if (!$errors) {
        $chk = $conn->prepare('SELECT user_id FROM users WHERE email = :email AND user_id <> :id LIMIT 1');
        $chk->execute(['email' => $email, 'id' => $userId]);
        if ($chk->fetchColumn()) {
            $errors[] = 'This email is already used by another account.';
        }
    }

    // If user wants to change password, require current password + validations
    $changingPw = ($newPassword !== '' || $confirmPassword !== '');
    if (!$errors && $changingPw) {
        if ($currentPassword === '') {
            $errors[] = 'Current password is required to change password.';
        } elseif (!password_verify($currentPassword, (string)$user['password'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        }
    }

    if (!$errors) {
        try {
            $conn->beginTransaction();

            // Update name + email
            $up = $conn->prepare('UPDATE users SET NAME = :name, email = :email WHERE user_id = :id');
            $up->execute(['name' => $name, 'email' => $email, 'id' => $userId]);

            // Update password
            if ($changingPw) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $pup = $conn->prepare('UPDATE users SET password = :pw WHERE user_id = :id');
                $pup->execute(['pw' => $hash, 'id' => $userId]);
            }

            $conn->commit();
            $success = 'Profile updated successfully.';

            // Refresh user (password included for verify next time)
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: $user;

            // Also refresh session name/email if you store them
            $_SESSION['name']  = $name;
            $_SESSION['email'] = $email;
        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $errors[] = 'Failed to update profile.';
        }
    }
}

// initials for avatar
$displayName = trim((string)($user['NAME'] ?? 'Admin'));
$parts = preg_split('/\s+/', $displayName) ?: [];
$initials = strtoupper(substr($parts[0] ?? 'A', 0, 1) . substr($parts[1] ?? '', 0, 1));
$initials = $initials ?: 'AD';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <style>
        .glass {
            background: rgba(255, 255, 255, .78);
            backdrop-filter: blur(12px);
        }

        .shadow-soft-xl {
            box-shadow: 0 28px 90px -50px rgba(0, 0, 0, .55);
        }

        .ring-soft {
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .06);
        }
    </style>
</head>

<body class="bg-slate-50">
    <?php /* navbar already included */ ?>

    <div class="md:ml-64 min-h-screen">
        <main class="pt-6 md:pt-16 p-4 sm:p-6 lg:p-8">

            <!-- HERO -->
            <section class="relative overflow-hidden rounded-3xl bg-white ring-1 ring-black/5 p-6 sm:p-8 mb-8">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/[0.06] via-transparent to-slate-900/[0.06] pointer-events-none"></div>

                <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center shadow">
                                <i class="fa-solid fa-user-gear"></i>
                            </span>
                            <div>
                                <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Profile Settings</h1>
                            </div>
                        </div>
                        <p class="text-slate-500 mt-1">Manage your identity and keep your admin account secure.</p>

                        <div class="flex flex-wrap items-center gap-3 mt-4 text-sm text-slate-500">
                            <span class="inline-flex items-center gap-2">
                                <i class="fa-regular fa-calendar"></i>
                                <?= date('l, F j, Y') ?>
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <i class="fa-regular fa-clock"></i>
                                <span id="liveTime" class="font-semibold text-slate-700"></span>
                            </span>

                            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold border border-emerald-100">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Admin Online
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="/MyBrand_Ecommerce/admin/controller/orders/order.php"
                            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                            <i class="fa-solid fa-bag-shopping"></i> Orders
                        </a>

                        <button type="button" id="btnRefresh"
                            class="inline-flex items-center justify-center w-12 h-12 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 transition"
                            title="Refresh"
                            onclick="window.location.reload()">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </div>
            </section>

            <!-- ALERTS -->
            <?php if ($success): ?>
                <div id="alertSuccess" class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800 flex items-start justify-between gap-4">
                    <div class="font-semibold">
                        <i class="fa-solid fa-circle-check mr-2"></i><?= e($success) ?>
                    </div>
                    <button type="button" class="text-emerald-700 hover:opacity-70" onclick="document.getElementById('alertSuccess').remove()">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div id="alertError" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-rose-800 flex items-start justify-between gap-4">
                    <div>
                        <div class="font-semibold mb-2"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Please fix:</div>
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($errors as $msg): ?>
                                <li><?= e($msg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <button type="button" class="text-rose-700 hover:opacity-70" onclick="document.getElementById('alertError').remove()">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- MAIN GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- LEFT: PROFILE -->
                <aside class="lg:col-span-4 space-y-6">
                    <div class="glass ring-soft rounded-3xl p-6 shadow-soft-xl">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-3xl bg-slate-900 text-white flex items-center justify-center font-extrabold text-xl shadow">
                                <?= e($initials) ?>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xl font-extrabold text-slate-900 truncate"><?= e($user['NAME'] ?? '') ?></div>
                                <div class="text-sm text-slate-500 truncate"><?= e($user['email'] ?? '') ?></div>

                                <div class="mt-2 inline-flex items-center gap-2 text-xs font-bold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    <i class="fa-solid fa-shield-halved"></i> ADMIN
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="text-slate-500 text-xs font-semibold">Account ID</div>
                                <div class="text-slate-900 font-extrabold mt-1">#<?= (int)$userId ?></div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="text-slate-500 text-xs font-semibold">Security</div>
                                <div class="text-slate-900 font-extrabold mt-1">Protected</div>
                            </div>
                        </div>

                        <div class="mt-6 rounded-2xl bg-slate-900 text-white px-4 py-4">
                            <div class="font-extrabold">Security Tip</div>
                            <div class="text-sm text-white/80 mt-1">
                                Use 8+ characters and include at least 1 number.
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-soft-xl">
                        <div class="font-extrabold text-slate-900 mb-3">Quick Actions</div>
                        <div class="grid grid-cols-2 gap-3">
                            <a href="../dashboard/dashboard.php"
                                class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition text-center">
                                Dashboard
                            </a>
                            <a href="/MyBrand_Ecommerce/admin/controller/users/user.php"
                                class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition text-center">
                                Users
                            </a>
                        </div>
                    </div>
                </aside>

                <!-- RIGHT: FORM -->
                <section class="lg:col-span-8 space-y-6">
                    <div class="glass ring-soft rounded-3xl shadow-soft-xl overflow-hidden">
                        <div class="px-6 sm:px-8 py-6 border-b border-slate-200/70">
                            <h2 class="text-xl font-extrabold text-slate-900">Profile Information</h2>
                            <p class="text-sm text-slate-500 mt-1">Update your name, email, and password.</p>
                        </div>

                        <form method="post" class="p-6 sm:p-8 space-y-6" id="profileForm">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

                            <!-- Name + Email -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-extrabold text-slate-700 mb-1">Name</label>
                                    <input name="name" type="text" required
                                        value="<?= e((string)($user['NAME'] ?? '')) ?>"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-200">
                                </div>

                                <div>
                                    <label class="block text-sm font-extrabold text-slate-700 mb-1">Email</label>
                                    <input name="email" type="email" required
                                        value="<?= e((string)($user['email'] ?? '')) ?>"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:ring-2 focus:ring-indigo-200">
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="h-px bg-slate-200/70"></div>

                            <!-- Security -->
                            <div>
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-extrabold text-slate-900">Security</h3>
                                        <p class="text-sm text-slate-500 mt-1">Leave blank if you don’t want to change password.</p>
                                    </div>
                                    <span class="text-xs font-bold text-slate-500 rounded-full border px-3 py-1 bg-white">
                                        8+ chars + number
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <!-- current -->
                                    <div>
                                        <label class="block text-sm font-extrabold text-slate-700 mb-1">Current Password</label>
                                        <div class="relative">
                                            <input name="current_password" id="current_password" type="password"
                                                placeholder="Required to change"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 outline-none focus:ring-2 focus:ring-indigo-200">
                                            <button type="button" class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-700"
                                                data-toggle="current_password" title="Show/Hide">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- new -->
                                    <div>
                                        <label class="block text-sm font-extrabold text-slate-700 mb-1">New Password</label>
                                        <div class="relative">
                                            <input name="new_password" id="new_password" type="password"
                                                placeholder="Min 8 chars"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 outline-none focus:ring-2 focus:ring-indigo-200">
                                            <button type="button" class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-700"
                                                data-toggle="new_password" title="Show/Hide">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>

                                        <!-- Strength -->
                                        <div class="mt-2">
                                            <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                                <div id="pwBar" class="h-2 w-0 bg-slate-900 transition-all"></div>
                                            </div>
                                            <div id="pwHint" class="mt-1 text-xs text-slate-500 font-semibold"></div>
                                        </div>
                                    </div>

                                    <!-- confirm -->
                                    <div>
                                        <label class="block text-sm font-extrabold text-slate-700 mb-1">Confirm Password</label>
                                        <div class="relative">
                                            <input name="confirm_password" id="confirm_password" type="password"
                                                placeholder="Repeat new password"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 outline-none focus:ring-2 focus:ring-indigo-200">
                                            <button type="button" class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-700"
                                                data-toggle="confirm_password" title="Show/Hide">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-3 text-xs text-slate-500">
                                    If you fill “New Password”, you must also provide “Current Password”.
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 pt-2">
                                <button type="submit" id="btnSave"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 font-extrabold text-white shadow hover:bg-indigo-700 transition">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    <span id="btnSaveText">Save Changes</span>
                                </button>

                                <a href="../dashboard/dashboard.php"
                                    class="inline-flex items-center justify-center rounded-2xl px-6 py-3 font-bold text-slate-700 hover:bg-slate-100 transition">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-soft-xl">
                        <h3 class="text-lg font-extrabold text-slate-900">Recommendations</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-600 list-disc pl-5">
                            <li>Don’t share your admin account with staff users.</li>
                            <li>Use unique email for admin account.</li>
                            <li>Logout when using public devices.</li>
                        </ul>
                    </div>
                </section>
            </div>

        </main>
    </div>

    <!-- JS (live time + toggles + strength + submit loading) -->
    <script>
        (() => {
            "use strict";

            // Live Clock
            const liveTime = document.getElementById("liveTime");

            function pad(n) {
                return String(n).padStart(2, '0');
            }

            function tick() {
                const d = new Date();
                const t = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
                if (liveTime) liveTime.textContent = t;
            }
            tick();
            setInterval(tick, 1000);

            // Refresh button
            const btnRefresh = document.getElementById("btnRefresh");
            if (btnRefresh) btnRefresh.addEventListener("click", () => location.reload());

            // Show/Hide password toggles
            document.querySelectorAll("[data-toggle]").forEach(btn => {
                btn.addEventListener("click", () => {
                    const id = btn.getAttribute("data-toggle");
                    const input = document.getElementById(id);
                    if (!input) return;

                    input.type = (input.type === "password") ? "text" : "password";

                    const icon = btn.querySelector("i");
                    if (icon) {
                        icon.classList.toggle("fa-eye");
                        icon.classList.toggle("fa-eye-slash");
                    }
                });
            });

            // Password strength (simple)
            const pw = document.getElementById("new_password");
            const bar = document.getElementById("pwBar");
            const hint = document.getElementById("pwHint");

            function strength(v) {
                let s = 0;
                if (v.length >= 8) s++;
                if (/[0-9]/.test(v)) s++;
                if (/[A-Z]/.test(v)) s++;
                if (/[^A-Za-z0-9]/.test(v)) s++;
                return s; // 0..4
            }

            function updateStrength() {
                const v = pw ? pw.value : "";
                const s = strength(v);
                const pct = [0, 25, 50, 75, 100][s];
                if (bar) bar.style.width = pct + "%";

                if (!hint) return;
                if (!v) hint.textContent = "";
                else if (s <= 1) hint.textContent = "Weak";
                else if (s === 2) hint.textContent = "Good";
                else if (s === 3) hint.textContent = "Strong";
                else hint.textContent = "Very Strong";
            }

            if (pw) pw.addEventListener("input", updateStrength);
            updateStrength();

            // Submit loading state
            const form = document.getElementById("profileForm");
            const btnSave = document.getElementById("btnSave");
            const btnSaveText = document.getElementById("btnSaveText");

            if (form && btnSave) {
                form.addEventListener("submit", () => {
                    btnSave.disabled = true;
                    btnSave.classList.add("opacity-80", "cursor-not-allowed");
                    if (btnSaveText) btnSaveText.textContent = "Saving...";
                });
            }
        })();
    </script>

</body>

</html>