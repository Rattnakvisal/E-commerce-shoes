document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  /* =========================
     HELPERS
  ========================= */
  const one = (s, ctx = document) => ctx.querySelector(s);
  const all = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));
  const isDesktop = () => window.matchMedia("(min-width: 1024px)").matches;

  const stop = (e) => {
    e.preventDefault();
    e.stopPropagation();
  };

  const isHidden = (el) => !el || el.classList.contains("hidden");

  /* =========================
     BODY LOCK (no scroll)
  ========================= */
  const setBodyLock = (locked) => {
    document.body.classList.toggle("overflow-hidden", locked);
  };

  /* =========================
     SMOOTH HEIGHT TOGGLE
  ========================= */
  function smoothToggle(el, shouldOpen, duration = 280) {
    if (!el) return;

    // reset old transition safely
    el.style.transition = "none";
    el.style.overflow = "hidden";

    if (shouldOpen) {
      el.classList.remove("hidden");
      el.style.height = "0px";

      requestAnimationFrame(() => {
        const h = el.scrollHeight;
        el.style.transition = `height ${duration}ms ease`;
        el.style.height = h + "px";
      });
    } else {
      el.style.height = el.scrollHeight + "px";
      requestAnimationFrame(() => {
        el.style.transition = `height ${duration}ms ease`;
        el.style.height = "0px";
      });
    }

    const done = () => {
      el.style.height = "";
      el.style.overflow = "";
      el.style.transition = "";
      if (!shouldOpen) el.classList.add("hidden");
    };

    el.addEventListener("transitionend", done, { once: true });
  }

  /* =========================
     GENERIC DROPDOWN MANAGER
     (close on outside click + ESC + only one open)
  ========================= */
  const dropdowns = new Set();

  function registerDropdown({
    trigger,
    panel,
    openClass = "is-open",
    useHidden = true,
  }) {
    if (!trigger || !panel) return;

    const open = () => {
      // close other dropdowns
      dropdowns.forEach((d) => {
        if (d.panel !== panel) d.close();
      });

      if (useHidden) panel.classList.remove("hidden");
      panel.classList.add(openClass);
      trigger.setAttribute("aria-expanded", "true");
    };

    const close = () => {
      panel.classList.remove(openClass);
      if (useHidden) panel.classList.add("hidden");
      trigger.setAttribute("aria-expanded", "false");
    };

    const toggle = () => {
      const opened =
        panel.classList.contains(openClass) ||
        !panel.classList.contains("hidden");
      opened ? close() : open();
    };

    trigger.addEventListener("click", (e) => {
      e.stopPropagation();
      toggle();
    });

    dropdowns.add({ trigger, panel, open, close, toggle });

    return { open, close, toggle };
  }

  function closeAllDropdowns() {
    dropdowns.forEach((d) => d.close());
  }

  document.addEventListener("click", (e) => {
    // outside click closes all dropdowns
    closeAllDropdowns();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeAllDropdowns();
      closeDrawer();
      closeSearchOverlay();
      closeMobileSearch();
    }
  });

  /* =========================
     MOBILE DRAWER
  ========================= */
  const mobileTrigger = one("#mobileMenuTrigger");
  const mobileMenu = one("#mobileMenu");
  const mobileOverlay = one("#mobileOverlay");
  const closeMobileBtn = one("#closeMobileMenuBtn");

  function openDrawer() {
    if (!mobileMenu || !mobileOverlay) return;
    mobileOverlay.classList.remove("hidden");
    mobileMenu.classList.remove("-translate-x-full");
    mobileMenu.classList.add("translate-x-0");
    mobileMenu.setAttribute("aria-hidden", "false");
    setBodyLock(true);

    // close dropdowns if any open
    closeAllDropdowns();
  }

  function closeDrawer() {
    if (!mobileMenu || !mobileOverlay) return;
    mobileOverlay.classList.add("hidden");
    mobileMenu.classList.add("-translate-x-full");
    mobileMenu.classList.remove("translate-x-0");
    mobileMenu.setAttribute("aria-hidden", "true");
    setBodyLock(false);
  }

  mobileTrigger?.addEventListener("click", (e) => {
    stop(e);
    openDrawer();
  });

  closeMobileBtn?.addEventListener("click", (e) => {
    stop(e);
    closeDrawer();
  });

  mobileOverlay?.addEventListener("click", closeDrawer);

  window.addEventListener("resize", () => {
    if (isDesktop()) closeDrawer();
  });

  /* =========================
     MOBILE SUBMENUS
  ========================= */
  all(".mobile-parent").forEach((parent) => {
    const btn = one(".parent-toggle", parent);
    const submenu = one(".mobile-submenu", parent);
    const arrow = one(".fa-chevron-right", parent);
    if (!btn || !submenu) return;

    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const willOpen = isHidden(submenu);

      // close other parent submenus
      all(".mobile-parent .mobile-submenu").forEach((s) => {
        if (s !== submenu && !isHidden(s)) smoothToggle(s, false);
      });
      all(".mobile-parent .fa-chevron-right").forEach((a) => {
        if (a !== arrow) a.classList.remove("rotate-90");
      });

      smoothToggle(submenu, willOpen);
      arrow?.classList.toggle("rotate-90", willOpen);
      btn.setAttribute("aria-expanded", String(willOpen));
    });
  });

  all(".mobile-group").forEach((group) => {
    const btn = one(".group-toggle", group);
    const list = one(".mobile-items", group);
    const arrow = one(".fa-chevron-down", group);
    if (!btn || !list) return;

    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const willOpen = isHidden(list);
      smoothToggle(list, willOpen);
      arrow?.classList.toggle("rotate-180", willOpen);
      btn.setAttribute("aria-expanded", String(willOpen));
    });
  });

  /* =========================
     DESKTOP MEGA MENU (hover)
     - delay to prevent flicker
     - safe zone: keeps open when mouse moves into menu
  ========================= */
  const megaParents = all(".mega-parent");

  megaParents.forEach((mp) => {
    const menu = one(".mega-menu-container", mp);
    const btn = one("button", mp);
    if (!menu || !btn) return;

    let openT = null;
    let closeT = null;

    const open = () => {
      clearTimeout(closeT);
      openT = setTimeout(() => {
        mp.classList.add("mega-open");
      }, 80);
    };

    const close = () => {
      clearTimeout(openT);
      closeT = setTimeout(() => {
        mp.classList.remove("mega-open");
      }, 120);
    };

    // mouse hover
    mp.addEventListener("mouseenter", open);
    mp.addEventListener("mouseleave", close);

    // keyboard (Tab focus)
    btn.addEventListener("focus", open);
    mp.addEventListener("focusout", (e) => {
      // if focus moved outside the mega parent, close it
      if (!mp.contains(e.relatedTarget)) close();
    });
  });

  /* IMPORTANT:
     Add this in your CSS:
     .mega-parent .mega-menu-container { opacity:0; visibility:hidden; pointer-events:none; }
     .mega-parent.mega-open .mega-menu-container { opacity:1; visibility:visible; pointer-events:auto; }
  */

  /* =========================
     USER + NOTIFICATION DROPDOWNS
  ========================= */
  const userDD = registerDropdown({
    trigger: one("#userMenuTrigger"),
    panel: one("#userDropdown"),
    openClass: "is-open",
    useHidden: true, // you currently use "hidden"
  });

  const notiDD = registerDropdown({
    trigger: one("#notificationTrigger"),
    panel: one("#notificationDropdown"),
    openClass: "is-open",
    useHidden: true,
  });

  /* =========================
     SEARCH OVERLAY (desktop results panel)
  ========================= */
  const searchOverlay = one("#globalSearchResults");
  const closeSearchBtn = one("#closeSearchResults");

  function openSearchOverlay() {
    if (!searchOverlay) return;
    searchOverlay.classList.remove("hidden");
  }

  function closeSearchOverlay() {
    if (!searchOverlay) return;
    searchOverlay.classList.add("hidden");
  }

  closeSearchBtn?.addEventListener("click", (e) => {
    stop(e);
    closeSearchOverlay();
  });

  // click outside overlay content closes it
  searchOverlay?.addEventListener("click", (e) => {
    if (e.target === searchOverlay) closeSearchOverlay();
  });

  // optional: open overlay when user types (if results exist)
  // You can call openSearchOverlay() inside your search.js after injecting results.

  /* =========================
     MOBILE SEARCH BAR
  ========================= */
  const mobileSearchTrigger = one("#mobileSearchTrigger");
  const mobileSearchBar = one("#mobileSearchBar");
  const closeMobileSearchBtn = one("#closeMobileSearch");
  const mobileSearchInput = one("#mobileSearchInput");
  const mobileSearchResults = one("#mobileSearchResults");

  function openMobileSearch() {
    if (!mobileSearchBar) return;
    mobileSearchBar.classList.remove("hidden");
    setTimeout(() => mobileSearchInput?.focus(), 50);
  }

  function closeMobileSearch() {
    if (!mobileSearchBar) return;
    mobileSearchBar.classList.add("hidden");
    mobileSearchResults?.classList.add("hidden");
  }

  mobileSearchTrigger?.addEventListener("click", (e) => {
    stop(e);
    openMobileSearch();
  });

  closeMobileSearchBtn?.addEventListener("click", (e) => {
    stop(e);
    closeMobileSearch();
  });

  /* =========================
     STOP CLICK INSIDE DROPDOWNS from closing
  ========================= */
  one("#userDropdown")?.addEventListener("click", (e) => e.stopPropagation());
  one("#notificationDropdown")?.addEventListener("click", (e) =>
    e.stopPropagation(),
  );
  searchOverlay
    ?.querySelector(".bg-white")
    ?.addEventListener("click", (e) => e.stopPropagation());
});
