<?php
session_start();

require_once __DIR__ . '/../config/conn.php';
require_once __DIR__ . '/token.php';

/* -----------------------------
   Init
------------------------------ */
$error = '';
$success = '';
$prefill_email = '';

/* -----------------------------
   Flash messages
------------------------------ */
if (!empty($_GET['registered'])) {
    $success = 'Registration successful! Please sign in.';
    $prefill_email = htmlspecialchars($_GET['email'] ?? '');
}

if (!empty($_GET['loggedout'])) {
    $success = 'You have been logged out successfully.';
}

/* -----------------------------
   Handle Login
------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please provide both email and password.';
        } else {

            $stmt = $conn->prepare(
                "SELECT user_id, name, email, password, role
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Invalid email or password.';
            } else {

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['name']    = $user['name'] ?? '';

                $auth_token = bin2hex(random_bytes(32));
                $_SESSION['auth_token'] = $auth_token;

                setcookie(
                    'auth_token',
                    $auth_token,
                    time() + 60 * 60 * 24 * 30,
                    '/',
                    '',
                    false,
                    true
                );

                try {
                    $stmt = $conn->prepare(
                        "UPDATE users SET auth_token = ? WHERE user_id = ?"
                    );
                    $stmt->execute([$auth_token, $user['user_id']]);
                } catch (Throwable $e) {
                }

                switch ($user['role']) {
                    case 'admin':
                        header('Location: ../admin/dashboard.php');
                        break;
                    case 'staff':
                        header('Location: ../pos/staff_dashboard.php');
                        break;
                    default:
                        header('Location: ../view/index.php');
                }
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-indigo-100 via-slate-100 to-indigo-200 flex items-center justify-center px-4">

    <div class="w-full max-w-md bg-white/90 backdrop-blur rounded-2xl shadow-xl p-8">

        <!-- Header -->
        <div class="text-center mb-8">
            <h2 class="text-3xl font-extrabold text-gray-800">Welcome Back</h2>
            <p class="text-gray-500 mt-2">Sign in to continue</p>
        </div>

        <!-- Success -->
        <?php if ($success): ?>
            <div class="mb-5 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Error -->
        <?php if ($error): ?>
            <div class="mb-5 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="space-y-6">
            <?= csrf_input_field(); ?>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input
                        type="email"
                        name="email"
                        required
                        value="<?= $prefill_email ?: htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="you@example.com"
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Password
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input
                        type="password"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Remember -->
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-gray-600">
                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Remember me
                </label>
                <a href="#" class="text-indigo-600 hover:underline">Forgot password?</a>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-semibold transition flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>

            <!-- Links -->
            <div class="text-center space-y-2 pt-4 text-sm">
                <p class="text-gray-600">
                    Don’t have an account?
                    <a href="register.php" class="text-indigo-600 font-medium hover:underline">Sign up</a>
                </p>
                <p class="text-gray-600">
                    Go to
                    <a href="../view/index.php" class="text-indigo-600 font-medium hover:underline">View Page</a>
                </p>
            </div>
        </form>
    </div>

</body>

<script src="../assets/Js/login.js"></script>
</body>

</html>