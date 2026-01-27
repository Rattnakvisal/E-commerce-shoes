<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../contract/navbar.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-p+1mYk0..." crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="../../assets/Css/navbar.css">
<nav class="sticky top-0 bg-white shadow-sm border-b z-50">

	<div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">

		<!-- LOGO -->
		<a href="/E-commerce-shoes/view/content/index.php" class="flex items-center gap-2">
			<div class="w-8 h-8 rounded-full bg-black text-white font-bold flex items-center justify-center">✓</div>
			<span class="text-xl font-bold text-gray-900">MyBrand</span>
		</a>

		<!-- DESKTOP NAVIGATION -->
		<div class="hidden lg:flex items-center gap-6 ">
			<?php foreach ($parents as $p): ?>
				<?php $groups = $groupsByParent[$p['id']] ?? []; ?>
				<?php if (!empty($groups)): ?>
					<!-- Parent with dropdown -->
					<div class="mega-parent relative">
						<button class="px-4 py-2 font-medium text-gray-700 hover:underline hover:text-black">
							<?= htmlspecialchars($p['title']) ?>
						</button>
						<!-- Desktop Mega Menu -->
						<div class="mega-menu-container">
							<div class="p-6 grid grid-cols-5 gap-8">
								<?php foreach ($groups as $g): ?>
									<?php $items = $itemsByGroup[$g['id']] ?? []; ?>
									<div>
										<div class="font-semibold text-gray-900 mb-2 pointer">
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
								<span class="font-semibold hover:text-red-600 cursor-pointer">View All →</span>
							</div>
						</div>
					</div>

				<?php else: ?>

					<!-- Normal nav item -->
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

			<!-- Mobile Search Button -->
			<button id="mobileSearchTrigger" class="md:hidden text-xl text-gray-700">
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
			<a href="/E-commerce-shoes/view/content/wishlist.php" class="relative hidden md:block text-xl text-gray-700 hover:text-black">
				<i class="far fa-heart"></i>
				<span id="wishlistCount" class="wishlist-count absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
					<?= $navWishlistCount ?>
				</span>
			</a>

			<!-- Cart -->
			<a href="/E-commerce-shoes/view/content/cart.php" class="relative text-xl text-gray-700 hover:text-black">
				<i class="fas fa-shopping-bag"></i>
				<span id="cartCount" class="cart-count absolute -top-1 -right-2 bg-purple-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
					<?= $navCartCount ?>
				</span>
			</a>

			<!-- USER PROFILE -->
			<div class="relative">
				<?php if ($userLogged): ?>

					<button id="userMenuTrigger" class="flex items-center gap-3">
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

					<!-- Profile Dropdown -->
					<div id="userDropdown"
						class="hidden absolute right-0 mt-3 bg-white rounded-xl shadow-xl border w-56 py-2">

						<div class="px-4 py-3 border-b">
							<div class="flex items-center gap-3">
								<?php if (!empty($userAvatar)): ?>
									<img src="<?= htmlspecialchars((string)$userAvatar) ?>" alt="User" class="w-10 h-10 rounded-full object-cover">
								<?php else: ?>
									<div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-semibold"><?= htmlspecialchars($initials) ?></div>
								<?php endif; ?>
								<div>
									<div class="font-medium text-gray-900"><?= htmlspecialchars((string)$userName) ?></div>
									<div class="text-xs text-gray-500">Member</div>
								</div>
							</div>
						</div>

						<a href="profile.php" class="block px-4  py-3 text-gray-900 hover:bg-gray-100">My Profile</a>
						<a href="myorder.php" class="block px-4 py-3 text-gray-900 hover:bg-gray-100">My Orders</a>
						<a href="wishlist.php" class="block px-4 py-3 text-gray-900 hover:bg-gray-100">Wishlist</a>
						<div class="border-t my-2"></div>
						<a href="/E-commerce-shoes/auth/Log/logout.php"
							class="block px-4 py-3 text-red-600 hover:bg-gray-100">
							Logout
						</a>
					</div>

				<?php else: ?>

					<!-- Sign in -->
					<a href="/E-commerce-shoes/auth/Log/login.php"
						class="flex items-center gap-2 text-gray-700 hover:text-black">
						<div class="w-9 h-9 bg-gray-200 rounded-full flex items-center justify-center">
							<i class="far fa-user text-gray-600"></i>
						</div>
						<span class="hidden lg:inline text-sm">Sign In</span>
					</a>

				<?php endif; ?>
			</div>

			<!-- Mobile Menu Button -->
			<button id="mobileMenuTrigger" class="lg:hidden text-2xl text-gray-700">
				<i class="fas fa-bars"></i>
			</button>

		</div>
	</div>

	<!-- MOBILE SEARCH BAR -->
	<div id="mobileSearchBar" class="hidden px-4 py-4 bg-gray-100 border-t">
		<div class="relative">
			<input placeholder="Search products..."
				class="w-full bg-white rounded-full py-3 pl-12 pr-12 shadow">
			<i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
			<button id="closeMobileSearch" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">
				<i class="fas fa-times"></i>
			</button>
		</div>
	</div>

</nav>
<!-- MOBILE OVERLAY -->
<div id="mobileOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40"></div>
<!-- MOBILE MENU PANEL -->
<div id="mobileMenu" class="mobile-menu fixed top-0 left-0 w-[85%] max-w-sm bg-white h-full p-5 transform -translate-x-full z-50 overflow-y-auto">

	<!-- Close Button -->
	<button id="closeMobileMenu"
		class="text-2xl text-gray-600 mb-5 active:scale-90 transition">
		&times;
	</button>

	<!-- Brand -->
	<div class="flex items-center gap-3 mb-6">
		<div class="w-9 h-9 bg-black text-white rounded-full flex justify-center items-center font-bold">
			✓
		</div>
		<span class="text-xl font-semibold text-gray-800">MyBrand</span>
	</div>

	<!-- MOBILE NAV -->
	<?php foreach ($parents as $p): ?>
		<?php $groups = $groupsByParent[$p['id']] ?? []; ?>

		<div class="mobile-parent border-b py-2">

			<!-- PARENT ROW -->
			<div class="
                flex items-center justify-between px-2 py-3 cursor-pointer hover:bg-gray-50
                parent-toggle transition active:scale-[0.98] active:bg-gray-100 duration-150 rounded-lg
            ">
				<span class="text-gray-900 font-medium">
					<?= htmlspecialchars($p['title']) ?>
				</span>

				<?php if (!empty($groups)): ?>
					<i class="fas fa-chevron-right text-gray-500 transition-transform duration-200"></i>
				<?php endif; ?>
			</div>

			<!-- SUBMENU -->
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
									<span class="text-gray-800 font-semibold">
										<?= htmlspecialchars($g['group_title']) ?>
									</span>
								<?php endif; ?>

								<?php if (!empty($items)): ?>
									<button class="group-toggle text-gray-400">
										<i class="fas fa-chevron-down text-gray-400 text-sm transition-transform"></i>
									</button>
								<?php endif; ?>
							</div>

							<!-- ITEMS -->
							<?php if (!empty($items)): ?>
								<div class="mobile-items hidden pl-4 space-y-1">

									<?php foreach (array_slice($items, 0, 6) as $it): ?>
										<a href="<?= htmlspecialchars($it['link_url'] ?? '#') ?>"
											class="
                                                block text-gray-600 text-sm py-2 hover:text-black hover:bg-gray-50
                                                transition active:scale-[0.98] active:bg-gray-100 rounded-lg
                                            ">
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
<script src="../../assets/Js/script.js"></script>
<script>
	(function() {
		function esc(s) {
			return String(s || '').replace(/[&<>"'\\]/g, function(c) {
				return {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					"'": '&#39;',
					'\\': '\\\\'
				} [c];
			});
		}

		const apiBase = '/E-commerce-shoes/admin/process/notifications_api.php';
		const countEl = document.getElementById('notificationCount');
		const listEl = document.getElementById('notificationList');
		const dropdown = document.getElementById('notificationDropdown');
		const trigger = document.getElementById('notificationTrigger');
		const markAllBtn = document.getElementById('markAllRead');

		async function fetchCount() {
			try {
				const res = await fetch(apiBase + '?action=fetch_unread_count', {
					credentials: 'same-origin'
				});
				if (!res.ok) return;
				const j = await res.json();
				if (j && j.ok) {
					countEl.textContent = Number(j.unread || 0);
				}
			} catch (e) {}
		}

		async function fetchLatest() {
			try {
				const res = await fetch(apiBase + '?action=fetch_latest', {
					credentials: 'same-origin'
				});
				if (!res.ok) return;
				const j = await res.json();
				if (!(j && j.ok)) return;
				listEl.innerHTML = '';
				if (!Array.isArray(j.items) || j.items.length === 0) {
					listEl.innerHTML = '<div class="p-3 text-gray-600">No notifications</div>';
					return;
				}
				j.items.forEach(it => {
					const row = document.createElement('div');
					row.className = 'notification-row px-3 py-2 hover:bg-gray-50 border-b cursor-pointer';
					row.dataset.id = it.notification_id ?? '';
					row.dataset.read = (it.is_read ? '1' : '0');
					row.innerHTML = '<div class="flex justify-between items-start gap-3">' +
						'<div class="flex-1">' +
						'<div class="font-medium">' + esc(it.title) + '</div>' +
						'<div class="text-gray-600 text-xs mt-1">' + esc(it.message) + '</div>' +
						'<div class="text-gray-400 text-xs mt-1">' + esc(it.created_at) + '</div>' +
						'</div>' +
						'<div class="flex-shrink-0 pl-2">' +
						'<button class="notif-delete text-red-500 text-xs">Clear</button>' +
						'</div>' +
						'</div>';
					if (it.is_read == 0) {
						row.classList.add('font-semibold');
					}
					listEl.appendChild(row);
				});

				listEl.querySelectorAll('.notification-row').forEach(r => {
					r.addEventListener('click', async function(ev) {
						const nid = this.dataset.id;
						if (!nid) return;
						try {
							const res = await fetch(apiBase + '?action=mark_read', {
								method: 'POST',
								credentials: 'same-origin',
								body: new URLSearchParams({
									id: nid
								})
							});
							if (!res.ok) return;
							const jj = await res.json();
							if (jj && jj.ok) {
								await fetchCount();
								await fetchLatest();
							}
						} catch (e) {}
					});
					// delete button inside row -> delete single notification
					r.querySelectorAll('.notif-delete').forEach(btn => {
						btn.addEventListener('click', async function(ev) {
							ev.stopPropagation();
							const nid = r.dataset.id;
							if (!nid) return;
							try {
								const res = await fetch(apiBase + '?action=delete', {
									method: 'POST',
									credentials: 'same-origin',
									body: new URLSearchParams({
										id: nid
									})
								});
								if (!res.ok) return;
								const jj = await res.json();
								if (jj && jj.ok) {
									await fetchCount();
									await fetchLatest();
								}
							} catch (e) {}
						});
					});
				});
			} catch (e) {}
		}

		trigger?.addEventListener('click', async function(e) {
			e.preventDefault();
			if (!dropdown) return;
			dropdown.classList.toggle('hidden');
			if (!dropdown.classList.contains('hidden')) {
				await fetchLatest();
			}
		});

		markAllBtn?.addEventListener('click', async function() {
			try {
				const res = await fetch(apiBase + '?action=mark_all_read', {
					method: 'POST',
					credentials: 'same-origin'
				});
				const j = await res.json();
				if (j && j.ok) {
					await fetchCount();
					await fetchLatest();
				}
			} catch (e) {}
		});

		const clearAllBtn = document.getElementById('clearAll');
		clearAllBtn?.addEventListener('click', async function() {
			try {
				const res = await fetch(apiBase + '?action=delete_all', {
					method: 'POST',
					credentials: 'same-origin'
				});
				if (!res.ok) return;
				const j = await res.json();
				if (j && j.ok) {
					await fetchCount();
					await fetchLatest();
				}
			} catch (e) {}
		});

		// init
		fetchCount();
		// poll
		setInterval(fetchCount, 30000);
	})();
</script>