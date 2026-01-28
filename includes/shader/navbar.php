<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../contract/navbar.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../view/assets/Css/navbar.css">
<nav class="sticky top-0 bg-white shadow-sm border-b z-30">
	<div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">

		<!-- LOGO -->
		<a href="/E-commerce-shoes/view/content/index.php" class="flex items-center gap-2">
			<div class="w-8 h-8 rounded-full bg-black text-white font-bold flex items-center justify-center">✓</div>
			<span class="text-xl font-bold text-gray-900">MyBrand</span>
		</a>

		<!-- DESKTOP NAVIGATION -->
		<div class="hidden lg:flex items-center gap-6">
			<?php foreach ($parents as $p): ?>
				<?php $groups = $groupsByParent[$p['id']] ?? []; ?>

				<?php if (!empty($groups)): ?>
					<div class="mega-parent relative">
						<button type="button" class="px-4 py-2 font-medium text-gray-700 hover:underline hover:text-black">
							<?= htmlspecialchars($p['title']) ?>
						</button>

						<!-- Desktop Mega Menu -->
						<div class="mega-menu-container">
							<div class="p-6 grid grid-cols-5 gap-8">
								<?php foreach ($groups as $g): ?>
									<?php $items = $itemsByGroup[$g['id']] ?? []; ?>
									<div>
										<div class="font-semibold text-gray-900 mb-2">
											<?php if (!empty($g['link_url'])): ?>
												<a href="<?= htmlspecialchars($g['link_url']) ?>" class="hover:underline">
													<?= htmlspecialchars($g['group_title']) ?>
												</a>
											<?php else: ?>
												<?= htmlspecialchars($g['group_title']) ?>
											<?php endif; ?>
										</div>

										<?php foreach (array_slice($items, 0, 5) as $item): ?>
											<a href="<?= htmlspecialchars($item['link_url'] ?? '#') ?>"
												class="block text-gray-600 hover:text-black py-1">
												<?= htmlspecialchars($item['item_title'] ?? '') ?>
											</a>
										<?php endforeach; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<div class="border-t bg-gray-50 p-4 flex items-center justify-between text-sm">
								<span class="text-gray-500">Free Shipping • 60-Day Returns</span>
								<a href="/E-commerce-shoes/view/content/products.php" class="font-semibold hover:text-red-600">
									View All →
								</a>
							</div>
						</div>
					</div>
				<?php else: ?>
					<a href="#"
						class="px-4 py-2 text-gray-700 hover:text-black font-medium">
						<?= htmlspecialchars($p['title']) ?>
					</a>
				<?php endif; ?>

			<?php endforeach; ?>
		</div>

		<!-- RIGHT ACTIONS -->
		<div class="flex items-center gap-5">

			<!-- Desktop Search -->
			<div class="hidden md:block relative">
				<input id="desktopSearchInput"
					class="w-44 bg-gray-100 rounded-full py-2 pl-12 pr-4 text-sm
                      focus:w-64 focus:ring-2 focus:ring-black transition-all"
					placeholder="Search products...">
				<i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
			</div>
			<!-- Search results overlay -->
			<div
				id="globalSearchResults"
				class="hidden fixed top-20 left-1/2 -translate-x-1/2 w-[92%] max-w-4xl z-50">
				<div
					class="bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">
					<!-- Header -->
					<div
						class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
						<div class="flex items-center gap-2">
							<svg
								class="w-4 h-4 text-gray-400"
								fill="none"
								stroke="currentColor"
								stroke-width="2"
								viewBox="0 0 24 24">
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z" />
							</svg>
							<span class="text-sm font-medium text-gray-700">
								Search results
							</span>
						</div>

						<button
							id="closeSearchResults"
							class="text-gray-400 hover:text-gray-600 text-xl leading-none"
							aria-label="Close">
							&times;
						</button>
					</div>

					<!-- Results -->
					<div
						id="searchResultsContent"
						class="px-4 py-3 max-h-[360px] overflow-y-auto text-sm divide-y divide-gray-100">
						<!-- JS injects results here -->
					</div>
				</div>
			</div>

			<!-- Mobile Search Trigger -->
			<button id="mobileSearchTrigger" type="button" class="md:hidden text-xl text-gray-700">
				<i class="fas fa-search"></i>
			</button>

			<!-- NOTIFICATION -->
			<?php if ($userLogged): ?>
				<div class="relative text-xl text-gray-700">
					<button id="notificationTrigger" type="button"
						class="relative focus:outline-none"
						aria-expanded="false" aria-haspopup="true">
						<i class="far fa-bell"></i>
						<span id="notificationCount"
							class="hidden absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
							0
						</span>
					</button>

					<div id="notificationDropdown"
						class="hidden z-[60]
                      absolute right-0 mt-3 w-[22rem] max-w-[92vw]
                      bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 overflow-hidden
                      max-sm:fixed max-sm:left-3 max-sm:right-3 max-sm:top-[72px] max-sm:mt-0">

						<div class="px-4 py-3 border-b flex items-center justify-between">
							<h3 class="font-semibold text-gray-900">Notifications</h3>
							<span class="text-xs text-gray-500" id="notificationMeta"></span>
						</div>

						<div id="notificationList" class="max-h-72 overflow-y-auto text-sm">
							<!-- items injected by JS -->
						</div>

						<div class="border-t px-3 py-2 flex items-center justify-between gap-3 bg-gray-50">
							<button id="markAllRead" type="button" class="text-sm text-blue-600 hover:underline">
								Mark all read
							</button>
							<button id="clearAll" type="button" class="text-sm text-red-600 hover:underline">
								Clear all
							</button>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- Wishlist -->
			<a href="/E-commerce-shoes/view/content/wishlist.php"
				class="relative hidden md:block text-xl text-gray-700 hover:text-black">
				<i class="far fa-heart"></i>
				<span id="wishlistCount"
					class="absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
					<?= (int)$navWishlistCount ?>
				</span>
			</a>

			<!-- Cart -->
			<a href="/E-commerce-shoes/view/content/cart.php"
				class="relative text-xl text-gray-700 hover:text-black">
				<i class="fas fa-shopping-bag"></i>
				<span id="cartCount"
					class="absolute -top-1 -right-2 bg-purple-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
					<?= (int)$navCartCount ?>
				</span>
			</a>

			<!-- USER PROFILE -->
			<div class="relative">
				<?php if ($userLogged): ?>
					<button id="userMenuTrigger" type="button" class="flex items-center gap-3">
						<?php if (!empty($userAvatar)): ?>
							<img src="<?= htmlspecialchars((string)$userAvatar) ?>" alt="User" class="w-9 h-9 rounded-full object-cover">
						<?php else: ?>
							<div class="w-9 h-9 rounded-full bg-gradient-to-r from-blue-600 to-purple-600 text-white flex items-center justify-center font-bold">
								<?= htmlspecialchars($initials) ?>
							</div>
						<?php endif; ?>
						<span class="hidden lg:inline text-sm text-gray-700"><?= htmlspecialchars((string)$userName) ?></span>
						<i class="fas fa-chevron-down text-xs text-gray-700"></i>
					</button>

					<div id="userDropdown" class="hidden absolute right-0 mt-3 bg-white rounded-xl shadow-xl border w-56 py-2 z-[60]">
						<div class="px-4 py-3 border-b">
							<div class="flex items-center gap-3">
								<?php if (!empty($userAvatar)): ?>
									<img src="<?= htmlspecialchars((string)$userAvatar) ?>" alt="User" class="w-10 h-10 rounded-full object-cover">
								<?php else: ?>
									<div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-semibold">
										<?= htmlspecialchars($initials) ?>
									</div>
								<?php endif; ?>
								<div>
									<div class="font-medium text-gray-900"><?= htmlspecialchars((string)$userName) ?></div>
									<div class="text-xs text-gray-500">Member</div>
								</div>
							</div>
						</div>

						<a href="profile.php" class="block px-4 py-3 text-gray-900 hover:bg-gray-100">My Profile</a>
						<a href="myorder.php" class="block px-4 py-3 text-gray-900 hover:bg-gray-100">My Orders</a>
						<a href="wishlist.php" class="block px-4 py-3 text-gray-900 hover:bg-gray-100">Wishlist</a>
						<div class="border-t my-2"></div>
						<a href="/E-commerce-shoes/auth/Log/logout.php" class="block px-4 py-3 text-red-600 hover:bg-gray-100">Logout</a>
					</div>
				<?php else: ?>
					<a href="/E-commerce-shoes/auth/Log/login.php" class="flex items-center gap-2 text-gray-700 hover:text-black">
						<div class="w-9 h-9 bg-gray-200 rounded-full flex items-center justify-center">
							<i class="far fa-user text-gray-600"></i>
						</div>
						<span class="hidden lg:inline text-sm">Sign In</span>
					</a>
				<?php endif; ?>
			</div>

			<!-- MOBILE MENU BUTTON -->
			<button id="mobileMenuTrigger" type="button" class="lg:hidden text-2xl text-gray-700">
				<i class="fas fa-bars"></i>
			</button>

		</div>
	</div>

	<div id="mobileSearchBar" class="hidden px-4 py-4 bg-gray-100 border-t md:hidden">
		<div class="max-w-7xl mx-auto">
			<!-- Make this wrapper relative so dropdown can be absolute -->
			<div class="relative">

				<input id="mobileSearchInput"
					placeholder="Search products..."
					class="w-full bg-white rounded-full py-3 pl-12 pr-12 shadow outline-none focus:ring-2 focus:ring-black/10" />

				<i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>

				<button id="closeMobileSearch" type="button"
					class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">
					<i class="fas fa-times"></i>
				</button>

				<!-- MOBILE SEARCH RESULTS (absolute dropdown under input) -->
				<div id="mobileSearchResults"
					class="hidden absolute left-0 right-0 top-full mt-3 z-[60]">
					<div class="bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">
						<!-- IMPORTANT: remove px-4 on outer container, keep padding inside -->
						<div id="mobileSearchResultsContent"
							class="px-4 py-3 max-h-[50vh] overflow-y-auto text-sm divide-y divide-gray-100">
							<!-- JS injects mobile results here -->
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>

</nav>

<!-- MOBILE OVERLAY (OUTSIDE NAV) -->
<div id="mobileOverlay" class="fixed inset-0 bg-black/50 hidden z-40"></div>

<!-- MOBILE MENU PANEL (OUTSIDE NAV) -->
<aside id="mobileMenu"
	class="fixed top-0 left-0 h-screen w-[85%] max-w-sm bg-white z-50
              -translate-x-full transition-transform duration-300 flex flex-col"
	aria-hidden="true">

	<div class="flex items-center justify-between px-4 py-4 border-b">
		<div class="flex items-center gap-3">
			<div class="w-9 h-9 bg-black text-white rounded-full flex justify-center items-center font-bold">✓</div>
			<span class="text-xl font-semibold text-gray-800">MyBrand</span>
		</div>

		<button id="closeMobileMenuBtn" type="button"
			class="w-10 h-10 grid place-items-center rounded-full text-2xl text-gray-600 hover:bg-gray-100 active:scale-95 transition"
			aria-label="Close menu">
			&times;
		</button>
	</div>

	<div class="flex-1 overflow-y-auto px-3 py-3">
		<?php foreach ($parents as $p): ?>
			<?php $groups = $groupsByParent[$p['id']] ?? []; ?>

			<div class="mobile-parent border-b py-2">
				<button type="button"
					class="parent-toggle w-full flex items-center justify-between px-2 py-3
                       hover:bg-gray-50 active:scale-[0.99] active:bg-gray-100 transition rounded-lg"
					aria-expanded="false">
					<span class="text-gray-900 font-medium"><?= htmlspecialchars($p['title']) ?></span>
					<?php if (!empty($groups)): ?>
						<i class="fas fa-chevron-right text-gray-500 transition-transform duration-200"></i>
					<?php endif; ?>
				</button>

				<?php if (!empty($groups)): ?>
					<div class="mobile-submenu hidden pl-4 mt-1 space-y-2 border-l border-gray-200">
						<?php foreach ($groups as $g): ?>
							<?php $items = $itemsByGroup[$g['id']] ?? []; ?>

							<div class="mobile-group">
								<div class="flex items-center justify-between py-2 pr-2">
									<?php if (!empty($g['link_url'])): ?>
										<a href="<?= htmlspecialchars($g['link_url']) ?>" class="text-gray-800 font-semibold hover:underline">
											<?= htmlspecialchars($g['group_title']) ?>
										</a>
									<?php else: ?>
										<span class="text-gray-800 font-semibold"><?= htmlspecialchars($g['group_title']) ?></span>
									<?php endif; ?>

									<?php if (!empty($items)): ?>
										<button type="button" class="group-toggle text-gray-400" aria-expanded="false">
											<i class="fas fa-chevron-down text-gray-400 text-sm transition-transform duration-200"></i>
										</button>
									<?php endif; ?>
								</div>

								<?php if (!empty($items)): ?>
									<div class="mobile-items hidden pl-4 space-y-1 pb-2">
										<?php foreach (array_slice($items, 0, 6) as $it): ?>
											<a href="<?= htmlspecialchars($it['link_url'] ?? '#') ?>"
												class="block text-gray-600 text-sm px-2 py-2 rounded-lg
                                hover:text-black hover:bg-gray-50 active:scale-[0.99] active:bg-gray-100 transition">
												<?= htmlspecialchars($it['item_title'] ?? '') ?>
											</a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</aside>
<script src="../../view/assets/Js/script.js"></script>
<script src="../../view/assets/Js/notification_users.js"></script>
<script src="../../view/assets/Js/search.js"></script>