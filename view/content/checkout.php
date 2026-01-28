<?php
require_once __DIR__ . '/../../includes/contract/checkout.php';
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

        .payment-card {
            transition: all 0.3s ease;
        }

        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, .1);
        }

        .payment-card.selected {
            border-color: #667eea;
            background-color: #f0f4ff;
        }

        .qr-modal {
            animation: fadeIn 0.25s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(.96);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php
    require_once __DIR__ . '/../../includes/shader/topbar.php';
    require_once __DIR__ . '/../../includes/shader/navbar.php';
    ?>

    <!-- QR Code Modal -->
    <div id="qrModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 qr-modal">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Scan to Pay</h3>
                <button type="button" onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="text-center">
                <div class="mb-4 p-4 bg-gray-50 rounded-lg inline-block">
                    <div class="w-48 h-48 rounded-lg overflow-hidden bg-white">
                        <img id="qrImage" src="" alt="QR Code" class="w-full h-full object-cover">
                    </div>
                </div>

                <p class="text-sm text-gray-600 mb-2">Scan this QR code with your payment app</p>
                <p class="font-bold text-lg text-gray-900 mb-6">$<?= number_format((float)$total, 2) ?></p>

                <div class="flex items-center justify-center text-sm text-gray-600">
                    <i class="fas fa-clock mr-2"></i>
                    <span>Valid for 15 minutes</span>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <button type="button" onclick="confirmPaidAndSubmit()"
                        class="w-full py-3 bg-gray-900 hover:bg-black text-white font-semibold rounded-lg transition duration-300">
                        I've Completed Payment
                    </button>
                    <p class="text-xs text-gray-500 mt-3">
                        After payment, your order will be processed
                    </p>
                </div>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">
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
                <form method="POST" id="checkoutForm" class="bg-white rounded-2xl shadow-lg p-6 space-y-6">

                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900">Shipping Information</h2>
                        <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full">Required *</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-3 top-3.5 text-gray-400"></i>
                                <input name="name" value="<?= e($_POST['name'] ?? '') ?>" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <div class="relative">
                                <i class="fas fa-envelope absolute left-3 top-3.5 text-gray-400"></i>
                                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                        <div class="relative">
                            <i class="fas fa-map-marker-alt absolute left-3 top-3.5 text-gray-400"></i>
                            <input name="address" value="<?= e($_POST['address'] ?? '') ?>" required
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                            <div class="relative">
                                <i class="fas fa-city absolute left-3 top-3.5 text-gray-400"></i>
                                <input name="city" value="<?= e($_POST['city'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                            <div class="relative">
                                <i class="fas fa-globe absolute left-3 top-3.5 text-gray-400"></i>
                                <input name="country" value="<?= e($_POST['country'] ?? 'Cambodia') ?>"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-3 top-3.5 text-gray-400"></i>
                                <input name="phone" value="<?= e($_POST['phone'] ?? '') ?>" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Payment Method</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 rounded-lg">
                            <?php if (empty($paymentMethods)): ?>
                                <div class="text-sm text-red-600">
                                    No payment methods found. Insert into payment_methods table.
                                </div>
                            <?php else: ?>
                                <?php foreach ($paymentMethods as $m):
                                    $code = strtolower(trim($m['method_code']));
                                    $name = $m['method_name'];

                                    $logo = '../assets/Payments/logo.png';
                                    if ($code === 'aba') $logo = '../assets/Payments/aba.png';
                                    elseif ($code === 'acleda') $logo = '../assets/Payments/acleda.png';
                                    elseif ($code === 'wing') $logo = '../assets/Payments/wing.png';
                                    elseif ($code === 'chipmong') $logo = '../assets/Payments/chipmong.png';
                                    elseif ($code === 'bakong') $logo = '../assets/Payments/icon.png';
                                ?>
                                    <button type="button"
                                        class="payment-card text-left p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 transition"
                                        data-method="<?= e($code) ?>"
                                        onclick="selectPayment(event,'<?= e($code) ?>')">

                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-lg bg-white flex items-center justify-center border border-gray-200">
                                                <img src="<?= e($logo) ?>" alt="<?= e($name) ?>"
                                                    class="max-h-8 max-w-10 object-contain"
                                                    onerror="this.style.display='none';">
                                            </div>

                                            <div>
                                                <p class="font-semibold text-gray-900"><?= e($name) ?></p>
                                                <p class="text-sm text-gray-600"><?= e(strtoupper($code)) ?> Payment</p>
                                            </div>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <input type="hidden" name="payment" id="paymentMethod" value="">
                        <input type="hidden" name="confirm_paid" id="confirmPaid" value="0">
                    </div>
                </form>
            </div>

            <!-- Summary -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl text-gray-900 font-bold">
                            <i class="fas fa-receipt mr-2"></i> Order Summary
                        </h2>
                        <p class="text-gray-600 text-sm mt-1">Review your items</p>
                    </div>

                    <div class="p-6 space-y-6 max-h-[400px] overflow-y-auto">
                        <?php foreach ($products as $p):
                            $qty = $cart[$p['product_id']] ?? 0;
                            $itemTotal = ((float)$p['price']) * (int)$qty;
                        ?>
                            <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                                <div class="relative">
                                    <img src="<?= e($p['image_url']) ?>" class="w-20 h-20 rounded-xl object-cover bg-gray-100">
                                    <span class="absolute -top-2 -right-2 bg-blue-600 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center">
                                        <?= e($qty) ?>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900"><?= e($p['name']) ?></p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        $<?= number_format((float)$p['price'], 2) ?> × <?= e($qty) ?>
                                    </p>
                                </div>
                                <p class="font-bold text-gray-900">$<?= number_format((float)$itemTotal, 2) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="p-6 border-t border-gray-100 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium">$<?= number_format((float)$subtotal, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center text-lg font-bold text-gray-900 pt-4 border-t border-gray-200">
                            <span>Total</span>
                            <span class="text-blue-600">$<?= number_format((float)$total, 2) ?></span>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50">
                        <div class="flex items-center text-sm text-gray-600 mb-4">
                            <i class="fas fa-lock text-green-600 mr-2"></i>
                            <span>Secure SSL encryption</span>
                        </div>

                        <button type="submit" form="checkoutForm" onclick="processOrder(event)"
                            class="w-full bg-black hover:bg-gray-900 text-white py-4 rounded-xl font-bold text-lg transition-all duration-300 flex items-center justify-center shadow-lg">
                            <i class="fas fa-qrcode mr-3"></i>
                            Place Order – $<?= number_format((float)$total, 2) ?>
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

    <?php require_once __DIR__ . '/../../includes/shader/footer.php'; ?>

    <script>
        let selectedPayment = null;

        // QR image paths
        const qrMap = {
            aba: "../assets/qr/aba.jpg",
            wing: "../assets/qr/wing.jpg",
            bakong: "../assets/qr/bakong.jpg",
            acleda: "../assets/qr/acleda.jpg",
            chipmong: "../assets/qr/chipmong.jpg"
        };

        function selectPayment(event, method) {
            selectedPayment = method;
            document.getElementById('paymentMethod').value = method;

            document.querySelectorAll('.payment-card').forEach(card => {
                card.classList.remove('selected', 'border-blue-500');
                card.classList.add('border-gray-200');
            });

            const card = event.currentTarget;
            card.classList.add('selected', 'border-blue-500');
            card.classList.remove('border-gray-200');
        }

        function showQRModal(method) {
            const src = qrMap[method];
            if (!src) {
                alert("QR image not found for: " + method);
                return;
            }
            document.getElementById('qrImage').src = src;

            const modal = document.getElementById('qrModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeQRModal() {
            const modal = document.getElementById('qrModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function processOrder(event) {
            const form = document.getElementById('checkoutForm');

            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
            if (!selectedPayment) {
                alert('Please select payment method');
                return false;
            }

            event.preventDefault();
            showQRModal(selectedPayment);
            return false;
        }

        function confirmPaidAndSubmit() {
            const confirmInput = document.getElementById('confirmPaid');
            if (confirmInput) confirmInput.value = '1';

            closeQRModal();
            document.getElementById('checkoutForm').submit();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const first = document.querySelector('.payment-card[data-method]');
            if (first) first.click();
        });
    </script>
</body>

</html>