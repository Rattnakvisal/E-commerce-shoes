<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../contract/contact.php';
?>

<section class="min-h-[70vh] bg-gray-50 py-10 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">

            <!-- LEFT CONTENT -->
            <div class="pt-2">
                <p class="text-xs tracking-widest text-gray-500 uppercase">
                    We’re here to help you
                </p>

                <h1 class="mt-3 text-4xl sm:text-5xl font-extrabold tracking-tight text-gray-900 leading-tight">
                    Discuss <span class="text-indigo-600">Your</span><br class="hidden sm:block" />
                    <span class="font-extrabold">Solution Needs</span>
                </h1>

                <p class="mt-4 max-w-md text-sm sm:text-base text-gray-600">
                    Are you looking for top-quality solutions tailored to your needs?
                    Reach out to us — we typically reply within 24 hours.
                </p>

                <!-- CONTACT BLOCKS -->
                <div class="mt-10 space-y-5">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white shadow-sm ring-1 ring-gray-200 flex items-center justify-center text-indigo-600">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">E-mail</p>
                            <p class="text-sm font-semibold text-gray-900">support@yourshop.com</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white shadow-sm ring-1 ring-gray-200 flex items-center justify-center text-indigo-600">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Phone number</p>
                            <p class="text-sm font-semibold text-gray-900">+855 000 000 000</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white shadow-sm ring-1 ring-gray-200 flex items-center justify-center text-indigo-600">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Location</p>
                            <p class="text-sm font-semibold text-gray-900">Phnom Penh, Cambodia</p>
                        </div>
                    </div>
                </div>

                <p class="mt-10 text-xs text-gray-500">
                    Tip: Please include your order ID if your message is about an order.
                </p>
            </div>

            <!-- RIGHT FORM CARD -->
            <div class="lg:pl-10">
                <div class="bg-white rounded-2xl shadow-lg ring-1 ring-gray-200 p-6 sm:p-8">
                    <!-- Alerts (keep yours) -->
                    <?php if ($success): ?>
                        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 flex items-start gap-3">
                            <div class="mt-0.5"><i class="fas fa-circle-check"></i></div>
                            <div class="text-sm">
                                <p class="font-semibold">Message sent</p>
                                <p class="text-green-700/90">We’ll get back to you shortly.</p>
                            </div>
                        </div>
                    <?php elseif ($error !== null): ?>
                        <?php if ((string)$error === '1'): ?>
                            <div class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800 flex items-start gap-3">
                                <div class="mt-0.5"><i class="fas fa-triangle-exclamation"></i></div>
                                <div class="text-sm">
                                    <p class="font-semibold">Missing information</p>
                                    <p>Please fill in all fields.</p>
                                </div>
                            </div>
                        <?php elseif ((string)$error === '2'): ?>
                            <div class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800 flex items-start gap-3">
                                <div class="mt-0.5"><i class="fas fa-triangle-exclamation"></i></div>
                                <div class="text-sm">
                                    <p class="font-semibold">Invalid email</p>
                                    <p>Please enter a valid email address.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 flex items-start gap-3">
                                <div class="mt-0.5"><i class="fas fa-circle-xmark"></i></div>
                                <div class="text-sm">
                                    <p class="font-semibold">Something went wrong</p>
                                    <p>Please try again later.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form action="/E-commerce-shoes/admin/process/message/messages_api.php" method="post" class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Name</label>
                            <input
                                type="text"
                                name="name"
                                required
                                value="<?= e($oldName) ?>"
                                placeholder="Your name"
                                class="w-full rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-sm outline-none
                       focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" />
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Email</label>
                            <input
                                type="email"
                                name="email"
                                required
                                value="<?= e($oldEmail) ?>"
                                placeholder="you@example.com"
                                class="w-full rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-sm outline-none
                       focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" />
                        </div>

                        <!-- Industry (optional like reference) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Industry</label>
                            <select
                                name="industry"
                                class="w-full rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-sm outline-none
                       focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                                <option value="">Select</option>
                                <option>Retail</option>
                                <option>E-commerce</option>
                                <option>Logistics</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <!-- Message -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Message</label>
                            <textarea
                                name="message"
                                rows="5"
                                required
                                placeholder="Write your message..."
                                class="w-full rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-sm outline-none resize-none
                       focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"><?= e($oldMessage) ?></textarea>
                            <p class="mt-2 text-xs text-gray-500">Don’t share passwords or payment info.</p>
                        </div>

                        <!-- Button (like reference: pill + arrow) -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                class="group inline-flex items-center gap-3 rounded-full bg-indigo-600 px-5 py-3 text-sm font-semibold text-white
                       shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                <span class="w-9 h-9 rounded-full bg-white/15 grid place-items-center">
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                                Get a Solution
                            </button>

                            <a href="/E-commerce-shoes/view/content/products.php"
                                class="ml-3 inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900">
                                <i class="fas fa-arrow-left"></i> Back to shop
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

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

        if (changed) window.history.replaceState({}, document.title, url.pathname + url.search);
    })();
</script>