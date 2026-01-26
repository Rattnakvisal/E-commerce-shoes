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
    <meta charset="UTF-8" />
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen flex items-center justify-center bg-white-to-br from-teal-900 via-slate-900 to-black px-4 py-10">

    <!-- Wrapper -->
    <div class="w-full max-w-4xl rounded-2xl overflow-hidden shadow-2xl bg-white">
        <div class="grid grid-cols-1 md:grid-cols-2">

            <!-- Left Image Panel -->
            <div class="relative hidden md:block">
                <!-- Replace image path -->
                <img
                    src="../assets/Images/Login image detail.avif"
                    alt="Artwork"
                    class="h-full w-full object-cover" />
                <div class="absolute inset-0 bg-black/45"></div>

                <div class="absolute inset-0 p-10 flex flex-col justify-end">
                    <h2 class="text-white text-4xl font-extrabold leading-tight">
                        Welcome Back
                    </h2>
                    <p class="text-white/85 mt-2">
                        Sign in to continue and manage your projects.
                    </p>
                </div>
            </div>

            <!-- Right Form Panel -->
            <div class="p-8 sm:p-10">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-extrabold text-slate-900">Sign In</h1>
                    <p class="text-slate-500 mt-2">Enter your details below</p>
                </div>

                <!-- Success -->
                <?php if ($success): ?>
                    <div class="mb-5 flex items-start gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">
                        <i class="fas fa-check-circle mt-0.5"></i>
                        <div><?= htmlspecialchars($success) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Error -->
                <?php if ($error): ?>
                    <div class="mb-5 flex items-start gap-2 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <?= csrf_input_field(); ?>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                value="<?= $prefill_email ?: htmlspecialchars($_POST['email'] ?? '') ?>"
                                placeholder="you@example.com"
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50
                       focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400
                       transition" />
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
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
                       focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400
                       transition" />
                            <!-- optional show/hide button (hook to your login.js) -->
                            <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-0 px-4 text-slate-400 hover:text-slate-700"
                                aria-label="Toggle password visibility" aria-pressed="false">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember / Forgot -->
                    <div class="flex items-center justify-between text-sm">
                        <label class="inline-flex items-center gap-2 text-slate-600">
                            <input type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/20" />
                            Remember me
                        </label>
                        <a href="#" class="text-slate-900 hover:underline">Forgot password?</a>
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

                    <!-- Social buttons (optional) -->
                    <button type="button"
                        class="w-full rounded-xl border border-slate-200 py-3 font-semibold text-slate-700
                   hover:bg-slate-50 transition flex items-center justify-center gap-2">
                        <i class="fa-brands fa-google"></i>
                        Sign in with Google
                    </button>

                    <button type="button"
                        class="w-full rounded-xl bg-black text-white py-3 font-semibold
                   hover:bg-black/90 transition flex items-center justify-center gap-2">
                        <i class="fa-brands fa-apple"></i>
                        Sign in with Apple
                    </button>

                    <!-- Links -->
                    <div class="text-center pt-2 text-sm text-slate-600 space-y-2">
                        <p>
                            Don’t have an account?
                            <a href="register.php" class="text-slate-900 font-semibold hover:underline">Sign up</a>
                        </p>
                        <p>
                            Go to
                            <a href="../view/index.php" class="text-slate-900 font-semibold hover:underline">View Page</a>
                        </p>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script src="../assets/Js/login.js"></script>
</body>

</html>