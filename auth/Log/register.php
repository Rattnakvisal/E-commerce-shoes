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

                    // Send verification email (after commit)
                    require_once __DIR__ . '/../Helper/mail_helper.php';

                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

                    $verifyUrl = $scheme . '://' . $host . '/auth/Helper/verify-email.php?token=' . urlencode($token);

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
    <title>Create Account</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen flex items-center justify-center bg-white-to-br from-teal-900 via-slate-900 to-black px-4 py-10">

    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2">

            <!-- Left image -->
            <div class="relative hidden md:block">
                <img src="../../assets/Images/Login image detail.avif"
                    class="h-full w-full object-cover" alt="Register">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 p-10 flex flex-col justify-end">
                    <h2 class="text-4xl font-extrabold text-white">Create your<br>Account</h2>
                    <p class="text-white/80 mt-2">Join and get started today.</p>
                </div>
            </div>

            <!-- Form -->
            <div class="p-8 sm:p-10">
                <h1 class="text-3xl font-extrabold text-center">Sign Up</h1>
                <p class="text-slate-500 text-center mt-2 mb-8">It only takes a minute</p>

                <?php if (!empty($error)): ?>
                    <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <?= csrf_input_field(); ?>

                    <!-- Name -->
                    <input id="name" type="text" name="name" required
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        placeholder="Full Name"
                        class="w-full px-4 py-3 rounded-xl border bg-slate-50 focus:ring-2 focus:ring-slate-900">

                    <!-- Email -->
                    <input id="email" type="email" name="email" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="Email Address"
                        class="w-full px-4 py-3 rounded-xl border bg-slate-50 focus:ring-2 focus:ring-slate-900">

                    <!-- Password -->
                    <div class="space-y-2">
                        <div class="relative">
                            <input id="password" type="password" name="password" required
                                placeholder="Password (min 8 chars)"
                                class="w-full px-4 py-3 pr-12 rounded-xl border bg-slate-50 focus:ring-2 focus:ring-slate-900">

                            <button id="togglePassword" type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-900"
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
                        <div class="relative">
                            <input id="confirm_password" type="password" name="confirm_password" required
                                placeholder="Confirm Password"
                                class="w-full px-4 py-3 pr-12 rounded-xl border bg-slate-50 focus:ring-2 focus:ring-slate-900">

                            <button id="toggleConfirmPassword" type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-900"
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
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input id="agree_terms" type="checkbox" name="agree_terms" required>
                        I agree to the Terms & Conditions
                    </label>

                    <!-- Submit -->
                    <button type="submit"
                        class="w-full bg-slate-900 text-white py-3 rounded-xl font-semibold hover:bg-slate-800">
                        Create Account
                    </button>

                    <div class="text-right">
                        <a href="forgot-password.php"
                            class="text-sm text-slate-600 hover:text-slate-900 hover:underline">
                            Forgot password?
                        </a>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-px bg-slate-200"></div>
                        <span class="text-xs text-slate-400">OR</span>
                        <div class="flex-1 h-px bg-slate-200"></div>
                    </div>

                    <!-- Google -->
                    <button type="button"
                        onclick="window.location.href='../google/google-login.php'"
                        class="w-full border py-3 rounded-xl font-semibold flex justify-center gap-2">
                        <i class="fa-brands fa-google"></i>
                        Sign up with Google
                    </button>

                    <p class="text-center text-sm mt-2">
                        Already have an account?
                        <a href="login.php" class="font-semibold underline">Sign in</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/Js/register.js"></script>
</body>

</html>