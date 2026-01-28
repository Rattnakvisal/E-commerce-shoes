(function () {
  const apiBase = "/E-commerce-shoes/admin/process/notifications_api.php";

  const trigger = document.getElementById("notificationTrigger");
  const dropdown = document.getElementById("notificationDropdown");
  const countEl = document.getElementById("notificationCount");
  const listEl = document.getElementById("notificationList");
  const metaEl = document.getElementById("notificationMeta");
  const markAllBtn = document.getElementById("markAllRead");
  const clearAllBtn = document.getElementById("clearAll");

  if (!trigger || !dropdown || !countEl || !listEl) return;

  const esc = (s = "") =>
    String(s).replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );

  const isOpen = () => !dropdown.classList.contains("hidden");

  function openDropdown() {
    dropdown.classList.remove("hidden");
    trigger.setAttribute("aria-expanded", "true");
  }

  function closeDropdown() {
    dropdown.classList.add("hidden");
    trigger.setAttribute("aria-expanded", "false");
  }

  function setCount(n) {
    const val = Number(n || 0);
    countEl.textContent = val > 99 ? "99+" : String(val);
    countEl.classList.toggle("hidden", val <= 0);
  }

  async function apiGet(action) {
    try {
      const res = await fetch(`${apiBase}?action=${action}`, {
        credentials: "same-origin",
      });
      if (!res.ok) return null;
      return await res.json();
    } catch {
      return null;
    }
  }

  async function apiPost(action, bodyObj = {}) {
    try {
      const res = await fetch(`${apiBase}?action=${action}`, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
        },
        body: new URLSearchParams(bodyObj),
      });
      if (!res.ok) return null;
      return await res.json();
    } catch {
      return null;
    }
  }

  async function fetchCount() {
    const j = await apiGet("fetch_unread_count");
    if (j && j.ok) setCount(j.unread || 0);
  }

  function renderEmpty() {
    listEl.innerHTML = `<div class="px-4 py-10 text-center text-sm text-gray-500">No notifications</div>`;
    if (metaEl) metaEl.textContent = "";
  }

  function renderItems(items) {
    let unread = 0;

    listEl.innerHTML = items
      .map((it) => {
        const id = it.notification_id ?? "";
        const isRead = Number(it.is_read || 0) === 1;
        if (!isRead) unread++;

        return `
          <div class="notification-row px-4 py-3 border-b last:border-b-0 hover:bg-gray-50 cursor-pointer"
               data-id="${esc(id)}" data-read="${isRead ? "1" : "0"}">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-start gap-2">
                  ${!isRead ? `<span class="mt-1 h-2 w-2 rounded-full bg-indigo-600 flex-shrink-0"></span>` : ""}
                  <div class="min-w-0">
                    <div class="${isRead ? "font-medium" : "font-semibold"} text-sm text-gray-900 truncate">
                      ${esc(it.title || "Notification")}
                    </div>
                    <div class="text-xs text-gray-600 mt-1 line-clamp-2">
                      ${esc(it.message || "")}
                    </div>
                    <div class="text-[11px] text-gray-400 mt-1">
                      ${esc(it.created_at || "")}
                    </div>
                  </div>
                </div>
              </div>

              <button type="button"
                      class="notif-delete flex-shrink-0 text-xs text-red-600 hover:underline">
                Clear
              </button>
            </div>
          </div>
        `;
      })
      .join("");

    if (metaEl) metaEl.textContent = unread > 0 ? `${unread} unread` : "";
  }

  async function fetchLatest() {
    const j = await apiGet("fetch_latest");
    if (!(j && j.ok)) return;

    if (!Array.isArray(j.items) || j.items.length === 0) {
      renderEmpty();
      return;
    }
    renderItems(j.items);
  }

  /* =========================
     OPEN / CLOSE
  ========================= */
  trigger.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (isOpen()) {
      closeDropdown();
      return;
    }

    openDropdown();
    await fetchLatest();
  });

  // prevent click inside dropdown from closing
  dropdown.addEventListener("click", (e) => e.stopPropagation());

  // close outside click
  document.addEventListener("click", () => {
    if (isOpen()) closeDropdown();
  });

  // ESC closes
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeDropdown();
  });

  /* =========================
     LIST EVENTS (delegation)
  ========================= */
  listEl.addEventListener("click", async (ev) => {
    const row = ev.target.closest(".notification-row");
    if (!row) return;

    const id = row.dataset.id;
    if (!id) return;

    // delete one
    if (ev.target.closest(".notif-delete")) {
      ev.preventDefault();
      ev.stopPropagation();

      const j = await apiPost("delete", { id });
      if (j && j.ok) {
        await fetchCount();
        await fetchLatest();
      }
      return;
    }

    // mark read (only if unread)
    if (row.dataset.read === "0") {
      const j = await apiPost("mark_read", { id });
      if (j && j.ok) {
        await fetchCount();
        await fetchLatest();
      }
    }
  });

  /* =========================
     FOOTER ACTIONS
  ========================= */
  markAllBtn?.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();

    const j = await apiPost("mark_all_read");
    if (j && j.ok) {
      await fetchCount();
      await fetchLatest();
    }
  });

  clearAllBtn?.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();

    const j = await apiPost("delete_all");
    if (j && j.ok) {
      await fetchCount();
      await fetchLatest();
    }
  });

  // init
  fetchCount();
  setInterval(fetchCount, 30000);
})();
