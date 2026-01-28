document.addEventListener("DOMContentLoaded", () => {
  const $ = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));
  const one = (s, ctx = document) => ctx.querySelector(s);
  const isDesktop = () => window.matchMedia("(min-width: 1024px)").matches;

  /* =========================
		   MOBILE DRAWER
		========================= */
  const mobileTrigger = one("#mobileMenuTrigger");
  const mobileMenu = one("#mobileMenu");
  const mobileOverlay = one("#mobileOverlay");
  const closeMobileBtn = one("#closeMobileMenuBtn");

  function openDrawer() {
    mobileOverlay?.classList.remove("hidden");
    mobileMenu?.classList.remove("-translate-x-full");
    mobileMenu?.classList.add("translate-x-0");
    document.body.classList.add("overflow-hidden");
    mobileMenu?.setAttribute("aria-hidden", "false");
  }

  function closeDrawer() {
    mobileOverlay?.classList.add("hidden");
    mobileMenu?.classList.add("-translate-x-full");
    mobileMenu?.classList.remove("translate-x-0");
    document.body.classList.remove("overflow-hidden");
    mobileMenu?.setAttribute("aria-hidden", "true");
  }

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

  /* =========================
		   MOBILE SUBMENUS (smooth)
		========================= */
  function smoothToggle(el, open) {
    if (!el) return;

    el.style.overflow = "hidden";
    el.style.height = open ? "0px" : el.scrollHeight + "px";
    el.classList.remove("hidden");

    requestAnimationFrame(() => {
      el.style.transition = "height 0.28s ease";
      el.style.height = open ? el.scrollHeight + "px" : "0px";
    });

    el.addEventListener(
      "transitionend",
      () => {
        el.style.height = "";
        el.style.overflow = "";
        el.style.transition = "";
        if (!open) el.classList.add("hidden");
      },
      {
        once: true,
      },
    );
  }

  $(".mobile-parent").forEach((parent) => {
    const toggle = one(".parent-toggle", parent);
    const submenu = one(".mobile-submenu", parent);
    const arrow = one(".fa-chevron-right", parent);
    if (!toggle || !submenu) return;

    toggle.addEventListener("click", () => {
      const isOpen = !submenu.classList.contains("hidden");

      $(".mobile-submenu").forEach((s) => {
        if (s !== submenu && !s.classList.contains("hidden"))
          smoothToggle(s, false);
      });
      $(".mobile-parent .fa-chevron-right").forEach((a) => {
        if (a !== arrow) a.classList.remove("rotate-90");
      });

      smoothToggle(submenu, !isOpen);
      arrow?.classList.toggle("rotate-90", !isOpen);
      toggle.setAttribute("aria-expanded", String(!isOpen));
    });
  });

  $(".mobile-group").forEach((group) => {
    const toggle = one(".group-toggle", group);
    const list = one(".mobile-items", group);
    const arrow = one(".fa-chevron-down", group);
    if (!toggle || !list) return;

    toggle.addEventListener("click", () => {
      const isOpen = !list.classList.contains("hidden");
      smoothToggle(list, !isOpen);
      arrow?.classList.toggle("rotate-180", !isOpen);
      toggle.setAttribute("aria-expanded", String(!isOpen));
    });
  });

  /* =========================
		   MOBILE SEARCH
		========================= */
  const mobileSearchTrigger = one("#mobileSearchTrigger");
  const mobileSearchBar = one("#mobileSearchBar");
  const closeMobileSearch = one("#closeMobileSearch");

  mobileSearchTrigger?.addEventListener("click", () => {
    mobileSearchBar?.classList.toggle("hidden");
  });

  closeMobileSearch?.addEventListener("click", () => {
    mobileSearchBar?.classList.add("hidden");
  });

  /* =========================
		   USER DROPDOWN
		========================= */
  const userBtn = one("#userMenuTrigger");
  const userDropdown = one("#userDropdown");

  userBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    userDropdown?.classList.toggle("hidden");
  });
});
