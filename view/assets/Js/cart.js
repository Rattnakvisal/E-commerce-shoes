function changeQty(id, delta) {
  const input = document.querySelector(`input[data-id="${id}"]`);
  let qty = parseInt(input.value) + delta;
  qty = Math.max(1, Math.min(qty, input.max));
  input.value = qty;
  updateCart(id, qty);
}

function updateCart(id, qty) {
  fetch("cart.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "update",
      product_id: id,
      quantity: qty,
    }),
  })
    .then((r) => r.json())
    .then((d) => {
      document.getElementById("summarySubtotal").textContent =
        d.subtotal.toFixed(2);
      document.getElementById("summaryTax").textContent = d.tax.toFixed(2);
      document.getElementById("summaryTotal").textContent = d.total.toFixed(2);
    });
}

function removeItem(id) {
  if (!confirm("Remove this item?")) return;
  fetch("cart.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "remove",
      product_id: id,
    }),
  }).then(() => location.reload());
}
