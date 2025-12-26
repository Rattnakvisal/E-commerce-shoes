document.addEventListener("DOMContentLoaded", () => {

    /* =====================================================
       HELPERS
    ===================================================== */
    const $ = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));
    const one = (s, ctx = document) => ctx.querySelector(s);

    let openMega = null;

    function closeAllMega() {
        $(".mega-menu-container").forEach(m => m.classList.remove("active"));
        openMega = null;
    }

    /* =====================================================
       DESKTOP MEGA MENU
    ===================================================== */
    function positionMega(menu, parent) {
        const rect = parent.getBoundingClientRect();
        menu.style.top = rect.bottom + "px";
        menu.style.left = "50%";
        menu.style.transform = "translateX(-50%)";
    }

    $(".mega-parent").forEach(parent => {
        const trigger =
            parent.querySelector("button") ||
            Array.from(parent.children).find(c => c.tagName === "A");

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
            if (!menu.contains(e.relatedTarget)) closeAllMega();
        });

        trigger.addEventListener("click", e => {
            if (window.innerWidth < 1024) return;

            if (trigger.tagName === "A") {
                const href = trigger.getAttribute("href") || "";
                if (!href || href === "#") e.preventDefault();
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

    window.addEventListener("scroll", () => {
        if (!openMega) return;
        const parent = openMega.closest(".mega-parent");
        if (parent) positionMega(openMega, parent);
    });

    document.addEventListener("click", e => {
        if (!e.target.closest(".mega-parent") &&
            !e.target.closest(".mega-menu-container")) {
            closeAllMega();
        }
    });

    /* =====================================================
       MOBILE MENU (DRAWER)
    ===================================================== */
    const mobileTrigger = one("#mobileMenuTrigger");
    const mobileMenu = one("#mobileMenu");
    const closeMobileBtn = one("#closeMobileMenu");
    let mobileOverlay = one("#mobileOverlay");

    function ensureOverlay() {
        if (mobileOverlay) return mobileOverlay;
        const el = document.createElement("div");
        el.id = "mobileOverlay";
        el.className = "fixed inset-0 bg-black bg-opacity-50 hidden";
        document.body.appendChild(el);
        mobileOverlay = el;
        return el;
    }

    function openMobileMenu() {
        ensureOverlay().classList.remove("hidden");
        mobileMenu?.classList.remove("-translate-x-full");
        mobileMenu?.classList.add("translate-x-0");
        document.body.classList.add("overflow-hidden");
    }

    function closeMobileMenu() {
        mobileOverlay?.classList.add("hidden");
        mobileMenu?.classList.add("-translate-x-full");
        mobileMenu?.classList.remove("translate-x-0");
        document.body.classList.remove("overflow-hidden");
    }

    mobileTrigger?.addEventListener("click", openMobileMenu);
    closeMobileBtn?.addEventListener("click", closeMobileMenu);
    ensureOverlay().addEventListener("click", closeMobileMenu);

    /* =====================================================
       SMOOTH HEIGHT TOGGLE (MOBILE DROPDOWNS)
    ===================================================== */
    function smoothToggle(el, open) {
        if (!el) return;

        el.style.overflow = "hidden";
        el.style.height = open ? "0px" : el.scrollHeight + "px";
        el.classList.toggle("hidden", false);

        requestAnimationFrame(() => {
            el.style.transition = "height 0.35s ease";
            el.style.height = open ? el.scrollHeight + "px" : "0px";
        });

        el.addEventListener("transitionend", () => {
            el.style.height = "";
            el.style.overflow = "";
            el.style.transition = "";
            if (!open) el.classList.add("hidden");
        }, { once: true });
    }

    /* =====================================================
       MOBILE PARENT (LEVEL 1)
    ===================================================== */
    $(".mobile-parent").forEach(parent => {
        const toggle = parent.querySelector(".parent-toggle");
        const submenu = parent.querySelector(".mobile-submenu");
        const arrow = parent.querySelector(".fa-chevron-right");

        toggle?.addEventListener("click", () => {
            const open = !submenu.classList.contains("hidden");

            $(".mobile-submenu").forEach(s => s !== submenu && smoothToggle(s, false));
            $(".mobile-parent .fa-chevron-right")
                .forEach(a => a !== arrow && a.classList.remove("rotate-90"));

            smoothToggle(submenu, !open);
            arrow?.classList.toggle("rotate-90", !open);
        });
    });

    /* =====================================================
       MOBILE GROUP (LEVEL 2)
    ===================================================== */
    $(".mobile-group").forEach(group => {
        const toggle = group.querySelector(".group-toggle");
        const list = group.querySelector(".mobile-items");
        const arrow = group.querySelector(".fa-chevron-down");

        toggle?.addEventListener("click", () => {
            const open = !list.classList.contains("hidden");
            smoothToggle(list, !open);
            arrow?.classList.toggle("rotate-180", !open);
        });
    });

    /* =====================================================
       MOBILE SEARCH
    ===================================================== */
    one("#mobileSearchTrigger")?.addEventListener("click", () =>
        one("#mobileSearchBar")?.classList.toggle("hidden")
    );

    one("#closeMobileSearch")?.addEventListener("click", () =>
        one("#mobileSearchBar")?.classList.add("hidden")
    );

    /* =====================================================
       USER DROPDOWN
    ===================================================== */
    const userBtn = one("#userMenuTrigger");
    const userDropdown = one("#userDropdown");

    userBtn?.addEventListener("click", e => {
        e.stopPropagation();
        userDropdown?.classList.toggle("hidden");
    });

    document.addEventListener("click", () =>
        userDropdown?.classList.add("hidden")
    );

    /* =====================================================
       ESC CLOSES EVERYTHING
    ===================================================== */
    document.addEventListener("keydown", e => {
        if (e.key !== "Escape") return;
        closeAllMega();
        closeMobileMenu();
        userDropdown?.classList.add("hidden");
    });

});
