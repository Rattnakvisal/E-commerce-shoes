/* =====================================================
    MOBILE FILTER DRAWER
===================================================== */

/**
 * Toggle mobile filter drawer & overlay
 */
function toggleMobileFilters() {
    const overlay = document.getElementById('mobileFiltersOverlay');
    const drawer = document.getElementById('mobileFiltersDrawer');
    const isOpen = !overlay.classList.contains('hidden');

    if (!isOpen) {
        overlay.classList.remove('hidden');
        toggleBodyScroll(true);

        setTimeout(() => {
            drawer.classList.remove('translate-x-full');
        }, 10);

        syncFilterForms('desktopToMobile');
    } else {
        drawer.classList.add('translate-x-full');

        setTimeout(() => {
            overlay.classList.add('hidden');
            toggleBodyScroll(false);
        }, 300);
    }
}

/**
 * Disable / enable body scroll
 */
function toggleBodyScroll(disable) {
    document.body.style.overflow = disable ? 'hidden' : '';
}

/**
 * Close drawer when clicking overlay
 */
document.getElementById('mobileFiltersOverlay')?.addEventListener('click', e => {
    if (e.target.id === 'mobileFiltersOverlay') {
        toggleMobileFilters();
    }
});

/* =====================================================
   FILTER FORM SYNC
===================================================== */

/**
 * Sync values between desktop & mobile filter forms
 */
function syncFilterForms(direction) {
    const desktopForm = document.getElementById('desktopFiltersForm');
    const mobileForm = document.getElementById('mobileFiltersForm');

    if (!desktopForm || !mobileForm) return;

    const sourceForm = direction === 'desktopToMobile' ? desktopForm : mobileForm;
    const targetForm = direction === 'desktopToMobile' ? mobileForm : desktopForm;

    const formData = new FormData(sourceForm);
    const fields = ['availability', 'category', 'gender', 'price_min', 'price_max'];

    fields.forEach(name => {
        const value = formData.get(name);
        const inputs = targetForm.querySelectorAll(`[name="${name}"]`);

        inputs.forEach(input => {
            if (input.type === 'radio' || input.type === 'checkbox') {
                input.checked = input.value === value;
            } else {
                input.value = value ?? '';
            }
        });
    });

    // Sync mobile slider display
    if (direction === 'desktopToMobile') {
        updateMobilePriceUI();
    }
}

/**
 * Apply mobile filters
 */
function applyMobileFilters() {
    syncFilterForms('mobileToDesktop');
    document.getElementById('desktopFiltersForm')?.submit();
}

/* =====================================================
   PRICE SLIDER (MOBILE)
===================================================== */

const mobilePriceSlider = document.getElementById('mobilePriceSlider');

if (mobilePriceSlider) {
    mobilePriceSlider.addEventListener('input', () => {
        setMobilePrice('max', mobilePriceSlider.value);
    });
}

/**
 * Update price labels & inputs
 */
function updateMobilePriceUI(min, max) {
    if (min !== undefined) {
        document.getElementById('mobileMinPriceValue').textContent = min;
    }
    if (max !== undefined) {
        document.getElementById('mobileMaxPriceValue').textContent = max;
        document.querySelector('#mobileFiltersForm [name="price_max"]').value = max;
    }
}

/* =====================================================
   TOAST NOTIFICATION
===================================================== */

/**
 * Show toast message
 */
function showToast(message, type = 'success') {
    let toast = document.getElementById('globalToast');

    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'globalToast';
        toast.className =
            'fixed bottom-4 right-4 px-6 py-3 rounded shadow-lg text-white z-50 transition-opacity';
        document.body.appendChild(toast);
    }

    toast.classList.toggle('bg-red-600', type === 'error');
    toast.classList.toggle('bg-black', type !== 'error');

    toast.textContent = message;
    toast.style.opacity = '1';

    setTimeout(() => {
        toast.style.opacity = '0';
    }, 1800);
}

/* =====================================================
   CART & WISHLIST
===================================================== */

/**
 * Add product to cart (AJAX)
 */
async function addToCart(productId, qty = 1) {
    try {
        const res = await fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'add',
                product_id: productId,
                quantity: qty
            })
        });

        const data = await res.json();
        const count = data.cart_count ?? data.count;

        if (count !== undefined) {
            updateBadge('.cart-count', count, 'cartCount');
            showToast('Added to cart');
        } else {
            showToast('Could not add to cart', 'error');
        }
    } catch (err) {
        console.error('Cart error', err);
        showToast('Error adding to cart', 'error');
    }
}

/**
 * Add product to wishlist (AJAX)
 */
async function addToWishlist(productId) {
    try {
        const res = await fetch('wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'add',
                product_id: productId
            })
        });

        const data = await res.json();
        const count = data.wishlist_count ?? data.count;

        if (count !== undefined) {
            updateBadge('.wishlist-count', count, 'wishlistCount');
            showToast('Added to wishlist');
        } else {
            showToast('Could not add to wishlist', 'error');
        }
    } catch (err) {
        console.error('Wishlist error', err);
        showToast('Error adding to wishlist', 'error');
    }
}

/**
 * Update navbar badge counters
 */
function updateBadge(selector, count, idFallback) {
    document.querySelectorAll(selector).forEach(el => el.textContent = count);
    const fallback = document.getElementById(idFallback);
    if (fallback) fallback.textContent = count;
}

