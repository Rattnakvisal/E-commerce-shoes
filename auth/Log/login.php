<?php

declare(strict_types=1);

/* =========================
   Session
========================= */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../Helper/token.php';
require_once __DIR__ . '/../Helper/helpers.php';

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
                    "SELECT user_id, name, email, password, role, provider, status
                     FROM users
                     WHERE email = ?
                     LIMIT 1"
                );
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $rawStatus = strtolower(trim((string)($user['status'] ?? '')));
                $isInactive = in_array($rawStatus, ['0', 'false', 'no', 'n', 'inactive', 'disabled', 'disable'], true);

                if ($user && (($user['provider'] ?? '') === 'google' || empty($user['password']))) {
                    $error = 'This account uses Google sign-in. Please sign in with Google.';
                } elseif ($user && $isInactive) {
                    $error = 'Your account is inactive. Please contact support.';
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
    <title>Sign in | MyBrand</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap');
        :root { --ink: #18231f; --accent: #b85c38; --accent-dark: #934426; }
        body { font-family: 'DM Sans', sans-serif; }
        h1, h2, .brand { font-family: 'Manrope', sans-serif; }
        .auth-shell { box-shadow: 0 28px 80px rgba(40, 36, 28, .14); }
        .photo-panel::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(10,14,12,.04) 20%, rgba(10,14,12,.84) 100%); }
        .field:focus-within { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(184,92,56,.12); }
        .field:focus-within .field-icon { color: var(--accent); }
        .primary-button { background: var(--ink); }
        .primary-button:hover { background: var(--accent-dark); transform: translateY(-1px); }
        .primary-button:active { transform: translateY(0); }
        .auth-link { color: var(--accent-dark); }
        @media (prefers-reduced-motion: no-preference) {
            .auth-shell { animation: enter .55s ease-out both; }
            @keyframes enter { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-[#f2efe8] px-4 py-6 sm:px-6 sm:py-10">

    <!-- Card -->
    <main class="auth-shell w-full max-w-5xl overflow-hidden rounded-[1.75rem] bg-[#fffdf9]">
        <div class="grid min-h-[650px] grid-cols-1 md:grid-cols-[0.92fr_1.08fr]">

            <!-- Left Image -->
            <section class="photo-panel relative hidden overflow-hidden md:block" aria-label="MyBrand welcome">
                <img src="../../assets/Images/Login image detail.avif"
                    class="h-full w-full object-cover transition duration-700 hover:scale-[1.02]" alt="Black and white sneakers displayed on a wooden stool">
                <div class="absolute left-9 top-8 z-10 flex items-center gap-3 text-white">
                    <span class="grid h-10 w-10 place-items-center rounded-full border border-white/30 bg-white/10 backdrop-blur-sm"><i class="fa-solid fa-bag-shopping text-sm"></i></span>
                    <span class="brand text-lg font-extrabold tracking-tight">MYBRAND</span>
                </div>
                <div class="absolute inset-x-0 bottom-0 z-10 p-9 lg:p-12">
                    <span class="mb-4 inline-flex rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[.18em] text-white/90 backdrop-blur-sm">Member access</span>
                    <h2 class="text-4xl font-extrabold leading-tight text-white lg:text-5xl">
                        Good to see<br>you again.
                    </h2>
                    <p class="mt-4 max-w-xs text-base leading-7 text-white/75">
                        Sign in to continue shopping, track your orders, and revisit your favorites.
                    </p>
                </div>
            </section>

            <!-- Right Form -->
            <section class="flex items-center px-6 py-9 sm:px-10 lg:px-16">
              <div class="mx-auto w-full max-w-md">
                <a href="../../view/content/index.php" class="mb-10 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-slate-900"><i class="fa-solid fa-arrow-left text-xs"></i> Back to store</a>
                <div class="mb-8 flex items-center gap-3 text-[#18231f] md:hidden">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-[#18231f] text-white"><i class="fa-solid fa-bag-shopping text-sm"></i></span>
                    <span class="brand text-lg font-extrabold tracking-tight">MYBRAND</span>
                </div>
                <h1 class="text-[2rem] font-extrabold tracking-[-0.035em] text-[#18231f] sm:text-4xl">Welcome back</h1>
                <p class="mb-8 mt-2 text-slate-500">Enter your details to access your account.</p>

                <!-- Success -->
                <?php if (!empty($success)): ?>
                    <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        <i class="fas fa-check-circle mr-1"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <!-- Error -->
                <?php if (!empty($error)): ?>
                    <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5" novalidate>
                    <?= csrf_input_field(); ?>

                    <!-- Email -->
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
                            Email Address
                        </label>
                        <div class="field relative rounded-xl border border-slate-200 bg-white transition">
                            <span class="field-icon pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 transition">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email"
                                id="email"
                                name="email"
                                required
                                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
                                placeholder="you@example.com"
                                autocomplete="email"
                                class="w-full rounded-xl bg-transparent py-3.5 pl-11 pr-4 text-slate-900 outline-none placeholder:text-slate-400">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                            <a href="forgot-password.php" class="auth-link text-sm font-semibold hover:underline">Forgot password?</a>
                        </div>
                        <div class="field relative rounded-xl border border-slate-200 bg-white transition">
                            <span class="field-icon pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 transition">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password"
                                id="password"
                                name="password"
                                required
                                placeholder="••••••••"
                                autocomplete="current-password"
                                class="w-full rounded-xl bg-transparent py-3.5 pl-11 pr-12 text-slate-900 outline-none placeholder:text-slate-400">

                            <!-- Toggle -->
                            <button type="button"
                                id="togglePassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#b85c38]"
                                aria-label="Toggle password visibility"
                                aria-pressed="false">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                        class="primary-button flex w-full items-center justify-center gap-2 rounded-xl py-3.5 font-semibold text-white shadow-lg shadow-slate-900/10 transition disabled:cursor-wait disabled:opacity-70">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>

                    <!-- Divider -->
                    <div class="flex items-center gap-4 py-1">
                        <div class="flex-1 h-px bg-slate-200"></div>
                        <span class="text-[11px] font-semibold uppercase tracking-[.16em] text-slate-400">or continue with</span>
                        <div class="flex-1 h-px bg-slate-200"></div>
                    </div>

                    <!-- Google -->
                    <a href="../google/google-login.php"
                        class="flex w-full items-center justify-center gap-3 rounded-xl border border-[#d7e3fc] bg-[#f8faff] py-3.5 font-semibold text-slate-800 transition hover:-translate-y-0.5 hover:border-[#a8c7fa] hover:bg-[#f1f6ff] hover:shadow-md">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 18 18" aria-hidden="true">
                            <path fill="#4285F4" d="M17.64 9.205c0-.638-.057-1.252-.164-1.841H9v3.482h4.844a4.14 4.14 0 0 1-1.797 2.715v2.258h2.909c1.702-1.567 2.684-3.875 2.684-6.614Z"/>
                            <path fill="#34A853" d="M9 18c2.43 0 4.468-.806 5.956-2.181l-2.91-2.258c-.805.54-1.835.859-3.046.859-2.344 0-4.328-1.585-5.037-3.714H.956v2.332A9 9 0 0 0 9 18Z"/>
                            <path fill="#FBBC05" d="M3.963 10.706A5.41 5.41 0 0 1 3.682 9c0-.592.102-1.168.281-1.706V4.962H.956A9 9 0 0 0 0 9c0 1.452.347 2.827.956 4.038l3.007-2.332Z"/>
                            <path fill="#EA4335" d="M9 3.58c1.321 0 2.507.454 3.441 1.346l2.581-2.581C13.464.892 11.426 0 9 0A9 9 0 0 0 .956 4.962l3.007 2.332C4.672 5.165 6.656 3.58 9 3.58Z"/>
                        </svg>
                        Sign in with Google
                    </a>

                    <!-- Links -->
                    <div class="pt-1 text-center text-sm text-slate-500">
                        <p>
                            Don’t have an account?
                            <a href="register.php" class="auth-link font-semibold hover:underline">Create one</a>
                        </p>
                    </div>
                </form>
              </div>
            </section>

        </div>
    </main>

    <script src="../../assets/Js/login.js"></script>
</body>

</html>
