document.addEventListener("DOMContentLoaded", () => {

    const $ = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));
    const one = (s, ctx = document) => ctx.querySelector(s);

    let openMega = null;

    /* -------------------------------------
       POSITION DROPDOWN CENTERED UNDER PARENT
    -------------------------------------- */
    function positionMega(menu, parent) {
        const rect = parent.getBoundingClientRect();
        const top = rect.bottom;
        menu.style.top = top + "px";
        menu.style.left = "50%";
        menu.style.transform = "translateX(-50%)";
    }

    /* -------------------------------------
       DESKTOP MEGA MENU
    -------------------------------------- */
    $(".mega-parent").forEach(parent => {
        // find a direct trigger element (button or anchor) without relying on :scope
        let trigger = parent.querySelector('button');
        if (!trigger) {
            // pick the first direct child that is an anchor
            trigger = Array.from(parent.children).find(c => c.tagName && c.tagName.toLowerCase() === 'a');
        }
        const menu = one(".mega-menu-container", parent);
        if (!menu || !trigger) return;

        parent.addEventListener("mouseenter", () => {
            if (window.innerWidth < 1024) return;
            closeAllMega();
            positionMega(menu, parent);
            menu.classList.add("active");
            openMega = menu;
        });

        parent.addEventListener("mouseleave", e => {
            if (window.innerWidth < 1024) return;
            if (!menu.contains(e.relatedTarget)) {
                closeAllMega();
            }
        });

        trigger.addEventListener("click", e => {
            if (window.innerWidth < 1024) return;

            // prevent navigation only when href is "#" or empty (we use # for dropdown triggers)
            if (trigger.tagName && trigger.tagName.toLowerCase() === 'a') {
                const href = trigger.getAttribute('href') || '';
                if (href.trim() === '#' || href.trim() === '') e.preventDefault();
            }

            const isOpen = menu.classList.contains("active");
            closeAllMega();
            if (!isOpen) {
                positionMega(menu, parent);
                menu.classList.add("active");
                openMega = menu;
            }
        });
    });

    function closeAllMega() {
        $(".mega-menu-container").forEach(m => m.classList.remove("active"));
        openMega = null;
    }

    /* -------------------------------------
       KEEP MENU FIXED WHILE SCROLLING
    -------------------------------------- */
    window.addEventListener("scroll", () => {
        if (!openMega) return;
        const parent = openMega.closest(".mega-parent");
        if (parent) positionMega(openMega, parent);
    });

    /* -------------------------------------
       CLICK OUTSIDE CLOSES MEGA MENU
    -------------------------------------- */
    document.addEventListener("click", e => {
        if (!e.target.closest(".mega-parent") &&
            !e.target.closest(".mega-menu-container")) {
            closeAllMega();
        }
    });

    /* -------------------------------------
       MOBILE MENU
    -------------------------------------- */
    const mobileTrigger = one("#mobileMenuTrigger");
    const mobileOverlay = one("#mobileOverlay") || one("#mobileOverlay") || one(".mobile-overlay");
    // support either #mobilePanel (older) or #mobileMenu (current) or .mobile-menu drawer
    const mobilePanel = one("#mobilePanel") || one("#mobileMenu") || one(".mobile-menu") || one(".mobile-menu-panel");
    const mobileClose = one("#closeMobileMenu") || one("#closeMobileMenuBtn");

    function openMobileMenu() {
        if (mobileOverlay) mobileOverlay.classList.remove('hidden');
        if (mobilePanel) {
            // handle Tailwind transform classes
            mobilePanel.classList.remove('-translate-x-full');
            mobilePanel.classList.add('translate-x-0');
            // also add generic active for compatibility with other scripts/styles
            mobilePanel.classList.add('active');
        }
        document.body.classList.add("overflow-hidden");
    }

    function closeMobileMenu() {
        if (mobileOverlay) mobileOverlay.classList.add('hidden');
        if (mobilePanel) {
            mobilePanel.classList.add('-translate-x-full');
            mobilePanel.classList.remove('translate-x-0');
            mobilePanel.classList.remove('active');
        }
        document.body.classList.remove("overflow-hidden");
    }

    mobileTrigger?.addEventListener("click", openMobileMenu);
    mobileOverlay?.addEventListener("click", closeMobileMenu);
    mobileClose?.addEventListener("click", closeMobileMenu);

    /* -------------------------------------
       MOBILE SEARCH
    -------------------------------------- */
    const mobileSearchTrigger = one("#mobileSearchTrigger");
    const mobileSearchBar = one("#mobileSearchBar");
    const closeMobileSearch = one("#closeMobileSearch");

    mobileSearchTrigger?.addEventListener("click", () => {
        mobileSearchBar.classList.toggle("hidden");
    });

    closeMobileSearch?.addEventListener("click", () => {
        mobileSearchBar.classList.add("hidden");
    });

    /* -------------------------------------
       USER PROFILE DROPDOWN
    -------------------------------------- */
    const userBtn = one("#userMenuTrigger");
    const userDropdown = one("#userDropdown");

    if (userBtn && userDropdown) {

        userBtn.addEventListener("click", e => {
            e.stopPropagation();
            userDropdown.classList.toggle("hidden");
        });

        userDropdown.addEventListener("click", e => e.stopPropagation());

        document.addEventListener("click", e => {
            if (!userDropdown.contains(e.target) &&
                !userBtn.contains(e.target)) {
                userDropdown.classList.add("hidden");
            }
        });

        document.addEventListener("keydown", e => {
            if (e.key === "Escape") userDropdown.classList.add("hidden");
        });
    }

    /* -------------------------------------
       ESC CLOSES EVERYTHING
    -------------------------------------- */
    document.addEventListener("keydown", e => {
        if (e.key === "Escape") {
            closeAllMega();
            closeMobileMenu();
            userDropdown?.classList.add("hidden");
        }
    });

    // When mobile menu closes, ensure all desktop/dropdown state is cleared too
    document.addEventListener('mobileMenuClosed', () => {
        try {
            closeAllMega();
            userDropdown?.classList.add('hidden');
        } catch (err) {
            // silent
        }
    });

});

document.addEventListener("DOMContentLoaded", () => {
    const mobileTrigger = document.querySelector("#mobileMenuTrigger");
    let mobileOverlay = document.querySelector("#mobileOverlay");
    const mobileMenu = document.querySelector("#mobileMenu");
    const closeMobileBtn = document.querySelector("#closeMobileMenu");

    // Ensure overlay exists (create if missing) and return it
    function ensureOverlay() {
        if (mobileOverlay) return mobileOverlay;
        const found = document.querySelector('#mobileOverlay');
        if (found) {
            mobileOverlay = found;
            return mobileOverlay;
        }

        const el = document.createElement('div');
        el.id = 'mobileOverlay';
        el.className = 'hidden fixed inset-0 bg-black bg-opacity-50';
        document.body.appendChild(el);
        mobileOverlay = el;
        return mobileOverlay;
    }

    /* ------------------------------------------------------------------
       UTILITY: Smooth Height Transition
    ------------------------------------------------------------------ */
    function smoothToggle(element, expand) {
        if (!element) return;

        if (expand) {
            element.classList.remove("hidden");
            element.style.height = "0px";
            element.style.overflow = "hidden";

            requestAnimationFrame(() => {
                const fullHeight = element.scrollHeight + "px";
                element.style.height = fullHeight;
                element.style.transition = "height 0.35s cubic-bezier(.4,0,.2,1)";
            });

            element.addEventListener("transitionend", () => {
                element.style.height = "";
                element.style.overflow = "";
                element.style.transition = "";
            }, { once: true });

        } else {
            element.style.height = element.scrollHeight + "px";
            element.style.overflow = "hidden";

            requestAnimationFrame(() => {
                element.style.height = "0px";
                element.style.transition = "height 0.3s cubic-bezier(.4,0,.2,1)";
            });

            element.addEventListener("transitionend", () => {
                element.classList.add("hidden");
                element.style.height = "";
                element.style.overflow = "";
                element.style.transition = "";
            }, { once: true });
        }
    }

    /* ------------------------------------------------------------------
       OPEN MOBILE MENU (Nike Smooth Slide)
    ------------------------------------------------------------------ */
    function openMobileMenu() {
        if (!mobileMenu) return;

        const overlay = ensureOverlay();
        if (overlay && overlay.classList) {
            overlay.classList.remove("hidden");
            overlay.classList.add("opacity-100");
            overlay.style.transition = "opacity 0.35s ease";
        }

        if (mobileMenu && mobileMenu.classList) {
            mobileMenu.classList.remove("-translate-x-full");
            mobileMenu.classList.add("translate-x-0");
            mobileMenu.style.transition = "transform 0.45s cubic-bezier(.32,.72,0,1)";
        }

        if (document.body && document.body.classList) document.body.classList.add("overflow-hidden");
    }

    /* ------------------------------------------------------------------
       CLOSE MOBILE MENU (Smooth Slide Back)
    ------------------------------------------------------------------ */
    function closeMobileMenu() {
        if (!mobileMenu) return;

        const overlay = ensureOverlay();
        if (overlay && overlay.classList) {
            overlay.classList.remove("opacity-100");
            overlay.classList.add("hidden");
        }

        if (mobileMenu && mobileMenu.classList) {
            mobileMenu.classList.remove("translate-x-0");
            mobileMenu.classList.add("-translate-x-full");
            mobileMenu.style.transition = "transform 0.40s cubic-bezier(.32,.72,0,1)";
        }

        if (document.body && document.body.classList) document.body.classList.remove("overflow-hidden");
    }

    mobileTrigger?.addEventListener("click", openMobileMenu);
    closeMobileBtn?.addEventListener("click", closeMobileMenu);
    // Ensure overlay exists when attaching click handler (creates it only if needed)
    const ov = ensureOverlay();
    if (ov && ov.addEventListener) ov.addEventListener("click", closeMobileMenu);

    /* ------------------------------------------------------------------
       MOBILE PARENT DROPDOWN (1st Level)
    ------------------------------------------------------------------ */
    document.querySelectorAll(".mobile-parent").forEach(parent => {
        const toggle = parent.querySelector(".parent-toggle");
        const submenu = parent.querySelector(".mobile-submenu");
        const arrow = parent.querySelector(".fa-chevron-right");

        if (!toggle || !submenu || !arrow) return;

        toggle.addEventListener("click", () => {

            const isOpen = !submenu.classList.contains("hidden");

            // Close all sections (Nike behavior)
            document.querySelectorAll(".mobile-submenu").forEach(s => {
                if (!s.classList.contains("hidden")) smoothToggle(s, false);
            });

            document.querySelectorAll(".mobile-parent .fa-chevron-right")
                .forEach(a => a.classList.remove("rotate-90"));

            if (!isOpen) {
                smoothToggle(submenu, true);
                arrow.classList.add("rotate-90");
            }
        });
    });

    /* ------------------------------------------------------------------
       MOBILE GROUP DROPDOWN (2nd Level)
    ------------------------------------------------------------------ */
    document.querySelectorAll(".mobile-group").forEach(group => {
        const toggle = group.querySelector(".group-toggle");
        const list = group.querySelector(".mobile-items");
        const arrow = group.querySelector(".fa-chevron-down");

        if (!toggle || !list || !arrow) return;

        toggle.addEventListener("click", () => {
            const isOpen = !list.classList.contains("hidden");

            smoothToggle(list, !isOpen);
            arrow.classList.toggle("rotate-180", !isOpen);
        });
    });

    /* ------------------------------------------------------------------
       ESC closes everything
    ------------------------------------------------------------------ */
    document.addEventListener("keydown", e => {
        if (e.key === "Escape") closeMobileMenu();
    });
});
