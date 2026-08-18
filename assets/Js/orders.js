/* =====================================================
   ORDERS ADMIN (FULL + FIXED)
  - pending -> processing -> shipped -> delivered -> completed
   - Cancel with reason (uses order_update_status.php)
   - Payment update + refund (uses orders_api.php)
   - View modal (orders_api.php?action=view)
   - Works with your data-action buttons
===================================================== */

(() => {
  "use strict";

  /* ===============================
     CONFIG (EDIT PATHS)
  ================================ */
  // Controller endpoints (corrected paths)
  const ORDERS_API = "/MyBrand_Ecommerce/admin/controller/orders/get_order.php";
  const STATUS_API =
    "/MyBrand_Ecommerce/admin/controller/orders/order_update_status.php";

  const RELOAD_DELAY = 700;

  // Allowed pipeline
  const FLOW = ["pending", "processing", "shipped", "delivered", "completed"];
  const VALID_STATUS = new Set([...FLOW, "cancelled"]);
  const VALID_PAYMENT = new Set([
    "pending",
    "paid",
    "failed",
    "refunded",
    "unpaid",
  ]);

  /* =====================================================
     EVENT DELEGATION
  ===================================================== */
  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-action]");
    if (!btn) return;

    const action = (btn.dataset.action || "").trim();
    const id = Number(btn.dataset.id || 0);
    const status = normalizeStatus(btn.dataset.status || "pending");
    const payment = normalizePayment(btn.dataset.payment || "pending");

    if (!id) return showError("Missing order id.");

    switch (action) {
      case "view":
        return viewOrder(id);

      case "edit":
        return editOrder(id, status);

      case "mark-processing":
        return confirmMoveNext(btn, id, status, payment, "processing");

      case "mark-shipped":
        return confirmMoveNext(btn, id, status, payment, "shipped");

      case "mark-delivered":
        return confirmMoveNext(btn, id, status, payment, "delivered");

      case "complete":
        return confirmMoveNext(btn, id, status, payment, "completed");

      case "cancel":
        return confirmCancel(btn, id, status);

      case "payment":
        return editPayment(btn, id, payment);

      case "refund":
        return refundOrder(btn, id);

      default:
        return;
    }
  });

  /* =====================================================
     SWEETALERT HELPERS
  ===================================================== */
  function showLoading(msg = "Loading...") {
    Swal.fire({
      title: msg,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => Swal.showLoading(),
    });
  }

  function showSuccess(title, text = "") {
    return Swal.fire({
      icon: "success",
      title,
      text: text || undefined,
      showConfirmButton: false,
      timer: 1200,
      timerProgressBar: true,
    });
  }

  function showError(msg) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: msg || "Something went wrong",
      confirmButtonColor: "#dc2626",
    });
  }

  function confirmEdit(title, text) {
    return Swal.fire({
      icon: "question",
      title,
      html: `<p class="text-gray-600 mt-2">${text}</p>`,
      showCancelButton: true,
      confirmButtonText: "Update",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#4f46e5",
      cancelButtonColor: "#6b7280",
    });
  }

  function confirmDelete(title, text) {
    return Swal.fire({
      icon: "warning",
      title,
      html: `<p class="text-gray-600 mt-2">${text}</p>`,
      showCancelButton: true,
      confirmButtonText: "Confirm",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#dc2626",
    });
  }

  /* =====================================================
     STEP LOGIC
  ===================================================== */
  function nextStatusFrom(current) {
    const idx = FLOW.indexOf(current);
    return idx >= 0 && idx < FLOW.length - 1 ? FLOW[idx + 1] : null;
  }

  function canMoveTo(target, current) {
    const idxC = FLOW.indexOf(current);
    const idxT = FLOW.indexOf(target);
    return idxC >= 0 && idxT === idxC + 1;
  }

  function normalizeStatus(s, fallback = "pending") {
    s = String(s || "")
      .toLowerCase()
      .trim();
    return VALID_STATUS.has(s) ? s : fallback;
  }

  function normalizePayment(p, fallback = "pending") {
    p = String(p || "")
      .toLowerCase()
      .trim();
    return VALID_PAYMENT.has(p) ? p : fallback;
  }

  /* =====================================================
     API HELPERS
  ===================================================== */
  async function safeJson(res) {
    const ct = (res.headers.get("content-type") || "").toLowerCase();
    if (!ct.includes("application/json")) {
      const t = await res.text().catch(() => "");
      return { success: false, error: t || "Non-JSON response from server." };
    }
    return await res.json();
  }

  async function postJson(url, body) {
    const res = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      credentials: "same-origin",
      body: JSON.stringify(body || {}),
    });
    const data = await safeJson(res);
    if (!res.ok || !data?.success) {
      throw new Error(data?.error || data?.message || `HTTP ${res.status}`);
    }
    return data;
  }

  async function getJson(url) {
    const res = await fetch(url, {
      headers: { Accept: "application/json" },
      credentials: "same-origin",
    });
    const data = await safeJson(res);
    if (!res.ok || !data?.success) {
      throw new Error(data?.error || data?.message || `HTTP ${res.status}`);
    }
    return data;
  }

  /* =====================================================
     EDIT ORDER STATUS (MANUAL SELECT)
     - Uses STATUS_API (logs + transition validation)
  ===================================================== */
  function editOrder(orderId, currentStatus = "pending") {
    currentStatus = normalizeStatus(currentStatus, "pending");

    Swal.fire({
      title: "Update Order Status",
      html: `<p class="text-gray-600 mt-2">Select the new status for this order.</p>`,
      input: "select",
      inputOptions: {
        pending: "Pending",
        processing: "Processing",
        shipped: "Shipped",
        delivered: "Delivered",
        completed: "Completed",
        cancelled: "Cancelled",
      },
      inputValue: currentStatus,
      showCancelButton: true,
      confirmButtonText: "Update",
      confirmButtonColor: "#4f46e5",
    }).then(async (res) => {
      if (!res.isConfirmed || !res.value) return;
      const newStatus = normalizeStatus(res.value, currentStatus);

      // If cancel from manual, ask reason
      if (newStatus === "cancelled") {
        return confirmCancel(null, orderId, currentStatus);
      }

      // If trying to "jump", backend will block, but we also warn.
      await updateOrderStatus(orderId, newStatus, null);
    });
  }

  /* =====================================================
     CONFIRM QUICK STATUS CHANGE (STEP BUTTONS)
  ===================================================== */
  function confirmMoveNext(
    btn,
    orderId,
    currentStatus,
    currentPayment,
    target,
  ) {
    currentStatus = normalizeStatus(currentStatus, "pending");
    currentPayment = normalizePayment(currentPayment, "pending");
    target = normalizeStatus(target, currentStatus);

    if (currentStatus === "completed" || currentStatus === "cancelled") {
      return showError("This order is locked.");
    }

    if (!canMoveTo(target, currentStatus)) {
      const expected = nextStatusFrom(currentStatus);
      return showError(
        expected
          ? `Invalid step. This order must go: ${currentStatus} → ${expected}`
          : "Invalid step for this status.",
      );
    }

    if (target === "completed" && currentPayment !== "paid") {
      return showError("You must set payment to PAID before completing.");
    }

    confirmEdit(
      "Confirm status change",
      `Change order status to <b>${target.toUpperCase()}</b>?`,
    ).then(async (res) => {
      if (!res.isConfirmed) return;
      await updateOrderStatus(orderId, target, btn);
    });
  }

  /* =====================================================
     CANCEL (REASON REQUIRED)
     - Uses STATUS_API with note
  ===================================================== */
  function confirmCancel(btn, orderId, currentStatus) {
    currentStatus = normalizeStatus(currentStatus, "pending");
    if (currentStatus === "completed")
      return showError("Completed order cannot be cancelled.");
    if (currentStatus === "cancelled")
      return showError("Order is already cancelled.");

    Swal.fire({
      title: "Cancel Order?",
      input: "text",
      inputLabel: "Reason (required)",
      inputPlaceholder: "Example: customer requested cancel",
      showCancelButton: true,
      confirmButtonText: "Yes, Cancel",
      confirmButtonColor: "#dc2626",
      preConfirm: (val) => {
        if (!val || !val.trim()) {
          Swal.showValidationMessage("Reason is required");
          return false;
        }
        return val.trim();
      },
    }).then(async (res) => {
      if (!res.isConfirmed) return;
      await updateOrderStatus(orderId, "cancelled", btn, res.value);
    });
  }

  /* =====================================================
     UPDATE ORDER STATUS (FIXED)
     - Uses STATUS_API (to_status + note)
  ===================================================== */
  async function updateOrderStatus(orderId, status, btn = null, note = "") {
    status = normalizeStatus(status, "pending");

    try {
      setBtnLoading(btn, true, "Updating...");
      showLoading("Updating order...");

      await postJson(STATUS_API, {
        order_id: Number(orderId),
        to_status: status,
        note: note || "",
      });

      Swal.close();
      await showSuccess(
        "Order updated",
        `Status changed to ${status.toUpperCase()}`,
      );

      if (btn) {
        const row = btn.closest("tr");
        if (row) patchRowStatus(row, status);
      }

      setTimeout(() => location.reload(), RELOAD_DELAY);
    } catch (err) {
      Swal.close();
      showError(err?.message || "Update failed");
    } finally {
      setBtnLoading(btn, false);
    }
  }

  /* =====================================================
     EDIT PAYMENT STATUS (FIXED)
     - Uses ORDERS_API?action=update_payment
  ===================================================== */
  function editPayment(btn, orderId, currentPayment = "pending") {
    currentPayment = normalizePayment(currentPayment, "pending");

    Swal.fire({
      title: "Update Payment Status",
      html: `<p class="text-gray-600 mt-2">Select the new payment status for this order.</p>`,
      input: "select",
      inputOptions: {
        pending: "Pending",
        unpaid: "Unpaid",
        paid: "Paid",
        failed: "Failed",
        refunded: "Refunded",
      },
      inputValue: currentPayment,
      showCancelButton: true,
      confirmButtonText: "Update",
      confirmButtonColor: "#2563eb",
    }).then(async (res) => {
      if (!res.isConfirmed || !res.value) return;
      const nextPay = normalizePayment(res.value, currentPayment);

      try {
        setBtnLoading(btn, true, "Updating...");
        showLoading("Updating payment...");

        await postJson(`${ORDERS_API}?action=update_payment`, {
          order_id: Number(orderId),
          payment_status: nextPay,
        });

        Swal.close();
        await showSuccess(
          "Payment updated",
          `Payment set to ${nextPay.toUpperCase()}`,
        );
        setTimeout(() => location.reload(), RELOAD_DELAY);
      } catch (err) {
        Swal.close();
        showError(err?.message || "Payment update failed");
      } finally {
        setBtnLoading(btn, false);
      }
    });
  }

  /* =====================================================
     REFUND ORDER
     - Uses ORDERS_API?action=refund
  ===================================================== */
  async function refundOrder(btn, orderId) {
    const res = await confirmDelete(
      "Refund order?",
      "This will refund the payment and restock all items associated with this order.",
    );
    if (!res.isConfirmed) return;

    try {
      setBtnLoading(btn, true, "Refunding...");
      showLoading("Processing refund...");

      await postJson(`${ORDERS_API}?action=refund`, {
        order_id: Number(orderId),
      });

      Swal.close();
      await showSuccess(
        "Order refunded",
        "Payment has been refunded successfully.",
      );
      setTimeout(() => location.reload(), RELOAD_DELAY);
    } catch (err) {
      Swal.close();
      showError(err?.message || "Refund failed");
    } finally {
      setBtnLoading(btn, false);
    }
  }

  /* =====================================================
     VIEW ORDER (MODAL)
     - Uses ORDERS_API?action=view
  ===================================================== */
  async function viewOrder(orderId) {
    try {
      showLoading("Loading order...");

      const data = await getJson(
        `${ORDERS_API}?action=view&order_id=${encodeURIComponent(orderId)}`,
      );

      renderOrderModal(data.order, data.items || []);
    } catch (err) {
      Swal.close();
      showError(err?.message || "Failed to load order");
    }
  }

  /* =====================================================
     BADGE HELPERS
  ===================================================== */
  function badgeHTML(type, status) {
    status = String(status || "").toLowerCase();
    const base =
      "inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold";
    let variant = "bg-gray-100 text-gray-800";

    if (status === "completed" || status === "paid")
      variant = "bg-emerald-100 text-emerald-800";
    else if (status === "processing") variant = "bg-blue-100 text-blue-800";
    else if (status === "pending" || status === "unpaid")
      variant = "bg-amber-100 text-amber-800";
    else if (status === "delivered") variant = "bg-indigo-100 text-indigo-800";
    else if (status === "cancelled" || status === "failed")
      variant = "bg-red-100 text-red-800";
    else if (status === "refunded") variant = "bg-purple-100 text-purple-800";

    const label = status.replace(/(^|\s)\S/g, (c) => c.toUpperCase());
    return `<span class="${base} ${variant}">${escapeHtml(type)}: ${escapeHtml(label)}</span>`;
  }

  /* =====================================================
     RENDER ORDER MODAL (FIXED)
     - supports item fields: product_name OR name
  ===================================================== */
  function renderOrderModal(order, items = []) {
    let total = 0;

    const rows = (Array.isArray(items) ? items : [])
      .map((i) => {
        const name = i.product_name || i.name || "";
        const price = Number(i.price || 0);
        const qty = Number(i.quantity || i.qty || 0);
        const line = price * qty;
        total += line;

        return `
          <tr class="border-b border-gray-100">
            <td class="px-5 py-3 font-semibold text-gray-800">${escapeHtml(name)}</td>
            <td class="px-5 py-3 text-center">${qty}</td>
            <td class="px-5 py-3 text-right">$${price.toFixed(2)}</td>
            <td class="px-5 py-3 text-right font-bold">$${line.toFixed(2)}</td>
          </tr>`;
      })
      .join("");

    Swal.close();

    Swal.fire({
      title: `Order #${escapeHtml(order.order_id)}`,
      width: 860,
      showCloseButton: true,
      focusConfirm: false,
      confirmButtonText: "Close",
      confirmButtonColor: "#111827",
      background: "#ffffff",
      customClass: {
        popup: "rounded-3xl shadow-2xl",
        title: "text-left text-xl font-extrabold text-gray-900",
        htmlContainer: "p-0",
        confirmButton:
          "rounded-xl px-6 py-2.5 font-bold bg-gray-900 hover:bg-black",
        closeButton: "text-gray-400 hover:text-gray-700",
      },
      html: `
        <div class="text-left">

          <div class="mt-4 p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
              <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Customer</p>
              <p class="mt-1 text-base font-extrabold text-gray-900 truncate">
                ${escapeHtml(order.customer_name || "Guest")}
              </p>
              <p class="mt-3 text-xs text-gray-500">
                <span class="font-semibold">Date:</span>
                ${escapeHtml(new Date(order.created_at).toLocaleString())}
              </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5">
              <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Status</p>
              <div class="mt-3 flex flex-wrap items-center gap-2">
                ${badgeHTML("Order", order.order_status)}
                ${badgeHTML("Payment", order.payment_status)}
              </div>

              <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                <span class="font-semibold">Order ID</span>
                <span class="font-mono text-gray-700">${escapeHtml(order.order_id)}</span>
              </div>
            </div>
          </div>

          <div class="px-4">
            <div class="mt-5 rounded-2xl border border-gray-200 overflow-hidden">
              <div class="px-5 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <p class="text-sm font-extrabold text-gray-900">Order Items</p>
                <p class="text-xs text-gray-500">${(items || []).length} item(s)</p>
              </div>

              <div class="max-h-[340px] overflow-auto">
                <table class="w-full text-sm">
                  <thead class="sticky top-0 bg-white z-10">
                    <tr class="text-xs text-gray-500 uppercase tracking-wide border-b border-gray-100">
                      <th class="text-left px-5 py-3 font-bold">Item</th>
                      <th class="text-center px-5 py-3 font-bold w-20">Qty</th>
                      <th class="text-right px-5 py-3 font-bold w-24">Price</th>
                      <th class="text-right px-5 py-3 font-bold w-28">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    ${rows || `<tr><td class="px-5 py-6 text-gray-500" colspan="4">No items found.</td></tr>`}
                  </tbody>
                </table>
              </div>

              <div class="px-5 py-4 bg-white border-t border-gray-100 flex items-center justify-between">
                <span class="text-sm font-bold text-gray-600">Grand Total</span>
                <span class="text-xl font-extrabold text-gray-900">$${Number(total).toFixed(2)}</span>
              </div>
            </div>
          </div>

          <div class="mt-4 p-4 flex items-center justify-between text-xs text-gray-400">
            <span>Press <span class="px-2 py-1 rounded-lg border bg-gray-50 font-semibold">ESC</span> to close</span>
            <span class="font-semibold">Admin View</span>
          </div>

        </div>
      `,
    });
  }

  /* =====================================================
     UI PATCH (optional)
  ===================================================== */
  function patchRowStatus(row, newStatus) {
    row.dataset.status = newStatus;
    row
      .querySelectorAll("[data-status]")
      .forEach((b) => (b.dataset.status = newStatus));
  }

  /* =====================================================
     UTILITIES
  ===================================================== */
  function escapeHtml(text = "") {
    const el = document.createElement("div");
    el.textContent = text == null ? "" : String(text);
    return el.innerHTML;
  }

  function setBtnLoading(btn, on, text = "Loading...") {
    if (!btn) return;
    if (on) {
      btn.dataset._oldHtml = btn.innerHTML;
      btn.disabled = true;
      btn.classList.add("opacity-70", "cursor-not-allowed");
      btn.innerHTML = text;
    } else {
      btn.disabled = false;
      btn.classList.remove("opacity-70", "cursor-not-allowed");
      if (btn.dataset._oldHtml) btn.innerHTML = btn.dataset._oldHtml;
      delete btn.dataset._oldHtml;
    }
  }

  /* =====================================================
     GLOBAL EXPORTS (optional)
  ===================================================== */
  Object.assign(window, {
    ordersEdit: editOrder,
    ordersView: viewOrder,
    ordersRefund: (id) => refundOrder(null, id),
  });
})();
