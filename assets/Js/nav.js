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

messagesButton?.addEventListener("click", (e) => {
  e.stopPropagation();
  const open = messagesDropdown.classList.contains("hidden");
  closeAllDropdowns();
  if (open) messagesDropdown.classList.remove("hidden");
});

quickAddButton?.addEventListener("click", (e) => {
  e.stopPropagation();
  const open = quickAddDropdown.classList.contains("hidden");
  closeAllDropdowns();
  if (open) quickAddDropdown.classList.remove("hidden");
});

adminDropdownButton?.addEventListener("click", (e) => {
  e.stopPropagation();
  const open = adminDropdownMenu.classList.contains("hidden");
  closeAllDropdowns();
  if (open) adminDropdownMenu.classList.remove("hidden");
});

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
