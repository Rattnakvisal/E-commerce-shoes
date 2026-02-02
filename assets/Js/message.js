(function () {
  "use strict";

  const API = "/E-commerce-shoes/admin/controller/message/messages_api.php";

  // DOM (match your HTML)
  const listEl = document.getElementById("messagesList");
  const markAllBtn = document.getElementById("msgMarkAllReadBtn");
  const clearAllBtn = document.getElementById("msgClearAllBtn");

  if (!listEl) {
    console.warn("[Msg] #messagesList not found");
    return;
  }

  // Find dropdown + button safely
  const dropdown = listEl.closest(".js-dropdown-menu");
  const wrap = dropdown ? dropdown.closest(".js-dropdown") : null;
  const btn = wrap ? wrap.querySelector(".js-dropdown-btn") : null;

  const badge = btn
    ? btn.querySelector(
        "span.absolute.rounded-full, span.absolute.min-w-\\[18px\\], span.absolute.min-w-\\[20px\\]",
      )
    : null;

  if (!dropdown || !btn) {
    console.warn("[Msg] dropdown or button not found", { dropdown, btn });
    return;
  }

  /* =========================================
     Helpers
  ========================================= */
  function setBadgeCount(n) {
    if (!badge) return;
    const num = Number(n || 0);

    if (num <= 0) {
      badge.remove(); // remove badge entirely (clean)
      return;
    }

    badge.style.display = "flex";
    badge.textContent = num > 99 ? "99+" : String(num);
  }

  function decreaseBadgeByOne() {
    if (!badge) return;
    const t = (badge.textContent || "").trim();
    let count = t === "99+" ? 100 : parseInt(t, 10) || 0;
    count = Math.max(0, count - 1);
    if (count <= 0) badge.remove();
    else badge.textContent = count > 99 ? "99+" : String(count);
  }

  function renderEmpty() {
    listEl.innerHTML = `<div class="py-10 text-center">
         <p class="text-sm font-semibold text-gray-700">No messages</p>
         <p class="text-xs text-gray-500 mt-1">Inbox is empty.</p>
       </div>`;
  }

  async function post(action, data = {}) {
    const url = `${API}?action=${encodeURIComponent(action)}`;
    const res = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams(data),
    });

    // allow 204 no content
    if (res.status === 204) return { ok: true };

    const j = await res.json().catch(() => null);
    if (!res.ok || !j || j.ok !== true) {
      throw new Error(j?.msg || `Request failed (${res.status})`);
    }
    return j;
  }

  /* =========================================
     Actions
  ========================================= */

  // Mark all read (DO NOT clear list)
  markAllBtn?.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();

    try {
      const r = await post("mark_all_read");
      // remove unread UI
      listEl.querySelectorAll(".msg-item").forEach((el) => {
        el.classList.remove("bg-indigo-50", "bg-indigo-50/50");
      });
      // remove "Unread" tag if you render it
      listEl
        .querySelectorAll(".msg-item .inline-flex")
        .forEach((tag) => tag.remove());
      // remove left unread bar if exists
      listEl
        .querySelectorAll(".msg-row > span.bg-indigo-500")
        .forEach((bar) => bar.remove());

      // badge to unread (if API returns)
      if (r.unread !== undefined) setBadgeCount(r.unread);
      else if (badge) badge.remove();
    } catch (err) {
      console.error("[Msg] mark_all_read failed:", err);
    }
  });

  // Clear all (delete all)
  clearAllBtn?.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!confirm("Clear all messages?")) return;

    try {
      await post("delete_all");
      if (badge) badge.remove();
      renderEmpty();
    } catch (err) {
      console.error("[Msg] delete_all failed:", err);
    }
  });

  // Delegation: delete one / mark one read
  listEl.addEventListener("click", async (e) => {
    const delBtn = e.target.closest(".msg-clear");
    if (delBtn) {
      e.preventDefault();
      e.stopPropagation();

      const id = delBtn.getAttribute("data-id") || delBtn.dataset.id;
      if (!id) return;

      if (!confirm("Delete this message?")) return;

      try {
        const r = await post("delete", { id });
        delBtn.closest(".group")?.remove(); // your row wrapper uses .group

        if (r.unread !== undefined) setBadgeCount(r.unread);
        else decreaseBadgeByOne();

        if (!listEl.querySelector(".msg-item")) renderEmpty();
      } catch (err) {
        console.error("[Msg] delete failed:", err);
      }
      return;
    }

    const item = e.target.closest(".msg-item");
    if (!item) return;

    e.preventDefault();
    e.stopPropagation();

    const id = item.getAttribute("data-id") || item.dataset.id;
    if (!id) return;

    try {
      const r = await post("mark_read", { id });

      item.closest(".group")?.remove();

      if (r.unread !== undefined) setBadgeCount(r.unread);
      else decreaseBadgeByOne();

      if (!listEl.querySelector(".msg-item")) renderEmpty();
    } catch (err) {
      console.error("[Msg] mark_read failed:", err);
    }
  });
})();
