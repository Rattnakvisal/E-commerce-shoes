/* =====================================================
  CONFIG
===================================================== */
const API_BASE_URL = "get_order.php";

/* =====================================================
   EVENT DELEGATION
===================================================== */
document.addEventListener("click", (e) => {
  const viewBtn = e.target.closest(".btn-view");
  const completeBtn = e.target.closest(".btn-complete");
  const cancelBtn = e.target.closest(".btn-cancel");

  if (viewBtn) viewOrder(viewBtn.dataset.id);
  if (completeBtn)
    confirmStatusChange(
      viewBtn?.dataset.id ?? completeBtn.dataset.id,
      "complete"
    );
  if (cancelBtn) confirmStatusChange(cancelBtn.dataset.id, "cancel");
});

/* =====================================================
   CONFIRM STATUS CHANGE
===================================================== */
function confirmStatusChange(orderId, action) {
  if (!orderId) return;

  Swal.fire({
    title: "Are you sure?",
    html: `Change order status to <strong>${action.toUpperCase()}</strong>?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, update",
    confirmButtonColor: "#4f46e5",
  }).then(({ isConfirmed }) => {
    if (isConfirmed) updateOrderStatus(orderId, action);
  });
}

/* =====================================================
   UPDATE ORDER STATUS
===================================================== */
async function updateOrderStatus(orderId, action) {
  try {
    showLoading("Updating order...");

    const res = await fetch(`${API_BASE_URL}?action=${action}`, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ order_id: orderId }),
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
      throw new Error(data.error || data.message || "Update failed");
    }

    showSuccess(data.message || "Order updated");
    setTimeout(() => location.reload(), 600);
  } catch (err) {
    showError(err.message || "Server error");
  }
}

/* =====================================================
   VIEW ORDER DETAILS
===================================================== */
async function viewOrder(orderId) {
  if (!orderId) return;

  try {
    showLoading("Loading order details...");

    const res = await fetch(`${API_BASE_URL}?action=view&order_id=${orderId}`, {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    });

    const data = await res.json();

    if (!res.ok || !data.success) {
      throw new Error(data.error || "Failed to load order");
    }

    renderOrderModal(data);
  } catch (err) {
    showError(err.message || "Server error");
  }
}

/* =====================================================
   RENDER ORDER MODAL
===================================================== */
function renderOrderModal({ order, items = [] }) {
  let subtotal = 0;

  const rows = items
    .map((it) => {
      const price = Number(it.price ?? it.unit_price ?? 0);
      const line = price * Number(it.quantity ?? 0);
      subtotal += line;

      return `
      <tr>
        <td>${escapeHtml(it.product_name ?? "Item")}</td>
        <td>${it.quantity}</td>
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
        <p><b>Customer:</b> ${escapeHtml(order.customer_name ?? "")}</p>
        <p><b>Payment:</b> ${escapeHtml(order.payment_status ?? "")}</p>
        <p><b>Status:</b> ${escapeHtml(order.order_status ?? "")}</p>
        <p><b>Date:</b> ${new Date(order.created_at).toLocaleString()}</p>
        <hr>
        <table class="w-full border text-sm">
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
        <div class="text-right mt-2">
          <b>Total:</b> $${Number(order.total ?? 0).toFixed(2)}
        </div>
      </div>
    `,
    confirmButtonText: "Close",
    confirmButtonColor: "#4f46e5",
  });
}

/* =====================================================
   UI HELPERS
===================================================== */
function showLoading(message = "Loading...") {
  Swal.fire({
    title: message,
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading(),
  });
}

function showSuccess(message) {
  Swal.fire({
    icon: "success",
    title: "Success",
    text: message,
    confirmButtonColor: "#059669",
  });
}

function showError(message) {
  Swal.fire({
    icon: "error",
    title: "Error",
    text: message,
    confirmButtonColor: "#dc2626",
  });
}

function escapeHtml(text = "") {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}
