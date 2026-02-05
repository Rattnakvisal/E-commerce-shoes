/* =====================================================
   MENU.JS — CLEAN PREMIUM TREE (UI + SEARCH + EXPAND)
   - NO <script> tags here (this is a .js file)
   - SweetAlert style kept
   - Renders <details class="menu-node"> for premium view
   - Search + highlight + expand/collapse + event delegation
===================================================== */

const API_URL = "api.php";

/* ===============================
   DOM HELPERS
=============================== */
const $ = (id) => document.getElementById(id);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

let els = {};
let _hlTimer = null;

/* ===============================
   SWEETALERT HELPERS
=============================== */
function showLoading(msg = "Loading...") {
  Swal.fire({
    title: msg,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading(),
  });
}

function showSuccess(title, text = "") {
  return Swal.fire({
    icon: "success",
    title,
    text: text || undefined,
    showConfirmButton: false,
    timer: 1200,
    timerProgressBar: true,
  });
}

function showError(msg) {
  Swal.fire({
    icon: "error",
    title: "Error",
    text: msg,
    confirmButtonColor: "#dc2626",
  });
}

function confirmEdit(
  title = "Edit item?",
  text = "Open the editor to update.",
) {
  return Swal.fire({
    icon: "question",
    title,
    html: `<p class="text-gray-600 mt-2">${text}</p>`,
    showCancelButton: true,
    confirmButtonText: "Edit",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#6b46c1",
    cancelButtonColor: "#6b7280",
  });
}

function confirmDelete(
  title = "Delete item?",
  text = "This action cannot be undone",
) {
  return Swal.fire({
    title,
    text,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Delete",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#6b7280",
  });
}

/* ===============================
   SUCCESS MAP
=============================== */
function menuSuccess(action, label = "Item") {
  const map = {
    add: {
      title: `${label} created successfully`,
      text: `The ${label.toLowerCase()} has been created successfully.`,
    },
    update: {
      title: `${label} updated successfully`,
      text: `The ${label.toLowerCase()} has been updated successfully.`,
    },
    delete: {
      title: `${label} deleted successfully`,
      text: `The ${label.toLowerCase()} has been removed successfully.`,
    },
  };
  const msg = map[action];
  if (msg) showSuccess(msg.title, msg.text);
}

/* ===============================
   MODAL HELPERS
=============================== */
const openModal = (id) => $(id)?.classList.replace("hidden", "flex");
const closeModal = (id) => $(id)?.classList.replace("flex", "hidden");

/* ===============================
   API (JSON)
=============================== */
async function api(action, payload = {}) {
  const res = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    body: JSON.stringify(payload),
  });

  const data = await res.json().catch(() => null);
  if (!data) throw new Error("Invalid API response");
  return data;
}

/* ===============================
   UTILS
=============================== */
function esc(text = "") {
  const d = document.createElement("div");
  d.textContent = String(text ?? "");
  return d.innerHTML;
}

function num(v) {
  const x = Number(v);
  return Number.isFinite(x) ? x : 0;
}

function sortByPos(a, b) {
  return num(a.position) - num(b.position);
}

function normalize(s) {
  return String(s || "")
    .toLowerCase()
    .trim();
}

function highlightHtml(html, q) {
  const query = normalize(q);
  if (!query) return html;

  const re = new RegExp(
    `(${query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})`,
    "ig",
  );
  return html.replace(
    re,
    `<mark class="rounded px-1 bg-yellow-200/70">$1</mark>`,
  );
}

/* ===============================
   LOAD DATA
=============================== */
async function loadData() {
  try {
    if (els.menuStructure) {
      els.menuStructure.innerHTML = `
        <div class="text-center py-10 text-gray-400">
          <i class="fa-solid fa-spinner fa-spin text-2xl mb-3"></i>
          <p class="text-sm">Loading menu data...</p>
        </div>`;
    }

    const res = await fetch(`${API_URL}?action=fetch_all`, {
      credentials: "same-origin",
    });
    const data = await res.json().catch(() => null);

    if (!data || !data.ok)
      throw new Error((data && data.msg) || "Failed to load menu data");

    window._menuData = data;

    // Stats
    if (els.parentCount)
      els.parentCount.textContent = data.parents?.length ?? 0;
    if (els.groupCount) els.groupCount.textContent = data.groups?.length ?? 0;
    if (els.itemCount) els.itemCount.textContent = data.items?.length ?? 0;

    populateSelects(data);
    renderMenu(data.parents || [], data.groups || [], data.items || []);

    // Keep filter after reload
    if (els.menuSearch && els.menuSearch.value.trim()) {
      applySearch(els.menuSearch.value.trim());
    } else {
      toggleNoResults(false);
    }
  } catch (e) {
    console.error(e);
    if (els.menuStructure) {
      els.menuStructure.innerHTML = `
        <div class="text-center py-10 text-red-600">
          <div class="mx-auto w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center mb-3">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <p class="font-semibold">Failed to load menu data</p>
          <p class="text-sm text-red-500 mt-1">${esc(e.message || "")}</p>
        </div>`;
    }
  }
}

/* ===============================
   SELECTS
=============================== */
function populateSelects({ parents = [], groups = [] }) {
  // Build parent options HTML first (don't rely on DOM state)
  const parentOptions =
    `<option value="">-- No parent --</option>` +
    parents
      .slice()
      .sort(sortByPos)
      .map((p) => `<option value="${esc(p.id)}">${esc(p.title)}</option>`)
      .join("");

  // Assign to selects only if they exist
  if (els.parentSelect) {
    els.parentSelect.innerHTML = parentOptions;
  }
  if (els.editGroupParentSelect) {
    els.editGroupParentSelect.innerHTML = parentOptions;
  }

  // Build group options HTML
  const groupOptions =
    `<option value="">-- Select Group --</option>` +
    groups
      .slice()
      .sort(sortByPos)
      .map((g) => `<option value="${esc(g.id)}">${esc(g.group_title)}</option>`)
      .join("");

  if (els.groupSelect) els.groupSelect.innerHTML = groupOptions;
  if (els.editItemGroupSelect) els.editItemGroupSelect.innerHTML = groupOptions;
}

/* ===============================
   RENDER PREMIUM TREE
=============================== */
function renderMenu(parents, groups, items) {
  if (!els.menuStructure) return;

  if (!parents.length) {
    els.menuStructure.innerHTML = `
      <div class="text-center py-12 text-gray-400">
        <div class="mx-auto w-14 h-14 rounded-3xl bg-gray-100 flex items-center justify-center mb-3">
          <i class="fas fa-folder-open text-2xl"></i>
        </div>
        <p class="text-sm font-semibold">No menu items yet</p>
        <p class="text-xs text-gray-400 mt-1">Create Parent → Group → Item from the form above.</p>
      </div>`;
    toggleNoResults(false);
    return;
  }

  // Maps
  const groupsByParent = new Map();
  for (const g of groups) {
    const pid = String(g.parent_id ?? "");
    if (!groupsByParent.has(pid)) groupsByParent.set(pid, []);
    groupsByParent.get(pid).push(g);
  }
  for (const arr of groupsByParent.values()) arr.sort(sortByPos);

  const itemsByGroup = new Map();
  for (const it of items) {
    const gid = String(it.group_id ?? "");
    if (!itemsByGroup.has(gid)) itemsByGroup.set(gid, []);
    itemsByGroup.get(gid).push(it);
  }
  for (const arr of itemsByGroup.values()) arr.sort(sortByPos);

  const q = els.menuSearch ? els.menuSearch.value.trim() : "";
  const parentsSorted = parents.slice().sort(sortByPos);

  els.menuStructure.innerHTML = parentsSorted
    .map((p) => {
      const pid = String(p.id);
      const pgroups = groupsByParent.get(pid) || [];

      const groupCount = pgroups.length;
      const itemCount = pgroups.reduce(
        (sum, g) => sum + (itemsByGroup.get(String(g.id)) || []).length,
        0,
      );

      const parentTitle = highlightHtml(esc(p.title), q);

      const groupsHtml = pgroups
        .map((g) => {
          const gid = String(g.id);
          const gitems = itemsByGroup.get(gid) || [];
          const groupTitle = highlightHtml(esc(g.group_title), q);
          const groupUrl = g.link_url ? highlightHtml(esc(g.link_url), q) : "";

          const itemsHtml = gitems
            .map((it) => {
              const itemTitle = highlightHtml(esc(it.item_title), q);
              const itemUrl = it.link_url
                ? highlightHtml(esc(it.link_url), q)
                : "";
              return `
                <div class="mt-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-2xl border border-gray-200 bg-gray-50/60 p-3">
                  <div class="min-w-0">
                    <div class="font-semibold text-gray-900 text-sm truncate">${itemTitle}</div>
                    <div class="text-xs text-gray-500 truncate">
                      URL: <span class="font-mono">${itemUrl || "—"}</span>
                    </div>
                  </div>

                  <div class="flex items-center gap-2 shrink-0">
                    <button type="button"
                      class="px-3 py-2 rounded-2xl border hover:bg-white transition text-xs font-semibold"
                      data-action="edit-item" data-id="${esc(it.id)}">
                      <i class="fa-solid fa-pen mr-1"></i> Edit
                    </button>
                    <button type="button"
                      class="px-3 py-2 rounded-2xl border border-red-200 text-red-600 hover:bg-red-50 transition text-xs font-semibold"
                      data-action="delete-item" data-id="${esc(it.id)}">
                      <i class="fa-solid fa-trash mr-1"></i> Delete
                    </button>
                  </div>
                </div>
              `;
            })
            .join("");

          return `
            <details class="menu-node menu-group group mt-4 rounded-3xl border border-gray-200 bg-white shadow-sm overflow-hidden">
              <summary class="cursor-pointer select-none p-4 sm:p-5 flex items-start justify-between gap-4 hover:bg-gray-50/80 transition">
                <div class="flex items-start gap-3 min-w-0">
                  <span class="mt-0.5 inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-green-50 text-green-700 border border-green-100">
                    <i class="fa-solid fa-folder-tree"></i>
                  </span>

                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <h4 class="font-extrabold text-gray-900 leading-tight truncate">${groupTitle}</h4>
                      <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-[11px] font-semibold">
                        #${esc(g.position ?? 0)}
                      </span>
                      <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 text-purple-700 px-2 py-0.5 text-[11px] font-semibold">
                        <i class="fa-solid fa-link"></i> ${gitems.length}
                      </span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 truncate">
                      ${groupUrl ? `URL: <span class="font-mono">${groupUrl}</span>` : "URL: —"}
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <button type="button"
                    class="px-3 py-2 rounded-2xl border hover:bg-white transition text-xs font-semibold"
                    data-action="edit-group" data-id="${esc(g.id)}">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                  </button>
                  <button type="button"
                    class="px-3 py-2 rounded-2xl border border-red-200 text-red-600 hover:bg-red-50 transition text-xs font-semibold"
                    data-action="delete-group" data-id="${esc(g.id)}">
                    <i class="fa-solid fa-trash mr-1"></i> Delete
                  </button>
                  <span class="ml-1 text-gray-400 group-open:rotate-180 transition">
                    <i class="fa-solid fa-chevron-down"></i>
                  </span>
                </div>
              </summary>

              <div class="p-4 sm:p-5 pt-0">
                <div class="mt-4 pl-4 border-l border-gray-200">
                  ${itemsHtml || `<div class="text-sm text-gray-400 py-3">No items in this group.</div>`}
                </div>
              </div>
            </details>
          `;
        })
        .join("");

      return `
        <details class="menu-node menu-parent group rounded-3xl border border-gray-200 bg-white shadow-sm overflow-hidden" open>
          <summary class="cursor-pointer select-none p-4 sm:p-5 flex items-start justify-between gap-4 hover:bg-gray-50/80 transition">
            <div class="flex items-start gap-3 min-w-0">
              <span class="mt-0.5 inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-blue-50 text-blue-700 border border-blue-100">
                <i class="fa-solid fa-layer-group"></i>
              </span>

              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="font-extrabold text-gray-900 leading-tight truncate">${parentTitle}</h3>
                  <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-[11px] font-semibold">
                    #${esc(p.position ?? 0)}
                  </span>
                  <span class="inline-flex items-center gap-1 rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-[11px] font-semibold">
                    <i class="fa-solid fa-diagram-project"></i> ${groupCount}
                  </span>
                  <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 text-purple-700 px-2 py-0.5 text-[11px] font-semibold">
                    <i class="fa-solid fa-link"></i> ${itemCount}
                  </span>
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  Parent ID: <span class="font-mono">${esc(p.id)}</span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button type="button"
                class="px-3 py-2 rounded-2xl border hover:bg-white transition text-xs font-semibold"
                data-action="edit-parent" data-id="${esc(p.id)}">
                <i class="fa-solid fa-pen mr-1"></i> Edit
              </button>
              <button type="button"
                class="px-3 py-2 rounded-2xl border border-red-200 text-red-600 hover:bg-red-50 transition text-xs font-semibold"
                data-action="delete-parent" data-id="${esc(p.id)}">
                <i class="fa-solid fa-trash mr-1"></i> Delete
              </button>
              <span class="ml-1 text-gray-400 group-open:rotate-180 transition">
                <i class="fa-solid fa-chevron-down"></i>
              </span>
            </div>
          </summary>

          <div class="p-4 sm:p-5 pt-0">
            <div class="mt-4 pl-4 border-l border-gray-200">
              ${groupsHtml || `<div class="text-sm text-gray-400 py-3">No groups under this parent.</div>`}
            </div>
          </div>
        </details>
      `;
    })
    .join("");

  toggleNoResults(false);
}

/* ===============================
   SEARCH / NO RESULTS
=============================== */
function toggleNoResults(show) {
  if (!els.menuNoResults) return;
  els.menuNoResults.classList.toggle("hidden", !show);
}

function applySearch(q) {
  const query = normalize(q);
  const nodes = $$(".menu-parent", els.menuStructure);

  if (!query) {
    nodes.forEach((d) => d.classList.remove("hidden"));
    toggleNoResults(false);
    return;
  }

  let visible = 0;
  nodes.forEach((d) => {
    const ok = normalize(d.textContent).includes(query);
    d.classList.toggle("hidden", !ok);
    if (ok) visible++;
  });

  toggleNoResults(visible === 0);
}

/* ===============================
   EDIT
=============================== */
async function editParent(id) {
  if (
    !(await confirmEdit("Edit parent?", "Update parent details.")).isConfirmed
  )
    return;

  const p = window._menuData?.parents?.find((x) => String(x.id) === String(id));
  if (!p) return showError("Parent not found.");

  $("editParentId").value = p.id;
  $("editParentTitle").value = p.title;
  $("editParentPosition").value = p.position;
  openModal("editParentModal");
}

async function editGroup(id) {
  if (!(await confirmEdit("Edit group?", "Update group details.")).isConfirmed)
    return;

  const g = window._menuData?.groups?.find((x) => String(x.id) === String(id));
  if (!g) return showError("Group not found.");

  $("editGroupId").value = g.id;
  $("editGroupTitle").value = g.group_title;
  $("editGroupUrl").value = g.link_url || "";
  if (els.editGroupParentSelect)
    els.editGroupParentSelect.value = g.parent_id ?? "";
  $("editGroupPosition").value = g.position;
  openModal("editGroupModal");
}

async function editItem(id) {
  if (!(await confirmEdit("Edit item?", "Update item details.")).isConfirmed)
    return;

  const i = window._menuData?.items?.find((x) => String(x.id) === String(id));
  if (!i) return showError("Item not found.");

  $("editItemId").value = i.id;
  $("editItemTitle").value = i.item_title;
  $("editItemUrl").value = i.link_url || "";
  if (els.editItemGroupSelect) els.editItemGroupSelect.value = i.group_id ?? "";
  $("editItemPosition").value = i.position;
  openModal("editItemModal");
}

/* ===============================
   DELETE
=============================== */
async function deleteEntity(type, id, label) {
  if (!(await confirmDelete(`Delete ${label}?`)).isConfirmed) return;

  try {
    showLoading(`Deleting ${label}...`);
    const res = await api(`delete_${type}`, { id });
    Swal.close();

    if (!res.ok) return showError(res.msg || "Delete failed");
    menuSuccess("delete", label);
    loadData();
  } catch (e) {
    Swal.close();
    showError(e.message);
  }
}

const deleteParent = (id) => deleteEntity("parent", id, "Parent");
const deleteGroup = (id) => deleteEntity("group", id, "Group");
const deleteItem = (id) => deleteEntity("item", id, "Item");

/* ===============================
   FORMS
=============================== */
function bindForm(form, action, closeId) {
  form?.addEventListener("submit", async (e) => {
    e.preventDefault();

    try {
      showLoading(action.startsWith("add_") ? "Creating..." : "Updating...");

      const payload = Object.fromEntries(new FormData(e.target));
      const res = await api(action, payload);

      Swal.close();

      if (!res.ok) return showError(res.msg || "Action failed");

      const label = action.includes("parent")
        ? "Parent"
        : action.includes("group")
          ? "Group"
          : "Item";
      menuSuccess(action.startsWith("add_") ? "add" : "update", label);

      if (action.startsWith("add_")) e.target.reset();
      if (closeId) closeModal(closeId);

      await loadData();
    } catch (err) {
      Swal.close();
      showError(err.message);
    }
  });
}

/* ===============================
   EXPAND / COLLAPSE
=============================== */
function setAllOpen(open) {
  if (!els.menuStructure) return;
  $$(".menu-node", els.menuStructure).forEach((d) => (d.open = !!open));
}

/* ===============================
   TREE ACTIONS (delegation)
=============================== */
function bindTreeActions() {
  if (!els.menuStructure) return;

  els.menuStructure.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const action = btn.dataset.action;
    const id = btn.dataset.id;

    if (action === "edit-parent") editParent(id);
    else if (action === "delete-parent") deleteParent(id);
    else if (action === "edit-group") editGroup(id);
    else if (action === "delete-group") deleteGroup(id);
    else if (action === "edit-item") editItem(id);
    else if (action === "delete-item") deleteItem(id);
  });
}

/* ===============================
   SEARCH BIND (no duplicate scripts)
=============================== */
function bindSearchUI() {
  if (!els.menuSearch) return;

  els.menuSearch.addEventListener("input", () => {
    const v = els.menuSearch.value || "";
    if (els.menuSearchClear)
      els.menuSearchClear.classList.toggle("hidden", !v.trim());

    // Filter quickly
    applySearch(v);

    // Debounced re-render for highlight
    clearTimeout(_hlTimer);
    _hlTimer = setTimeout(() => {
      if (!window._menuData) return;
      renderMenu(
        window._menuData.parents || [],
        window._menuData.groups || [],
        window._menuData.items || [],
      );
      applySearch(v);
    }, 180);
  });

  if (els.menuSearchClear) {
    els.menuSearchClear.addEventListener("click", () => {
      els.menuSearch.value = "";
      els.menuSearchClear.classList.add("hidden");
      applySearch("");

      if (window._menuData) {
        renderMenu(
          window._menuData.parents || [],
          window._menuData.groups || [],
          window._menuData.items || [],
        );
      }

      els.menuSearch.focus();
    });
  }
}

/* ===============================
   TABS (Parent / Group / Item)
=============================== */
function bindTabs() {
  const tabButtons = Array.from(document.querySelectorAll(".tab-btn"));
  if (!tabButtons.length) return;

  const panels = Array.from(document.querySelectorAll(".tab-panel"));

  function setActive(tabId) {
    // buttons
    tabButtons.forEach((btn) => {
      const isActive = btn.dataset.tab === tabId;
      if (isActive) {
        btn.classList.add("bg-indigo-600", "text-white", "shadow-sm");
        btn.classList.remove("hover:bg-gray-50");
      } else {
        btn.classList.remove("bg-indigo-600", "text-white", "shadow-sm");
        if (!btn.classList.contains("hover:bg-gray-50"))
          btn.classList.add("hover:bg-gray-50");
      }
    });

    // panels
    panels.forEach((p) => {
      if (p.id === tabId) p.classList.remove("hidden");
      else p.classList.add("hidden");
    });
  }

  tabButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const target = btn.dataset.tab;
      if (!target) return;
      setActive(target);
    });
  });

  // initialize active state (first visible or first button)
  const initial =
    tabButtons.find((b) => b.classList.contains("bg-indigo-600")) ||
    tabButtons[0];
  if (initial)
    setActive(
      initial.dataset.tab || initial.dataset.tab === ""
        ? initial.dataset.tab
        : initial.getAttribute("data-tab"),
    );
}

/* ===============================
   INIT
=============================== */
document.addEventListener("DOMContentLoaded", () => {
  els = {
    parentSelect: $("parentSelect"),
    groupSelect: $("groupSelect"),
    editGroupParentSelect: $("editGroupParentSelect"),
    editItemGroupSelect: $("editItemGroupSelect"),

    parentCount: $("parentCount"),
    groupCount: $("groupCount"),
    itemCount: $("itemCount"),

    menuStructure: $("menuStructure"),

    // Premium structure toolbar (optional)
    menuSearch: $("menuSearch"),
    menuSearchClear: $("menuSearchClear"),
    menuNoResults: $("menuNoResults"),
    expandAllBtn: $("expandAllBtn"),
    collapseAllBtn: $("collapseAllBtn"),
  };

  // Forms
  bindForm($("addParentForm"), "add_parent");
  bindForm($("addGroupForm"), "add_group");
  bindForm($("addItemForm"), "add_item");

  bindForm($("editParentForm"), "edit_parent", "editParentModal");
  bindForm($("editGroupForm"), "edit_group", "editGroupModal");
  bindForm($("editItemForm"), "edit_item", "editItemModal");

  // Tree action buttons
  bindTreeActions();

  // Search
  bindSearchUI();
  // Tabs
  bindTabs();

  // Expand/Collapse
  if (els.expandAllBtn)
    els.expandAllBtn.addEventListener("click", () => setAllOpen(true));
  if (els.collapseAllBtn)
    els.collapseAllBtn.addEventListener("click", () => setAllOpen(false));

  loadData();
});

/* ===============================
   GLOBAL EXPORTS (optional)
=============================== */
Object.assign(window, {
  loadData,
  editParent,
  editGroup,
  editItem,
  deleteParent,
  deleteGroup,
  deleteItem,
});
