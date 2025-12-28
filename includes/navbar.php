<?php
require_once __DIR__ . '/../config/connection.php';
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
// logged-in flag
$userLogged = !empty($_SESSION['user_id']);

$user_name = $_SESSION['name'] ?? $_SESSION['NAME'] ?? $_SESSION['user_name'] ?? $_SESSION['email'] ?? '';

$initials = '';
if (!empty($user_name)) {
	$parts = preg_split('/\s+/', trim($user_name));
	$initials = strtoupper((($parts[0][0] ?? '') . ($parts[1][0] ?? '')));
	$initials = trim($initials);
}
if (empty($initials) && !empty($_SESSION['email'])) {
	$local = explode('@', $_SESSION['email'])[0];
	$initials = strtoupper(substr($local, 0, 2));
}

// avatar URL: prefer session avatar, otherwise ui-avatars
$user_avatar_url = '';
if (!empty($_SESSION['avatar'])) {
	$user_avatar_url = $_SESSION['avatar'];
	if (!preg_match('#^https?://#i', $user_avatar_url) && $user_avatar_url[0] !== '/') {
		$user_avatar_url = '/' . ltrim($user_avatar_url, '/');
	}
} else {
	$user_avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_name ?: 'User') . '&background=10b981&color=fff';
}

// Navigation counts - determine current user and compute counts (per-user or guest)
$navUserId = $_SESSION['user_id'] ?? null;
$navCartCount = $navUserId ? array_sum($_SESSION["cart_user_{$navUserId}"] ?? []) : array_sum($_SESSION['cart_guest'] ?? []);
$navWishlistCount = $navUserId ? count($_SESSION["wishlist_user_{$navUserId}"] ?? []) : count($_SESSION['wishlist_guest'] ?? []);

try {
	$parents = $pdo->query("SELECT id, title, position FROM navbar_parents ORDER BY position, id")->fetchAll(PDO::FETCH_ASSOC);
	$groups = $pdo->query("SELECT id, parent_id, group_title, position, link_url FROM navbar_groups ORDER BY position, id")->fetchAll(PDO::FETCH_ASSOC);
	$items = $pdo->query("SELECT id, group_id, item_title, position, link_url FROM navbar_items ORDER BY position, id")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	echo '<!-- Navbar load error -->';
	return;
}

// Index groups by parent_id
$groupsByParent = [];
foreach ($groups as $g) {
	$pid = $g['parent_id'] ?? 0;
	$groupsByParent[$pid][] = $g;
}

// Index items by group_id
$itemsByGroup = [];
foreach ($items as $it) {
	$gid = $it['group_id'];
	$itemsByGroup[$gid][] = $it;
}

?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-p+1mYk0..." crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="/E-commerce-shoes/assets/Css/navbar.css">
<nav class="sticky top-0 bg-white shadow-sm border-b z-50">

	<div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">

		<!-- LOGO -->
		<a href="/E-commerce-shoes/view/index.php" class="flex items-center gap-2">
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

			<!-- Wishlist -->
			<a href="/E-commerce-shoes/view/wishlist.php" class="relative hidden md:block text-xl text-gray-700 hover:text-black">
				<i class="far fa-heart"></i>
				<span id="wishlistCount" class="wishlist-count absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
					<?= $navWishlistCount ?>
				</span>
			</a>

			<!-- Cart -->
			<a href="/E-commerce-shoes/view/cart.php" class="relative text-xl text-gray-700 hover:text-black">
				<i class="fas fa-shopping-bag"></i>
				<span id="cartCount" class="cart-count absolute -top-1 -right-2 bg-purple-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
					<?= $navCartCount ?>
				</span>
			</a>

			<!-- USER PROFILE -->
			<div class="relative">
				<?php if ($userLogged): ?>

					<button id="userMenuTrigger" class="flex items-center gap-3">
						<?php if (!empty($user_avatar_url)): ?>
							<img src="<?= htmlspecialchars($user_avatar_url) ?>" alt="User" class="w-9 h-9 rounded-full object-cover">
						<?php else: ?>
							<div class="w-9 h-9 rounded-full bg-gradient-to-r from-blue-600 to-purple-600 text-white flex items-center justify-center font-bold">
								<?= htmlspecialchars($initials) ?>
							</div>
						<?php endif; ?>
						<span class="hidden lg:inline text-sm text-gray-700"><?= htmlspecialchars($user_name) ?></span>
						<i class="fas fa-chevron-down text-xs text-gray-700"></i>
					</button>

					<!-- Profile Dropdown -->
					<div id="userDropdown"
						class="hidden absolute right-0 mt-3 bg-white rounded-xl shadow-xl border w-56 py-2">

						<div class="px-4 py-3 border-b">
							<div class="flex items-center gap-3">
								<?php if (!empty($user_avatar_url)): ?>
									<img src="<?= htmlspecialchars($user_avatar_url) ?>" alt="User" class="w-10 h-10 rounded-full object-cover">
								<?php else: ?>
									<div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-semibold"><?= htmlspecialchars($initials) ?></div>
								<?php endif; ?>
								<div>
									<div class="font-medium text-gray-900"><?= htmlspecialchars($user_name) ?></div>
									<div class="text-xs text-gray-500">Member</div>
								</div>
							</div>
						</div>

						<a href="profile.php" class="block px-4  py-3 text-gray-900 hover:bg-gray-100">My Profile</a>
						<a href="orders.php" class="block px-4 py-3 text-gray-900 hover:bg-gray-100">My Orders</a>
						<a href="wishlist.php" class="block px-4 py-3 text-gray-900 hover:bg-gray-100">Wishlist</a>
						<div class="border-t my-2"></div>
						<a href="/E-commerce-shoes/auth/logout.php"
							class="block px-4 py-3 text-red-600 hover:bg-gray-100">
							Logout
						</a>
					</div>

				<?php else: ?>

					<!-- Sign in -->
					<a href="/E-commerce-shoes/auth/login.php"
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
<script src="/E-commerce-shoes/assets/Js/script.js"></script>