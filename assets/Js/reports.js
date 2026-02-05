/* =========================================================
   CONFIG
========================================================= */
const ORDERS_API_URL = "recent_orders_api.php";
const DEFAULT_DAYS = 7;
const DEFAULT_LIMIT = 7;

/* =========================================================
   STATE
========================================================= */
let ordersChartInstance = null;
let ordersAbortController = null;

let topProductsChart = null;

/* =========================================================
   SMALL HELPERS
========================================================= */
function addOnceStyleTag(id, cssText) {
  if (document.getElementById(id)) return;
  const style = document.createElement("style");
  style.id = id;
  style.textContent = cssText;
  document.head.appendChild(style);
}

function showNotification(message, type = "info") {
  const types = {
    success: [
      "bg-green-50",
      "text-green-800",
      "border-green-200",
      "fa-check-circle",
    ],
    error: [
      "bg-red-50",
      "text-red-800",
      "border-red-200",
      "fa-exclamation-circle",
    ],
    warning: [
      "bg-yellow-50",
      "text-yellow-800",
      "border-yellow-200",
      "fa-exclamation-triangle",
    ],
    info: ["bg-blue-50", "text-blue-800", "border-blue-200", "fa-info-circle"],
  };

  const [bg, text, border, icon] = types[type] || types.info;

  const el = document.createElement("div");
  el.className = `
    fixed top-4 right-4 z-50
    p-4 rounded-xl shadow-lg border
    backdrop-blur transform translate-x-full
    transition-transform duration-300
    ${bg} ${text} ${border}
  `;

  el.innerHTML = `
    <div class="flex items-center gap-3">
      <i class="fas ${icon}"></i>
      <span class="font-medium">${message}</span>
    </div>
  `;

  document.body.appendChild(el);
  requestAnimationFrame(() => el.classList.remove("translate-x-full"));

  setTimeout(() => {
    el.classList.add("translate-x-full");
    setTimeout(() => el.remove(), 320);
  }, 2800);
}

function setLoading(isLoading) {
  document.querySelectorAll(".loading-shimmer").forEach((el) => {
    el.classList.toggle("is-loading", isLoading);
  });
}

/* =========================================================
   ORDERS CHART (LINE)
========================================================= */
async function fetchOrdersSeries(days = DEFAULT_DAYS, limit = DEFAULT_LIMIT) {
  const fallback = { labels: [], data: [] };

  try {
    if (ordersAbortController) ordersAbortController.abort();
    ordersAbortController = new AbortController();

    const resp = await fetch(`${ORDERS_API_URL}?days=${days}&limit=${limit}`, {
      credentials: "same-origin",
      signal: ordersAbortController.signal,
    });

    if (!resp.ok) return fallback;

    const json = await resp.json();
    if (!json?.success || !Array.isArray(json.daily) || !json.daily.length) {
      return fallback;
    }

    const labels = json.daily.map((d) => {
      const raw = d?.sale_date ?? "";
      const dt = new Date(`${raw}T00:00:00`);
      return Number.isNaN(dt.getTime())
        ? raw
        : dt.toLocaleDateString(undefined, { month: "short", day: "numeric" });
    });

    const data = json.daily.map((d) => Number(d?.order_count ?? 0));
    return { labels, data };
  } catch (err) {
    if (err.name !== "AbortError")
      console.warn("Orders data fetch failed:", err);
    return fallback;
  }
}

async function initOrdersChart() {
  const canvas = document.getElementById("ordersChart");
  if (!canvas || typeof Chart === "undefined") return;

  const ctx = canvas.getContext("2d");
  const { labels, data } = await fetchOrdersSeries();

  if (ordersChartInstance) {
    ordersChartInstance.destroy();
    ordersChartInstance = null;
  }

  ordersChartInstance = new Chart(ctx, {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          label: "Orders",
          data,
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          borderColor: "#4f46e5",
          backgroundColor: "rgba(79,70,229,.10)",
          pointRadius: 4,
          pointBackgroundColor: "#4f46e5",
          pointBorderColor: "#fff",
          pointBorderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: "#6b7280" },
          grid: { color: "rgba(0,0,0,.05)" },
        },
        x: {
          ticks: { color: "#6b7280" },
          grid: { display: false },
        },
      },
    },
  });
}

/* =========================================================
   TOP PRODUCTS CHART (NEW PREMIUM STYLE)
========================================================= */
function buildTopProductsChart(range = "all") {
  const canvas = document.getElementById("topProductsChart");
  if (!canvas || typeof Chart === "undefined") return;

  const source = window.TOP_PRODUCTS?.[range] || window.TOP_PRODUCTS?.all;
  let finalSource = source;

  // ---------- Fallback: build from DOM ----------
  if (
    !finalSource ||
    !Array.isArray(finalSource.labels) ||
    !finalSource.labels.length
  ) {
    try {
      const card = canvas.closest("section") || document;
      const productNodes = card.querySelectorAll(".space-y-5 > div");
      const labels = [];
      const revenue = [];

      productNodes?.forEach((node) => {
        const nameEl =
          node.querySelector("p.font-semibold") || node.querySelector("p");
        const priceEl =
          node.querySelector("p.font-extrabold") ||
          node.querySelector("p.text-xs + p") ||
          node.querySelector("div.text-right p");

        const name = nameEl ? nameEl.textContent.trim() : null;
        const rev = priceEl?.textContent
          ? parseFloat(priceEl.textContent.replace(/[^0-9.\-]/g, "")) || 0
          : 0;

        if (name) {
          labels.push(name);
          revenue.push(rev);
        }
      });

      if (labels.length) {
        finalSource = { labels, revenue };
      }
    } catch (err) {
      console.warn("Top Products DOM fallback failed:", err);
    }
  }

  if (
    !finalSource ||
    !Array.isArray(finalSource.labels) ||
    !finalSource.labels.length
  )
    return;

  const labels = finalSource.labels.slice(0, 10);
  const revenue = (finalSource.revenue || []).slice(0, 10);

  // ---------- Destroy old ----------
  if (topProductsChart) {
    topProductsChart.destroy();
    topProductsChart = null;
  }

  // ---------- NEW: plugin for value label on bars ----------
  const valueLabelPlugin = {
    id: "valueLabelPlugin",
    afterDatasetsDraw(chart) {
      const { ctx } = chart;
      const ds = chart.data.datasets[0];
      const meta = chart.getDatasetMeta(0);
      if (!meta?.data?.length) return;

      ctx.save();
      ctx.font =
        "700 12px Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial";
      ctx.fillStyle = "rgba(15, 23, 42, 0.92)";

      meta.data.forEach((bar, i) => {
        const val = Number(ds.data[i] || 0);
        const text = `$${val.toLocaleString(undefined, {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        })}`;

        // place label near end of bar (inside if possible, otherwise outside)
        const x = bar.x;
        const y = bar.y;

        const pad = 8;
        const textW = ctx.measureText(text).width;

        // If bar is long enough, draw inside bar with white text
        const inside = x > chart.chartArea.left + textW + 40;
        if (inside) {
          ctx.fillStyle = "rgba(255,255,255,0.92)";
          ctx.fillText(text, x - textW - pad, y + 4);
        } else {
          ctx.fillStyle = "rgba(15, 23, 42, 0.85)";
          ctx.fillText(text, x + pad, y + 4);
        }

        // reset for next
        ctx.fillStyle = "rgba(15, 23, 42, 0.92)";
      });

      ctx.restore();
    },
  };

  // ---------- NEW: gradient helper ----------
  const ctx = canvas.getContext("2d");
  const makeGrad = (a, b) => {
    const g = ctx.createLinearGradient(0, 0, canvas.width || 600, 0);
    g.addColorStop(0, a);
    g.addColorStop(1, b);
    return g;
  };

  const barFills = labels.map((_, i) => {
    if (i === 0)
      return makeGrad("rgba(245, 158, 11, 0.95)", "rgba(249, 115, 22, 0.95)"); // gold-orange
    if (i === 1)
      return makeGrad("rgba(148, 163, 184, 0.95)", "rgba(100, 116, 139, 0.95)"); // slate
    if (i === 2)
      return makeGrad("rgba(180, 83, 9, 0.95)", "rgba(234, 88, 12, 0.95)"); // bronze
    return makeGrad("rgba(79, 70, 229, 0.85)", "rgba(99, 102, 241, 0.85)"); // indigo
  });

  // ---------- Build chart (horizontal bar) ----------
  topProductsChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels,
      datasets: [
        {
          label: "Revenue",
          data: revenue,
          backgroundColor: barFills,
          borderRadius: 14,
          borderSkipped: false,
          barThickness: 20,
          maxBarThickness: 22,
        },
      ],
    },
    plugins: [valueLabelPlugin],
    options: {
      indexAxis: "y",
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { left: 6, right: 14, top: 6, bottom: 6 } },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: "rgba(15, 23, 42, 0.96)",
          titleColor: "#fff",
          bodyColor: "rgba(226,232,240,0.95)",
          padding: 12,
          displayColors: false,
          callbacks: {
            title: (items) => {
              const i = items?.[0]?.dataIndex ?? 0;
              return `#${i + 1} ${labels[i] || ""}`;
            },
            label: (t) =>
              `Revenue: $${Number(t.raw || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })}`,
          },
        },
      },
      scales: {
        y: {
          grid: { display: false },
          ticks: {
            color: "rgba(100,116,139,0.95)",
            font: { weight: "700" },
            callback: (v) => {
              const name = labels[v] ?? "";
              // shorten long names (clean look)
              return name.length > 22 ? name.slice(0, 22) + "…" : name;
            },
          },
        },
        x: {
          beginAtZero: true,
          grid: { color: "rgba(2,6,23,0.06)" },
          ticks: {
            color: "rgba(100,116,139,0.95)",
            font: { weight: "700" },
            callback: (v) => `$${Number(v).toLocaleString()}`,
          },
        },
      },
      animation: { duration: 900, easing: "easeOutQuart" },
    },
  });
}

function initTopProducts() {
  const select = document.getElementById("topProductsRange");
  if (!select) {
    buildTopProductsChart("all");
    return;
  }

  buildTopProductsChart(select.value || "all");
  select.addEventListener("change", () =>
    buildTopProductsChart(select.value || "all"),
  );
}

/* =========================================================
   INTERACTIONS
========================================================= */
function bindRefreshButtons() {
  ["floatingRefresh", "refresh-btn"]
    .map((id) => document.getElementById(id))
    .filter(Boolean)
    .forEach((btn) => {
      btn.addEventListener("click", async () => {
        btn.classList.add("rotate-180");
        setTimeout(() => btn.classList.remove("rotate-180"), 500);

        setLoading(true);

        await initOrdersChart();
        initTopProducts();

        setTimeout(() => {
          setLoading(false);
          showNotification("Data refreshed successfully!", "success");
        }, 450);
      });
    });
}

function bindExportButtons() {
  document.querySelectorAll("[data-export]").forEach((el) => {
    el.addEventListener("click", () => {
      const type = el.dataset.export;
      const format = el.dataset.format || "file";
      showNotification(
        `Preparing ${type} ${format.toUpperCase()} download...`,
        "info",
      );
    });
  });
}

/* =========================================================
   INIT STYLES
========================================================= */
function injectStyles() {
  addOnceStyleTag(
    "dashboard-ui-styles",
    `
      .rotate-180 { transform: rotate(180deg); transition: transform .5s ease; }
      /* Removed stat-card and report-progress styles */

      .loading-shimmer.is-loading { position: relative; overflow: hidden; }
      .loading-shimmer.is-loading::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.6), transparent);
        animation: shimmer 1.2s infinite;
        transform: translateX(-100%);
      }

      @keyframes shimmer { 100% { transform: translateX(100%); } }

      @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: translateY(0); }
      }
    `,
  );
}

/* =========================================================
   BOOT (ONLY ONE)
========================================================= */
document.addEventListener("DOMContentLoaded", async () => {
  injectStyles();

  setLoading(true);

  await initOrdersChart();
  initTopProducts();

  bindRefreshButtons();
  bindRowHover();
  bindExportButtons();

  // Time and stat-related initializers removed

  setLoading(false);
});
