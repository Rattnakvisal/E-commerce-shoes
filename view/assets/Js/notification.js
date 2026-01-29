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

  trigger?.addEventListener("click", async function (e) {
    e.preventDefault();
    if (!dropdown) return;
    dropdown.classList.toggle("hidden");
    if (!dropdown.classList.contains("hidden")) {
      await fetchLatest();
    }
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
