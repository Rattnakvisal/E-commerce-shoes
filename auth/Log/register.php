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
$default_role = 'customer';

/* =========================
   Handle Register
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please refresh the page.';
    } else {

        $name     = trim((string)($_POST['name'] ?? ''));
        $email    = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');
        $agree    = !empty($_POST['agree_terms']);

        /* ---------- Validation ---------- */
        if ($name === '' || $email === '' || $password === '' || $confirm === '') {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (!$agree) {
            $error = 'You must agree to the terms.';
        } else {

            /* ---------- Check existing email ---------- */
            $stmt = $conn->prepare(
                "SELECT user_id, provider, password
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if (($existing['provider'] ?? '') === 'google' || empty($existing['password'])) {
                    $error = 'This email is registered with Google. Please sign in using Google.';
                } else {
                    $error = 'Email already registered.';
                }
            } else {

                /* ---------- Create user + verify token ---------- */
                try {
                    $conn->beginTransaction();

                    $stmt = $conn->prepare(
                        "INSERT INTO users
                         (name, email, password, role, provider, email_verified, created_at)
                         VALUES (?, ?, ?, ?, 'local', 0, NOW())"
                    );

                    $stmt->execute([
                        $name,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $default_role
                    ]);

                    $userId = (int)$conn->lastInsertId();

                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $expiresAt = date('Y-m-d H:i:s', time() + 24 * 60 * 60);

                    $conn->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$userId]);

                    $conn->prepare(
                        "INSERT INTO email_verifications (user_id, token_hash, expires_at)
                         VALUES (?, ?, ?)"
                    )->execute([$userId, $tokenHash, $expiresAt]);

                    $conn->commit();

                    // Send verification email
                    require_once __DIR__ . '/../Helper/mail_helper.php';

                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

                    $verifyUrl = $scheme . '://' . $host . '/auth/verify/verify-email.php?token=' . urlencode($token);

                    $subject = 'Verify your email';
                    $html = "
                        <div style='font-family:Arial,sans-serif'>
                          <h2>Verify your email</h2>
                          <p>Hello <b>" . htmlspecialchars($name) . "</b>,</p>
                          <p>Please verify your email by clicking the button below:</p>
                          <p style='margin:18px 0'>
                            <a href='{$verifyUrl}'
                               style='background:#0f172a;color:#fff;padding:12px 18px;border-radius:10px;text-decoration:none;display:inline-block'>
                              Verify Email
                            </a>
                          </p>
                          <p style='color:#64748b;font-size:12px'>This link expires in 24 hours.</p>
                        </div>
                    ";
                    $text = "Verify your email: {$verifyUrl}";

                    $sent = send_mail($email, $name, $subject, $html, $text);
                    if (!$sent) {
                        error_log('[Register] verify email send failed for ' . $email);
                    }

                    regenerate_csrf_token();

                    header('Location: login.php?registered=1&verify=1&email=' . urlencode($email));
                    exit;
                } catch (Throwable $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    error_log('[Register] ' . $e->getMessage());
                    $error = 'Something went wrong. Please try again later.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Create account | MyBrand</title>
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
        .auth-link { color: var(--accent-dark); }
        input[type="checkbox"] { accent-color: var(--accent); }
        @media (prefers-reduced-motion: no-preference) {
            .auth-shell { animation: enter .55s ease-out both; }
            @keyframes enter { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-[#f2efe8] px-4 py-6 sm:px-6 sm:py-10">

    <main class="auth-shell w-full max-w-5xl overflow-hidden rounded-[1.75rem] bg-[#fffdf9]">
        <div class="grid min-h-[760px] grid-cols-1 md:grid-cols-[0.92fr_1.08fr]">

            <!-- Left image -->
            <section class="photo-panel relative hidden overflow-hidden md:block" aria-label="MyBrand membership">
                <img src="../../assets/Images/Login image detail.avif"
                    class="h-full w-full object-cover transition duration-700 hover:scale-[1.02]" alt="Black and white sneakers displayed on a wooden stool">
                <div class="absolute left-9 top-8 z-10 flex items-center gap-3 text-white">
                    <span class="grid h-10 w-10 place-items-center rounded-full border border-white/30 bg-white/10 backdrop-blur-sm"><i class="fa-solid fa-bag-shopping text-sm"></i></span>
                    <span class="brand text-lg font-extrabold tracking-tight">MYBRAND</span>
                </div>
                <div class="absolute inset-x-0 bottom-0 z-10 p-9 lg:p-12">
                    <span class="mb-4 inline-flex rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[.18em] text-white/90 backdrop-blur-sm">Join the community</span>
                    <h2 class="text-4xl font-extrabold leading-tight text-white lg:text-5xl">Your next favorite<br>is waiting.</h2>
                    <p class="mt-4 max-w-xs text-base leading-7 text-white/75">Create an account for faster checkout, order updates, and a wishlist that stays with you.</p>
                </div>
            </section>

            <!-- Form -->
            <section class="flex items-center px-6 py-9 sm:px-10 lg:px-16">
              <div class="mx-auto w-full max-w-md">
                <a href="../../view/content/index.php" class="mb-8 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-slate-900"><i class="fa-solid fa-arrow-left text-xs"></i> Back to store</a>
                <div class="mb-7 flex items-center gap-3 text-[#18231f] md:hidden">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-[#18231f] text-white"><i class="fa-solid fa-bag-shopping text-sm"></i></span>
                    <span class="brand text-lg font-extrabold tracking-tight">MYBRAND</span>
                </div>
                <h1 class="text-[2rem] font-extrabold tracking-[-0.035em] text-[#18231f] sm:text-4xl">Create your account</h1>
                <p class="mb-7 mt-2 text-slate-500">Join MyBrand and make every checkout easier.</p>

                <?php if (!empty($error)): ?>
                    <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4" novalidate>
                    <?= csrf_input_field(); ?>

                    <!-- Name -->
                    <div>
                        <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Full name</label>
                        <div class="field relative rounded-xl border border-slate-200 bg-white transition">
                            <i class="field-icon fa-regular fa-user pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 transition"></i>
                            <input id="name" type="text" name="name" required autocomplete="name"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Your full name"
                                class="w-full rounded-xl bg-transparent py-3 pl-11 pr-4 text-slate-900 outline-none placeholder:text-slate-400">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email address</label>
                        <div class="field relative rounded-xl border border-slate-200 bg-white transition">
                            <i class="field-icon fa-regular fa-envelope pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 transition"></i>
                            <input id="email" type="email" name="email" required autocomplete="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com"
                                class="w-full rounded-xl bg-transparent py-3 pl-11 pr-4 text-slate-900 outline-none placeholder:text-slate-400">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                        <div class="field relative rounded-xl border border-slate-200 bg-white transition">
                            <i class="field-icon fa-solid fa-lock pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 transition"></i>
                            <input id="password" type="password" name="password" required
                                placeholder="At least 8 characters" autocomplete="new-password"
                                class="w-full rounded-xl bg-transparent py-3 pl-11 pr-12 text-slate-900 outline-none placeholder:text-slate-400">

                            <button id="togglePassword" type="button"
                                class="absolute right-4 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#b85c38]"
                                aria-label="Toggle password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>

                        <!-- Strength -->
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div id="strengthBar" class="h-2 w-0 rounded-full transition-all bg-rose-500"></div>
                        </div>
                        <div id="strengthText" class="text-xs font-medium text-slate-500">Weak</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-2">
                        <label for="confirm_password" class="block text-sm font-semibold text-slate-700">Confirm password</label>
                        <div class="field relative rounded-xl border border-slate-200 bg-white transition">
                            <i class="field-icon fa-solid fa-shield-halved pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 transition"></i>
                            <input id="confirm_password" type="password" name="confirm_password" required
                                placeholder="Repeat your password" autocomplete="new-password"
                                class="w-full rounded-xl bg-transparent py-3 pl-11 pr-12 text-slate-900 outline-none placeholder:text-slate-400">

                            <button id="toggleConfirmPassword" type="button"
                                class="absolute right-4 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#b85c38]"
                                aria-label="Toggle confirm password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>

                        <!-- Match / Mismatch -->
                        <div id="passwordMatch" class="hidden text-xs text-emerald-600 font-medium">
                            Passwords match ✓
                        </div>
                        <div id="passwordMismatch" class="hidden text-xs text-rose-600 font-medium">
                            Passwords do not match ✗
                        </div>
                    </div>

                    <!-- Terms -->
                    <label class="flex cursor-pointer items-start gap-3 text-sm leading-5 text-slate-600">
                        <input id="agree_terms" type="checkbox" name="agree_terms" required class="mt-0.5 h-4 w-4 rounded border-slate-300">
                        <span>I agree to the <span class="font-semibold text-slate-800">Terms &amp; Conditions</span></span>
                    </label>

                    <!-- Submit -->
                    <button type="submit"
                        class="primary-button w-full rounded-xl py-3.5 font-semibold text-white shadow-lg shadow-slate-900/10 transition disabled:cursor-wait disabled:opacity-70">
                        Create Account
                    </button>

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
                        Sign up with Google
                    </a>

                    <p class="mt-2 text-center text-sm text-slate-500">
                        Already have an account?
                        <a href="login.php" class="auth-link font-semibold hover:underline">Sign in</a>
                    </p>
                </form>
              </div>
            </section>
        </div>
    </main>

    <script src="../../assets/Js/register.js"></script>
</body>

</html>
