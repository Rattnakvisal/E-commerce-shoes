(function () {
  const qs = (s, p = document) => p.querySelector(s);

  // USER DROPDOWN
  const userBtn = qs("#userMenuTrigger");
  const userDrop = qs("#userDropdown");

  // NOTIFICATION DROPDOWN
  const notiBtn = qs("#notificationTrigger");
  const notiDrop = qs("#notificationDropdown");

  function closeAllDropdowns(except) {
    [userDrop, notiDrop].forEach((d) => {
      if (!d || d === except) return;
      d.classList.remove("dropdown-open");
    });
  }

  if (userBtn && userDrop) {
    userBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      const open = userDrop.classList.toggle("dropdown-open");
      closeAllDropdowns(open ? userDrop : null);
    });
  }

  if (notiBtn && notiDrop) {
    notiBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      const open = notiDrop.classList.toggle("dropdown-open");
      closeAllDropdowns(open ? notiDrop : null);
    });
  }

  // Close dropdowns on outside click
  document.addEventListener("click", () => closeAllDropdowns(null));

  // Close on ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeAllDropdowns(null);
  });

  // MOBILE MENU
  const mobileBtn = qs("#mobileMenuTrigger");
  const closeBtn = qs("#closeMobileMenuBtn");
  const overlay = qs("#mobileOverlay");
  const mobileMenu = qs("#mobileMenu");

  function openMobile() {
    if (!overlay || !mobileMenu) return;
    overlay.classList.remove("hidden");
    overlay.classList.add("mobile-overlay-show");
    mobileMenu.classList.add("mobile-open");
    mobileMenu.setAttribute("aria-hidden", "false");
  }

  function closeMobile() {
    if (!overlay || !mobileMenu) return;
    overlay.classList.add("hidden");
    overlay.classList.remove("mobile-overlay-show");
    mobileMenu.classList.remove("mobile-open");
    mobileMenu.setAttribute("aria-hidden", "true");
  }

  if (mobileBtn) mobileBtn.addEventListener("click", openMobile);
  if (closeBtn) closeBtn.addEventListener("click", closeMobile);
  if (overlay) overlay.addEventListener("click", closeMobile);

  // MOBILE ACCORDION
  document.querySelectorAll(".mobile-parent").forEach((parent) => {
    const toggle = parent.querySelector(".parent-toggle");
    const submenu = parent.querySelector(".mobile-submenu");
    const icon = toggle ? toggle.querySelector("i.fas") : null;

    if (!toggle || !submenu) return;

    toggle.addEventListener("click", () => {
      const isOpen = !submenu.classList.contains("hidden");

      // close others for cleaner UX
      document.querySelectorAll(".mobile-submenu").forEach((sm) => {
        if (sm !== submenu) sm.classList.add("hidden");
      });
      document
        .querySelectorAll(".mobile-parent .parent-toggle i.fas")
        .forEach((i) => {
          if (i !== icon) i.classList.remove("parent-rotate");
        });

      submenu.classList.toggle("hidden", isOpen);
      if (icon) icon.classList.toggle("parent-rotate", !isOpen);
      toggle.setAttribute("aria-expanded", String(!isOpen));
    });

    parent.querySelectorAll(".mobile-group").forEach((group) => {
      const gBtn = group.querySelector(".group-toggle");
      const items = group.querySelector(".mobile-items");
      const gIcon = gBtn ? gBtn.querySelector("i") : null;

      if (!gBtn || !items) return;

      gBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        const open = !items.classList.contains("hidden");
        items.classList.toggle("hidden", open);
        if (gIcon) gIcon.classList.toggle("mobile-rotate", !open);
        gBtn.setAttribute("aria-expanded", String(!open));
      });
    });
  });

  // OPTIONAL: allow mega menu click-open (desktop) as well
  document.querySelectorAll(".mega-parent > .mega-trigger").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      // click toggles; hover still works
      e.stopPropagation();
      const parent = btn.closest(".mega-parent");
      document.querySelectorAll(".mega-parent").forEach((p) => {
        if (p !== parent) p.classList.remove("mega-open");
      });
      parent.classList.toggle("mega-open");
    });
  });
  document.addEventListener("click", () =>
    document
      .querySelectorAll(".mega-parent")
      .forEach((p) => p.classList.remove("mega-open")),
  );
})();
