<?php
session_start();
require_once __DIR__ . '/../config/conn.php';

$error = '';
$success = '';

// Define default role for registration
$default_role = 'customer';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $agree_terms = isset($_POST['agree_terms']) ? 1 : 0;
    
    // Validation
    if(empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif(strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif(!$agree_terms) {
        $error = 'You must agree to the terms and conditions';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        
        if($stmt->rowCount() > 0) {
            $error = 'Email already registered. Please use a different email or login.';
        } else {
            try {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, created_at) 
                                       VALUES (?, ?, ?, ?, NOW())');
                
                if($stmt->execute([$name, $email, $hashed_password, $default_role])) {
                    // Redirect to login form with a success flag and prefilled email
                    header('Location: login.php?registered=1&email=' . urlencode($email));
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
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
    <title>Register - POS System</title>
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
            max-width: 480px;
            margin: 0 auto;
        }
        .register-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .register-card:hover {
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
        .password-strength {
            height: 4px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="register-card animate-slide-in">
            <!-- Form Content -->
            <div class="p-8">
                <div class="mb-8 text-center">
                    <h2 class="text-2xl font-bold text-gray-800">Get Started</h2>
                    <p class="text-gray-600 mt-2">Create your account in minutes</p>
                </div>

                <!-- Success Message -->
                <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 animate-fade-in">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Registration Successful!</h3>
                            <div class="mt-1 text-sm text-green-700">
                                <?= htmlspecialchars($success) ?>
                                <a href="login.php" class="font-medium underline ml-1">Login now</a>
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
                            <h3 class="text-sm font-medium text-red-800">Registration Error</h3>
                            <div class="mt-1 text-sm text-red-700">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="POST" class="space-y-6">
                    <!-- Full Name -->
                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-user mr-2 text-blue-500"></i>Full Name *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-circle text-gray-400 text-sm"></i>
                            </div>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                                required 
                                placeholder="John Doe"
                                class="form-input pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-envelope mr-2 text-blue-500"></i>Email Address *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-at text-gray-400 text-sm"></i>
                            </div>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                required 
                                placeholder="you@example.com"
                                class="form-input pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>
                    </div>
                    <!-- Password -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-lock mr-2 text-blue-500"></i>Password *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-key text-gray-400 text-sm"></i>
                            </div>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                placeholder="Create a strong password"
                                class="form-input pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" id="togglePassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Password Strength -->
                        <div class="mt-3">
                            <div class="flex justify-between mb-1">
                                <span class="text-xs text-gray-500">Password strength</span>
                                <span id="strengthText" class="text-xs font-medium text-red-500">Weak</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div id="strengthBar" class="password-strength bg-red-500 rounded-full w-1/4"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-2">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-redo mr-2 text-blue-500"></i>Confirm Password *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-check-circle text-gray-400 text-sm"></i>
                            </div>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required 
                                placeholder="Re-enter your password"
                                class="form-input pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" id="toggleConfirmPassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                        </div>
                        <div id="passwordMatch" class="mt-2 text-sm hidden">
                            <i class="fas fa-check text-green-500 mr-1"></i>
                            <span class="text-green-600">Passwords match</span>
                        </div>
                        <div id="passwordMismatch" class="mt-2 text-sm hidden">
                            <i class="fas fa-times text-red-500 mr-1"></i>
                            <span class="text-red-600">Passwords do not match</span>
                        </div>
                    </div>

                    <!-- Terms Agreement -->
                    <div class="space-y-2">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input 
                                    id="agree_terms" 
                                    name="agree_terms" 
                                    type="checkbox" 
                                    required
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    <?= isset($_POST['agree_terms']) ? 'checked' : '' ?>
                                >
                            </div>
                            <div class="ml-3">
                                <label for="agree_terms" class="text-sm text-gray-700">
                                    I agree to the <a href="#" class="text-blue-600 hover:text-blue-800 hover:underline">Terms of Service</a> and <a href="#" class="text-blue-600 hover:text-blue-800 hover:underline">Privacy Policy</a> *
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="btn-primary w-full text-white py-3 px-4 rounded-lg font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </button>

                    <!-- Login Link -->
                    <div class="text-center pt-4">
                        <p class="text-gray-600 text-sm">
                            Already have an account?
                            <a href="login.php" class="text-blue-600 font-medium hover:text-blue-800 hover:underline ml-1">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../assets/Js/register.js"></script>
    <script>
        <?php if ($error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('name').focus();
        });
        <?php endif; ?>
    </script>
</body>
</html>