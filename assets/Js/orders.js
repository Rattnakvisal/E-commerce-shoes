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
    title: `Order #${order.order_id}`,
    width: 720,
    html: `
      <div class="text-left text-sm space-y-2">
        <p><b>Customer:</b> ${escapeHtml(order.customer_name)}</p>
        <p><b>Status:</b> ${escapeHtml(order.order_status)}</p>
        <p><b>Payment:</b> ${escapeHtml(order.payment_status)}</p>
        <p><b>Date:</b> ${new Date(order.created_at).toLocaleString()}</p>

        <table class="w-full border mt-3 text-sm">
          <thead>
            <tr>
              <th>Item</th>
              <th>Qty</th>
              <th>Price</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>

        <div class="text-right mt-3">
          <b>Total:</b> $${total.toFixed(2)}
        </div>
      </div>
    `,
    confirmButtonText: "Close",
    confirmButtonColor: "#4f46e5",
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
