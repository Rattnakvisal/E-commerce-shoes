<?php
session_start();
require_once __DIR__ . '/../config/conn.php';
// CSRF token helper
require_once __DIR__ . '/token.php';
$error = '';
$success = '';
$prefill_email = '';

// Show success message and prefill email after registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Registration successful! Please sign in.';
    $prefill_email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
}

// Show logged out message if redirected from logout
if (isset($_GET['loggedout']) && $_GET['loggedout'] == '1') {
    $success = 'You have been logged out successfully.';
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please provide both email and password.';
        } else {
            $stmt = $conn->prepare('SELECT * FROM users where email = ?');
            $stmt->execute([$email]);
            $users = $stmt->fetch();
            if (is_array($users)) {
                $users = array_change_key_case($users, CASE_LOWER);
            }

            $stored_hash = $users['password'] ?? null;

            if ($users && is_string($stored_hash) && $stored_hash !== '' && password_verify($password, $stored_hash)) {
                $_SESSION['user_id'] = $users['user_id'];
                $_SESSION['role'] = $users['role'];
                $_SESSION['email'] = $users['email'];
                // Support both 'name' and 'NAME' column casing
                $_SESSION['name'] = $users['name'] ?? $users['NAME'] ?? '';

                // Generate and store auth token for the user (all roles)
                try {
                    $auth_token = bin2hex(random_bytes(32));
                } catch (Exception $e) {
                    $auth_token = bin2hex(openssl_random_pseudo_bytes(32));
                }
                $_SESSION['auth_token'] = $auth_token;
                // Set cookie for longer-lived client reference (HttpOnly)
                setcookie('auth_token', $auth_token, time() + 60 * 60 * 24 * 30, '/', '', false, true);

                // Try to persist token to DB if a column exists; ignore errors
                try {
                    $upd = $conn->prepare('UPDATE users SET auth_token = ? WHERE user_id = ?');
                    $upd->execute([$auth_token, $users['user_id']]);
                } catch (Exception $e) {
                    // ignore: DB might not have the column
                }

                //ROLE BASED REDIRECTION
                if ($users['role'] == 'admin') {
                    header('Location: ../admin/dashboard.php');
                    exit;
                } elseif ($users['role'] == 'staff') {
                    header('Location: ../pos/staff_dashboard.php');
                    exit;
                } elseif ($users['role'] == 'customer') {
                    header('Location: ../view/index.php');
                    exit;
                }
            } else {
                $error = 'Invalid email or password';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .form-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .brand-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 24px;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .form-input {
            transition: all 0.3s ease;
        }

        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="login-card">
            <!-- Form Content -->
            <div class="p-8">
                <div class="mb-8 text-center">
                    <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
                    <p class="text-gray-600 mt-2">Enter your credentials to continue</p>
                </div>

                <!-- Error Message -->
                <?php if ($success): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 animate-fade-in">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Success</h3>
                                <div class="mt-1 text-sm text-green-700">
                                    <?= htmlspecialchars($success) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 animate-fade-in">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Login Failed</h3>
                                <div class="mt-1 text-sm text-red-700">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" class="space-y-6">
                    <?php echo csrf_input_field(); ?>
                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-envelope mr-2 text-blue-500"></i>Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-at text-gray-400 text-sm"></i>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= !empty($prefill_email) ? $prefill_email : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '') ?>"
                                required
                                placeholder="you@example.com"
                                class="form-input pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-lock mr-2 text-blue-500"></i>Password
                            </label>
                            <a href="forgot-password.php" class="text-xs text-blue-600 hover:text-blue-800 hover:underline">
                                Forgot password?
                            </a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-key text-gray-400 text-sm"></i>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                placeholder="Enter your password"
                                class="form-input pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" id="togglePassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Remember this device
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="btn-primary w-full text-white py-3 px-4 rounded-lg font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                    </button>
                    <!-- Divider -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-3 bg-white text-gray-500">Or sign in with</span>
                        </div>
                    </div>

                    <!-- Social Login -->
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="flex items-center justify-center py-2.5 px-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200 text-sm">
                            <i class="fab fa-google text-red-500 mr-2"></i>
                            Google
                        </button>
                        <button
                            type="button"
                            class="flex items-center justify-center py-2.5 px-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200 text-sm">
                            <i class="fab fa-microsoft text-blue-500 mr-2"></i>
                            Microsoft
                        </button>
                    </div>

                    <!-- Sign Up Link -->
                    <div class="text-center pt-4">
                        <p class="text-gray-600 text-sm">
                            Don't have an account?
                            <a href="register.php" class="text-blue-600 font-medium hover:text-blue-800 hover:underline ml-1">
                                Sign up here
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/Js/login.js"></script>
    <script>
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('email').focus();
            });
        <?php endif; ?>
    </script>
</body>

</html>