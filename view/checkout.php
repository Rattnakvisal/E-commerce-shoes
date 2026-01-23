<?php
require_once __DIR__ . '/../includes/checkout.php';
$qrCodeData = "../view/assets/Acleda.jpg" . uniqid();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Checkout - Complete Your Purchase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .payment-card {
            transition: all 0.3s ease;
        }

        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .payment-card.selected {
            border-color: #667eea;
            background-color: #f0f4ff;
        }

        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .qr-modal {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }

            100% {
                opacity: 1;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php
    require_once __DIR__ . '/../includes/topbar.php';
    require_once __DIR__ . '/../includes/navbar.php';
    ?>

    <!-- QR Code Modal -->
    <div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4 qr-modal">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Scan to Pay</h3>
                <button onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="text-center">
                <div class="mb-4 p-4 bg-gray-50 rounded-lg inline-block">
                    <img src="<?= $qrCodeData ?>" alt="QR Code" class="w-48 h-48 mx-auto">
                </div>

                <p class="text-sm text-gray-600 mb-2">Scan this QR code with your payment app</p>
                <p class="font-bold text-lg text-gray-900 mb-6">$<?= number_format($total, 2) ?></p>

                <div class="space-y-3">
                    <div class="flex items-center justify-center text-sm text-gray-600">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Valid for 15 minutes</span>
                    </div>
                    <div class="flex items-center justify-center text-sm text-gray-600">
                        <i class="fas fa-receipt mr-2"></i>
                        <span>Order ID: #<?= uniqid() ?></span>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <button onclick="closeQRModal()"
                        class="w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold rounded-lg transition duration-300">
                        I've Completed Payment
                    </button>
                    <p class="text-xs text-gray-500 mt-3">
                        After payment, your order will be processed automatically
                    </p>
                </div>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Title -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
            <p class="text-gray-600 mt-2">Complete your purchase securely</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <span><?= e($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Shipping Information -->
                <form method="POST" id="checkoutForm" class="bg-white rounded-2xl shadow-lg p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900">
                            <i class="fas fa-shipping-fast mr-2 text-blue-600"></i>
                            Shipping Information
                        </h2>
                        <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                            Required *
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name *
                            </label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-3 top-3.5 text-gray-400"></i>
                                <input
                                    name="name"
                                    value="<?= e($_POST['name'] ?? '') ?>"
                                    required
                                    placeholder="John Doe"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg form-input focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email *
                            </label>
                            <div class="relative">
                                <i class="fas fa-envelope absolute left-3 top-3.5 text-gray-400"></i>
                                <input
                                    type="email"
                                    name="email"
                                    value="<?= e($_POST['email'] ?? '') ?>"
                                    required
                                    placeholder="john@example.com"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg form-input focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Address *
                        </label>
                        <div class="relative">
                            <i class="fas fa-map-marker-alt absolute left-3 top-3.5 text-gray-400"></i>
                            <input
                                name="address"
                                value="<?= e($_POST['address'] ?? '') ?>"
                                required
                                placeholder="123 Main Street"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg form-input focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                City
                            </label>
                            <div class="relative">
                                <i class="fas fa-city absolute left-3 top-3.5 text-gray-400"></i>
                                <input
                                    name="city"
                                    value="<?= e($_POST['city'] ?? '') ?>"
                                    placeholder="Manila"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg form-input focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Country
                            </label>
                            <div class="relative">
                                <i class="fas fa-globe absolute left-3 top-3.5 text-gray-400"></i>
                                <select
                                    name="country"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg form-input focus:border-blue-500">
                                    <option value="">Select Country</option>
                                    <option value="PH" selected>Philippines</option>
                                    <option value="US">United States</option>
                                    <option value="UK">United Kingdom</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Phone *
                            </label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-3 top-3.5 text-gray-400"></i>
                                <input
                                    name="phone"
                                    value="<?= e($_POST['phone'] ?? '') ?>"
                                    required
                                    placeholder="+63 912 345 6789"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg form-input focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="pt-6 border-t border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-credit-card mr-2 text-blue-600"></i>
                            Payment Method
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="payment-card p-4 border-2 border-gray-200 rounded-xl cursor-pointer"
                                onclick="selectPayment('qr')">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-qrcode text-blue-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">QR Code</p>
                                        <p class="text-sm text-gray-600">Scan to pay</p>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-card p-4 border-2 border-gray-200 rounded-xl cursor-pointer"
                                onclick="selectPayment('card')">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                        <i class="far fa-credit-card text-purple-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Credit/Debit Card</p>
                                        <p class="text-sm text-gray-600">Visa, Mastercard</p>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-card p-4 border-2 border-gray-200 rounded-xl cursor-pointer"
                                onclick="selectPayment('paypal')">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fab fa-paypal text-yellow-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">PayPal</p>
                                        <p class="text-sm text-gray-600">Secure online payment</p>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-card p-4 border-2 border-gray-200 rounded-xl cursor-pointer"
                                onclick="selectPayment('cod')">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Cash on Delivery</p>
                                        <p class="text-sm text-gray-600">Pay when received</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="payment_method" id="paymentMethod" value="qr">
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="text-white p-6">
                        <h2 class="text-xl text-black font-bold">
                            <i class="fas fa-receipt mr-2"></i>
                            Order Summary
                        </h2>
                        <p class="text-black text-sm mt-1">Review your items</p>
                    </div>

                    <div class="p-6 space-y-6 max-h-[400px] overflow-y-auto">
                        <?php foreach ($products as $p):
                            $qty = $cart[$p['product_id']];
                            $itemTotal = $p['price'] * $qty;
                        ?>
                            <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                                <div class="relative">
                                    <img
                                        src="<?= e($p['image_url']) ?>"
                                        class="w-20 h-20 rounded-xl object-cover bg-gray-100">
                                    <span class="absolute -top-2 -right-2 bg-blue-600 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center">
                                        <?= e($qty) ?>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900">
                                        <?= e($p['name']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        $<?= number_format($p['price'], 2) ?> × <?= e($qty) ?>
                                    </p>
                                </div>
                                <p class="font-bold text-gray-900">
                                    $<?= number_format($itemTotal, 2) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="p-6 border-t border-gray-100 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium">$<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Shipping</span>
                            <span class="font-medium">$<?= number_format(50, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Tax (12%)</span>
                            <span class="font-medium">$<?= number_format($tax, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center text-lg font-bold text-gray-900 pt-4 border-t border-gray-200">
                            <span>Total</span>
                            <span class="text-blue-600">$<?= number_format($total + 50, 2) ?></span>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50">
                        <div class="flex items-center text-sm text-gray-600 mb-4">
                            <i class="fas fa-lock text-green-600 mr-2"></i>
                            <span>Secure SSL encryption</span>
                        </div>

                        <button
                            type="submit"
                            form="checkoutForm"
                            onclick="processOrder(event)"
                            class="w-full bg-black hover:bg-gray-900 text-white py-4 rounded-xl font-bold text-lg 
           transition-all duration-300 flex items-center justify-center shadow-lg">
                            <i class="fas fa-lock mr-3"></i>
                            Place Order – $<?= number_format($total + 50, 2) ?>
                        </button>


                        <p class="text-center text-xs text-gray-500 mt-4">
                            By placing your order, you agree to our
                            <a href="#" class="text-blue-600 hover:underline">Terms & Conditions</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        let selectedPayment = 'qr';

        function selectPayment(method) {
            selectedPayment = method;
            document.getElementById('paymentMethod').value = method;

            document.querySelectorAll('.payment-card').forEach(card => {
                card.classList.remove('selected');
                card.classList.remove('border-blue-500');
                card.classList.add('border-gray-200');
            });

            event.currentTarget.classList.add('selected', 'border-blue-500');
            event.currentTarget.classList.remove('border-gray-200');
        }

        function showQRModal() {
            document.getElementById('qrModal').classList.remove('hidden');
            document.getElementById('qrModal').classList.add('flex');
        }

        function closeQRModal() {
            document.getElementById('qrModal').classList.add('hidden');
            document.getElementById('qrModal').classList.remove('flex');
        }

        function processOrder(event) {
            const form = document.getElementById('checkoutForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }

            event.preventDefault();

            if (selectedPayment === 'qr') {
                showQRModal();

                setTimeout(() => {
                    form.submit();
                }, 30000);
            } else {
                form.submit();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            selectPayment('qr');
        });
    </script>
</body>

</html>