/* =========================================================
   CONFIG
========================================================= */
const ORDERS_API_URL = "recent_orders_api.php";
const DEFAULT_DAYS = 7;
const DEFAULT_LIMIT = 7;

/* =========================================================
   CHARTS
========================================================= */
let ordersChartInstance = null;
let ordersAbortController = null;

async function fetchOrdersSeries(days = DEFAULT_DAYS, limit = DEFAULT_LIMIT) {
  const fallback = { labels: [], data: [] };

  try {
    if (ordersAbortController) {
      ordersAbortController.abort();
    }

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
        : dt.toLocaleDateString(undefined, {
            month: "short",
            day: "numeric",
          });
    });

    const data = json.daily.map((d) => Number(d?.order_count ?? 0));

    return { labels, data };
  } catch (err) {
    if (err.name !== "AbortError") {
      console.warn("Orders data fetch failed:", err);
    }
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

async function initCharts() {
  await initOrdersChart();
}

/* =========================================================
   UI HELPERS
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
  }, 3000);
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

        await initCharts();

        document.querySelectorAll(".loading-shimmer").forEach((el) => {
          el.classList.add("is-loading");
        });

        setTimeout(() => {
          document.querySelectorAll(".loading-shimmer").forEach((el) => {
            el.classList.remove("is-loading");
          });
          showNotification("Data refreshed successfully!", "success");
        }, 900);
      });
    });
}

function bindProgressAnimation() {
  const cards = document.querySelectorAll(".stat-card");
  if (!cards.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        entry.target.querySelectorAll(".report-progress").forEach((bar) => {
          const width = bar.dataset.width || bar.style.width || "0%";
          bar.style.width = "0%";
          requestAnimationFrame(() => (bar.style.width = width));
        });

        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.5 },
  );

  cards.forEach((card) => observer.observe(card));
}

function bindRowHover() {
  document.querySelectorAll(".table-row").forEach((row) => {
    row.classList.add("transition", "duration-200", "hover:bg-slate-50/80");
  });
}

function initTimeDisplay() {
  const els = document.querySelectorAll(".time-display");
  if (!els.length) return;

  const update = () => {
    const now = new Date();
    const time = now.toLocaleTimeString(undefined, {
      hour: "numeric",
      minute: "2-digit",
    });
    els.forEach((el) => (el.textContent = time));
  };

  update();
  setInterval(update, 60000);
}

function animateStatCards() {
  document.querySelectorAll(".stat-card").forEach((card, i) => {
    card.style.animationDelay = `${i * 0.08}s`;
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
   BOOT
========================================================= */
document.addEventListener("DOMContentLoaded", async () => {
  await initCharts();

  bindRefreshButtons();
  bindProgressAnimation();
  bindRowHover();
  initTimeDisplay();
  animateStatCards();
  bindExportButtons();

  addOnceStyleTag(
    "dashboard-ui-styles",
    `
      .rotate-180 { transform: rotate(180deg); transition: transform .5s ease; }

      .stat-card { animation: fadeInUp .6s ease-out both; }

      .report-progress {
        transition: width .9s cubic-bezier(.4,0,.2,1);
        will-change: width;
      }

      .loading-shimmer.is-loading {
        position: relative;
        overflow: hidden;
      }
      .loading-shimmer.is-loading::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.6), transparent);
        animation: shimmer 1.2s infinite;
      }

      @keyframes shimmer {
        100% { transform: translateX(100%); }
      }

      @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: translateY(0); }
      }
    `,
  );
});
