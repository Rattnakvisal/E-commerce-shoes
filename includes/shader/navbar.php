<?php
require_once __DIR__ . '/../../config/conn.php';
require_once __DIR__ . '/../contract/navbar.php';

$parents = is_array($parents ?? null) ? $parents : [];
$groupsByParent = is_array($groupsByParent ?? null) ? $groupsByParent : [];
$itemsByGroup = is_array($itemsByGroup ?? null) ? $itemsByGroup : [];
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../../view/assets/Css/navbar.css">
<nav class="sticky top-0 bg-white shadow-sm border-b z-30">
	<div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">

		<!-- LOGO -->
		<a href="/MyBrand_Ecommerce/view/content/index.php" class="flex items-center gap-2">
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
									href="/MyBrand_Ecommerce/view/content/products.php"
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

			<!-- Desktop Search (Premium) -->
			<div class="hidden md:block relative">
				<div class="relative group">
					<!-- Icon bubble -->
					<div
						class="absolute left-2.5 top-1/2 -translate-y-1/2 h-9 w-9 rounded-full
				bg-white/70 border border-gray-200 text-gray-500
				flex items-center justify-center
				group-focus-within:bg-black group-focus-within:text-white group-focus-within:border-black/20
				transition">
						<i class="fas fa-search text-[13px]"></i>
					</div>

					<input
						id="desktopSearchInput"
						type="search"
						autocomplete="off"
						placeholder="Search products…"
						class="w-52 bg-gray-100/80 border border-gray-200/70 rounded-2xl
				py-2.5 pl-14 pr-10 text-sm
				shadow-[0_10px_30px_-18px_rgba(0,0,0,0.35)]
				outline-none transition-all duration-200
				focus:w-[360px] focus:bg-white focus:ring-4 focus:ring-black/5 focus:border-black/20
				placeholder:text-gray-400" />

					<!-- ESC hint -->
					<div class="absolute right-2.5 top-1/2 -translate-y-1/2 hidden lg:flex items-center gap-1">
						<span class="text-[10px] px-2 py-1 rounded-lg border border-gray-200 bg-white text-gray-500">
							ESC
						</span>
					</div>
				</div>
			</div>

			<!-- Backdrop (click to close) -->
			<div
				id="globalSearchBackdrop"
				class="hidden fixed inset-0 z-40 bg-black/25 backdrop-blur-[2px]"></div>
			<!-- Search results overlay (centered dropdown) -->
			<div
				id="globalSearchResults"
				class="hidden fixed top-20 left-1/2 -translate-x-1/2 w-[94%] max-w-4xl z-50">
				<div class="bg-white rounded-3xl shadow-[0_25px_80px_-30px_rgba(0,0,0,0.6)] border border-gray-200 overflow-hidden">
					<!-- Header -->
					<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
						<div class="flex items-center gap-3">
							<div class="h-9 w-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-500">
								<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round"
										d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z" />
								</svg>
							</div>

							<div class="leading-tight">
								<p class="text-sm font-extrabold text-gray-900">Search results</p>
								<p id="desktopSearchMeta" class="text-xs text-gray-500">Type to find products fast</p>
							</div>
						</div>

						<button
							id="closeSearchResults"
							class="h-9 w-9 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-100 active:scale-95 transition flex items-center justify-center"
							aria-label="Close"
							type="button">
							<i class="fas fa-times text-[14px]"></i>
						</button>
					</div>

					<!-- Results -->
					<div
						id="searchResultsContent"
						class="max-h-[420px] overflow-y-auto text-sm divide-y divide-gray-100 mt-5">
						<!-- Optional default empty state -->
						<div class="px-6 py-6 text-gray-500">
							<div class="flex items-start gap-3">
								<div class="h-10 w-10 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-500">
									<i class="fas fa-bolt text-[13px]"></i>
								</div>
								<div>
									<p class="font-semibold text-gray-900">Search your favorite shoes</p>
									<p class="text-xs text-gray-500 mt-1">Try “Air Max”, “Jordan”, “Adidas”…</p>
								</div>
							</div>
						</div>
					</div>

					<!-- Footer -->
					<div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-white">
						<button
							id="desktopSearchClear"
							type="button"
							class="text-xs font-bold text-gray-600 hover:text-black transition">
							Clear
						</button>

						<div class="text-[11px] text-gray-400 flex items-center gap-2">
							<span class="hidden lg:inline">Shortcut:</span>
							<span class="px-2 py-1 rounded-lg border border-gray-200 bg-gray-50 text-gray-500">Ctrl</span>
							<span class="text-gray-400">+</span>
							<span class="px-2 py-1 rounded-lg border border-gray-200 bg-gray-50 text-gray-500">K</span>
						</div>
					</div>
				</div>
			</div>
			<button id="mobileSearchTrigger" type="button" class="md:hidden text-xl text-gray-700">
				<i class="fas fa-search"></i>
			</button>

			<!-- notification -->
			<?php if ($userLogged): ?>
				<div class="relative md:block text-xl text-gray-700">
					<button id="notificationTrigger" class="relative focus:outline-none" aria-expanded="false" aria-haspopup="true">
						<i class="far fa-bell"></i>
						<span id="notificationCount" class="wishlist-count absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
					</button>
					<!-- Dropdown (Premium) -->
					<div
						id="notificationDropdown"
						class="hidden absolute left-1/2 -translate-x-1/2 mt-3 w-[340px]
						bg-white rounded-2xl shadow-[0_25px_80px_-30px_rgba(0,0,0,0.55)]
						border border-gray-200 z-50 overflow-hidden">
						<!-- Header -->
						<div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
							<div class="flex items-center gap-2">
								<div class="h-9 w-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-600">
									<i class="far fa-bell text-[14px]"></i>
								</div>
								<div class="leading-tight">
									<p class="text-sm font-extrabold text-gray-900">Notifications</p>
									<p id="notificationMeta" class="text-xs text-gray-500">Latest updates</p>
								</div>
							</div>

							<button
								id="closeNotification"
								type="button"
								class="h-9 w-9 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-100 active:scale-95 transition flex items-center justify-center"
								aria-label="Close">
								<i class="fas fa-times text-[14px]"></i>
							</button>
						</div>

						<!-- List -->
						<div
							id="notificationList"
							class="max-h-72 overflow-auto text-sm divide-y divide-gray-100">
							<div class="px-4 py-5 text-gray-500">
								<div class="flex items-start gap-3">
									<div class="h-10 w-10 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-500">
										<i class="fas fa-bell-slash text-[13px]"></i>
									</div>
									<div>
										<p class="font-extrabold text-gray-900">No notifications</p>
										<p class="text-xs text-gray-500 mt-1">You’re all caught up.</p>
									</div>
								</div>
							</div>
						</div>

						<!-- Footer -->
						<div class="border-t border-gray-100 bg-white px-4 py-3 flex items-center justify-between gap-3">
							<button
								id="markAllRead"
								type="button"
								class="text-sm font-bold text-blue-600 hover:underline">
								Mark all read
							</button>

							<button
								id="clearAll"
								type="button"
								class="text-sm font-bold text-red-600 hover:underline">
								Clear all
							</button>
						</div>
					</div>

				</div>
			<?php endif; ?>

			<!-- Wishlist -->
			<a href="/MyBrand_Ecommerce/view/content/wishlist.php"
				class="relative hidden md:block text-xl text-gray-700 hover:text-black">
				<i class="far fa-heart"></i>
				<span id="wishlistCount"
					class="absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
					<?= (int)$navWishlistCount ?>
				</span>
			</a>

			<!-- Cart -->
			<a href="/MyBrand_Ecommerce/view/content/cart.php"
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
					<button id="userMenuTrigger" type="button"
						class="nav-icon-btn focus-ring flex items-center gap-3"
						aria-haspopup="menu" aria-expanded="false">
						<?php if (!empty($userAvatar)): ?>
							<img src="<?= htmlspecialchars((string)$userAvatar) ?>" alt="User"
								class="w-9 h-9 rounded-full object-cover border border-gray-200">
						<?php else: ?>
							<div class="w-9 h-9 rounded-full bg-gradient-to-r from-blue-600 to-purple-600
                    text-white flex items-center justify-center font-extrabold text-sm shadow-sm">
								<?= htmlspecialchars($initials) ?>
							</div>
						<?php endif; ?>
					</button>

					<!-- Dropdown (NO hidden) -->
					<div id="userDropdown"
						class="dropdown-panel absolute right-0 mt-3 w-64 py-2 z-[60]"
						role="menu" aria-hidden="true">

						<!-- Header -->
						<div class="px-4 py-3 border-b">
							<div class="flex items-center gap-3 min-w-0">
								<div class="relative">
									<?php if (!empty($userAvatar)): ?>
										<img src="<?= htmlspecialchars((string)$userAvatar) ?>"
											alt="User avatar"
											class="w-10 h-10 rounded-full object-cover border border-gray-200">
									<?php else: ?>
										<div class="w-10 h-10 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-800 font-extrabold text-sm">
											<?= htmlspecialchars($initials) ?>
										</div>
									<?php endif; ?>

									<span class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></span>
								</div>

								<div class="min-w-0">
									<div class="font-extrabold text-gray-900 truncate">
										<?= htmlspecialchars((string)$userName) ?>
									</div>
									<div class="text-xs text-gray-500 truncate">Member</div>
								</div>
							</div>
						</div>

						<!-- Menu Items -->
						<a href="/MyBrand_Ecommerce/view/content/profile.php"
							class="dropdown-row flex items-center gap-3 mx-2 px-3 py-3 text-gray-900"
							role="menuitem">
							<i class="fas fa-user text-gray-400 w-4"></i>
							<span class="text-sm font-semibold">My Profile</span>
						</a>

						<a href="/MyBrand_Ecommerce/view/content/myorder.php"
							class="dropdown-row flex items-center gap-3 mx-2 px-3 py-3 text-gray-900"
							role="menuitem">
							<i class="fas fa-receipt text-gray-400 w-4"></i>
							<span class="text-sm font-semibold">My Orders</span>
						</a>

						<a href="/MyBrand_Ecommerce/view/content/wishlist.php"
							class="dropdown-row flex items-center gap-3 mx-2 px-3 py-3 text-gray-900"
							role="menuitem">
							<i class="fas fa-heart text-gray-400 w-4"></i>
							<span class="text-sm font-semibold">Wishlist</span>
						</a>

						<div class="border-t my-2"></div>

						<a href="/MyBrand_Ecommerce/auth/Log/logout.php"
							class="dropdown-row flex items-center gap-3 mx-2 px-3 py-3 text-red-600 hover:bg-red-50"
							role="menuitem">
							<i class="fas fa-right-from-bracket text-red-400 w-4"></i>
							<span class="text-sm font-extrabold">Logout</span>
						</a>
					</div>

				<?php else: ?>

					<a href="/MyBrand_Ecommerce/auth/Log/login.php"
						class="flex items-center gap-2 text-gray-700 hover:text-black">
						<div class="w-9 h-9 bg-gray-200 rounded-full flex items-center justify-center">
							<i class="far fa-user text-gray-600"></i>
						</div>
						<span class="hidden lg:inline text-sm font-semibold">Sign In</span>
					</a>

				<?php endif; ?>
			</div>


			<!-- MOBILE MENU BUTTON -->
			<button id="mobileMenuTrigger" type="button" class="lg:hidden text-2xl text-gray-700">
				<i class="fas fa-bars"></i>
			</button>

		</div>
	</div>

	<!-- Mobile Search Bar (Premium) -->
	<div id="mobileSearchBar" class="hidden md:hidden border-t border-gray-200 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/70">
		<div class="max-w-7xl mx-auto px-4 py-4">
			<div class="relative">

				<!-- Search Input -->
				<div class="relative group">
					<!-- left icon bubble -->
					<div class="absolute left-3 top-1/2 -translate-y-1/2 h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 group-focus-within:bg-black group-focus-within:text-white transition">
						<i class="fas fa-search text-[13px]"></i>
					</div>

					<input
						id="mobileSearchInput"
						type="search"
						inputmode="search"
						autocomplete="off"
						placeholder="Search products…"
						class="w-full rounded-2xl bg-white pl-14 pr-12 py-3.5 text-[15px] shadow-[0_10px_30px_-15px_rgba(0,0,0,0.35)]
                border border-gray-200/80 outline-none
                focus:border-black/20 focus:ring-4 focus:ring-black/5
                placeholder:text-gray-400" />

					<!-- Close Button -->
					<button
						id="closeMobileSearch"
						type="button"
						aria-label="Close search"
						class="absolute right-3 top-1/2 -translate-y-1/2 h-9 w-9 rounded-full
                bg-gray-100 text-gray-600 hover:bg-gray-200 active:scale-95 transition
                flex items-center justify-center">
						<i class="fas fa-times text-[14px]"></i>
					</button>
				</div>

				<!-- Dropdown -->
				<div id="mobileSearchResults" class="hidden absolute left-0 right-0 top-full mt-3 z-[80]">
					<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-[0_18px_50px_-20px_rgba(0,0,0,0.55)]">
						<!-- top small header -->
						<div class="px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
							<span>Results</span>
							<span id="mobileSearchMeta" class="text-[11px] font-medium text-gray-400"></span>
						</div>

						<!-- Content -->
						<div
							id="mobileSearchResultsContent"
							class="max-h-[55vh] overflow-y-auto text-sm divide-y divide-gray-100">
							<!-- Default empty hint (JS can overwrite) -->
							<div class="px-4 py-5 text-gray-500">
								<div class="flex items-start gap-3">
									<div class="h-9 w-9 rounded-xl bg-gray-100 flex items-center justify-center text-gray-500">
										<i class="fas fa-bolt text-[13px]"></i>
									</div>
									<div>
										<p class="font-semibold text-gray-800">Search your favorite shoes</p>
										<p class="text-xs text-gray-500 mt-1">Type to see products instantly.</p>
									</div>
								</div>
							</div>
						</div>

						<!-- Footer actions (optional) -->
						<div class="px-4 py-3 bg-white border-t border-gray-100 flex items-center justify-between">
							<button
								type="button"
								id="mobileSearchClear"
								class="text-xs font-semibold text-gray-600 hover:text-black transition">
								Clear
							</button>
							<span class="text-[11px] text-gray-400">Tip: press ESC to close</span>
						</div>
					</div>
				</div>

				<!-- Tap-outside overlay (optional, JS toggles) -->
				<div id="mobileSearchOverlay" class="hidden fixed inset-0 z-[70] bg-black/20 backdrop-blur-[1px]"></div>

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
		<a href="/MyBrand_Ecommerce/view/content/products.php"
			class="font-semibold text-gray-900 hover:text-red-600 transition">
			View All →
		</a>
	</div>
</aside>

<script src="../../view/assets/Js/script.js"></script>
<script src="../../view/assets/Js/notification.js"></script>
<script src="../../view/assets/Js/search.js"></script>
