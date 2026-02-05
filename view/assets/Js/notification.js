(() => {
  /* ================================
    Notification Dropdown (Clean + Safe)
  ================================= */

  const apiBase = "/E-commerce-shoes/admin/controller/notifications_api.php";

  const countEl = document.getElementById("notificationCount");
  const listEl = document.getElementById("notificationList");
  const dropdown = document.getElementById("notificationDropdown");
  const trigger = document.getElementById("notificationTrigger");
  const markAllBtn = document.getElementById("markAllRead");
  const clearAllBtn = document.getElementById("clearAll");
  const closeBtn = document.getElementById("closeNotification");
  const backdrop = document.getElementById("notificationBackdrop");

  // If core elements are missing, stop safely.
  if (!countEl || !listEl || !dropdown || !trigger) return;

  function esc(s) {
    return String(s ?? "").replace(/[&<>"'\\]/g, (c) => {
      const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
        "\\": "\\\\",
      };
      return map[c] || c;
    });
  }

  function isLogoutItem(it) {
    const t = String(it?.title ?? "").toLowerCase();
    const m = String(it?.message ?? "").toLowerCase();
    return (
      t.includes("logout") ||
      t.includes("logged out") ||
      m.includes("logout") ||
      m.includes("logged out")
    );
  }

  async function apiGet(action) {
    const res = await fetch(`${apiBase}?action=${encodeURIComponent(action)}`, {
      credentials: "same-origin",
    });
    if (!res.ok) throw new Error("HTTP " + res.status);
    return res.json();
  }

  async function apiPost(action, data = {}) {
    const res = await fetch(`${apiBase}?action=${encodeURIComponent(action)}`, {
      method: "POST",
      credentials: "same-origin",
      body: new URLSearchParams(data),
    });
    if (!res.ok) throw new Error("HTTP " + res.status);
    return res.json();
  }

  function setBadge(n) {
    const num = Math.max(0, Number(n) || 0);
    countEl.textContent = String(num);
    // Optional: hide badge when 0
    if (num <= 0) countEl.classList.add("hidden");
    else countEl.classList.remove("hidden");
  }

  function renderList(items) {
    listEl.innerHTML = "";

    if (!Array.isArray(items) || items.length === 0) {
      listEl.innerHTML = `<div class="p-3 text-gray-600">No notifications</div>`;
      setBadge(0);
      return;
    }

    const visible = items.filter((it) => !isLogoutItem(it));
    if (visible.length === 0) {
      listEl.innerHTML = `<div class="p-3 text-gray-600">No notifications</div>`;
      setBadge(0);
      return;
    }

    // Render rows
    const frag = document.createDocumentFragment();
    let visibleUnread = 0;

    for (const it of visible) {
      const id = it?.notification_id ?? "";
      const isRead = Number(it?.is_read) === 1;

      if (!isRead) visibleUnread++;

      const row = document.createElement("div");
      row.className =
        "notification-row px-3 py-2 hover:bg-gray-50 border-b cursor-pointer";
      row.dataset.id = String(id);
      row.dataset.read = isRead ? "1" : "0";
      if (!isRead) row.classList.add("font-semibold");

      row.innerHTML = `
        <div class="flex justify-between items-start gap-3">
          <div class="flex-1">
            <div class="font-medium">${esc(it?.title)}</div>
            <div class="text-gray-600 text-xs mt-1">${esc(it?.message)}</div>
            <div class="text-gray-400 text-xs mt-1">${esc(it?.created_at)}</div>
          </div>
          <div class="flex-shrink-0 pl-2">
            <button type="button" class="notif-delete text-red-500 text-xs hover:underline">
              Clear
            </button>
          </div>
        </div>
      `;

      frag.appendChild(row);
    }

    listEl.appendChild(frag);
    setBadge(visibleUnread);
  }

  async function fetchCount() {
    try {
      const j = await apiGet("fetch_unread_count");
      if (j?.ok) setBadge(j?.unread ?? 0);
    } catch (_) {
      // ignore
    }
  }

  async function fetchLatest() {
    try {
      const j = await apiGet("fetch_latest");
      if (!j?.ok) return;
      renderList(j.items || []);
    } catch (_) {
      // ignore
    }
  }

  // Utility to position dropdown centered under the trigger and clamp to viewport
  function positionDropdown() {
    dropdown.style.visibility = "hidden";
    dropdown.classList.remove("hidden");
    dropdown.style.position = "fixed";

    const rect = trigger.getBoundingClientRect();
    const ddW = dropdown.offsetWidth || 320;
    const ddH = dropdown.offsetHeight || 240;

    let left = rect.left + rect.width / 2 - ddW / 2;
    const padding = 8;

    left = Math.max(padding, Math.min(left, window.innerWidth - ddW - padding));

    let top = rect.bottom + 8;
    if (top + ddH > window.innerHeight - padding) {
      top = rect.top - ddH - 8;
      if (top < padding) top = padding;
    }

    dropdown.style.left = `${left}px`;
    dropdown.style.top = `${top}px`;
    dropdown.style.right = "auto";
    dropdown.style.transform = "none";
    dropdown.style.visibility = "visible";
  }

  function showDropdown() {
    positionDropdown();
    backdrop?.classList.remove("hidden");
  }

  function hideDropdown() {
    dropdown.classList.add("hidden");
    dropdown.style.left = "";
    dropdown.style.top = "";
    dropdown.style.position = "";
    dropdown.style.transform = "";
    dropdown.style.visibility = "";
    backdrop?.classList.add("hidden");
  }

  function isOpen() {
    return !dropdown.classList.contains("hidden");
  }

  // Open/Close trigger
  trigger.addEventListener("click", async (e) => {
    e.preventDefault();

    if (!isOpen()) {
      await fetchLatest();
      showDropdown();
    } else {
      hideDropdown();
    }
  });

  // Close controls
  closeBtn?.addEventListener("click", hideDropdown);
  backdrop?.addEventListener("click", hideDropdown);

  document.addEventListener("keydown", (ev) => {
    if (ev.key === "Escape") hideDropdown();
  });

  // Reposition on resize/scroll (only when open)
  window.addEventListener("resize", () => {
    if (isOpen()) positionDropdown();
  });
  window.addEventListener(
    "scroll",
    () => {
      if (isOpen()) positionDropdown();
    },
    { passive: true },
  );

  /* ================================
    Event Delegation (NO duplicates)
  ================================= */

  listEl.addEventListener("click", async (ev) => {
    const row = ev.target.closest(".notification-row");
    if (!row) return;

    const id = row.dataset.id;
    if (!id) return;

    // If clicked delete button -> delete only
    if (ev.target.closest(".notif-delete")) {
      ev.preventDefault();
      ev.stopPropagation();

      try {
        const j = await apiPost("delete", { id });
        if (j?.ok) {
          // remove from UI quickly
          row.remove();
          // refresh badge + list if empty
          await fetchLatest();
          await fetchCount();
        }
      } catch (_) {}
      return;
    }

    // Clicked row -> mark read (only if unread)
    if (row.dataset.read === "1") return;

    try {
      const j = await apiPost("mark_read", { id });
      if (j?.ok) {
        row.dataset.read = "1";
        row.classList.remove("font-semibold");
        // update badge quickly (avoid full reload)
        const current = Number(countEl.textContent || 0);
        setBadge(Math.max(0, current - 1));
      }
    } catch (_) {}
  });

  markAllBtn?.addEventListener("click", async () => {
    try {
      const j = await apiPost("mark_all_read");
      if (j?.ok) {
        await fetchLatest();
        await fetchCount();
      }
    } catch (_) {}
  });

  clearAllBtn?.addEventListener("click", async () => {
    try {
      const j = await apiPost("delete_all");
      if (j?.ok) {
        await fetchLatest();
        await fetchCount();
      }
    } catch (_) {}
  });

  // init
  fetchLatest(); // optional: show fresh list on load
  fetchCount();
  setInterval(fetchCount, 30000);
})();
