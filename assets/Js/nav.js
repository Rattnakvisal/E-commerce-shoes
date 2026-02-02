/* =========================================================
   DOM ELEMENTS
========================================================= */

// Mobile
const mobileMenuButton = document.getElementById("mobileMenuButton");
const closeMobileMenu = document.getElementById("closeMobileMenu");
const mobileSidebar = document.getElementById("mobileSidebar");
const mobileOverlay = document.getElementById("mobileOverlay");
const mobileSearchButton = document.getElementById("mobileSearchButton");
const mobileSearchBar = document.getElementById("mobileSearchBar");

// Dropdowns (notifications REMOVED)
const messagesButton = document.getElementById("messagesButton");
const messagesDropdown = document.getElementById("messagesDropdown");

const quickAddButton = document.getElementById("quickAddButton");
const quickAddDropdown = document.getElementById("quickAddDropdown");

const adminDropdownButton = document.getElementById("adminDropdownButton");
const adminDropdownMenu = document.getElementById("adminDropdownMenu");

/* =========================================================
   HELPERS
========================================================= */

function closeAllDropdowns() {
  messagesDropdown?.classList.add("hidden");
  quickAddDropdown?.classList.add("hidden");
  adminDropdownMenu?.classList.add("hidden");
}

/* =========================================================
   MOBILE MENU
========================================================= */

if (mobileMenuButton && mobileSidebar && mobileOverlay) {
  mobileMenuButton.addEventListener("click", () => {
    mobileSidebar.classList.remove("-translate-x-full");
    mobileOverlay.classList.remove("hidden");
  });

  closeMobileMenu?.addEventListener("click", () => {
    mobileSidebar.classList.add("-translate-x-full");
    mobileOverlay.classList.add("hidden");
  });

  mobileOverlay.addEventListener("click", () => {
    mobileSidebar.classList.add("-translate-x-full");
    mobileOverlay.classList.add("hidden");
  });
}

/* =========================================================
   MOBILE SEARCH
========================================================= */

mobileSearchButton?.addEventListener("click", () => {
  mobileSearchBar?.classList.toggle("hidden");
});

/* =========================================================
   DROPDOWNS (Messages / Quick Add / Admin)
========================================================= */
(function () {
  const dropdowns = document.querySelectorAll(".js-dropdown");

  function closeAll(except = null) {
    dropdowns.forEach((d) => {
      if (except && d === except) return;
      const menu = d.querySelector(".js-dropdown-menu");
      if (menu) menu.classList.add("hidden");
    });
  }

  dropdowns.forEach((d) => {
    const btn = d.querySelector(".js-dropdown-btn");
    const menu = d.querySelector(".js-dropdown-menu");
    if (!btn || !menu) return;

    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const isOpen = !menu.classList.contains("hidden");
      closeAll(d);
      menu.classList.toggle("hidden", isOpen);
    });
  });

  document.addEventListener("click", () => closeAll());
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeAll();
  });
})();

/** ========== Accordion (Item) ========== */
(function () {
  document.querySelectorAll(".js-accordion").forEach((btn) => {
    btn.addEventListener("click", () => {
      const panel = btn.parentElement.querySelector(".js-accordion-panel");
      const icon = btn.querySelector(".fa-chevron-down");
      const open = btn.getAttribute("aria-expanded") === "true";

      btn.setAttribute("aria-expanded", open ? "false" : "true");
      if (panel) panel.classList.toggle("hidden");
      if (icon) icon.classList.toggle("rotate-180");
    });
  });
})();

/** ========== Mobile sidebar ========== */
function openSidebar() {
  if (!mobileSidebar || !mobileOverlay) return;
  mobileSidebar.classList.remove("-translate-x-full");
  mobileOverlay.classList.remove("hidden");
  document.body.style.overflow = "hidden";
}

function closeSidebar() {
  if (!mobileSidebar || !mobileOverlay) return;
  mobileSidebar.classList.add("-translate-x-full");
  mobileOverlay.classList.add("hidden");
  document.body.style.overflow = "";
}

mobileMenuButton && mobileMenuButton.addEventListener("click", openSidebar);
closeMobileMenu && closeMobileMenu.addEventListener("click", closeSidebar);
mobileOverlay && mobileOverlay.addEventListener("click", closeSidebar);
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeSidebar();
});

/** ========== Mobile search toggle ========== */
(function () {
  const btn = document.getElementById("mobileSearchButton");
  const bar = document.getElementById("mobileSearchBar");
  if (!btn || !bar) return;
  btn.addEventListener("click", () => bar.classList.toggle("hidden"));
})();

/* =========================================================
   CLICK OUTSIDE CLOSE
========================================================= */

document.addEventListener("click", (e) => {
  if (
    messagesDropdown &&
    !messagesButton?.contains(e.target) &&
    !messagesDropdown.contains(e.target)
  ) {
    messagesDropdown.classList.add("hidden");
  }

  if (
    quickAddDropdown &&
    !quickAddButton?.contains(e.target) &&
    !quickAddDropdown.contains(e.target)
  ) {
    quickAddDropdown.classList.add("hidden");
  }

  if (
    adminDropdownMenu &&
    !adminDropdownButton?.contains(e.target) &&
    !adminDropdownMenu.contains(e.target)
  ) {
    adminDropdownMenu.classList.add("hidden");
  }
});

/* =========================================================
   TOAST
========================================================= */

function showToast(message, type = "success") {
  let toast = document.getElementById("custom-toast");

  if (!toast) {
    toast = document.createElement("div");
    toast.id = "custom-toast";
    toast.className = `
            fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-xl
            text-white transform transition-all duration-300 translate-y-full
            ${type === "success" ? "bg-green-500" : "bg-red-500"}
        `;
    document.body.appendChild(toast);
  }

  toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === "success" ? "check-circle" : "exclamation-triangle"} mr-2"></i>
            <span>${message}</span>
        </div>
    `;

  toast.classList.remove("translate-y-full");

  setTimeout(() => {
    toast.classList.add("translate-y-full");
  }, 3000);
}

/* =========================================================
   ESC KEY CLOSE
========================================================= */

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeAllDropdowns();
  }
});
