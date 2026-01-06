document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("details").forEach((details) => {
    details.addEventListener("toggle", async () => {
      if (!details.open) return;
      const container = details.querySelector(".order-items-container");
      if (!container || container.dataset.loaded) return;

      const orderId = container.dataset.orderId;
      container.textContent = "Loading items...";

      try {
        const res = await fetch(
          `/E-commerce-shoes/view/order_items.php?order_id=${orderId}`
        );
        const data = await res.json();

        if (!data.items || data.items.length === 0) {
          container.textContent = "No items found.";
        } else {
          container.innerHTML = "";
          data.items.forEach((it) => {
            const row = document.createElement("div");
            row.className = "flex justify-between py-2 border-b text-sm";
            row.innerHTML = `
                            <div>
                                <p class="font-medium">${it.name}</p>
                                <p class="text-xs text-gray-500">
                                    Qty ${it.quantity} × $${Number(
              it.price
            ).toFixed(2)}
                                </p>
                            </div>
                            <p class="font-semibold">
                                $${(it.quantity * it.price).toFixed(2)}
                            </p>
                        `;
            container.appendChild(row);
          });
        }
        container.dataset.loaded = "1";
      } catch {
        container.textContent = "Failed to load items.";
      }
    });
  });
});
