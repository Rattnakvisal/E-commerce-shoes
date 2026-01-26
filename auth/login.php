<?php

declare(strict_types=1);

/* =========================
   Session
========================= */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/conn.php';
require_once __DIR__ . '/token.php';
require_once __DIR__ . '/helpers.php';

/* =========================
   Ensure PDO
========================= */
if (!isset($conn) || !($conn instanceof PDO)) {
    if (isset($pdo) && $pdo instanceof PDO) {
        $conn = $pdo;
    } else {
        http_response_code(500);
        die('Database connection not available');
    }
}

/* =========================
   Init
========================= */
$error = '';
$success = '';
$prefill_email = '';

/* =========================
   Flash after register
========================= */
if (!empty($_GET['registered'])) {
    $success = 'Registration successful! Please sign in.';
    $prefill_email = htmlspecialchars((string)($_GET['email'] ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   Handle Login
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || !verify_csrf_token((string)$_POST['csrf_token'])) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {

        $email    = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Please provide both email and password.';
        } else {

            try {
                $stmt = $conn->prepare(
                    "SELECT user_id, name, email, password, role, provider
                     FROM users
                     WHERE email = ?
                     LIMIT 1"
                );
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && (($user['provider'] ?? '') === 'google' || empty($user['password']))) {
                    $error = 'This account uses Google sign-in. Please sign in with Google.';
                } elseif (!$user || !password_verify($password, (string)$user['password'])) {
                    $error = 'Invalid email or password.';
                } else {

                    login_set_session_and_cookie($conn, $user);

                    regenerate_csrf_token();

                    redirect_by_role((string)$user['role']);
                    exit;
                }
            } catch (Throwable $e) {
                error_log('[Login] ' . $e->getMessage());
                $error = 'Server error. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen flex items-center justify-center bg-white-to-br from-teal-900 via-slate-900 to-black px-4 py-10">

    <!-- Card -->
    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2">

            <!-- Left Image (same style as register) -->
            <div class="relative hidden md:block">
                <img
                    src="../assets/Images/Login image detail.avif"
                    class="h-full w-full object-cover"
                    alt="Login" />
                <div class="absolute inset-0 bg-black/50"></div>

                <div class="absolute inset-0 p-10 flex flex-col justify-end">
                    <h2 class="text-4xl font-extrabold text-white leading-tight">
                        Welcome<br />Back
                    </h2>
                    <p class="text-white/80 mt-2">
                        Sign in to continue shopping.
                    </p>
                </div>
            </div>

            <!-- Right Form -->
            <div class="p-8 sm:p-10">
                <h1 class="text-3xl font-extrabold text-slate-900 text-center">
                    Sign In
                </h1>
                <p class="text-slate-500 text-center mt-2 mb-8">
                    Enter your details below
                </p>

                <!-- Success -->
                <?php if ($success): ?>
                    <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">
                        <i class="fas fa-check-circle mr-1"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <!-- Error -->
                <?php if ($error): ?>
                    <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <?= csrf_input_field(); ?>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Email Address
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input
                                type="email"
                                name="email"
                                required
                                value="<?= $prefill_email ?: htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="you@example.com"
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                placeholder="••••••••"
                                class="w-full pl-10 pr-12 py-3 rounded-xl border border-slate-200 bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400">
                            <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-0 px-4 text-slate-400 hover:text-slate-700"
                                aria-label="Toggle password visibility" aria-pressed="false">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        class="w-full rounded-xl bg-slate-900 text-white py-3 font-semibold
                               hover:bg-slate-800 transition flex items-center justify-center gap-2">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>

                    <!-- Divider -->
                    <div class="flex items-center gap-3 py-2">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <span class="text-xs text-slate-400">OR</span>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    <!-- Google -->
                    <button type="button"
                        onclick="window.location.href='/E-commerce-shoes/auth/google/google-login.php'"
                        class="w-full rounded-xl border border-slate-200 py-3 font-semibold text-slate-700
                               hover:bg-slate-50 transition flex items-center justify-center gap-2">
                        <i class="fa-brands fa-google"></i>
                        Sign in with Google
                    </button>

                    <!-- Links -->
                    <div class="text-center pt-2 text-sm text-slate-600 space-y-2">
                        <p>
                            Don’t have an account?
                            <a href="register.php" class="text-slate-900 font-semibold hover:underline">
                                Sign up
                            </a>
                        </p>
                        <p>
                            Go to
                            <a href="../view/index.php" class="text-slate-900 font-semibold hover:underline">
                                View Page
                            </a>
                        </p>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script src="../assets/Js/login.js"></script>
</body>

</html>