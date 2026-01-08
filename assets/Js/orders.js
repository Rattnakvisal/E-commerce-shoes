/* =====================================================
   CONFIG
===================================================== */
const API_BASE_URL = "get_order.php";

/* =====================================================
   EVENT DELEGATION
===================================================== */
document.addEventListener("click", (e) => {
  const btn = e.target.closest("[data-action]");
  if (!btn) return;

  const action = btn.dataset.action;
  const id = btn.dataset.id;

  switch (action) {
    case "view":
      viewOrder(id);
      break;

    case "edit":
      editOrder(id, btn.dataset.status);
      break;

    case "payment":
      editPayment(id, btn.dataset.payment);
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
   EDIT ORDER STATUS
===================================================== */
function editOrder(orderId, currentStatus = "pending") {
  if (!orderId) return;

  ensureSwal();

  const allowed = ["pending", "processing", "completed", "cancelled"];
  currentStatus = (currentStatus || "pending").toLowerCase();

  if (!allowed.includes(currentStatus)) {
    currentStatus = "pending";
  }

  Swal.fire({
    title: "Update Order Status",
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
   REFUND ORDER (client-side)
===================================================== */
async function refundOrder(orderId) {
  if (!orderId) return;

  ensureSwal();

  const confirmed = await Swal.fire({
    title: "Refund order?",
    html: `This will mark payment as <strong>REFUNDED</strong> and restock items. Continue?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, refund",
    confirmButtonColor: "#dc2626",
  });

  if (!confirmed.isConfirmed) return;

  try {
    showLoading("Processing refund...");

    const res = await fetch(`${API_BASE_URL}?action=refund`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({ order_id: orderId }),
    });

    const data = await res.json();
    if (!res.ok || !data.success)
      throw new Error(data.error || data.message || "Refund failed");

    showSuccess(data.message || "Order refunded");
    setTimeout(() => location.reload(), 700);
  } catch (err) {
    showError(err.message || "Server error");
  }
}

/* =====================================================
   UPDATE ORDER STATUS (API)
===================================================== */
async function updateOrderStatus(orderId, status) {
  try {
    ensureSwal();
    showLoading("Updating order...");

    const res = await fetch(`${API_BASE_URL}?action=update_status`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      credentials: "same-origin",
      body: JSON.stringify({
        order_id: orderId,
        status,
      }),
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
      throw new Error(data.error || data.message || "Update failed");
    }

    showSuccess(data.message || "Order updated");
    setTimeout(() => location.reload(), 600);
  } catch (err) {
    showError(err.message);
  }
}

/* =====================================================
   EDIT PAYMENT STATUS
===================================================== */
function editPayment(orderId, currentPayment = "pending") {
  if (!orderId) return;

  ensureSwal();

  const allowed = {
    pending: "Pending",
    paid: "Paid",
    failed: "Failed",
    refunded: "Refunded",
  };
  currentPayment = (currentPayment || "pending").toLowerCase();

  Swal.fire({
    title: "Update Payment Status",
    input: "select",
    inputOptions: allowed,
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
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        credentials: "same-origin",
        body: JSON.stringify({ order_id: orderId, payment_status: res.value }),
      });

      const data = await response.json();
      if (!response.ok || !data.success)
        throw new Error(data.error || data.message || "Update failed");

      showSuccess(data.message || "Payment status updated");
      setTimeout(() => location.reload(), 600);
    } catch (err) {
      showError(err.message || "Server error");
    }
  });
}

/* =====================================================
   CONFIRM STATUS CHANGE
===================================================== */
function confirmStatusChange(orderId, status) {
  if (!orderId) return;

  ensureSwal();

  Swal.fire({
    title: "Confirm Status Change",
    html: `Change order status to <b>${status.toUpperCase()}</b>?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, update",
    confirmButtonColor: "#4f46e5",
  }).then((res) => {
    if (res.isConfirmed) updateOrderStatus(orderId, status);
  });
}

/* =====================================================
   VIEW ORDER
===================================================== */
async function viewOrder(orderId) {
  if (!orderId) return;

  try {
    ensureSwal();
    showLoading("Loading order...");

    const res = await fetch(`${API_BASE_URL}?action=view&order_id=${orderId}`, {
      headers: { Accept: "application/json" },
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.error || "Failed to load order");
    }

    renderOrderModal(data.order, data.items);
  } catch (err) {
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
function ensureSwal() {
  if (typeof Swal === "undefined") {
    alert("SweetAlert2 is not loaded. Please reload the page.");
    throw new Error("Swal not available");
  }
}

/* =====================================================
   UI HELPERS
===================================================== */
function showLoading(text) {
  Swal.fire({
    title: text,
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });
}

function showSuccess(msg) {
  Swal.fire({ icon: "success", title: msg, timer: 1200 });
}

function showError(msg) {
  Swal.fire({ icon: "error", title: "Error", text: msg });
}

function escapeHtml(text = "") {
  const el = document.createElement("div");
  el.textContent = text;
  return el.innerHTML;
}

// Expose stable globals for inline proxies in order.php
window.ordersEdit = editOrder;
window.ordersView = viewOrder;
window.ordersComplete = (id) => confirmStatusChange(id, "completed");
