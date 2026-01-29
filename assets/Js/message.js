// messages_dropdown.js (CONTACT / MESSAGES)
document.addEventListener("DOMContentLoaded", () => {
  const dropdown = document.getElementById("messagesDropdown");
  const msgBtn = document.getElementById("messagesButton");
  const badge = document.querySelector("#msgBadge");
  const list = document.getElementById("messagesList");
  const viewAll = document.getElementById("viewAllMessages");
  const markAll = document.getElementById("msgMarkAllReadBtn");
  const clearAllBtn = document.getElementById("msgClearAllBtn");

  const API = "/E-commerce-shoes/admin/controller/message/messages_api.php";

  if (!dropdown || !msgBtn || !list) return;

  /* ----------------------------------------
      Helpers
  ------------------------------------- */

  const hideBadge = () => {
    if (badge) badge.style.display = "none";
  };

  const updateBadge = (delta = 0) => {
    if (!badge) return;

    const t = (badge.textContent || "").trim();
    if (!t) return;

    // handle 99+
    let count = t === "99+" ? 100 : parseInt(t, 10) || 0;
    count = Math.max(0, count + delta);

    if (count === 0) {
      hideBadge();
    } else {
      badge.style.display = "flex";
      badge.textContent = count > 99 ? "99+" : String(count);
    }
  };

  const clearUI = () => {
    hideBadge();
    list.innerHTML =
      '<p class="py-6 text-center text-sm text-gray-500">No messages</p>';
  };

  const post = async (url) => {
    try {
      const res = await fetch(url, {
        method: "POST",
        credentials: "same-origin",
      });
      return res;
    } catch (err) {
      console.error(url, err);
      return null;
    }
  };

  /* ----------------------------------------
     Dropdown Toggle
  ------------------------------------- */

  msgBtn.addEventListener("click", async (e) => {
    e.stopPropagation();
    setTimeout(async () => {
      if (!dropdown.classList.contains("hidden")) {
        await post(`${API}?action=mark_all_read`);
        hideBadge();

        list
          .querySelectorAll(".msg-item")
          .forEach((el) => el.classList.remove("bg-indigo-50"));
      }
    }, 0);
  });

  /* ----------------------------------------
      Mark All / View All / Clear All
  ------------------------------------- */

  markAll?.addEventListener("click", async (e) => {
    e?.preventDefault?.();
    await post(`${API}?action=mark_all_read`);
    clearUI();
  });

  viewAll?.addEventListener("click", async (e) => {
    e.preventDefault();
    const res = await post(`${API}?action=mark_all_read`);
    if (res && (res.ok || res.status === 204)) {
      window.location.href = viewAll.href;
    } else {
      console.warn("Failed to mark messages read before navigation", res);
      window.location.href = viewAll.href;
    }
  });

  clearAllBtn?.addEventListener("click", async (e) => {
    e.preventDefault();
    await post(`${API}?action=delete_all`);
    clearUI();
  });

  /* ----------------------------------------
     Messages List (Event Delegation)
  ------------------------------------- */

  list.addEventListener("click", async (e) => {
    const clearBtn = e.target.closest(".msg-clear");
    const item = e.target.closest(".msg-item");

    // Delete message
    if (clearBtn) {
      e.preventDefault();
      const id = clearBtn.dataset.id;
      if (!id) return;

      await post(`${API}?action=delete&id=${encodeURIComponent(id)}`);
      clearBtn.closest(".msg-row")?.remove();
      updateBadge(-1);
    }

    // Mark single message as read
    if (item && !clearBtn) {
      e.preventDefault();
      const id = item.dataset.id;
      if (!id) return;

      await post(`${API}?action=mark_read&id=${encodeURIComponent(id)}`);
      item.closest(".msg-row")?.remove();
      updateBadge(-1);
    }

    if (list.querySelectorAll(".msg-item").length === 0) {
      clearUI();
    }
  });
});
