document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  const one = (s, ctx = document) => ctx.querySelector(s);
  const all = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));
  const isDesktop = () => window.matchMedia("(min-width: 1024px)").matches;

  /* =========================================================
     MOBILE DRAWER
  ========================================================= */
  const mobileTrigger = one("#mobileMenuTrigger");
  const mobileMenu = one("#mobileMenu");
  const mobileOverlay = one("#mobileOverlay");
  const closeMobileBtn = one("#closeMobileMenuBtn");

  const setBodyLock = (locked) => {
    document.body.classList.toggle("overflow-hidden", locked);
  };

  const openDrawer = () => {
    if (!mobileMenu || !mobileOverlay) return;
    mobileOverlay.classList.remove("hidden");
    mobileMenu.classList.remove("-translate-x-full");
    mobileMenu.classList.add("translate-x-0");
    mobileMenu.setAttribute("aria-hidden", "false");
    setBodyLock(true);
  };

  const closeDrawer = () => {
    if (!mobileMenu || !mobileOverlay) return;
    mobileOverlay.classList.add("hidden");
    mobileMenu.classList.add("-translate-x-full");
    mobileMenu.classList.remove("translate-x-0");
    mobileMenu.setAttribute("aria-hidden", "true");
    setBodyLock(false);
  };

  mobileTrigger?.addEventListener("click", (e) => {
    e.preventDefault();
    openDrawer();
  });

  closeMobileBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    closeDrawer();
  });

  mobileOverlay?.addEventListener("click", closeDrawer);

  window.addEventListener("resize", () => {
    if (isDesktop()) closeDrawer();
  });

  /* =========================================================
     SMOOTH TOGGLE (height animation)
  ========================================================= */
  function smoothToggle(el, shouldOpen) {
    if (!el) return;

    // cancel old transitions safely
    el.style.transition = "none";
    el.style.overflow = "hidden";

    if (shouldOpen) {
      el.classList.remove("hidden");
      el.style.height = "0px";

      requestAnimationFrame(() => {
        const h = el.scrollHeight;
        el.style.transition = "height 0.28s ease";
        el.style.height = h + "px";
      });
    } else {
      el.style.height = el.scrollHeight + "px";

      requestAnimationFrame(() => {
        el.style.transition = "height 0.28s ease";
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

  const isHidden = (el) => !el || el.classList.contains("hidden");

  /* =========================================================
     MOBILE SUBMENUS
  ========================================================= */
  all(".mobile-parent").forEach((parent) => {
    const toggle = one(".parent-toggle", parent);
    const submenu = one(".mobile-submenu", parent);
    const arrow = one(".fa-chevron-right", parent);
    if (!toggle || !submenu) return;

    toggle.addEventListener("click", () => {
      const willOpen = isHidden(submenu);

      // close other parents
      all(".mobile-parent .mobile-submenu").forEach((s) => {
        if (s !== submenu && !isHidden(s)) smoothToggle(s, false);
      });
      all(".mobile-parent .fa-chevron-right").forEach((a) => {
        if (a !== arrow) a.classList.remove("rotate-90");
      });

      smoothToggle(submenu, willOpen);
      arrow?.classList.toggle("rotate-90", willOpen);
      toggle.setAttribute("aria-expanded", String(willOpen));
    });
  });

  all(".mobile-group").forEach((group) => {
    const toggle = one(".group-toggle", group);
    const list = one(".mobile-items", group);
    const arrow = one(".fa-chevron-down", group);
    if (!toggle || !list) return;

    toggle.addEventListener("click", () => {
      const willOpen = isHidden(list);
      smoothToggle(list, willOpen);
      arrow?.classList.toggle("rotate-180", willOpen);
      toggle.setAttribute("aria-expanded", String(willOpen));
    });
  });

  /* =========================================================
     USER DROPDOWN (close on outside click + ESC)
  ========================================================= */
  const userBtn = one("#userMenuTrigger");
  const userDropdown = one("#userDropdown");

  const closeUserDropdown = () => userDropdown?.classList.add("hidden");

  userBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    userDropdown?.classList.toggle("hidden");
  });

  document.addEventListener("click", (e) => {
    const t = e.target;
    if (
      userDropdown &&
      !userDropdown.classList.contains("hidden") &&
      !userDropdown.contains(t) &&
      !t.closest("#userMenuTrigger")
    ) {
      closeUserDropdown();
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeUserDropdown();
      closeDrawer();
    }
  });
});
