document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("details").forEach((details) => {
    details.addEventListener("toggle", async () => {
      if (!details.open) return;

      const container = details.querySelector(".order-items-container");
      if (!container || container.dataset.loaded) return;

      const orderId = container.dataset.orderId;
      if (!orderId) return;

      container.textContent = "Loading items...";

      try {
        const res = await fetch(
          `/E-commerce-shoes/view/order_items.php?order_id=${encodeURIComponent(orderId)}`,
          { credentials: "same-origin" },
        );

        if (!res.ok) throw new Error("HTTP " + res.status);

        const data = await res.json();

        if (!data.success) {
          container.textContent = data.error || "Failed to load items.";
          return;
        }

        if (!Array.isArray(data.items) || data.items.length === 0) {
          container.textContent = "No items found.";
          container.dataset.loaded = "1";
          return;
        }

        container.innerHTML = "";
        data.items.forEach((it) => {
          const row = document.createElement("div");
          row.className =
            "flex justify-between py-2 border-b text-sm items-center gap-3";

          const imgHtml = it.image
            ? `<img src="${it.image}" alt="${it.name || ""}" class="w-12 h-12 object-cover rounded-md mr-3">`
            : "";

          const productUrl = `/E-commerce-shoes/view/content/product.php?product_id=${encodeURIComponent(it.product_id)}`;

          const price = Number(it.price || 0);
          const qty = Number(it.quantity || 0);
          const lineTotal = (qty * price).toFixed(2);

          row.innerHTML = `
            <div class="flex items-center gap-3">
              ${imgHtml}
              <div>
                <a href="${productUrl}" class="font-medium text-gray-900">
                  ${it.name || "Product"}
                </a>
                <div class="text-xs text-gray-500">
                  Qty ${qty} × $${price.toFixed(2)}
                </div>
              </div>
            </div>
            <div class="font-semibold">$${lineTotal}</div>
          `;

          container.appendChild(row);
        });

        container.dataset.loaded = "1";
      } catch (err) {
        container.textContent = "Failed to load items.";
      }
    });
  });
});
