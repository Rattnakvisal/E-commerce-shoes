/* =====================================================
   CONFIG
===================================================== */
const API_BASE_URL = "get_order.php";
const RELOAD_DELAY = 700;

/* =====================================================
   EVENT DELEGATION
===================================================== */
document.addEventListener("click", (e) => {
  const btn = e.target.closest("[data-action]");
  if (!btn) return;

  const { action, id, status, payment } = btn.dataset;

  switch (action) {
    case "view":
      viewOrder(id);
      break;
    case "edit":
      editOrder(id, status);
      break;
    case "payment":
      editPayment(id, payment);
      break;
    case "refund":
      refundOrder(id);
      break;
    case "complete":
      confirmStatusChange(id, "completed");
      break;
    case "cancel":
      confirmStatusChange(id, "cancelled");
      break;
  }
});

/* =====================================================
   SWEETALERT HELPERS (GLOBAL STANDARD)
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
    text: msg,
    confirmButtonColor: "#dc2626",
  });
}

/* =====================================================
   CONFIRM HELPERS (MATCH USERS & PRODUCTS)
===================================================== */
function confirmEdit(title, text) {
  return Swal.fire({
    icon: "question",
    title,
    html: `
      <p class="text-gray-600 mt-2">
        ${text}
      </p>
    `,
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
    html: `
      <p class="text-gray-600 mt-2">
        ${text}
      </p>
    `,
    showCancelButton: true,
    confirmButtonText: "Confirm",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#dc2626",
  });
}

/* =====================================================
   EDIT ORDER STATUS
===================================================== */
function editOrder(orderId, currentStatus = "pending") {
  if (!orderId) return;

  const allowed = ["pending", "processing", "completed", "cancelled"];
  currentStatus = allowed.includes(currentStatus) ? currentStatus : "pending";

  Swal.fire({
    title: "Update Order Status",
    html: `
      <p class="text-gray-600 mt-2">
        Select the new status for this order.
      </p>
    `,
    input: "select",
    inputOptions: {
      pending: "Pending",
      processing: "Processing",
      completed: "Completed",
      cancelled: "Cancelled",
    },
    inputValue: currentStatus,
    showCancelButton: true,
    confirmButtonText: "Update",
    confirmButtonColor: "#4f46e5",
  }).then((res) => {
    if (res.isConfirmed && res.value) {
      updateOrderStatus(orderId, res.value);
    }
  });
}

/* =====================================================
   UPDATE ORDER STATUS
===================================================== */
async function updateOrderStatus(orderId, status) {
  try {
    showLoading("Updating order...");

    const res = await fetch(`${API_BASE_URL}?action=update_status`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({ order_id: orderId, status }),
    });

    const data = await res.json();
    if (!res.ok || !data.success)
      throw new Error(data.error || data.message || "Update failed");

    Swal.close();
    showSuccess("Order updated", "Order status updated successfully.");
    setTimeout(() => location.reload(), RELOAD_DELAY);
  } catch (err) {
    Swal.close();
    showError(err.message);
  }
}

/* =====================================================
   EDIT PAYMENT STATUS
===================================================== */
function editPayment(orderId, currentPayment = "pending") {
  if (!orderId) return;

  Swal.fire({
    title: "Update Payment Status",
    html: `
      <p class="text-gray-600 mt-2">
        Select the new payment status for this order.
      </p>
    `,
    input: "select",
    inputOptions: {
      pending: "Pending",
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

    try {
      showLoading("Updating payment...");

      const response = await fetch(`${API_BASE_URL}?action=update_payment`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({
          order_id: orderId,
          payment_status: res.value,
        }),
      });

      const data = await response.json();
      if (!response.ok || !data.success)
        throw new Error(data.error || data.message || "Update failed");

      Swal.close();
      showSuccess("Payment updated", "Payment status updated successfully.");
      setTimeout(() => location.reload(), RELOAD_DELAY);
    } catch (err) {
      Swal.close();
      showError(err.message);
    }
  });
}

/* =====================================================
   REFUND ORDER
===================================================== */
async function refundOrder(orderId) {
  const res = await confirmDelete(
    "Refund order?",
    "This will refund the payment and restock all items associated with this order.",
  );
  if (!res.isConfirmed) return;

  try {
    showLoading("Processing refund...");

    const response = await fetch(`${API_BASE_URL}?action=refund`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({ order_id: orderId }),
    });

    const data = await response.json();
    if (!response.ok || !data.success)
      throw new Error(data.error || data.message || "Refund failed");

    Swal.close();
    showSuccess("Order refunded", "Payment has been refunded successfully.");
    setTimeout(() => location.reload(), RELOAD_DELAY);
  } catch (err) {
    Swal.close();
    showError(err.message);
  }
}

/* =====================================================
   CONFIRM QUICK STATUS CHANGE
===================================================== */
function confirmStatusChange(orderId, status) {
  confirmEdit(
    "Confirm status change",
    `Change order status to <b>${status.toUpperCase()}</b>?`,
  ).then((res) => {
    if (res.isConfirmed) updateOrderStatus(orderId, status);
  });
}

/* =====================================================
   VIEW ORDER
===================================================== */
async function viewOrder(orderId) {
  try {
    showLoading("Loading order...");

    const res = await fetch(`${API_BASE_URL}?action=view&order_id=${orderId}`);
    const data = await res.json();
    if (!res.ok || !data.success)
      throw new Error(data.error || "Failed to load order");

    renderOrderModal(data.order, data.items);
  } catch (err) {
    Swal.close();
    showError(err.message);
  }
}

/* =====================================================
   BADGE HELPER
===================================================== */
function badgeHTML(type, status) {
  status = String(status || "").toLowerCase();
  const base =
    "inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold";
  let variant = "bg-gray-100 text-gray-800";

  if (status === "completed" || status === "paid")
    variant = "bg-emerald-100 text-emerald-800";
  else if (status === "processing") variant = "bg-blue-100 text-blue-800";
  else if (status === "pending") variant = "bg-yellow-100 text-amber-800";
  else if (status === "cancelled" || status === "failed")
    variant = "bg-red-100 text-red-800";
  else if (status === "refunded") variant = "bg-purple-100 text-purple-800";

  const label = String(status || "").replace(/(^|\s)\S/g, (c) =>
    c.toUpperCase(),
  );
  return `<span class="${base} ${variant}">${escapeHtml(type)}: ${escapeHtml(label)}</span>`;
}

/* =====================================================
   RENDER ORDER MODAL
===================================================== */
function renderOrderModal(order, items = []) {
  let total = 0;

  const rows = items
    .map((i) => {
      const price = Number(i.price || 0);
      const qty = Number(i.quantity || 0);
      const line = price * qty;
      total += line;

      return `
        <tr>
          <td>${escapeHtml(i.product_name)}</td>
          <td>${qty}</td>
          <td>$${price.toFixed(2)}</td>
          <td>$${line.toFixed(2)}</td>
        </tr>`;
    })
    .join("");

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

    <!-- CUSTOMER + STATUS -->
    <div class="mt-4 p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">

      <!-- Customer -->
      <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Customer</p>
        <p class="mt-1 text-base font-extrabold text-gray-900 truncate">
          ${escapeHtml(order.customer_name)}
        </p>
        <p class="mt-3 text-xs text-gray-500">
          <span class="font-semibold">Date:</span>
          ${escapeHtml(new Date(order.created_at).toLocaleString())}
        </p>
      </div>

      <!-- Status -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Status</p>

        <div class="mt-3 flex flex-wrap items-center gap-2">
          ${badgeHTML("Order", order.order_status)}
          ${badgeHTML("Payment", order.payment_status)}
        </div>

        <!-- mini timeline -->
        <div class="mt-4 flex items-center gap-2 text-xs text-gray-400">
          <span class="font-semibold text-gray-600">Order</span>
          <span>→</span>
          <span class="font-semibold text-gray-600">Payment</span>
        </div>

        <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
          <span class="font-semibold">Order ID</span>
          <span class="font-mono text-gray-700">${escapeHtml(order.order_id)}</span>
        </div>
      </div>
    </div>

    <!-- ITEMS -->
    <div class="px-4">
      <div class="mt-5 rounded-2xl border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="px-5 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
          <p class="text-sm font-extrabold text-gray-900">Order Items</p>
          <p class="text-xs text-gray-500">Scroll to view</p>
        </div>
        <!-- Table -->
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
              ${rows}
            </tbody>
          </table>
        </div>

        <!-- Sticky Total -->
        <div class="px-5 py-4 bg-white border-t border-gray-100 flex items-center justify-between">
          <span class="text-sm font-bold text-gray-600">Grand Total</span>
          <span class="text-xl font-extrabold text-gray-900">
            $${Number(total).toFixed(2)}
          </span>
        </div>
      </div>
    </div>

    <!-- Footer tip -->
    <div class="mt-4 p-4 flex items-center justify-between text-xs text-gray-400">
      <span>
        Press <span class="px-2 py-1 rounded-lg border bg-gray-50 font-semibold">ESC</span> to close
      </span>
      <span class="font-semibold">Admin View</span>
    </div>

  </div>
  `,
  });
}

/* =====================================================
   UTILITIES
===================================================== */
function escapeHtml(text = "") {
  const el = document.createElement("div");
  el.textContent = text;
  return el.innerHTML;
}

/* =====================================================
   GLOBAL EXPORTS
===================================================== */
Object.assign(window, {
  ordersEdit: editOrder,
  ordersView: viewOrder,
  ordersComplete: (id) => confirmStatusChange(id, "completed"),
});
