(() => {
  "use strict";

  /* ============================
     CONFIG
  ============================ */
  const ENDPOINT = "/E-commerce-shoes/view/order_items.php"; // change if needed
  const AUTO_LOAD = true; // set false if you want load only on <details> open

  // cache: orderId -> items[]
  const itemsCache = new Map();
  // in-flight requests: orderId -> Promise
  const inflight = new Map();

  /* ============================
     UTIL
  ============================ */
  const qs = (sel, root = document) => root.querySelector(sel);
  const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const esc = (s) =>
    String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  const money = (n) => `$${Number(n || 0).toFixed(2)}`;

  function setLoading(box) {
    box.innerHTML = `
      <div class="space-y-3 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
        <div class="h-20 bg-gray-200 rounded-2xl"></div>
        <div class="h-20 bg-gray-200 rounded-2xl"></div>
      </div>
    `;
  }

  function setError(box, msg = "Failed to load items.") {
    box.innerHTML = `<div class="text-sm text-rose-600">${esc(msg)}</div>`;
  }

  /* ============================
     FETCH
  ============================ */
  async function fetchItems(orderId) {
    // cached
    if (itemsCache.has(orderId)) return itemsCache.get(orderId);

    // in-flight
    if (inflight.has(orderId)) return inflight.get(orderId);

    const p = (async () => {
      const url = `${ENDPOINT}?order_id=${encodeURIComponent(orderId)}`;

      const res = await fetch(url, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
      });

      const text = await res.text().catch(() => "");
      let data = {};
      try {
        data = text ? JSON.parse(text) : {};
      } catch {
        data = {};
      }

      if (!res.ok) {
        throw new Error(data?.error || `HTTP ${res.status}`);
      }

      // support: {success:true, items:[...]} OR [...items]
      const items = Array.isArray(data?.items)
        ? data.items
        : Array.isArray(data)
          ? data
          : [];

      // if your API uses success:false
      if (
        data &&
        typeof data === "object" &&
        "success" in data &&
        data.success === false
      ) {
        throw new Error(data.error || "Failed to load items.");
      }

      itemsCache.set(orderId, items);
      return items;
    })();

    inflight.set(orderId, p);

    try {
      return await p;
    } finally {
      inflight.delete(orderId);
    }
  }

  /* ============================
     RENDER
  ============================ */
  function renderItems(box, items) {
    if (!Array.isArray(items) || items.length === 0) {
      box.innerHTML = `<div class="text-sm text-gray-500">No items found.</div>`;
      box.dataset.loaded = "1";
      return;
    }

    const html = `
      <div class="space-y-4">
        ${items
          .map((it) => {
            const img = esc(it.image_url || it.image || "");
            const name = esc(it.name || it.product_name || "Item");
            const productId = it.product_id ?? it.id ?? "";
            const productUrl = productId
              ? `/E-commerce-shoes/view/content/product.php?product_id=${encodeURIComponent(productId)}`
              : "#";

            const qty = Number(it.qty ?? it.quantity ?? 0);
            const price = Number(it.price ?? 0);

            const status = esc(it.item_status || it.status || "");
            const expected = esc(
              it.expected_date || it.delivery_expected || "",
            );

            const lineTotal = qty * price;

            return `
              <div class="flex gap-4 p-4 rounded-3xl border border-gray-200 bg-gray-50/40">
                <div class="w-20 h-20 rounded-2xl overflow-hidden bg-white border">
                  ${img ? `<img src="${img}" class="w-full h-full object-cover" alt="">` : ``}
                </div>

                <div class="flex-1 min-w-0">
                  <a href="${productUrl}" class="block font-extrabold text-gray-900 truncate hover:text-indigo-600">
                    ${name}
                  </a>
                  <p class="text-xs text-gray-500 mt-1">
                    Qty: <b>${qty}</b> • Price <b>${money(price)}</b>
                    <span class="mx-2 text-gray-300">|</span>
                    Line: <b>${money(lineTotal)}</b>
                  </p>
                </div>

                <div class="hidden sm:flex items-center gap-10 text-sm">
                  <div class="text-right">
                    <p class="text-xs text-gray-500">Status</p>
                    <p class="font-extrabold ${
                      status.toLowerCase() === "cancelled"
                        ? "text-rose-600"
                        : "text-emerald-600"
                    }">
                      ${status || "—"}
                    </p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-gray-500">Delivery Expected</p>
                    <p class="font-extrabold text-gray-900">${expected || "—"}</p>
                  </div>
                </div>
              </div>
            `;
          })
          .join("")}
      </div>
    `;

    box.innerHTML = html;
    box.dataset.loaded = "1";
  }

  /* ============================
     MAIN LOADER
  ============================ */
  async function loadOrderItemsIntoBox(box) {
    const orderId = box?.dataset?.orderId;
    if (!orderId) return;

    // if already rendered
    if (box.dataset.loaded === "1") return;

    setLoading(box);

    try {
      const items = await fetchItems(orderId);
      renderItems(box, items);
    } catch (err) {
      setError(box, err?.message || "Failed to load items.");
    }
  }

  /* ============================
     PRINT / CANCEL
  ============================ */
  window.printOrderCard = function printOrderCard(orderId) {
    const card = document.querySelector(`[data-order-id="${orderId}"]`);
    if (!card) return window.print();

    const w = window.open("", "_blank");
    w.document.write(`
      <!doctype html><html><head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <script src="https://cdn.tailwindcss.com"><\/script>
        <title>Invoice #${orderId}</title>
      </head>
      <body class="p-6 bg-white">
        ${card.outerHTML}
        <script>
          window.onload=function(){window.print();setTimeout(()=>window.close(),300)}
        <\/script>
      </body></html>
    `);
    w.document.close();
  };

  window.requestCancel = function requestCancel(orderId) {
    if (!confirm("Cancel this order?")) return;

    const btn = document.querySelector(
      `[data-order-id="${orderId}"] button[onclick^="requestCancel("]`,
    );
    if (btn) btn.disabled = true;

    fetch("/E-commerce-shoes/view/cancel_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      credentials: "same-origin",
      body: `order_id=${encodeURIComponent(orderId)}`,
    })
      .then((res) =>
        res.json().catch(() => ({ success: false, error: "Invalid response" })),
      )
      .then((data) => {
        if (!data || data.success !== true) {
          throw new Error(data?.error || "Failed to cancel order");
        }

        // update DOM: mark order as cancelled and remove cancel button
        const card = document.querySelector(`[data-order-id="${orderId}"]`);
        if (!card) return location.reload();

        card.dataset.orderStatus = "cancelled";

        // update order pill
        const inlinePills = Array.from(
          card.querySelectorAll("span.inline-flex"),
        );
        for (const span of inlinePills) {
          const t = (span.textContent || "").trim();
          if (t.startsWith("Order:") || t.includes("Order:")) {
            span.className =
              "inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold border text-rose-700 bg-rose-50 border-rose-100";
            span.innerHTML = `<span class="h-2 w-2 rounded-full bg-rose-500"></span> <i class="fa-solid fa-truck-fast"></i> Order: Cancelled`;
          }
        }

        // hide/remove cancel button
        const cbtn = card.querySelector('button[onclick^="requestCancel("]');
        if (cbtn) cbtn.remove();

        alert("Order cancelled");
      })
      .catch((err) => {
        alert(err?.message || "Failed to cancel order");
      })
      .finally(() => {
        if (btn) btn.disabled = false;
      });
  };

  /* ============================
     INIT
  ============================ */
  document.addEventListener("DOMContentLoaded", () => {
    // 1) If you use <details>, load items when opened
    qsa("details").forEach((details) => {
      details.addEventListener("toggle", () => {
        if (!details.open) return;
        const box = qs(".order-items-container", details);
        if (box) loadOrderItemsIntoBox(box);
      });
    });

    // 2) Auto-load (optional)
    if (AUTO_LOAD) {
      qsa(".order-items-container").forEach((box) =>
        loadOrderItemsIntoBox(box),
      );
    }
  });
})();
