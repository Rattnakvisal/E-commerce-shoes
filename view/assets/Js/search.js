/* =========================================================
   GLOBAL SEARCH (Desktop + Mobile)
   - Desktop => #globalSearchResults
   - Mobile  => #mobileSearchResults
========================================================= */
(() => {
  "use strict";

  const one = (sel, root = document) => root.querySelector(sel);

  const debounce = (fn, ms = 300) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  };

  // Elements
  const desktopInput = one("#desktopSearchInput");

  const mobileTrigger = one("#mobileSearchTrigger");
  const mobileBar = one("#mobileSearchBar");
  const mobileInput =
    one("#mobileSearchInput") || one("#mobileSearchBar input");
  const mobileClose = one("#closeMobileSearch");

  // Desktop results
  const desktopResultsRoot = one("#globalSearchResults");
  const desktopResultsWrap = one("#searchResultsContent");
  const desktopResultsClose = one("#closeSearchResults");

  // Mobile results
  const mobileResultsRoot = one("#mobileSearchResults");
  const mobileResultsWrap = one("#mobileSearchResultsContent");

  const API = "/E-commerce-shoes/includes/contract/search_api.php?q=";

  const safeArray = (v) => (Array.isArray(v) ? v : []);

  const openEl = (el) => el && el.classList.remove("hidden");
  const closeEl = (el) => el && el.classList.add("hidden");
  const isHidden = (el) => !el || el.classList.contains("hidden");

  function buildHTML(payload) {
    const categories = safeArray(payload?.categories);
    const products = safeArray(payload?.products);

    // No results
    if (!categories.length && !products.length) {
      return `<div class="p-3 text-gray-500">No results</div>`;
    }

    let html = "";

    if (categories.length) {
      html += `
        <div class="mb-2">
          <div class="text-xs text-gray-400 uppercase mb-1">Categories</div>
          ${categories
            .map((c) => {
              const id = encodeURIComponent(c.category_id ?? "");
              const name = c.category_name ?? "";
              return `
                <a href="/E-commerce-shoes/view/content/products.php?category_id=${id}"
                   class="block p-2 result-item rounded-md text-gray-800">
                  <strong>${name}</strong>
                </a>
              `;
            })
            .join("")}
        </div>
      `;
    }

    if (products.length) {
      html += `
        <div class="mb-2">
          <div class="text-xs text-gray-400 uppercase mb-1">Products</div>
          ${products
            .map((p) => {
              const id = encodeURIComponent(p.product_id ?? "");
              const name = p.name ?? "";
              const img = p.image_url
                ? `<img src="${p.image_url}" class="w-10 h-10 object-cover rounded" alt="">`
                : "";
              const price =
                p.price !== undefined && p.price !== null && p.price !== ""
                  ? `<div class="text-xs text-gray-500">${p.price}</div>`
                  : "";

              return `
                <a href="/E-commerce-shoes/view/content/products.php?product_id=${id}"
                   class="block p-2 result-item rounded-md text-gray-800">
                  <div class="flex items-center gap-3">
                    ${img}
                    <div>
                      <div class="font-medium">${name}</div>
                      ${price}
                    </div>
                  </div>
                </a>
              `;
            })
            .join("")}
        </div>
      `;
    }

    return html;
  }

  function renderInto(elWrap, payload) {
    if (!elWrap) return;
    elWrap.innerHTML = buildHTML(payload);
  }

  function closeAllResults() {
    closeEl(desktopResultsRoot);
    closeEl(mobileResultsRoot);
  }

  function openForMode(mode) {
    // mode: "desktop" | "mobile"
    if (mode === "desktop") {
      closeEl(mobileResultsRoot);
      openEl(desktopResultsRoot);
    } else {
      closeEl(desktopResultsRoot);
      openEl(mobileResultsRoot);
    }
  }

  const doSearch = debounce(async (term, mode) => {
    const q = (term ?? "").trim();

    // Better UX: if too short -> close results
    if (q.length < 2) {
      closeAllResults();
      return;
    }

    try {
      const res = await fetch(API + encodeURIComponent(q), {
        headers: { Accept: "application/json" },
      });

      const json = await res.json();
      const payload = json?.success ? json : { categories: [], products: [] };

      // Render to correct container
      if (mode === "mobile") renderInto(mobileResultsWrap, payload);
      else renderInto(desktopResultsWrap, payload);

      openForMode(mode);
    } catch {
      const payload = { categories: [], products: [] };
      if (mode === "mobile") renderInto(mobileResultsWrap, payload);
      else renderInto(desktopResultsWrap, payload);
      openForMode(mode);
    }
  }, 300);

  /* -------------------------
     Mobile toggle
  -------------------------- */
  mobileTrigger?.addEventListener("click", () => {
    mobileBar?.classList.toggle("hidden");

    if (mobileBar && !mobileBar.classList.contains("hidden")) {
      setTimeout(() => mobileInput?.focus(), 0);
    } else {
      closeEl(mobileResultsRoot);
    }
  });

  mobileClose?.addEventListener("click", () => {
    closeEl(mobileBar);
    closeEl(mobileResultsRoot);
  });

  /* -------------------------
     Input listeners
  -------------------------- */
  desktopInput?.addEventListener("input", (e) =>
    doSearch(e.target.value, "desktop"),
  );

  mobileInput?.addEventListener("input", (e) =>
    doSearch(e.target.value, "mobile"),
  );

  /* -------------------------
     Desktop close button
  -------------------------- */
  desktopResultsClose?.addEventListener("click", () =>
    closeEl(desktopResultsRoot),
  );

  /* -------------------------
     Outside click closes
  -------------------------- */
  document.addEventListener("click", (ev) => {
    const t = ev.target;

    const insideDesktopResults =
      desktopResultsRoot && desktopResultsRoot.contains(t);
    const insideMobileResults =
      mobileResultsRoot && mobileResultsRoot.contains(t);

    const onDesktopInput = !!t.closest("#desktopSearchInput");
    const onMobileBar = !!t.closest("#mobileSearchBar");
    const onMobileTrigger = !!t.closest("#mobileSearchTrigger");

    if (
      !insideDesktopResults &&
      !insideMobileResults &&
      !onDesktopInput &&
      !onMobileBar &&
      !onMobileTrigger
    ) {
      closeAllResults();
    }
  });

  /* -------------------------
     ESC closes
  -------------------------- */
  document.addEventListener("keydown", (ev) => {
    if (ev.key === "Escape") closeAllResults();
  });
})();
