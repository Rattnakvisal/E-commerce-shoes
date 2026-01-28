(function () {
  function esc(s) {
    return String(s || "").replace(/[&<>"'\\]/g, function (c) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
        "\\": "\\\\",
      }[c];
    });
  }

  const apiBase = "/E-commerce-shoes/admin/process/notifications_api.php";

  const countEl = document.getElementById("notificationCount");
  const listEl = document.getElementById("notificationList");
  const dropdown = document.getElementById("notificationDropdown");
  const trigger = document.getElementById("notificationTrigger");
  const markAllBtn = document.getElementById("markAllRead");
  const clearAllBtn = document.getElementById("clearAll");
  const metaEl = document.getElementById("notificationMeta"); // optional

  if (!countEl || !listEl || !dropdown || !trigger) return;

  function setCount(n) {
    const val = Number(n || 0);
    countEl.textContent = val;

    // optional: hide badge if 0
    countEl.classList.toggle("hidden", val <= 0);
  }

  async function fetchCount() {
    try {
      const res = await fetch(apiBase + "?action=fetch_unread_count", {
        credentials: "same-origin",
      });
      if (!res.ok) return;
      const j = await res.json();
      if (j && j.ok) setCount(j.unread || 0);
    } catch (e) {}
  }

  async function markRead(id) {
    try {
      const res = await fetch(apiBase + "?action=mark_read", {
        method: "POST",
        credentials: "same-origin",
        body: new URLSearchParams({ id }),
      });
      if (!res.ok) return null;
      return await res.json();
    } catch (e) {
      return null;
    }
  }

  async function deleteOne(id) {
    try {
      const res = await fetch(apiBase + "?action=delete", {
        method: "POST",
        credentials: "same-origin",
        body: new URLSearchParams({ id }),
      });
      if (!res.ok) return null;
      return await res.json();
    } catch (e) {
      return null;
    }
  }

  function renderEmpty() {
    listEl.innerHTML =
      '<div class="px-4 py-10 text-center text-sm text-gray-500">No notifications</div>';
    if (metaEl) metaEl.textContent = "";
  }

  function renderItems(items) {
    listEl.innerHTML = "";

    let unread = 0;

    items.forEach((it) => {
      const id = it.notification_id ?? "";
      const isRead = Number(it.is_read || 0) === 1;

      if (!isRead) unread++;

      const row = document.createElement("div");
      row.className =
        "notification-row px-4 py-3 border-b last:border-b-0 hover:bg-gray-50 cursor-pointer";
      row.dataset.id = id;
      row.dataset.read = isRead ? "1" : "0";

      // ✅ clean layout (no weird overflow)
      row.innerHTML =
        '<div class="flex items-start justify-between gap-3">' +
        '<div class="min-w-0 flex-1">' +
        '<div class="flex items-center gap-2">' +
        (!isRead
          ? '<span class="mt-0.5 h-2 w-2 rounded-full bg-indigo-600 flex-shrink-0"></span>'
          : "") +
        '<div class="min-w-0">' +
        '<div class="text-sm ' +
        (isRead ? "font-medium text-gray-900" : "font-semibold text-gray-900") +
        ' truncate">' +
        esc(it.title) +
        "</div>" +
        '<div class="text-xs text-gray-600 mt-1 line-clamp-2">' +
        esc(it.message) +
        "</div>" +
        '<div class="text-[11px] text-gray-400 mt-1">' +
        esc(it.created_at) +
        "</div>" +
        "</div>" +
        "</div>" +
        "</div>" +
        '<button type="button" class="notif-delete flex-shrink-0 text-xs text-red-600 hover:underline">' +
        "Clear" +
        "</button>" +
        "</div>";

      listEl.appendChild(row);
    });

    if (metaEl) {
      metaEl.textContent = unread > 0 ? `${unread} unread` : "";
    }
  }

  async function fetchLatest() {
    try {
      const res = await fetch(apiBase + "?action=fetch_latest", {
        credentials: "same-origin",
      });
      if (!res.ok) return;

      const j = await res.json();
      if (!(j && j.ok)) return;

      if (!Array.isArray(j.items) || j.items.length === 0) {
        renderEmpty();
        return;
      }

      renderItems(j.items);
    } catch (e) {}
  }

  /* =========================
     DROPDOWN OPEN/CLOSE FIX
  ========================= */
  function openDropdown() {
    dropdown.classList.remove("hidden");
    trigger.setAttribute("aria-expanded", "true");
  }

  function closeDropdown() {
    dropdown.classList.add("hidden");
    trigger.setAttribute("aria-expanded", "false");
  }

  function toggleDropdown() {
    const willOpen = dropdown.classList.contains("hidden");
    if (willOpen) openDropdown();
    else closeDropdown();
    return willOpen;
  }

  trigger.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();

    const opened = toggleDropdown();
    if (opened) await fetchLatest();
  });

  // click inside dropdown should not close
  dropdown.addEventListener("click", (e) => e.stopPropagation());

  // close on outside click
  document.addEventListener("click", closeDropdown);

  // ESC closes
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeDropdown();
  });

  /* =========================
     ROW EVENTS (event delegation)
     ✅ avoids attaching many listeners
  ========================= */
  listEl.addEventListener("click", async (ev) => {
    const delBtn = ev.target.closest(".notif-delete");
    const row = ev.target.closest(".notification-row");
    if (!row) return;

    const id = row.dataset.id;
    if (!id) return;

    // Delete one
    if (delBtn) {
      ev.preventDefault();
      ev.stopPropagation();
      const jj = await deleteOne(id);
      if (jj && jj.ok) {
        await fetchCount();
        await fetchLatest();
      }
      return;
    }

    // Mark read
    const jj = await markRead(id);
    if (jj && jj.ok) {
      await fetchCount();
      await fetchLatest();
    }
  });

  /* =========================
     ACTION BUTTONS
  ========================= */
  markAllBtn?.addEventListener("click", async () => {
    try {
      const res = await fetch(apiBase + "?action=mark_all_read", {
        method: "POST",
        credentials: "same-origin",
      });
      if (!res.ok) return;
      const j = await res.json();
      if (j && j.ok) {
        await fetchCount();
        await fetchLatest();
      }
    } catch (e) {}
  });

  clearAllBtn?.addEventListener("click", async () => {
    try {
      const res = await fetch(apiBase + "?action=delete_all", {
        method: "POST",
        credentials: "same-origin",
      });
      if (!res.ok) return;
      const j = await res.json();
      if (j && j.ok) {
        await fetchCount();
        await fetchLatest();
      }
    } catch (e) {}
  });

  // init
  fetchCount();
  setInterval(fetchCount, 30000);
})();
