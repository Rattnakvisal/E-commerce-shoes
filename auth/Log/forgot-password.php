<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../Helper/token.php';
require_once __DIR__ . '/../Helper/mail_helper.php';

$error = '';
$success = '';
$debugCode = (string)($_SESSION['debug_code'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || !verify_csrf_token((string)$_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {

        $email = trim((string)($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email.';
        } else {

            $success = 'If that email exists, we sent a code to it.';

            // save email for verify page (even if not found)
            $_SESSION['verify_email'] = $email;

            try {
                // Find user
                $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($u) {
                    $userId = (int)$u['user_id'];
                    $name   = (string)($u['name'] ?? '');

                    // Remove old reset requests
                    $conn->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$userId]);

                    // Generate 6-digit code
                    $code = (string)random_int(100000, 999999);
                    $codeHash = hash('sha256', $code);
                    $expiresAt = date('Y-m-d H:i:s', time() + 10 * 60); // 10 min

                    // Insert reset row (attempts + used_at)
                    $ins = $conn->prepare("
                        INSERT INTO password_resets (user_id, token_hash, expires_at, attempts, used_at)
                        VALUES (?, ?, ?, 0, NULL)
                    ");
                    $ok = $ins->execute([$userId, $codeHash, $expiresAt]);

                    // Verify insertion: check lastInsertId and fallback to selecting the row
                    $insertedId = 0;
                    try {
                        $insertedId = (int)$conn->lastInsertId();
                    } catch (Throwable $e) {
                        // ignore
                    }

                    if ($insertedId <= 0) {
                        $chk = $conn->prepare("SELECT id FROM password_resets WHERE user_id = ? AND token_hash = ? LIMIT 1");
                        try {
                            $chk->execute([$userId, $codeHash]);
                            $found = $chk->fetch(PDO::FETCH_ASSOC);
                            if ($found && !empty($found['id'])) {
                                $insertedId = (int)$found['id'];
                            }
                        } catch (Throwable $e) {
                            error_log('[ForgotPassword] verify SELECT failed: ' . $e->getMessage());
                        }
                    }

                    if ($insertedId <= 0) {
                        error_log('[ForgotPassword] INSERT password_resets failed user_id=' . $userId . ' execute=' . var_export($ok, true));
                    }

                    // Email content
                    $subject = 'Your password reset code';
                    $html = "
                        <div style='font-family:Arial,sans-serif'>
                          <h2>Password Reset Code</h2>
                          <p>Hello " . htmlspecialchars($name ?: $email) . ",</p>
                          <p>Your verification code is:</p>
                          <div style='font-size:28px;font-weight:700;letter-spacing:6px;
                                      padding:12px 16px;border:1px solid #e2e8f0;
                                      display:inline-block;border-radius:10px;background:#f8fafc'>
                            {$code}
                          </div>
                          <p style='margin-top:14px;color:#475569'>
                            This code expires in <b>10 minutes</b>. If you did not request this, ignore this email.
                          </p>
                        </div>
                    ";
                    $text = "Your password reset code is: {$code}\nThis code expires in 10 minutes.";

                    $sent = send_mail($email, $name ?: $email, $subject, $html, $text);
                    if (!$sent) {
                        error_log('[ForgotPassword] send_mail failed for ' . $email);
                    }

                    // DEV: show code only on localhost
                    if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
                        $_SESSION['debug_code'] = $code;
                        $debugCode = $code;
                    } else {
                        unset($_SESSION['debug_code']);
                    }
                }
            } catch (Throwable $e) {
                error_log('[ForgotPassword] ' . $e->getMessage());
                // keep privacy message; do NOT expose errors to user
            }

            header("Location: verify.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen flex items-center justify-center bg-white-to-br from-teal-900 via-slate-900 to-black px-4 py-10">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">

        <div class="flex items-center justify-between mb-4">
            <a href="login.php" class="text-slate-600 hover:text-slate-900">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div class="text-slate-700">
                <i class="fa-solid fa-lock text-xl"></i>
            </div>
            <div class="w-6"></div>
        </div>

        <h1 class="text-xl font-extrabold text-center">Reset your password</h1>
        <p class="text-slate-500 text-center mt-1 mb-6">
            Enter your registered email address.
        </p>

        <?php if (!empty($error)): ?>
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <?= csrf_input_field(); ?>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input name="email" type="email" required
                    class="w-full px-4 py-3 rounded-xl border bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900"
                    placeholder="Enter your email address">
            </div>

            <button type="submit"
                class="w-full bg-slate-900 text-white py-3 rounded-xl font-semibold hover:bg-slate-800 transition">
                Send Code
            </button>
        </form>

        <?php if (!empty($debugCode)): ?>
            <div class="mt-4 p-3 bg-slate-50 border rounded-xl text-sm text-slate-700">
                Dev code: <b><?= htmlspecialchars($debugCode) ?></b>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>