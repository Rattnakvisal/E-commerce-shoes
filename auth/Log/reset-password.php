<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../token.php';

$error = '';
$success = '';
$showForm = true;

// must come from verify.php
$userId = (int)($_SESSION['pw_reset_user'] ?? 0);
if ($userId <= 0) {
    header('Location: forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || !verify_csrf_token((string)$_POST['csrf_token'])) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {

        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');

        if ($password === '' || $confirm === '') {
            $error = 'Please fill all fields.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            try {
                $stmt = $conn->prepare(
                    "SELECT id
                                         FROM password_resets
                                         WHERE user_id = ?
                                             AND (used_at IS NOT NULL OR expires_at > NOW())
                                         ORDER BY id DESC
                                         LIMIT 1"
                );
                $stmt->execute([$userId]);
                $resetRow = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$resetRow) {
                    $error = 'Reset session expired. Please request a new code.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $upd = $conn->prepare("
                        UPDATE users
                        SET password = ?, auth_token = NULL
                        WHERE user_id = ?
                    ");
                    $upd->execute([$hash, $userId]);

                    $conn->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$userId]);

                    unset($_SESSION['pw_reset_user']);

                    $success = 'Password updated. You may now sign in.';
                    $showForm = false;

                    header('Refresh:2; url=login.php?reset=1');
                }
            } catch (Throwable $e) {
                error_log('[ResetPassword] ' . $e->getMessage());
                $error = 'Server error. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset Password</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen flex items-center justify-center bg-white-to-br from-teal-900 via-slate-900 to-black px-4 py-10">

    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2">

            <!-- Left image -->
            <div class="relative hidden md:block">
                <img src="../../assets/Images/Login image detail.avif"
                    class="h-full w-full object-cover"
                    alt="Reset Password">
                <div class="absolute inset-0 bg-black/55"></div>
                <div class="absolute inset-0 p-10 flex flex-col justify-end">
                    <h2 class="text-4xl font-extrabold text-white">
                        Create a new<br>Password
                    </h2>
                    <p class="text-white/80 mt-2">
                        Choose a strong password you haven’t used before.
                    </p>
                </div>
            </div>

            <!-- Right form -->
            <div class="p-8 sm:p-10">

                <h1 class="text-3xl font-extrabold text-center">Reset Password</h1>
                <p class="text-slate-500 text-center mt-2 mb-8">
                    Enter and confirm your new password
                </p>

                <!-- SUCCESS -->
                <?php if (!empty($success)): ?>
                    <div class="mb-5 bg-emerald-50 border border-emerald-200
                            text-emerald-800 px-4 py-3 rounded-xl text-sm flex gap-2">
                        <i class="fa-solid fa-circle-check mt-0.5"></i>
                        <div><?= htmlspecialchars($success) ?></div>
                    </div>
                <?php endif; ?>

                <!-- ERROR -->
                <?php if (!empty($error)): ?>
                    <div class="mb-5 bg-rose-50 border border-rose-200
                            text-rose-800 px-4 py-3 rounded-xl text-sm flex gap-2">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($showForm)): ?>
                    <form method="POST" class="space-y-5">
                        <?= csrf_input_field(); ?>

                        <!-- New password -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                New password
                            </label>

                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-3 top-1/2
                                      -translate-y-1/2 text-slate-400"></i>

                                <input id="pw1" name="password" type="password" required
                                    placeholder="New password (min 8 chars)"
                                    class="w-full pl-10 pr-12 py-3 rounded-xl border
                                          bg-slate-50 focus:ring-2 focus:ring-slate-900">

                                <button type="button"
                                    onclick="togglePw('pw1', this)"
                                    class="absolute right-3 top-1/2 -translate-y-1/2
                                           text-slate-500 hover:text-slate-900">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>

                            <p class="text-xs text-slate-500 mt-2">
                                Use at least 8 characters with letters & numbers.
                            </p>
                        </div>

                        <!-- Confirm password -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Confirm password
                            </label>

                            <div class="relative">
                                <i class="fa-solid fa-shield-halved absolute left-3 top-1/2
                                      -translate-y-1/2 text-slate-400"></i>

                                <input id="pw2" name="confirm_password" type="password" required
                                    placeholder="Confirm password"
                                    class="w-full pl-10 pr-12 py-3 rounded-xl border
                                          bg-slate-50 focus:ring-2 focus:ring-slate-900">

                                <button type="button"
                                    onclick="togglePw('pw2', this)"
                                    class="absolute right-3 top-1/2 -translate-y-1/2
                                           text-slate-500 hover:text-slate-900">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>

                            <p id="matchHint" class="text-xs mt-2"></p>
                        </div>

                        <!-- Submit -->
                        <button type="submit"
                            class="w-full bg-slate-900 text-white py-3 rounded-xl
                                   font-semibold hover:bg-slate-800 transition
                                   flex items-center justify-center gap-2">
                            <i class="fa-solid fa-key"></i>
                            Set new password
                        </button>

                        <div class="text-sm text-slate-600 text-center">
                            Back to
                            <a href="login.php"
                                class="text-indigo-600 font-semibold hover:underline">
                                Sign in
                            </a>
                        </div>
                    </form>

                    <script>
                        function togglePw(id, btn) {
                            const input = document.getElementById(id);
                            const icon = btn.querySelector('i');
                            const isPw = input.type === 'password';
                            input.type = isPw ? 'text' : 'password';
                            icon.className = isPw ?
                                'fa-regular fa-eye-slash' :
                                'fa-regular fa-eye';
                        }

                        const pw1 = document.getElementById('pw1');
                        const pw2 = document.getElementById('pw2');
                        const hint = document.getElementById('matchHint');

                        function checkMatch() {
                            if (!pw2.value) {
                                hint.textContent = '';
                                return;
                            }
                            if (pw1.value === pw2.value) {
                                hint.textContent = 'Passwords match ✓';
                                hint.className = 'text-xs mt-2 text-emerald-600';
                            } else {
                                hint.textContent = 'Passwords do not match ✗';
                                hint.className = 'text-xs mt-2 text-rose-600';
                            }
                        }

                        pw1.addEventListener('input', checkMatch);
                        pw2.addEventListener('input', checkMatch);
                    </script>

                <?php else: ?>
                    <div class="text-center text-slate-600">
                        Return to
                        <a href="login.php"
                            class="text-indigo-600 font-semibold hover:underline">
                            Sign in
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>

</html>