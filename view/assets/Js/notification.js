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

  const apiBase = "/E-commerce-shoes/admin/controller/notifications_api.php";
  const countEl = document.getElementById("notificationCount");
  const listEl = document.getElementById("notificationList");
  const dropdown = document.getElementById("notificationDropdown");
  const trigger = document.getElementById("notificationTrigger");
  const markAllBtn = document.getElementById("markAllRead");

  async function fetchCount() {
    try {
      const res = await fetch(apiBase + "?action=fetch_unread_count", {
        credentials: "same-origin",
      });
      if (!res.ok) return;
      const j = await res.json();
      if (j && j.ok) {
        countEl.textContent = Number(j.unread || 0);
      }
    } catch (e) {}
  }

  async function fetchLatest() {
    try {
      const res = await fetch(apiBase + "?action=fetch_latest", {
        credentials: "same-origin",
      });
      if (!res.ok) return;
      const j = await res.json();
      if (!(j && j.ok)) return;
      listEl.innerHTML = "";
      if (!Array.isArray(j.items) || j.items.length === 0) {
        listEl.innerHTML =
          '<div class="p-3 text-gray-600">No notifications</div>';
        return;
      }
      j.items.forEach((it) => {
        const row = document.createElement("div");
        row.className =
          "notification-row px-3 py-2 hover:bg-gray-50 border-b cursor-pointer";
        row.dataset.id = it.notification_id ?? "";
        row.dataset.read = it.is_read ? "1" : "0";
        row.innerHTML =
          '<div class="flex justify-between items-start gap-3">' +
          '<div class="flex-1">' +
          '<div class="font-medium">' +
          esc(it.title) +
          "</div>" +
          '<div class="text-gray-600 text-xs mt-1">' +
          esc(it.message) +
          "</div>" +
          '<div class="text-gray-400 text-xs mt-1">' +
          esc(it.created_at) +
          "</div>" +
          "</div>" +
          '<div class="flex-shrink-0 pl-2">' +
          '<button class="notif-delete text-red-500 text-xs">Clear</button>' +
          "</div>" +
          "</div>";
        if (it.is_read == 0) {
          row.classList.add("font-semibold");
        }
        listEl.appendChild(row);
      });

      listEl.querySelectorAll(".notification-row").forEach((r) => {
        r.addEventListener("click", async function (ev) {
          const nid = this.dataset.id;
          if (!nid) return;
          try {
            const res = await fetch(apiBase + "?action=mark_read", {
              method: "POST",
              credentials: "same-origin",
              body: new URLSearchParams({
                id: nid,
              }),
            });
            if (!res.ok) return;
            const jj = await res.json();
            if (jj && jj.ok) {
              await fetchCount();
              await fetchLatest();
            }
          } catch (e) {}
        });
        // delete button inside row -> delete single notification
        r.querySelectorAll(".notif-delete").forEach((btn) => {
          btn.addEventListener("click", async function (ev) {
            ev.stopPropagation();
            const nid = r.dataset.id;
            if (!nid) return;
            try {
              const res = await fetch(apiBase + "?action=delete", {
                method: "POST",
                credentials: "same-origin",
                body: new URLSearchParams({
                  id: nid,
                }),
              });
              if (!res.ok) return;
              const jj = await res.json();
              if (jj && jj.ok) {
                await fetchCount();
                await fetchLatest();
              }
            } catch (e) {}
          });
        });
      });
    } catch (e) {}
  }

  // Utility to position dropdown centered under the trigger and clamp to viewport
  function positionDropdown() {
    if (!trigger || !dropdown) return;
    // ensure dropdown is visible to measure
    dropdown.style.visibility = "hidden";
    dropdown.classList.remove("hidden");
    dropdown.style.position = "fixed";

    const rect = trigger.getBoundingClientRect();
    const ddW = dropdown.offsetWidth || 300;
    const ddH = dropdown.offsetHeight || 200;

    let left = rect.left + rect.width / 2 - ddW / 2;
    const padding = 8;
    if (left < padding) left = padding;
    if (left + ddW > window.innerWidth - padding)
      left = window.innerWidth - ddW - padding;

    let top = rect.bottom + 8; // 8px gap
    // if not enough space below, show above
    if (top + ddH > window.innerHeight - padding) {
      top = rect.top - ddH - 8;
      if (top < padding) top = padding;
    }

    dropdown.style.left = left + "px";
    dropdown.style.top = top + "px";
    dropdown.style.right = "auto";
    dropdown.style.transform = "none";
    dropdown.style.visibility = "visible";
  }

  function hideDropdown() {
    if (!dropdown) return;
    dropdown.classList.add("hidden");
    dropdown.style.left = "";
    dropdown.style.top = "";
    dropdown.style.position = "";
    const back = document.getElementById("notificationBackdrop");
    if (back) back.classList.add("hidden");
  }

  trigger?.addEventListener("click", async function (e) {
    e.preventDefault();
    if (!dropdown) return;

    const wasHidden = dropdown.classList.contains("hidden");
    if (wasHidden) {
      await fetchLatest();
      positionDropdown();
      const back = document.getElementById("notificationBackdrop");
      if (back) back.classList.remove("hidden");
    } else {
      hideDropdown();
    }
  });

  // Close button (inside dropdown)
  document
    .getElementById("closeNotification")
    ?.addEventListener("click", function () {
      hideDropdown();
    });

  // Clicking backdrop closes dropdown
  document
    .getElementById("notificationBackdrop")
    ?.addEventListener("click", function () {
      hideDropdown();
    });

  // Close on ESC
  document.addEventListener("keydown", function (ev) {
    if (ev.key === "Escape") hideDropdown();
  });

  markAllBtn?.addEventListener("click", async function () {
    try {
      const res = await fetch(apiBase + "?action=mark_all_read", {
        method: "POST",
        credentials: "same-origin",
      });
      const j = await res.json();
      if (j && j.ok) {
        await fetchCount();
        await fetchLatest();
      }
    } catch (e) {}
  });

  const clearAllBtn = document.getElementById("clearAll");
  clearAllBtn?.addEventListener("click", async function () {
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
  // poll
  setInterval(fetchCount, 30000);
})();
