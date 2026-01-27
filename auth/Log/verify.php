<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../Helper/token.php';

$error = '';
$success = '';
$email = trim((string)($_SESSION['verify_email'] ?? ''));
$debugCode = (string)($_SESSION['debug_code'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: forgot-password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {

        $code = trim($_POST['code'] ?? '');

        if ($code === '' || !preg_match('/^\d{6}$/', $code)) {
            $error = 'Please enter the 6-digit code.';
        } else {

            // Find user id by email
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$u) {
                $error = 'Invalid code or expired.';
            } else {
                $userId = (int)$u['user_id'];

                $stmt = $conn->prepare("
                    SELECT id, token_hash, expires_at, used_at, attempts
                    FROM password_resets
                    WHERE user_id = ?
                    ORDER BY id DESC
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$r) {
                    $error = 'Invalid code or expired.';
                } elseif (!empty($r['used_at'])) {
                    $error = 'This code was already used. Request a new code.';
                } elseif (strtotime($r['expires_at']) < time()) {
                    $error = 'Code expired. Please request a new code.';
                } elseif ((int)$r['attempts'] >= 5) {
                    $error = 'Too many attempts. Please request a new code.';
                } else {

                    $codeHash = hash('sha256', $code);

                    // Wrong code -> increment attempts
                    if (!hash_equals((string)$r['token_hash'], $codeHash)) {
                        $conn->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?")
                            ->execute([(int)$r['id']]);

                        $error = 'Invalid code or expired.';
                    } else {
                        // Correct -> mark used
                        $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?")
                            ->execute([(int)$r['id']]);

                        // Allow reset password
                        $_SESSION['pw_reset_user'] = $userId;

                        // Cleanup
                        unset($_SESSION['verify_email'], $_SESSION['debug_code']);

                        header("Location: reset-password.php");
                        exit;
                    }
                }
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
    <title>Verify Code</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-slate-50 p-6">
    <div class="w-full max-w-md bg-white rounded-2xl shadow p-8">
        <h1 class="text-2xl font-extrabold">Enter code</h1>
        <p class="text-slate-500 mt-1 mb-6">
            We sent a 6-digit code to <b><?= htmlspecialchars($email) ?></b>
        </p>

        <?php if (!empty($error)): ?>
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <?= csrf_input_field(); ?>

            <input name="code" inputmode="numeric" maxlength="6" required
                class="w-full px-4 py-3 rounded-xl border bg-slate-50 focus:ring-2 focus:ring-slate-900"
                placeholder="6-digit code">

            <button class="w-full bg-slate-900 text-white py-3 rounded-xl font-semibold hover:bg-slate-800">
                Verify
            </button>
        </form>

        <?php if (!empty($debugCode)): ?>
            <div class="mt-4 p-3 bg-slate-50 border rounded-xl text-sm text-slate-700">
                Dev code: <b><?= htmlspecialchars($debugCode) ?></b>
            </div>
        <?php endif; ?>

        <div class="mt-5 text-sm text-slate-600">
            <a class="text-indigo-600 hover:underline" href="forgot-password.php">Resend code</a>
        </div>
    </div>
</body>

</html>