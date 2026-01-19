<?php
session_start();
require_once __DIR__ . '/../config/conn.php';

/* -----------------------------
   Init
------------------------------ */
$error = '';
$default_role = 'customer';

/* -----------------------------
   Handle Register
------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $agree      = !empty($_POST['agree_terms']);

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!$agree) {
        $error = 'You must agree to the terms.';
    } else {

        $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {

            try {
                $stmt = $conn->prepare(
                    "INSERT INTO users (name, email, password, role, created_at)
                     VALUES (?, ?, ?, ?, NOW())"
                );

                $stmt->execute([
                    $name,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $default_role
                ]);
                // create a system notification for admin about new registration
                try {
                    $newId = $conn->lastInsertId();
                    $nt = $conn->prepare(
                        "INSERT INTO notifications (user_id, title, message, type, reference_id, is_read, created_at)
                         VALUES (:uid, :title, :msg, 'system', :ref, 0, NOW())"
                    );
                    $nt->execute([
                        ':uid' => null,
                        ':title' => 'New user registered',
                        ':msg' => sprintf('User %s (%s) registered', $name, $email),
                        ':ref' => $newId ?: null
                    ]);
                } catch (Throwable $e) {
                    // ignore notification errors
                }

                header('Location: login.php?registered=1&email=' . urlencode($email));
                exit;
            } catch (Throwable $e) {
                $error = 'Something went wrong. Please try again later.';
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

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-teal-900 via-slate-900 to-black px-4 py-10">

    <!-- Card -->
    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2">

            <!-- Left Image -->
            <div class="relative hidden md:block">
                <img
                    src="../assets/Images/Login image detail.avif"
                    class="h-full w-full object-cover"
                    alt="Register" />
                <div class="absolute inset-0 bg-black/50"></div>

                <div class="absolute inset-0 p-10 flex flex-col justify-end">
                    <h2 class="text-4xl font-extrabold text-white leading-tight">
                        Create your<br />Account
                    </h2>
                    <p class="text-white/80 mt-2">
                        Join our platform and start your journey.
                    </p>
                </div>
            </div>

            <!-- Right Form -->
            <div class="p-8 sm:p-10">
                <h1 class="text-3xl font-extrabold text-slate-900 text-center">
                    Sign Up
                </h1>
                <p class="text-slate-500 text-center mt-2 mb-8">
                    It only takes a minute
                </p>

                <!-- Error -->
                <?php if ($error): ?>
                    <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Full Name
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <i class="fas fa-user"></i>
                            </span>
                            <input
                                type="text"
                                name="name"
                                required
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                placeholder="John Doe"
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50
                       focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400">
                        </div>
                    </div>

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
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Confirm Password
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                required
                                placeholder="••••••••"
                                class="w-full pl-10 pr-12 py-3 rounded-xl border border-slate-200 bg-slate-50
                       focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400">
                            <button type="button" id="toggleConfirmPassword"
                                class="absolute inset-y-0 right-0 px-4 text-slate-400 hover:text-slate-700"
                                aria-label="Toggle confirm password visibility" aria-pressed="false">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Terms -->
                    <label class="flex items-start gap-2 text-sm text-slate-600">
                        <input
                            type="checkbox"
                            name="agree_terms"
                            required
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/20">
                        <span>
                            I agree to the
                            <a href="#" class="text-slate-900 font-medium hover:underline">
                                Terms & Conditions
                            </a>
                        </span>
                    </label>

                    <!-- Submit -->
                    <button
                        type="submit"
                        class="w-full rounded-xl bg-slate-900 text-white py-3 font-semibold
                   hover:bg-slate-800 transition flex items-center justify-center gap-2">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>

                    <!-- Link -->
                    <p class="text-center text-sm text-slate-600 pt-2">
                        Already have an account?
                        <a href="login.php" class="text-slate-900 font-semibold hover:underline">
                            Sign in
                        </a>
                    </p>
                </form>
            </div>

        </div>
    </div>

    <script src="../assets/Js/register.js"></script>
</body>

</html>