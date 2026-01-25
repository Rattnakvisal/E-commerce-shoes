<?php
require_once __DIR__ . '/../config/connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// logged-in flag
$userLogged = !empty($_SESSION['user_id']);
$user_name = $_SESSION['name'] ?? $_SESSION['NAME'] ?? $_SESSION['user_name'] ?? $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <footer class="bg-gray-900 text-gray-300 mt-20">
        <div class="max-w-7xl mx-auto px-6 py-16">

            <!-- Top Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10">

                <!-- Brand -->
                <div>
                    <h3 class="text-white text-xl font-bold mb-4">MyBrand</h3>
                    <p class="text-sm leading-relaxed text-gray-400">
                        Premium fashion products crafted with quality and care.
                        Shop confidently with fast delivery and secure payments.
                    </p>

                    <!-- Social -->
                    <div class="flex gap-4 mt-5">
                        <a href="#" class="hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="hover:text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Shop -->
                <div>
                    <h4 class="text-white font-semibold mb-4">Shop</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Men</a></li>
                        <li><a href="#" class="hover:text-white">Women</a></li>
                        <li><a href="#" class="hover:text-white">Kids</a></li>
                        <li><a href="#" class="hover:text-white">New Arrivals</a></li>
                        <li><a href="#" class="hover:text-white">Sale</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-white font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="../view/contact.php" class="hover:text-white">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white">Shipping & Returns</a></li>
                        <li><a href="#" class="hover:text-white">FAQs</a></li>
                        <li><a href="#" class="hover:text-white">Order Tracking</a></li>
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div>
                    <h4 class="text-white font-semibold mb-4">Newsletter</h4>
                    <p class="text-sm text-gray-400 mb-4">
                        Subscribe to receive updates, access to exclusive deals, and more.
                    </p>

                    <form class="flex">
                        <input
                            type="email"
                            placeholder="Your email"
                            class="w-full px-4 py-2 rounded-l-md
                               bg-gray-800 text-gray-200
                               border border-gray-700
                               focus:outline-none focus:border-white">
                        <button
                            type="submit"
                            class="px-4 py-2 bg-white text-gray-900
                               font-semibold rounded-r-md
                               hover:bg-gray-200 transition">
                            Subscribe
                        </button>
                    </form>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-800 mt-12 pt-6">

                <!-- Bottom -->
                <div class="flex flex-col md:flex-row
                        items-center justify-between gap-4">

                    <p class="text-sm text-gray-500">
                        © <?= date('Y') ?> MyBrand. All rights reserved.
                    </p>

                    <!-- Payments -->
                    <div class="flex gap-4 text-2xl text-gray-400">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fab fa-cc-amex"></i>
                    </div>
                </div>
            </div>

        </div>
    </footer>
</body>

</html>