<?php
require_once __DIR__ . '/../../config/conn.php';
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
						<!-- TOP LEVEL BUTTON -->
						<button
							type="button"
							class="mega-trigger nav-link px-4 py-2 font-medium text-gray-700 hover:text-black focus-ring">
							<?= htmlspecialchars($p['title']) ?>
						</button>

						<!-- Desktop Mega Menu -->
						<div class="mega-menu-container">
							<div class="p-6 grid grid-cols-5 gap-8 mega-scroll">
								<?php foreach ($groups as $g): ?>
									<?php $items = $itemsByGroup[$g['id']] ?? []; ?>

									<div>
										<!-- GROUP TITLE -->
										<div class="mega-title mb-3">
											<span class="dot"></span>

											<?php if (!empty($g['link_url'])): ?>
												<a
													href="<?= htmlspecialchars($g['link_url']) ?>"
													class="font-semibold text-gray-900 hover:underline">
													<?= htmlspecialchars($g['group_title']) ?>
												</a>
											<?php else: ?>
												<span class="font-semibold text-gray-900">
													<?= htmlspecialchars($g['group_title']) ?>
												</span>
											<?php endif; ?>
										</div>

										<!-- ITEMS -->
										<?php foreach (array_slice($items, 0, 5) as $item): ?>
											<a
												href="<?= htmlspecialchars($item['link_url'] ?? '#') ?>"
												class="mega-item block text-gray-600">
												<?= htmlspecialchars($item['item_title'] ?? '') ?>
											</a>
										<?php endforeach; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<!-- FOOTER -->
							<div class="border-t bg-gray-50/80 backdrop-blur p-4 flex items-center justify-between text-sm">
								<span class="text-gray-500">
									Free Shipping • 60-Day Returns
								</span>

								<a
									href="/E-commerce-shoes/view/content/products.php"
									class="font-semibold text-gray-900 hover:text-red-600 transition">
									View All →
								</a>
							</div>
						</div>
					</div>

				<?php else: ?>
					<!-- NORMAL LINK (NO MEGA MENU) -->
					<a
						href="#"
						class="nav-link px-4 py-2 text-gray-700 hover:text-black font-medium focus-ring">
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

			<!-- notification -->
			<?php if ($userLogged): ?>
				<div class="relative hidden md:block text-xl text-gray-700">
					<button id="notificationTrigger" class="relative focus:outline-none" aria-expanded="false" aria-haspopup="true">
						<i class="far fa-bell"></i>
						<span id="notificationCount" class="wishlist-count absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
					</button>
					<!-- Dropdown -->
					<div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border z-50">
						<div class="p-3 border-b font-semibold">Notifications</div>
						<div id="notificationList" class="max-h-64 overflow-auto text-sm"></div>
						<div class="border-t p-2 flex items-center justify-between gap-2">
							<div class="text-left">
								<button id="markAllRead" class="text-sm text-blue-600">Mark all read</button>
							</div>
							<div class="text-right">
								<button id="clearAll" class="text-sm text-red-600">Clear all</button>
							</div>
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
					</button>

					<div id="userDropdown" class="dropdown-panel absolute right-0 mt-3 bg-white rounded-xl shadow-xl border w-56 py-2 z-[60]">
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
<div id="mobileOverlay"
	class="fixed inset-0 bg-black/50 hidden z-40 backdrop-blur-[2px]">
</div>

<!-- MOBILE MENU PANEL (OUTSIDE NAV) -->
<aside id="mobileMenu"
	class="fixed top-0 left-0 h-screen w-[86%] max-w-sm z-50
         -translate-x-full transition-transform duration-300
         bg-white/90 backdrop-blur-xl border-r border-gray-200/70
         shadow-2xl flex flex-col rounded-r-3xl overflow-hidden"
	aria-hidden="true">

	<!-- HEADER -->
	<div class="flex items-center justify-between px-4 py-4 border-b border-gray-200/70 bg-white/70">
		<div class="flex items-center gap-3">
			<div class="w-10 h-10 rounded-full grid place-items-center font-extrabold
                  text-white bg-gray-900 shadow-sm">
				✓
			</div>
			<span class="text-xl font-bold tracking-tight text-gray-900">MyBrand</span>
		</div>

		<button id="closeMobileMenuBtn" type="button"
			class="nav-icon-btn focus-ring text-2xl"
			aria-label="Close menu">
			&times;
		</button>
	</div>

	<!-- CONTENT -->
	<div class="flex-1 overflow-y-auto px-3 py-3 space-y-2">
		<?php foreach ($parents as $p): ?>
			<?php $groups = $groupsByParent[$p['id']] ?? []; ?>

			<div class="mobile-parent rounded-2xl">
				<!-- PARENT -->
				<button type="button"
					class="parent-toggle w-full flex items-center justify-between px-3 py-3
                 rounded-2xl text-left
                 transition focus-ring"
					aria-expanded="false">

					<span class="text-gray-900 font-semibold tracking-tight">
						<?= htmlspecialchars($p['title']) ?>
					</span>

					<?php if (!empty($groups)): ?>
						<i class="fas fa-chevron-right text-gray-500 transition-transform duration-200"></i>
					<?php else: ?>
						<i class="fas fa-arrow-right text-gray-300 text-sm"></i>
					<?php endif; ?>
				</button>

				<?php if (!empty($groups)): ?>
					<!-- SUBMENU -->
					<div class="mobile-submenu hidden px-3 pb-3">
						<div class="mt-2 space-y-2 border-l border-gray-200/80 pl-3">
							<?php foreach ($groups as $g): ?>
								<?php $items = $itemsByGroup[$g['id']] ?? []; ?>

								<div class="mobile-group rounded-xl">
									<!-- GROUP TITLE ROW -->
									<div class="flex items-center justify-between py-2 pr-1">
										<div class="flex items-center gap-2">
											<span class="inline-block w-2 h-2 rounded-full bg-gray-900/70"></span>

											<?php if (!empty($g['link_url'])): ?>
												<a href="<?= htmlspecialchars($g['link_url']) ?>"
													class="text-gray-900 font-semibold hover:underline">
													<?= htmlspecialchars($g['group_title']) ?>
												</a>
											<?php else: ?>
												<span class="text-gray-900 font-semibold">
													<?= htmlspecialchars($g['group_title']) ?>
												</span>
											<?php endif; ?>
										</div>

										<?php if (!empty($items)): ?>
											<button type="button"
												class="group-toggle nav-icon-btn !w-9 !h-9 !text-base text-gray-500 focus-ring"
												aria-expanded="false">
												<i class="fas fa-chevron-down text-gray-500 text-sm transition-transform duration-200"></i>
											</button>
										<?php endif; ?>
									</div>

									<?php if (!empty($items)): ?>
										<!-- GROUP ITEMS -->
										<div class="mobile-items hidden pl-2 pb-2 space-y-1">
											<?php foreach (array_slice($items, 0, 6) as $it): ?>
												<a href="<?= htmlspecialchars($it['link_url'] ?? '#') ?>"
													class="block text-gray-700 text-sm px-3 py-2 rounded-xl
                                 hover:text-gray-900 hover:bg-gray-50/90
                                 active:bg-gray-100/90 transition">
													<?= htmlspecialchars($it['item_title'] ?? '') ?>
												</a>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>

							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

			</div>
		<?php endforeach; ?>
	</div>

	<!-- OPTIONAL FOOTER -->
	<div class="border-t border-gray-200/70 bg-white/70 px-4 py-4 text-sm flex items-center justify-between">
		<span class="text-gray-600">Free Shipping • 60-Day Returns</span>
		<a href="/E-commerce-shoes/view/content/products.php"
			class="font-semibold text-gray-900 hover:text-red-600 transition">
			View All →
		</a>
	</div>
</aside>

<script src="../../view/assets/Js/script.js"></script>
<script src="../../view/assets/Js/notification.js"></script>
<script src="../../view/assets/Js/search.js"></script>