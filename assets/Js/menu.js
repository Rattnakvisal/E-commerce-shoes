/* =====================================================
   MENU.JS — FINAL VERSION
   SweetAlert style SAME AS users.js
===================================================== */

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
   SWEETALERT HELPERS (USERS STYLE)
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
  Swal.fire({
    icon: "error",
    title: "Error",
    text: msg,
    confirmButtonColor: "#dc2626",
  });
}

function confirmEdit(
  title = "Edit item?",
  text = "Open the editor to update this information.",
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

/* =====================================================
   SUCCESS BY ACTION (ONE SOURCE OF TRUTH)
===================================================== */
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

/* =====================================================
   MODAL HELPERS
===================================================== */
const openModal = (id) => $(id)?.classList.replace("hidden", "flex");
const closeModal = (id) => $(id)?.classList.replace("flex", "hidden");

/* =====================================================
   API (JSON)
===================================================== */
async function api(action, payload = {}) {
  const res = await fetch(`${API_URL}?action=${action}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  const data = await res.json().catch(() => null);
  if (!data) throw new Error("Invalid API response");
  return data;
}

/* =====================================================
   LOAD DATA
===================================================== */
async function loadData() {
  try {
    const res = await fetch(`${API_URL}?action=fetch_all`);
    const data = await res.json();
    if (!data.ok) throw new Error(data.msg);

    window._menuData = data;

    els.parentCount.textContent = data.parents.length;
    els.groupCount.textContent = data.groups.length;
    els.itemCount.textContent = data.items.length;

    populateSelects(data);
    renderMenu(data.parents, data.groups, data.items);
  } catch (e) {
    console.error(e);
    els.menuStructure.innerHTML = `<div class="text-center text-red-500 py-6">Failed to load menu data</div>`;
  }
}

/* =====================================================
   SELECTS
===================================================== */
function populateSelects({ parents, groups }) {
  els.parentSelect.innerHTML =
    `<option value="">-- No parent --</option>` +
    parents
      .map((p) => `<option value="${p.id}">${esc(p.title)}</option>`)
      .join("");

  els.editGroupParentSelect.innerHTML = els.parentSelect.innerHTML;

  els.groupSelect.innerHTML =
    `<option value="">-- Select Group --</option>` +
    groups
      .map((g) => `<option value="${g.id}">${esc(g.group_title)}</option>`)
      .join("");

  els.editItemGroupSelect.innerHTML = els.groupSelect.innerHTML;
}

function esc(text = "") {
  const d = document.createElement("div");
  d.textContent = text;
  return d.innerHTML;
}

/* =====================================================
   RENDER TREE
===================================================== */
function renderMenu(parents, groups, items) {
  if (!parents.length) {
    els.menuStructure.innerHTML = `<div class="text-center py-8 text-gray-500">No menu items yet.</div>`;
    return;
  }

  els.menuStructure.innerHTML = parents
    .sort((a, b) => a.position - b.position)
    .map(
      (p) => `
      <div class="border rounded-lg p-4 bg-white">
        <div class="flex justify-between mb-2">
          <b>${esc(p.title)}</b>
          <div>
            <button onclick="editParent(${p.id})" class="text-blue-600 p-2"><i class="fas fa-edit"></i></button>
            <button onclick="deleteParent(${p.id})" class="text-red-600 p-2"><i class="fas fa-trash"></i></button>
          </div>
        </div>

        ${groups
          .filter((g) => g.parent_id == p.id)
          .map(
            (g) => `
            <div class="ml-4 border-l pl-4 mt-2">
              <div class="flex justify-between">
                <span>${esc(g.group_title)}</span>
                <div>
                  <button onclick="editGroup(${g.id})" class="text-blue-600 p-2"><i class="fas fa-edit"></i></button>
                  <button onclick="deleteGroup(${g.id})" class="text-red-600 p-2"><i class="fas fa-trash"></i></button>
                </div>
              </div>

              ${items
                .filter((i) => i.group_id == g.id)
                .map(
                  (i) => `
                  <div class="ml-4 flex justify-between text-sm bg-gray-50 p-2 rounded mt-1">
                    ${esc(i.item_title)}
                    <div>
                      <button onclick="editItem(${i.id})" class="text-blue-600 p-2"><i class="fas fa-edit"></i></button>
                      <button onclick="deleteItem(${i.id})" class="text-red-600 p-2"><i class="fas fa-trash"></i></button>
                    </div>
                  </div>
                `,
                )
                .join("")}
            </div>
          `,
          )
          .join("")}
      </div>
    `,
    )
    .join("");
}

/* =====================================================
   EDIT
===================================================== */
async function editParent(id) {
  if (
    !(await confirmEdit("Edit parent?", "Update parent details.")).isConfirmed
  )
    return;
  const p = _menuData.parents.find((x) => x.id == id);
  $("editParentId").value = p.id;
  $("editParentTitle").value = p.title;
  $("editParentPosition").value = p.position;
  openModal("editParentModal");
}

async function editGroup(id) {
  if (!(await confirmEdit("Edit group?", "Update group details.")).isConfirmed)
    return;
  const g = _menuData.groups.find((x) => x.id == id);
  $("editGroupId").value = g.id;
  $("editGroupTitle").value = g.group_title;
  $("editGroupUrl").value = g.link_url || "";
  els.editGroupParentSelect.value = g.parent_id;
  $("editGroupPosition").value = g.position;
  openModal("editGroupModal");
}

async function editItem(id) {
  if (!(await confirmEdit("Edit item?", "Update item details.")).isConfirmed)
    return;
  const i = _menuData.items.find((x) => x.id == id);
  $("editItemId").value = i.id;
  $("editItemTitle").value = i.item_title;
  $("editItemUrl").value = i.link_url || "";
  els.editItemGroupSelect.value = i.group_id;
  $("editItemPosition").value = i.position;
  openModal("editItemModal");
}

/* =====================================================
   DELETE
===================================================== */
async function deleteEntity(type, id, label) {
  if (!(await confirmDelete(`Delete ${label}?`)).isConfirmed) return;

  try {
    showLoading(`Deleting ${label}...`);
    const res = await api(`delete_${type}`, { id });
    Swal.close();
    if (!res.ok) return showError(res.msg);
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

/* =====================================================
   FORMS
===================================================== */
function bindForm(form, action, closeId) {
  form?.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      showLoading(action.startsWith("add_") ? "Creating..." : "Updating...");
      const payload = Object.fromEntries(new FormData(e.target));
      const res = await api(action, payload);
      Swal.close();
      if (!res.ok) return showError(res.msg);

      const label = action.includes("parent")
        ? "Parent"
        : action.includes("group")
          ? "Group"
          : "Item";

      menuSuccess(action.startsWith("add_") ? "add" : "update", label);

      e.target.reset();
      if (closeId) closeModal(closeId);
      loadData();
    } catch (e) {
      Swal.close();
      showError(e.message);
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
    editGroupParentSelect: $("editGroupParentSelect"),
    editItemGroupSelect: $("editItemGroupSelect"),
    parentCount: $("parentCount"),
    groupCount: $("groupCount"),
    itemCount: $("itemCount"),
    menuStructure: $("menuStructure"),
  };

  bindForm($("addParentForm"), "add_parent", "addParentModal");
  bindForm($("addGroupForm"), "add_group", "addGroupModal");
  bindForm($("addItemForm"), "add_item", "addItemModal");

  bindForm($("editParentForm"), "edit_parent", "editParentModal");
  bindForm($("editGroupForm"), "edit_group", "editGroupModal");
  bindForm($("editItemForm"), "edit_item", "editItemModal");

  loadData();
});

/* =====================================================
   GLOBAL EXPORTS
===================================================== */
Object.assign(window, {
  loadData,
  editParent,
  editGroup,
  editItem,
  deleteParent,
  deleteGroup,
  deleteItem,
});
