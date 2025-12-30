/* =====================================================
   ORDERS MANAGEMENT SCRIPT
===================================================== */

document.addEventListener('click', (e) => {
    const viewBtn = e.target.closest('.btn-view');
    const completeBtn = e.target.closest('.btn-complete');
    const cancelBtn = e.target.closest('.btn-cancel');

    if (viewBtn) {
        viewOrder(viewBtn.dataset.id);
        return;
    }

    if (completeBtn) {
        confirmStatusChange(completeBtn.dataset.id, 'completed', completeBtn);
        return;
    }

    if (cancelBtn) {
        confirmStatusChange(cancelBtn.dataset.id, 'cancelled', cancelBtn);
        return;
    }
});

/* =====================================================
   CONFIRM STATUS CHANGE
===================================================== */
function confirmStatusChange(orderId, status, button) {
    if (!orderId) return;

    Swal.fire({
        title: 'Are you sure?',
        text: `Mark order as ${status}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, update'
    }).then(({ isConfirmed }) => {
        if (!isConfirmed) return;
        updateOrderStatus(orderId, status, button);
    });
}

/* =====================================================
   UPDATE ORDER STATUS (API)
===================================================== */
async function updateOrderStatus(orderId, status, button) {

    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Saving...';

    try {
        const action = status === 'completed' ? 'complete' : 'cancel';
        const res = await fetch(`/E-commerce-shoes/admin/process/orders/get_order.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ order_id: orderId })
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            throw new Error(data.error || data.message || 'Update failed');
        }

        updateRowStatus(orderId, status);

        Swal.fire('Updated!', 'Order status updated successfully.', 'success');

        // Reload the page so related UI (buttons, counts, inventory) refreshes
        if (status === 'completed' || status === 'cancelled') {
            setTimeout(() => location.reload(), 700);
        }

    } catch (err) {
        Swal.fire('Error', err.message || 'Server error', 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
}

/* =====================================================
   VIEW ORDER DETAILS
===================================================== */
async function viewOrder(orderId) {
    if (!orderId) return;

    try {
        const res = await fetch(
            `/E-commerce-shoes/admin/process/orders/get_order.php?action=view&order_id=${orderId}`,
            { credentials: 'same-origin' }
        );

        const data = await res.json();

        if (!res.ok) {
            Swal.fire('Error', data.error || 'Failed to load order', 'error');
            return;
        }

        renderOrderModal(data);

    } catch {
        Swal.fire('Error', 'Server error', 'error');
    }
}

/* =====================================================
   UPDATE STATUS TEXT IN TABLE
===================================================== */
function updateRowStatus(orderId, status) {
    const row = document.querySelector(`tr[data-row="${orderId}"]`);
    if (!row) return;

    const statusCell = row.querySelector('.status');
    if (statusCell) {
        statusCell.textContent = capitalize(status);
    }
}

/* =====================================================
   RENDER ORDER MODAL
===================================================== */
function renderOrderModal({ order, items = [], shipping }) {

    let subtotal = 0;

    const rows = items.map(it => {
        const line = (Number(it.price) * Number(it.quantity)) || 0;
        subtotal += line;

        return `
            <tr>
                <td>${escapeHtml(it.name)}</td>
                <td>${it.quantity}</td>
                <td>$${Number(it.price).toFixed(2)}</td>
                <td>$${line.toFixed(2)}</td>
            </tr>
        `;
    }).join('');

    Swal.fire({
        title: `Order #${order.order_id}`,
        width: 720,
        html: `
            <div class="text-left text-sm space-y-1">
                <p><b>Customer:</b> ${escapeHtml(order.customer)}</p>
                <p><b>Payment:</b> ${escapeHtml(order.payment_status)}</p>
                <p><b>Status:</b> ${escapeHtml(order.order_status)}</p>
                <p><b>Date:</b> ${new Date(order.created_at).toLocaleString()}</p>
                ${shipping ? `<p><b>Shipping:</b> ${escapeHtml(shipping.address || '')}</p>` : ''}
                <hr class="my-2"/>
                <table class="w-full text-sm">
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
                    <p><b>Subtotal:</b> $${subtotal.toFixed(2)}</p>
                    <p><b>Total:</b> $${Number(order.total).toFixed(2)}</p>
                </div>
            </div>
        `
    });
}

/* =====================================================
   UTILITIES
===================================================== */
function capitalize(text = '') {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function escapeHtml(text = '') {
    return text.replace(/[&"'<>]/g, c => ({
        '&': '&amp;',
        '"': '&quot;',
        "'": '&#39;',
        '<': '&lt;',
        '>': '&gt;'
    }[c]));
}
