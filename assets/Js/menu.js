/* =====================================================
   CONFIG
===================================================== */
const API_URL = "api.php";

/* =====================================================
   DOM HELPERS
===================================================== */
const $ = (id) => document.getElementById(id);
let els = {};

/* =====================================================
   SWEETALERT HELPERS
===================================================== */
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
  return Swal.fire({
    icon: "error",
    title: "Error",
    text: msg,
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
  });
}

function confirmEdit(title, text) {
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

function confirmDelete(title, text) {
  return Swal.fire({
    icon: "warning",
    title,
    html: `<p class="text-gray-600 mt-2">${text}</p>`,
    showCancelButton: true,
    confirmButtonText: "Delete",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#6b7280",
  });
}

/* Compatibility wrappers */
const toast = (title) => showSuccess(title);
const loading = (msg) => showLoading(msg);
const errorBox = (msg) => showError(msg);

/* =====================================================
   MODAL HELPERS
===================================================== */
const openModal = (id) => $(id)?.classList.replace("hidden", "flex");
const closeModal = (id) => $(id)?.classList.replace("flex", "hidden");

/* =====================================================
   API
===================================================== */
async function api(action, payload = {}) {
  const res = await fetch(`${API_URL}?action=${action}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  try {
    return await res.json();
  } catch {
    throw new Error("Invalid API response");
  }
}

/* =====================================================
   LOAD DATA
===================================================== */
async function loadData() {
  try {
    const res = await fetch(`${API_URL}?action=fetch_all`);
    const data = await res.json();
    if (!data.ok) throw new Error(data.msg || "Failed to load");

    window._menuData = data;

    els.parentCount.textContent = data.parents.length;
    els.groupCount.textContent = data.groups.length;
    els.itemCount.textContent = data.items.length;

    populateSelects(data);
    renderMenu(data.parents, data.groups, data.items);
  } catch (err) {
    console.error(err);
    els.menuStructure.innerHTML = `
      <div class="text-center text-red-500 py-6">
        Failed to load menu data
      </div>
    `;
  }
}

/* =====================================================
   SELECT POPULATION
===================================================== */
function populateSelects({ parents, groups }) {
  const parentOptions =
    `<option value="">-- No parent --</option>` +
    parents.map((p) => `<option value="${p.id}">${p.title}</option>`).join("");

  els.parentSelect.innerHTML = parentOptions;
  els.editGroupParentSelect.innerHTML = parentOptions;

  const groupOptions =
    `<option value="">-- Select Group --</option>` +
    groups
      .map((g) => `<option value="${g.id}">${g.group_title}</option>`)
      .join("");

  els.groupSelect.innerHTML = groupOptions;
  els.editItemGroupSelect.innerHTML = groupOptions;
}

/* =====================================================
   RENDER MENU TREE
===================================================== */
function renderMenu(parents, groups, items) {
  if (!parents.length) {
    els.menuStructure.innerHTML = `
      <div class="text-center py-8 text-gray-500">No menu items yet.</div>
    `;
    return;
  }

  els.menuStructure.innerHTML = parents
    .sort((a, b) => a.position - b.position)
    .map((p) => renderParent(p, groups, items))
    .join("");
}

function renderParent(p, groups, items) {
  return `
    <div class="border rounded-lg p-4 bg-white">
      <div class="flex justify-between mb-2">
        <div class="flex items-center gap-3">
          <b>${p.title}</b>
          <button id="parent-toggle-${p.id}" onclick="toggleParent(${p.id})"
            class="text-gray-400 hover:text-gray-600 p-1">
            <i class="fas fa-chevron-up"></i>
          </button>
        </div>
        <div>
          <button onclick="editParent(${p.id})" class="text-blue-600 p-2">
            <i class="fas fa-edit"></i>
          </button>
          <button onclick="deleteParent(${p.id})" class="text-red-600 p-2">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>

      <div id="parent-children-${p.id}" class="slide">
        ${groups
          .filter((g) => g.parent_id == p.id)
          .map((g) => renderGroup(g, items))
          .join("")}
      </div>
    </div>
  `;
}

function renderGroup(g, items) {
  return `
    <div class="ml-4 border-l pl-4 mt-2">
      <div class="flex justify-between">
        <div class="flex items-center gap-3">
          <span>${g.group_title}</span>
          <button id="group-toggle-${g.id}" onclick="toggleGroup(${g.id})"
            class="text-gray-400 hover:text-gray-600 p-1">
            <i class="fas fa-chevron-up"></i>
          </button>
        </div>
        <div>
          <button onclick="editGroup(${g.id})" class="text-blue-600 p-2">
            <i class="fas fa-edit"></i>
          </button>
          <button onclick="deleteGroup(${g.id})" class="text-red-600 p-2">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>

      <div id="group-children-${g.id}" class="slide">
        ${items
          .filter((i) => i.group_id == g.id)
          .map(
            (i) => `
              <div class="ml-4 flex justify-between text-sm bg-gray-50 p-2 rounded mt-1">
                ${i.item_title}
                <div>
                  <button onclick="editItem(${i.id})" class="text-blue-600 p-2">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button onclick="deleteItem(${i.id})" class="text-red-600 p-2">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            `,
          )
          .join("")}
      </div>
    </div>
  `;
}

/* =====================================================
   SLIDE TOGGLES (SMOOTH)
   Add this CSS once:
   .slide{overflow:hidden;transition:max-height .35s ease}
   .slide-hidden{max-height:0!important}
===================================================== */
function slideToggle(el) {
  if (!el) return;

  if (el.classList.contains("slide-hidden")) {
    // OPEN
    el.classList.remove("slide-hidden");
    el.style.maxHeight = el.scrollHeight + "px";
    setTimeout(() => {
      el.style.maxHeight = "none";
    }, 350);
    return;
  }

  // CLOSE
  el.style.maxHeight = el.scrollHeight + "px";
  requestAnimationFrame(() => {
    el.classList.add("slide-hidden");
    el.style.maxHeight = "0px";
  });
}

function updateIcon(btn, open) {
  const icon = btn?.querySelector("i");
  if (!icon) return;
  icon.classList.toggle("fa-chevron-up", open);
  icon.classList.toggle("fa-chevron-down", !open);
}

function toggleParent(id) {
  const block = $(`parent-children-${id}`);
  const btn = $(`parent-toggle-${id}`);
  const willOpen = block?.classList.contains("slide-hidden");
  slideToggle(block);
  updateIcon(btn, willOpen);
}

function toggleGroup(id) {
  const block = $(`group-children-${id}`);
  const btn = $(`group-toggle-${id}`);
  const willOpen = block?.classList.contains("slide-hidden");
  slideToggle(block);
  updateIcon(btn, willOpen);
}

/* Optional: Expand/Collapse all */
function expandAll() {
  document
    .querySelectorAll('[id^="parent-children-"], [id^="group-children-"]')
    .forEach((el) => {
      el.classList.remove("slide-hidden");
      el.style.maxHeight = el.scrollHeight + "px";
      setTimeout(() => (el.style.maxHeight = "none"), 350);
    });

  document
    .querySelectorAll('[id^="parent-toggle-"], [id^="group-toggle-"]')
    .forEach((btn) => updateIcon(btn, true));
}

function collapseAll() {
  document
    .querySelectorAll('[id^="parent-children-"], [id^="group-children-"]')
    .forEach((el) => {
      el.classList.add("slide-hidden");
      el.style.maxHeight = "0px";
    });

  document
    .querySelectorAll('[id^="parent-toggle-"], [id^="group-toggle-"]')
    .forEach((btn) => updateIcon(btn, false));
}

/* =====================================================
   EDIT HANDLERS
===================================================== */
function editParent(id) {
  (async () => {
    const ok = await confirmEdit(
      "Edit parent?",
      "You can update the parent's title or position.",
    );
    if (!ok.isConfirmed) return;

    const p = _menuData.parents.find((x) => x.id == id);
    $("editParentId").value = p.id;
    $("editParentTitle").value = p.title;
    $("editParentPosition").value = p.position;
    openModal("editParentModal");
  })();
}

function editGroup(id) {
  (async () => {
    const ok = await confirmEdit(
      "Edit group?",
      "You can update the group's title, link, parent, or position.",
    );
    if (!ok.isConfirmed) return;

    const g = _menuData.groups.find((x) => x.id == id);
    $("editGroupId").value = g.id;
    $("editGroupTitle").value = g.group_title;
    $("editGroupUrl").value = g.link_url || "";
    els.editGroupParentSelect.value = g.parent_id || "";
    $("editGroupPosition").value = g.position;
    openModal("editGroupModal");
  })();
}

function editItem(id) {
  (async () => {
    const ok = await confirmEdit(
      "Edit item?",
      "You can update the item's title, link, group, or position.",
    );
    if (!ok.isConfirmed) return;

    const i = _menuData.items.find((x) => x.id == id);
    $("editItemId").value = i.id;
    $("editItemTitle").value = i.item_title;
    $("editItemUrl").value = i.link_url || "";
    els.editItemGroupSelect.value = i.group_id || "";
    $("editItemPosition").value = i.position;
    openModal("editItemModal");
  })();
}

/* =====================================================
   DELETE (MATCH PRODUCTS) - NO OK BUTTON
===================================================== */
async function deleteEntity(type, id, label) {
  const ok = await confirmDelete(
    `Delete ${label}?`,
    "Are you sure? This action cannot be undone.",
  );
  if (!ok.isConfirmed) return;

  try {
    showLoading(`Deleting ${label}...`);
    const res = await api(`delete_${type}`, { id });
    Swal.close();

    if (!res.ok) {
      return showError(res.msg || "Delete failed");
    }

    showSuccess(`${label} deleted successfully`);
    loadData();
  } catch {
    Swal.close();
    showError("Network error. Please try again.");
  }
}

const deleteParent = (id) => deleteEntity("parent", id, "Parent");
const deleteGroup = (id) => deleteEntity("group", id, "Group");
const deleteItem = (id) => deleteEntity("item", id, "Item");

/* =====================================================
   FORMS (MATCH PRODUCTS) - NO OK BUTTON
===================================================== */
function bindForm(form, action, closeId = null) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    try {
      showLoading(action.startsWith("add_") ? "Creating..." : "Updating...");

      const data = Object.fromEntries(new FormData(form));
      const res = await api(action, data);

      Swal.close();

      if (!res.ok) {
        return showError(res.msg || "Operation failed");
      }

      showSuccess(
        action.startsWith("add_")
          ? "Created successfully"
          : "Updated successfully",
      );

      form.reset();
      if (closeId) closeModal(closeId);
      loadData();
    } catch {
      Swal.close();
      showError("Network error. Please try again.");
    }
  });
}

/* =====================================================
   INIT
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  els = {
    parentSelect: $("parentSelect"),
    groupSelect: $("groupSelect"),
    addParentForm: $("addParentForm"),
    addGroupForm: $("addGroupForm"),
    addItemForm: $("addItemForm"),
    editParentForm: $("editParentForm"),
    editGroupForm: $("editGroupForm"),
    editItemForm: $("editItemForm"),
    editGroupParentSelect: $("editGroupParentSelect"),
    editItemGroupSelect: $("editItemGroupSelect"),
    parentCount: $("parentCount"),
    groupCount: $("groupCount"),
    itemCount: $("itemCount"),
    menuStructure: $("menuStructure"),
  };

  bindForm(els.addParentForm, "add_parent");
  bindForm(els.addGroupForm, "add_group");
  bindForm(els.addItemForm, "add_item");
  bindForm(els.editParentForm, "edit_parent", "editParentModal");
  bindForm(els.editGroupForm, "edit_group", "editGroupModal");
  bindForm(els.editItemForm, "edit_item", "editItemModal");

  loadData();
});

/* =====================================================
   GLOBAL EXPORTS
===================================================== */
Object.assign(window, {
  toggleParent,
  toggleGroup,
  expandAll,
  collapseAll,
  editParent,
  editGroup,
  editItem,
  deleteParent,
  deleteGroup,
  deleteItem,
});
