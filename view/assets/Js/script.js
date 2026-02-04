(() => {
  // ==========================================================
  // Helpers
  // ==========================================================
  const qs = (s, p = document) => p.querySelector(s);
  const qsa = (s, p = document) => Array.from(p.querySelectorAll(s));

  const OPEN_CLASS = "dropdown-open";
  const MEGA_OPEN_CLASS = "mega-open";

  const isOpen = (el) => !!el && el.classList.contains(OPEN_CLASS);
  const openEl = (el) => el && el.classList.add(OPEN_CLASS);
  const closeEl = (el) => el && el.classList.remove(OPEN_CLASS);

  const setAria = (btn, panel, opened) => {
    if (btn) btn.setAttribute("aria-expanded", String(opened));
    if (panel) panel.setAttribute("aria-hidden", String(!opened));
  };

  const isInside = (node, target) => node && target && node.contains(target);

  // ==========================================================
  // Reusable dropdown controller
  // ==========================================================
  function setupDropdown({ btn, panel, onOpen, onClose }) {
    if (!btn || !panel) return null;

    // Basic a11y
    btn.setAttribute("aria-haspopup", "menu");
    btn.setAttribute("aria-expanded", "false");
    panel.setAttribute("aria-hidden", "true");

    // Ensure panel can be focused for keyboard users
    if (!panel.hasAttribute("tabindex")) panel.setAttribute("tabindex", "-1");

    const api = {
      btn,
      panel,
      open() {
        openEl(panel);
        setAria(btn, panel, true);
        onOpen?.();
      },
      close() {
        closeEl(panel);
        setAria(btn, panel, false);
        onClose?.();
      },
      toggle() {
        if (isOpen(panel)) api.close();
        else api.open();
      },
      isOpen() {
        return isOpen(panel);
      },
      isTargetInside(target) {
        return isInside(btn, target) || isInside(panel, target);
      },
    };

    // Click toggle
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      api.toggle();
    });

    // Prevent clicks inside panel from bubbling to document
    panel.addEventListener("click", (e) => e.stopPropagation());

    // Keyboard: Enter/Space to toggle, ArrowDown to open & focus first item
    btn.addEventListener("keydown", (e) => {
      const key = e.key;

      if (key === "Enter" || key === " ") {
        e.preventDefault();
        api.toggle();
      }

      if (key === "ArrowDown") {
        e.preventDefault();
        if (!api.isOpen()) api.open();
        focusFirstMenuItem(panel);
      }
    });

    // Close when focus moves outside (Tab away)
    panel.addEventListener("focusout", () => {
      // Delay so the next focused element is available
      requestAnimationFrame(() => {
        const active = document.activeElement;
        if (!isInside(panel, active) && !isInside(btn, active)) api.close();
      });
    });

    return api;
  }

  function focusFirstMenuItem(panel) {
    if (!panel) return;
    const items = qsa(
      '[role="menuitem"], a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])',
      panel,
    ).filter(
      (el) => !el.hasAttribute("disabled") && !el.getAttribute("aria-hidden"),
    );

    if (items.length) items[0].focus();
    else panel.focus();
  }

  // ==========================================================
  // Elements
  // ==========================================================
  const userBtn = qs("#userMenuTrigger");
  const userDrop = qs("#userDropdown");

  const notiBtn = qs("#notificationTrigger");
  const notiDrop = qs("#notificationDropdown");

  const mobileBtn = qs("#mobileMenuTrigger");
  const closeBtn = qs("#closeMobileMenuBtn");
  const overlay = qs("#mobileOverlay");
  const mobileMenu = qs("#mobileMenu");

  // ==========================================================
  // Dropdowns
  // ==========================================================
  const dropdowns = [];

  const userDD = setupDropdown({
    btn: userBtn,
    panel: userDrop,
    onOpen: () => closeMegaAll(),
  });
  if (userDD) dropdowns.push(userDD);

  const notiDD = setupDropdown({
    btn: notiBtn,
    panel: notiDrop,
    onOpen: () => closeMegaAll(),
  });
  if (notiDD) dropdowns.push(notiDD);

  function closeAllDropdowns(exceptPanel = null) {
    dropdowns.forEach((d) => {
      if (!d) return;
      if (exceptPanel && d.panel === exceptPanel) return;
      d.close();
    });
  }

  // ==========================================================
  // Mega menu (desktop click-open)
  // ==========================================================
  function closeMegaAll(exceptParent = null) {
    qsa(".mega-parent").forEach((p) => {
      if (exceptParent && p === exceptParent) return;
      p.classList.remove(MEGA_OPEN_CLASS);
    });
  }

  qsa(".mega-parent > .mega-trigger").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      const parent = btn.closest(".mega-parent");
      if (!parent) return;

      const willOpen = !parent.classList.contains(MEGA_OPEN_CLASS);

      // close other mega + dropdowns
      closeMegaAll(parent);
      closeAllDropdowns(null);

      // toggle this one
      parent.classList.toggle(MEGA_OPEN_CLASS, willOpen);
    });

    // Keyboard: Enter/Space toggle; Escape close
    btn.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        closeMegaAll();
      }
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        btn.click();
      }
    });
  });

  // ==========================================================
  // Mobile menu open/close + scroll lock
  // ==========================================================
  function lockBody(lock) {
    document.body.classList.toggle("overflow-hidden", !!lock);
  }

  function openMobile() {
    if (!overlay || !mobileMenu) return;
    overlay.classList.remove("hidden");
    overlay.classList.add("mobile-overlay-show");
    mobileMenu.classList.add("mobile-open");
    mobileMenu.setAttribute("aria-hidden", "false");
    lockBody(true);

    closeAllDropdowns(null);
    closeMegaAll();
  }

  function closeMobile() {
    if (!overlay || !mobileMenu) return;
    overlay.classList.add("hidden");
    overlay.classList.remove("mobile-overlay-show");
    mobileMenu.classList.remove("mobile-open");
    mobileMenu.setAttribute("aria-hidden", "true");
    lockBody(false);
  }

  if (mobileBtn) {
    mobileBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      openMobile();
    });
  }

  if (closeBtn) closeBtn.addEventListener("click", closeMobile);
  if (overlay) overlay.addEventListener("click", closeMobile);

  // ==========================================================
  // Mobile accordion (parents + groups)
  // ==========================================================
  qsa(".mobile-parent").forEach((parent) => {
    const toggle = parent.querySelector(".parent-toggle");
    const submenu = parent.querySelector(".mobile-submenu");
    const icon = toggle ? toggle.querySelector("i.fas") : null;
    if (!toggle || !submenu) return;

    // a11y
    toggle.setAttribute("aria-expanded", "false");

    toggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      const opening = submenu.classList.contains("hidden");

      // close other parent submenus
      qsa(".mobile-parent").forEach((p) => {
        const sm = p.querySelector(".mobile-submenu");
        const tg = p.querySelector(".parent-toggle");
        const ic = tg ? tg.querySelector("i.fas") : null;

        if (!sm || !tg) return;
        if (p === parent) return;

        sm.classList.add("hidden");
        tg.setAttribute("aria-expanded", "false");
        ic && ic.classList.remove("parent-rotate");
      });

      submenu.classList.toggle("hidden", !opening);
      toggle.setAttribute("aria-expanded", String(opening));
      if (icon) icon.classList.toggle("parent-rotate", opening);
    });

    // subgroups inside a parent
    qsa(".mobile-group", parent).forEach((group) => {
      const gBtn = group.querySelector(".group-toggle");
      const items = group.querySelector(".mobile-items");
      const gIcon = gBtn ? gBtn.querySelector("i") : null;
      if (!gBtn || !items) return;

      gBtn.setAttribute("aria-expanded", "false");

      gBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        const opening = items.classList.contains("hidden");
        items.classList.toggle("hidden", !opening);
        gBtn.setAttribute("aria-expanded", String(opening));
        if (gIcon) gIcon.classList.toggle("mobile-rotate", opening);
      });
    });
  });

  // ==========================================================
  // Global close logic (ONE listener only)
  // - Click/touch outside closes dropdowns + mega
  // - ESC closes everything + mobile
  // ==========================================================
  function closeEverything() {
    closeAllDropdowns(null);
    closeMegaAll();
    closeMobile();
  }

  // Use pointerdown so it works for mouse + touch + pen
  document.addEventListener(
    "pointerdown",
    (e) => {
      const t = e.target;

      // If click is inside any dropdown (button or panel), do nothing
      const insideDropdown = dropdowns.some((d) => d.isTargetInside(t));
      if (insideDropdown) return;

      // If click is inside mega parent/trigger, do nothing (mega handler toggles it)
      if (t && t.closest && t.closest(".mega-parent")) return;

      // If click is inside mobile menu or its button, do nothing
      if (
        (mobileMenu && mobileMenu.contains(t)) ||
        (mobileBtn && mobileBtn.contains(t))
      )
        return;

      closeAllDropdowns(null);
      closeMegaAll();
    },
    { passive: true },
  );

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeEverything();
    }
  });
})();
