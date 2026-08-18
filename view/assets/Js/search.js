/* =========================================================
   GLOBAL SEARCH (Desktop + Mobile) - Premium
========================================================= */
(() => {
  "use strict";

  const one = (sel, root = document) => root.querySelector(sel);

  const debounce = (fn, ms = 250) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  };

  // Elements
  const desktopInput = one("#desktopSearchInput");
  const desktopMeta = one("#desktopSearchMeta"); // optional (from premium UI)

  const mobileTrigger = one("#mobileSearchTrigger");
  const mobileBar = one("#mobileSearchBar");
  const mobileInput =
    one("#mobileSearchInput") || one("#mobileSearchBar input");
  const mobileClose = one("#closeMobileSearch");
  const mobileMeta = one("#mobileSearchMeta"); // optional (from premium UI)
  const mobileClear = one("#mobileSearchClear"); // optional

  // Desktop results
  const desktopResultsRoot = one("#globalSearchResults");
  const desktopResultsWrap = one("#searchResultsContent");
  const desktopResultsClose = one("#closeSearchResults");
  const desktopBackdrop = one("#globalSearchBackdrop"); // optional
  const desktopClear = one("#desktopSearchClear"); // optional

  // Mobile results
  const mobileResultsRoot = one("#mobileSearchResults");
  const mobileResultsWrap = one("#mobileSearchResultsContent");
  const mobileOverlay = one("#mobileSearchOverlay"); // optional

  const API = "/MyBrand_Ecommerce/includes/contract/search_api.php?q=";

  const safeArray = (v) => (Array.isArray(v) ? v : []);
  const openEl = (el) => el && el.classList.remove("hidden");
  const closeEl = (el) => el && el.classList.add("hidden");

  const MIN_CHARS = 2;
  const LIMIT_PRODUCTS = 6;
  const LIMIT_CATEGORIES = 5;

  // Abort previous request per mode to avoid out-of-order results
  const controllers = { desktop: null, mobile: null };

  // Simple cache: query -> payload
  const cache = new Map();
  const MAX_CACHE = 50;

  const setMeta = (mode, txt) => {
    const el = mode === "desktop" ? desktopMeta : mobileMeta;
    if (el) el.textContent = txt;
  };

  const escHtml = (s) =>
    String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  const highlight = (text, q) => {
    const t = String(text ?? "");
    const query = String(q ?? "").trim();
    if (!query) return escHtml(t);

    // Escape regex
    const reSafe = query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const re = new RegExp(`(${reSafe})`, "ig");

    return escHtml(t).replace(
      re,
      `<mark class="bg-yellow-200/60 rounded px-1">$1</mark>`,
    );
  };

  function skeletonHTML() {
    // A few nice skeleton rows
    return `
      <div class="px-4 py-3">
        ${Array.from({ length: 5 })
          .map(
            () => `
          <div class="flex items-center gap-3 py-3">
            <div class="h-11 w-11 rounded-xl bg-gray-100 animate-pulse"></div>
            <div class="flex-1">
              <div class="h-3 w-2/3 bg-gray-100 rounded animate-pulse"></div>
              <div class="h-3 w-1/3 bg-gray-100 rounded mt-2 animate-pulse"></div>
            </div>
          </div>
        `,
          )
          .join("")}
      </div>
    `;
  }

  function emptyHTML(q) {
    return `
      <div class="px-4 py-5 text-gray-500">
        <p class="font-semibold text-gray-900">No results</p>
        <p class="text-xs text-gray-500 mt-1">Try another keyword like “Air Max”, “Jordan”.</p>
        <p class="text-xs text-gray-400 mt-2">Query: <span class="font-mono">${escHtml(q)}</span></p>
      </div>
    `;
  }

  function viewAllHTML(q) {
    const url = `/MyBrand_Ecommerce/view/content/products.php?search=${encodeURIComponent(q)}`;
    return `
      <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
        <a href="${url}" class="inline-flex items-center gap-2 text-sm font-bold text-gray-900 hover:underline">
          View all results
          <span class="text-gray-400">→</span>
        </a>
      </div>
    `;
  }

  function resultRowCategory(c, q) {
    const id = encodeURIComponent(c.category_id ?? "");
    const name = c.category_name ?? "Category";
    return `
      <a href="/MyBrand_Ecommerce/view/content/products.php?category_id=${id}"
         class="block px-4 py-3 hover:bg-gray-50 active:bg-gray-100 transition">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-600">
            <i class="fas fa-tags text-[13px]"></i>
          </div>
          <div class="min-w-0">
            <div class="font-extrabold text-gray-900 truncate">${highlight(name, q)}</div>
            <div class="text-xs text-gray-500">Category</div>
          </div>
        </div>
      </a>
    `;
  }

  function resultRowProduct(p, q) {
    const id = encodeURIComponent(p.product_id ?? "");
    const name = p.name ?? "Product";
    const imgUrl = p.image_url ? escHtml(p.image_url) : "";
    const priceText =
      p.price !== undefined && p.price !== null && p.price !== ""
        ? escHtml(p.price)
        : "";

    const img = imgUrl
      ? `<img src="${imgUrl}" class="h-12 w-12 rounded-2xl object-cover border border-gray-200" alt="">`
      : `<div class="h-12 w-12 rounded-2xl bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-500">
           <i class="fas fa-shoe-prints text-[13px]"></i>
         </div>`;

    return `
      <a href="/MyBrand_Ecommerce/view/content/products.php?product_id=${id}"
         class="block px-4 py-3 hover:bg-gray-50 active:bg-gray-100 transition">
        <div class="flex items-center gap-3">
          ${img}
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-3">
              <div class="font-extrabold text-gray-900 truncate">${highlight(name, q)}</div>
              ${priceText ? `<div class="text-sm font-extrabold text-black whitespace-nowrap">${priceText}</div>` : ""}
            </div>
            <div class="text-xs text-gray-500 truncate mt-0.5">Product</div>
          </div>
        </div>
      </a>
    `;
  }

  function buildHTML(payload, q) {
    const categories = safeArray(payload?.categories).slice(
      0,
      LIMIT_CATEGORIES,
    );
    const products = safeArray(payload?.products).slice(0, LIMIT_PRODUCTS);

    if (!categories.length && !products.length) return emptyHTML(q);

    let html = "";

    if (categories.length) {
      html += `
        <div class="px-4 pt-4 pb-2 text-[11px] font-bold tracking-wide text-gray-500 uppercase">
          Categories
        </div>
        <div class="divide-y divide-gray-100">
          ${categories.map((c) => resultRowCategory(c, q)).join("")}
        </div>
      `;
    }

    if (products.length) {
      html += `
        <div class="px-4 pt-4 pb-2 text-[11px] font-bold tracking-wide text-gray-500 uppercase">
          Products
        </div>
        <div class="divide-y divide-gray-100">
          ${products.map((p) => resultRowProduct(p, q)).join("")}
        </div>
      `;
    }

    html += viewAllHTML(q);
    return html;
  }

  function renderInto(elWrap, payload, q) {
    if (!elWrap) return;
    elWrap.innerHTML = buildHTML(payload, q);
  }

  function closeAllResults() {
    closeEl(desktopResultsRoot);
    closeEl(mobileResultsRoot);
    closeEl(desktopBackdrop);
    closeEl(mobileOverlay);
    setMeta("desktop", "Type to find products fast");
    setMeta("mobile", "Type to find products fast");
  }

  function openForMode(mode) {
    if (mode === "desktop") {
      closeEl(mobileResultsRoot);
      openEl(desktopResultsRoot);
      openEl(desktopBackdrop);
    } else {
      closeEl(desktopResultsRoot);
      openEl(mobileResultsRoot);
      openEl(mobileOverlay);
    }
  }

  async function fetchSearch(q, mode) {
    // abort previous
    controllers[mode]?.abort?.();
    const ctrl = new AbortController();
    controllers[mode] = ctrl;

    // cached?
    if (cache.has(q)) return cache.get(q);

    const res = await fetch(API + encodeURIComponent(q), {
      headers: { Accept: "application/json" },
      signal: ctrl.signal,
    });

    const json = await res.json();
    const payload = json?.success ? json : { categories: [], products: [] };

    // store cache
    cache.set(q, payload);
    if (cache.size > MAX_CACHE) {
      const firstKey = cache.keys().next().value;
      cache.delete(firstKey);
    }

    return payload;
  }

  const doSearch = debounce(async (term, mode) => {
    const q = (term ?? "").trim();

    if (q.length < MIN_CHARS) {
      closeAllResults();
      return;
    }

    // show skeleton immediately
    if (mode === "mobile")
      mobileResultsWrap && (mobileResultsWrap.innerHTML = skeletonHTML());
    else desktopResultsWrap && (desktopResultsWrap.innerHTML = skeletonHTML());

    setMeta(mode, "Searching…");
    openForMode(mode);

    try {
      const payload = await fetchSearch(q, mode);

      // count for meta
      const cCount = safeArray(payload?.categories).length;
      const pCount = safeArray(payload?.products).length;
      const total = cCount + pCount;

      setMeta(
        mode,
        total ? `${total} result${total > 1 ? "s" : ""}` : "No results",
      );

      if (mode === "mobile") renderInto(mobileResultsWrap, payload, q);
      else renderInto(desktopResultsWrap, payload, q);

      openForMode(mode);
    } catch (err) {
      // Ignore abort errors (typing fast)
      if (err?.name === "AbortError") return;

      setMeta(mode, "No results");
      const payload = { categories: [], products: [] };
      if (mode === "mobile") renderInto(mobileResultsWrap, payload, q);
      else renderInto(desktopResultsWrap, payload, q);
      openForMode(mode);
    }
  }, 250);

  /* -------------------------
     Mobile toggle
  -------------------------- */
  mobileTrigger?.addEventListener("click", () => {
    mobileBar?.classList.toggle("hidden");

    if (mobileBar && !mobileBar.classList.contains("hidden")) {
      setTimeout(() => mobileInput?.focus(), 0);
    } else {
      closeEl(mobileResultsRoot);
      closeEl(mobileOverlay);
    }
  });

  mobileClose?.addEventListener("click", () => {
    closeEl(mobileBar);
    closeAllResults();
  });

  mobileClear?.addEventListener("click", () => {
    if (mobileInput) mobileInput.value = "";
    closeAllResults();
    mobileInput?.focus();
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

  // Focus opens dropdown (nice UX)
  desktopInput?.addEventListener("focus", () => {
    const q = (desktopInput.value ?? "").trim();
    if (q.length >= MIN_CHARS) doSearch(q, "desktop");
  });

  mobileInput?.addEventListener("focus", () => {
    const q = (mobileInput.value ?? "").trim();
    if (q.length >= MIN_CHARS) doSearch(q, "mobile");
  });

  /* -------------------------
     Desktop close button + backdrop
  -------------------------- */
  desktopResultsClose?.addEventListener("click", closeAllResults);
  desktopBackdrop?.addEventListener("click", closeAllResults);

  desktopClear?.addEventListener("click", () => {
    if (desktopInput) desktopInput.value = "";
    closeAllResults();
    desktopInput?.focus();
  });

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
     ESC closes / Ctrl+K focuses
  -------------------------- */
  document.addEventListener("keydown", (ev) => {
    if (ev.key === "Escape") closeAllResults();

    // Ctrl+K / Cmd+K quick search
    if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === "k") {
      ev.preventDefault();
      desktopInput?.focus();
    }
  });
})();
