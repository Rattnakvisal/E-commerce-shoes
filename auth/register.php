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
    <meta charset="UTF-8">
    <title>Create Account</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-100 to-indigo-200 px-4">

    <div class="w-full max-w-lg bg-white rounded-xl shadow-lg p-8">

        <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Create Account</h2>
        <p class="text-center text-gray-600 mb-6">Join us in just a minute</p>

        <!-- Error -->
        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border border-red-200 p-3 rounded text-red-700 text-sm">
                <i class="fas fa-exclamation-circle mr-1"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">

            <!-- Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input
                    type="text"
                    name="name"
                    required
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300"
                    placeholder="John Doe">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                    type="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300"
                    placeholder="you@example.com">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300"
                    placeholder="••••••••">
            </div>

            <!-- Confirm -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    required
                    class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-300"
                    placeholder="••••••••">
            </div>

            <!-- Terms -->
            <div class="flex items-start">
                <input type="checkbox" name="agree_terms" required class="mt-1 mr-2">
                <span class="text-sm text-gray-600">
                    I agree to the <a href="#" class="text-indigo-600 underline">terms</a>.
                </span>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition">
                <i class="fas fa-user-plus mr-1"></i> Create Account
            </button>

            <p class="text-center text-sm text-gray-600 mt-4">
                Already registered?
                <a href="login.php" class="text-indigo-600 font-medium hover:underline">Sign in</a>
            </p>
        </form>
    </div>

    <script src="../assets/Js/register.js"></script>
</body>

</html>