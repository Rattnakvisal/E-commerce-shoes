(function () {
  "use strict";

  const apiBase = "/E-commerce-shoes/admin/process/notifications_api.php";

  // DOM
  const wrap = document.getElementById("notifWrap");
  const btn = document.getElementById("notificationsButton");
  const dd = document.getElementById("notificationsDropdown");
  const listEl = document.getElementById("notificationsList");
  const badge = document.getElementById("notificationBadge");
  const markAllBtn = document.getElementById("markAllReadBtn");
  const clearAllBtn = document.getElementById("clearAllNotifsBtn");

  if (!btn || !dd || !listEl) return;

  /* ================================
     Helpers
  ================================= */
  const esc = (s) =>
    String(s ?? "").replace(
      /[&<>"'\\]/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
          "\\": "\\\\",
        })[c],
    );

  function setBadgeCount(n) {
    const num = Number(n || 0);
    if (!badge) return;

    if (num <= 0) {
      badge.classList.add("hidden");
      badge.textContent = "0";
      return;
    }

    badge.classList.remove("hidden");
    badge.textContent = num > 99 ? "99+" : String(num);
  }

  function openDropdown() {
    dd.classList.remove("hidden");
    btn.setAttribute("aria-expanded", "true");
  }

  function closeDropdown() {
    dd.classList.add("hidden");
    btn.setAttribute("aria-expanded", "false");
  }

  function toggleDropdown() {
    dd.classList.contains("hidden") ? openDropdown() : closeDropdown();
  }

  async function apiGet(action) {
    const url = `${apiBase}?action=${encodeURIComponent(action)}`;
    const res = await fetch(url, { credentials: "same-origin" });
    const j = await res.json().catch(() => null);
    if (!res.ok || !j || j.ok !== true)
      throw new Error(j?.msg || "Request failed");
    return j;
  }

  async function apiPost(action, dataObj = {}) {
    const url = `${apiBase}?action=${encodeURIComponent(action)}`;
    const res = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams(dataObj),
    });
    const j = await res.json().catch(() => null);
    if (!res.ok || !j || j.ok !== true)
      throw new Error(j?.msg || "Request failed");
    return j;
  }

  function renderEmpty() {
    listEl.innerHTML =
      '<div class="py-10 text-center text-sm text-gray-500">No notifications</div>';
  }

  function renderItems(items) {
    if (!Array.isArray(items) || items.length === 0) {
      renderEmpty();
      return;
    }

    listEl.innerHTML = items
      .map((it) => {
        const id = esc(it.notification_id ?? "");
        const title = esc(it.title ?? "");
        const message = esc(it.message ?? "");
        const created = esc(it.created_at ?? "");
        const isRead = String(it.is_read ?? "0") === "1";

        return `
          <div class="relative group notif-row">
            <a href="#" data-id="${id}"
              class="notif-item block px-4 py-3 transition hover:bg-gray-50 ${
                isRead ? "" : "bg-indigo-50/60"
              }">
              <div class="flex justify-between items-start gap-2">
                <p class="text-sm font-medium text-gray-900 line-clamp-1">${title}</p>
                <span class="text-xs text-gray-400 whitespace-nowrap">${created}</span>
              </div>
              <p class="mt-1 text-xs text-gray-600 line-clamp-2">${message}</p>
            </a>

            ${
              isRead
                ? ""
                : `<span class="absolute top-4 left-2 w-2 h-2 bg-indigo-500 rounded-full"></span>`
            }

            <button type="button" data-id="${id}"
              class="notif-clear absolute top-3 right-3 opacity-0 group-hover:opacity-100
                     text-gray-400 hover:text-red-500 transition text-sm"
              aria-label="Delete notification" title="Delete">
              &times;
            </button>
          </div>
        `;
      })
      .join("");
  }

  /* ================================
     Fetch + Update UI
  ================================= */
  async function fetchCount() {
    try {
      const j = await apiGet("fetch_unread_count");
      setBadgeCount(j.unread || 0);
    } catch (_) {
      // silent
    }
  }

  async function fetchLatest() {
    try {
      const j = await apiGet("fetch_latest");
      renderItems(j.items || []);
    } catch (_) {
      renderEmpty();
    }
  }

  /* ================================
     Events
  ================================= */
  // Toggle dropdown
  btn.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();

    toggleDropdown();
    if (!dd.classList.contains("hidden")) {
      await fetchLatest();
    }
  });

  // Click outside close
  document.addEventListener("click", (e) => {
    if (wrap && wrap.contains(e.target)) return;
    closeDropdown();
  });

  // ESC close
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeDropdown();
  });

  // Mark all read
  markAllBtn?.addEventListener("click", async (e) => {
    e.preventDefault();
    try {
      await apiPost("mark_all_read");
      await fetchCount();
      await fetchLatest();
    } catch (_) {}
  });

  // Clear all
  clearAllBtn?.addEventListener("click", async (e) => {
    e.preventDefault();
    try {
      await apiPost("delete_all");
      await fetchCount();
      await fetchLatest();
    } catch (_) {}
  });

  // Delegation inside list:
  // - click row => mark_read
  // - click X => delete
  listEl.addEventListener("click", async (e) => {
    const delBtn = e.target.closest(".notif-clear");
    if (delBtn) {
      e.preventDefault();
      e.stopPropagation();
      const id = delBtn.getAttribute("data-id");
      if (!id) return;

      try {
        await apiPost("delete", { id });
        await fetchCount();
        await fetchLatest();
      } catch (_) {}
      return;
    }

    const row = e.target.closest(".notif-item");
    if (!row) return;

    e.preventDefault();
    const id = row.getAttribute("data-id");
    if (!id) return;

    try {
      await apiPost("mark_read", { id });
      await fetchCount();
      await fetchLatest();
    } catch (_) {}
  });

  /* ================================
     Init
  ================================= */
  fetchCount();
  setInterval(fetchCount, 30000);
})();
