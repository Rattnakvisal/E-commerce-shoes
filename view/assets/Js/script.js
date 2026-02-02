(function () {
  const qs = (s, p = document) => p.querySelector(s);
  const qsa = (s, p = document) => Array.from(p.querySelectorAll(s));

  // =========================
  // Small utilities
  // =========================
  const isOpen = (el) => el && el.classList.contains("dropdown-open");
  const openEl = (el) => el && el.classList.add("dropdown-open");
  const closeEl = (el) => el && el.classList.remove("dropdown-open");

  function setAria(btn, panel, opened) {
    if (btn) btn.setAttribute("aria-expanded", String(opened));
    if (panel) panel.setAttribute("aria-hidden", String(!opened));
  }

  // close dropdowns except one
  function closeAllDropdowns(except = null) {
    [userDrop, notiDrop].forEach((d) => {
      if (!d || d === except) return;
      closeEl(d);
    });

    // also close any mega menus
    qsa(".mega-parent.mega-open").forEach((p) =>
      p.classList.remove("mega-open"),
    );

    // update aria
    setAria(userBtn, userDrop, isOpen(userDrop));
    setAria(notiBtn, notiDrop, isOpen(notiDrop));
  }

  // =========================
  // USER DROPDOWN
  // =========================
  const userBtn = qs("#userMenuTrigger");
  const userDrop = qs("#userDropdown");

  if (userBtn && userDrop) {
    userBtn.setAttribute("aria-haspopup", "menu");
    userBtn.setAttribute("aria-expanded", "false");
    userDrop.setAttribute("aria-hidden", "true");

    userBtn.addEventListener("click", (e) => {
      e.stopPropagation();

      const opening = !isOpen(userDrop);
      closeAllDropdowns(opening ? userDrop : null);

      if (opening) openEl(userDrop);
      else closeEl(userDrop);

      setAria(userBtn, userDrop, opening);
    });

    // Prevent clicks inside dropdown from closing it
    userDrop.addEventListener("click", (e) => e.stopPropagation());
  }

  // =========================
  // NOTIFICATION DROPDOWN
  // =========================
  const notiBtn = qs("#notificationTrigger");
  const notiDrop = qs("#notificationDropdown");

  if (notiBtn && notiDrop) {
    notiBtn.setAttribute("aria-haspopup", "menu");
    notiBtn.setAttribute("aria-expanded", "false");
    notiDrop.setAttribute("aria-hidden", "true");

    notiBtn.addEventListener("click", (e) => {
      e.stopPropagation();

      const opening = !isOpen(notiDrop);
      closeAllDropdowns(opening ? notiDrop : null);

      if (opening) openEl(notiDrop);
      else closeEl(notiDrop);

      setAria(notiBtn, notiDrop, opening);
    });

    notiDrop.addEventListener("click", (e) => e.stopPropagation());
  }

  // =========================
  // Outside click close
  // =========================
  document.addEventListener("click", () => closeAllDropdowns(null));

  // =========================
  // Close on ESC
  // =========================
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeAllDropdowns(null);
      closeMobile(); // also close mobile
    }
  });

  // =========================
  // MOBILE MENU
  // =========================
  const mobileBtn = qs("#mobileMenuTrigger");
  const closeBtn = qs("#closeMobileMenuBtn");
  const overlay = qs("#mobileOverlay");
  const mobileMenu = qs("#mobileMenu");

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

    // close dropdowns when opening mobile menu
    closeAllDropdowns(null);
  }

  function closeMobile() {
    if (!overlay || !mobileMenu) return;
    overlay.classList.add("hidden");
    overlay.classList.remove("mobile-overlay-show");
    mobileMenu.classList.remove("mobile-open");
    mobileMenu.setAttribute("aria-hidden", "true");
    lockBody(false);
  }

  if (mobileBtn)
    mobileBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      openMobile();
    });

  if (closeBtn) closeBtn.addEventListener("click", closeMobile);
  if (overlay) overlay.addEventListener("click", closeMobile);

  // =========================
  // MOBILE ACCORDION (Parents + Groups)
  // =========================
  qsa(".mobile-parent").forEach((parent) => {
    const toggle = parent.querySelector(".parent-toggle");
    const submenu = parent.querySelector(".mobile-submenu");
    const icon = toggle ? toggle.querySelector("i.fas") : null;

    if (!toggle || !submenu) return;

    toggle.addEventListener("click", (e) => {
      e.stopPropagation();
      const opening = submenu.classList.contains("hidden");

      // close other parent submenus
      qsa(".mobile-submenu").forEach((sm) => {
        if (sm !== submenu) sm.classList.add("hidden");
      });
      qsa(".mobile-parent .parent-toggle i.fas").forEach((i) => {
        if (i !== icon) i.classList.remove("parent-rotate");
      });

      submenu.classList.toggle("hidden", !opening);
      if (icon) icon.classList.toggle("parent-rotate", opening);
      toggle.setAttribute("aria-expanded", String(opening));
    });

    // subgroups inside a parent
    qsa(".mobile-group", parent).forEach((group) => {
      const gBtn = group.querySelector(".group-toggle");
      const items = group.querySelector(".mobile-items");
      const gIcon = gBtn ? gBtn.querySelector("i") : null;

      if (!gBtn || !items) return;

      gBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        const opening = items.classList.contains("hidden");
        items.classList.toggle("hidden", !opening);
        if (gIcon) gIcon.classList.toggle("mobile-rotate", opening);
        gBtn.setAttribute("aria-expanded", String(opening));
      });
    });
  });

  // =========================
  // MEGA MENU (Desktop click-open)
  // =========================
  qsa(".mega-parent > .mega-trigger").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const parent = btn.closest(".mega-parent");
      if (!parent) return;

      // close other mega
      qsa(".mega-parent").forEach((p) => {
        if (p !== parent) p.classList.remove("mega-open");
      });

      parent.classList.toggle("mega-open");

      // close dropdowns when mega opens
      closeEl(userDrop);
      closeEl(notiDrop);
      setAria(userBtn, userDrop, false);
      setAria(notiBtn, notiDrop, false);
    });
  });

  document.addEventListener("click", () => {
    qsa(".mega-parent").forEach((p) => p.classList.remove("mega-open"));
  });
})();
