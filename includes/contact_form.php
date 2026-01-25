<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$success = (int)($_SESSION['flash']['success'] ?? 0);
$error   = $_SESSION['flash']['error'] ?? null;
$old     = $_SESSION['flash']['old'] ?? ['name' => '', 'email' => '', 'message' => ''];

unset($_SESSION['flash']);

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$oldName    = (string)($old['name'] ?? '');
$oldEmail   = (string)($old['email'] ?? '');
$oldMessage = (string)($old['message'] ?? '');
?>
<!-- Background -->
<div class="min-h-[70vh] bg-gradient-to-b from-indigo-50 via-white to-white py-10 px-4">
    <div class="max-w-5xl mx-auto">

        <!-- Card -->
        <div class="bg-white/90 backdrop-blur rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-5">

                <!-- Left Info Panel -->
                <div class="lg:col-span-2 bg-gradient-to-br from-indigo-600 to-purple-600 p-8 text-white">
                    <h2 class="text-3xl font-bold tracking-tight">Contact Us</h2>
                    <p class="mt-2 text-sm text-indigo-100">
                        Send us a message and we’ll respond as soon as possible.
                    </p>

                    <div class="mt-8 space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Email</p>
                                <p class="text-sm text-indigo-100">support@yourshop.com</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Phone</p>
                                <p class="text-sm text-indigo-100">+855 000 000 000</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                                <i class="fas fa-location-dot"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Address</p>
                                <p class="text-sm text-indigo-100">Phnom Penh, Cambodia</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10">
                        <p class="text-xs text-indigo-100">
                            Tip: Please include order ID if your message is about an order.
                        </p>
                    </div>
                </div>

                <!-- Right Form Panel -->
                <div class="lg:col-span-3 p-8">
                    <!-- Alerts -->
                    <?php if ($success): ?>
                        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 flex items-start gap-3">
                            <div class="mt-0.5">
                                <i class="fas fa-circle-check"></i>
                            </div>
                            <div class="text-sm">
                                <p class="font-semibold">Message sent</p>
                                <p class="text-green-700/90">We’ll get back to you shortly.</p>
                            </div>
                        </div>
                    <?php elseif ($error !== null): ?>
                        <?php if ((string)$error === '1'): ?>
                            <div class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800 flex items-start gap-3">
                                <div class="mt-0.5">
                                    <i class="fas fa-triangle-exclamation"></i>
                                </div>
                                <div class="text-sm">
                                    <p class="font-semibold">Missing information</p>
                                    <p>Please fill in all fields.</p>
                                </div>
                            </div>
                        <?php elseif ((string)$error === '2'): ?>
                            <div class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800 flex items-start gap-3">
                                <div class="mt-0.5">
                                    <i class="fas fa-triangle-exclamation"></i>
                                </div>
                                <div class="text-sm">
                                    <p class="font-semibold">Invalid email</p>
                                    <p>Please enter a valid email address.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 flex items-start gap-3">
                                <div class="mt-0.5">
                                    <i class="fas fa-circle-xmark"></i>
                                </div>
                                <div class="text-sm">
                                    <p class="font-semibold">Something went wrong</p>
                                    <p>Please try again later.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <h3 class="text-xl font-semibold text-gray-900">Send a message</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        We typically reply within 24 hours.
                    </p>

                    <form action="/E-commerce-shoes/admin/process/message/messages_api.php" method="post" class="mt-6 space-y-5">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input
                                        type="text"
                                        name="name"
                                        required
                                        value="<?= e($oldName) ?>"
                                        class="block w-full rounded-xl border border-gray-200 bg-white px-10 py-2.5 text-sm
                                               placeholder:text-gray-400 shadow-sm outline-none
                                               focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                                        placeholder="Your name">
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                        <i class="fas fa-at"></i>
                                    </span>
                                    <input
                                        type="email"
                                        name="email"
                                        required
                                        value="<?= e($oldEmail) ?>"
                                        class="block w-full rounded-xl border border-gray-200 bg-white px-10 py-2.5 text-sm
                                               placeholder:text-gray-400 shadow-sm outline-none
                                               focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                                        placeholder="you@example.com">
                                </div>
                            </div>
                        </div>

                        <!-- Message -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute top-3 left-3 text-gray-400">
                                    <i class="fas fa-pen-to-square"></i>
                                </span>
                                <textarea
                                    name="message"
                                    rows="7"
                                    required
                                    class="block w-full rounded-xl border border-gray-200 bg-white px-10 py-2.5 text-sm
                                           placeholder:text-gray-400 shadow-sm outline-none resize-none
                                           focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                                    placeholder="Write your message..."><?= e($oldMessage) ?></textarea>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                Don’t share passwords or payment info in the message.
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5
                                       text-sm font-semibold text-white shadow-sm
                                       hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                <i class="fas fa-paper-plane"></i>
                                Send Message
                            </button>

                            <a href="/E-commerce-shoes/view/products.php"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 px-5 py-2.5
                                      text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-arrow-left"></i>
                                Back to shop
                            </a>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        const url = new URL(window.location.href);
        let changed = false;

        ['success', 'error', 'name', 'email', 'message'].forEach(p => {
            if (url.searchParams.has(p)) {
                url.searchParams.delete(p);
                changed = true;
            }
        });

        if (changed) {
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    })();
</script>